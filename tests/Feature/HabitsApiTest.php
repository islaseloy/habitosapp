<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HabitsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-01: Gate Freemium - Usuario gratuito no puede crear un 6to hábito activo
     */
    public function test_free_user_cannot_create_sixth_active_habit(): void
    {
        $user = User::factory()->create(['plan_expires_at' => null]);
        Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

        $this->actingAs($user)
            ->postJson('/api/habits', $this->validHabitPayload())
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'plan_limit_reached']);

        $this->assertDatabaseCount('habits', 5);
    }

    /**
     * TC-01b: Contracaso Gate Freemium - Usuario premium activo puede crear más de 5 hábitos
     */
    public function test_premium_user_can_create_more_than_five_active_habits(): void
    {
        $user = User::factory()->create(['plan_expires_at' => now()->addDays(14)]);
        Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

        $this->actingAs($user)
            ->postJson('/api/habits', $this->validHabitPayload())
            ->assertCreated()
            ->assertJsonPath('schedule.tipo', 'diario');

        $this->assertDatabaseCount('habits', 6);
    }

    /**
     * TC-01c: Análisis de Borde - Usuario con plan Premium ya vencido vuelve al límite de 5 hábitos
     */
    public function test_expired_premium_user_is_treated_as_free_and_cannot_create_sixth_habit(): void
    {
        // Usuario cuyo plan premium expiró hace 1 segundo / en el pasado
        $user = User::factory()->create(['plan_expires_at' => now()->subSecond()]);
        $this->assertFalse($user->isPremium());

        Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

        $this->actingAs($user)
            ->postJson('/api/habits', $this->validHabitPayload())
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'plan_limit_reached']);

        $this->assertDatabaseCount('habits', 5);
    }

    /**
     * TC-04: Restricción de fechas futuras en tracking
     */
    public function test_tracking_rejects_future_dates(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/habit-tracking', [
                'habito_id' => $habit->id,
                'fecha_registro' => now()->addDay()->toDateString(),
                'estado_cumplimiento' => 'completado',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('habit_tracking', 0);
    }

    /**
     * TC-09: Idempotencia y actualización ante tracking duplicado en el mismo día
     */
    public function test_duplicate_tracking_on_same_day_updates_existing_record_without_duplication(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $user->id]);
        $todayStr = now()->toDateString();

        // 1er registro: Completado
        $firstResponse = $this->actingAs($user)
            ->postJson('/api/habit-tracking', [
                'habito_id' => $habit->id,
                'fecha_registro' => $todayStr,
                'estado_cumplimiento' => 'completado',
                'notas_opcionales' => 'Primera nota',
            ]);

        $firstResponse->assertStatus(201);
        $this->assertDatabaseCount('habit_tracking', 1);

        // 2do registro (mismo hábito, mismo día): El usuario cambia a Fallado y actualiza la nota
        $secondResponse = $this->actingAs($user)
            ->postJson('/api/habit-tracking', [
                'habito_id' => $habit->id,
                'fecha_registro' => $todayStr,
                'estado_cumplimiento' => 'fallado',
                'notas_opcionales' => 'Nota corregida',
            ]);

        $secondResponse->assertStatus(200);
        // Debe seguir habiendo exactamente 1 registro en la BD (sin duplicación)
        $this->assertDatabaseCount('habit_tracking', 1);

        $this->assertDatabaseHas('habit_tracking', [
            'habito_id' => $habit->id,
            'fecha_registro' => $todayStr . ' 00:00:00',
            'estado_cumplimiento' => 'fallado',
            'notas_opcionales' => 'Nota corregida',
        ]);
    }

    /**
     * TC-05a: Aislamiento entre usuarios - Consulta (SHOW) denegada ante hábito ajeno
     */
    public function test_user_cannot_access_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->getJson("/api/habits/{$habit->id}")
            ->assertForbidden();
    }

    /**
     * TC-05b: Aislamiento entre usuarios - Modificación (UPDATE) denegada ante hábito ajeno
     */
    public function test_user_cannot_update_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create([
            'usuario_id' => $owner->id,
            'titulo' => 'Hábito Original',
        ]);

        $this->actingAs($otherUser)
            ->putJson("/api/habits/{$habit->id}", [
                'titulo' => 'Hábito Hackeado',
                'fecha_inicio' => now()->toDateString(),
                'schedule' => ['tipo' => 'diario'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('habits', [
            'id' => $habit->id,
            'titulo' => 'Hábito Original',
        ]);
    }

    /**
     * TC-05c: Aislamiento entre usuarios - Eliminación (DELETE) denegada ante hábito ajeno
     */
    public function test_user_cannot_delete_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->deleteJson("/api/habits/{$habit->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('habits', [
            'id' => $habit->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * TC-05d: Aislamiento entre usuarios - Consulta de Estadísticas denegada ante hábito ajeno
     */
    public function test_user_cannot_view_statistics_of_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->getJson("/api/habits/{$habit->id}/statistics")
            ->assertForbidden();
    }

    /**
     * TC-05e: Aislamiento entre usuarios - Tracking denegado ante hábito ajeno
     */
    public function test_user_cannot_record_tracking_for_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->postJson('/api/habit-tracking', [
                'habito_id' => $habit->id,
                'fecha_registro' => now()->toDateString(),
                'estado_cumplimiento' => 'completado',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('habit_tracking', 0);
    }

    /**
     * TC-06: Análisis de Valores Límite (BVA) - Ventana de 7 días (Día 0, 6, 7 y 8)
     */
    public function test_free_user_statistics_boundary_analysis_days_six_seven_and_eight(): void
    {
        // Fijamos fecha base: 2026-06-25 (Día 0)
        Carbon::setTestNow('2026-06-25 12:00:00');

        $user = User::factory()->create(['plan_expires_at' => null]);
        $habit = Habit::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => now()->subDays(30)->toDateString(),
        ]);
        $habit->schedule()->create(['tipo' => 'diario']);

        // Día 0 (2026-06-25, Hoy) -> DENTRO de la ventana de 7 días
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Día 6 (2026-06-19, Hoy - 6 días) -> BORDE INFERIOR EXACTO (INCLUIDO)
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->subDays(6)->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Día 7 (2026-06-18, Hoy - 7 días) -> PRIMER VALOR FUERA DEL BORDE (EXCLUIDO)
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->subDays(7)->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Día 8 (2026-06-17, Hoy - 8 días) -> FUERA DEL BORDE (EXCLUIDO)
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->subDays(8)->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Aseguramos que los 4 registros existen en la base de datos
        $this->assertDatabaseCount('habit_tracking', 4);

        // Act: Consulta de estadísticas del usuario Free
        $response = $this->actingAs($user)
            ->getJson("/api/habits/{$habit->id}/statistics")
            ->assertOk();

        // Assert BVA:
        // El range_start es exactamente 2026-06-19 (Hoy - 6 días = ventana de 7 días corridos)
        $response->assertJsonPath('range_start', '2026-06-19');
        
        // Solo deben venir 2 registros (Día 0 y Día 6). Día 7 y Día 8 están estrictamente excluidos.
        $records = $response->json('records');
        $this->assertCount(2, $records);

        $returnedDates = collect($records)->pluck('fecha_registro')->map(fn($d) => substr($d, 0, 10))->all();
        $this->assertContains('2026-06-25', $returnedDates);
        $this->assertContains('2026-06-19', $returnedDates);
        $this->assertNotContains('2026-06-18', $returnedDates);
        $this->assertNotContains('2026-06-17', $returnedDates);

        Carbon::setTestNow();
    }

    /**
     * TC-06b: Análisis de Valores Límite - Usuario Premium vencido solo accede a ventana de 7 días
     */
    public function test_expired_premium_user_statistics_fall_back_to_seven_day_window(): void
    {
        Carbon::setTestNow('2026-06-25 12:00:00');

        $user = User::factory()->create(['plan_expires_at' => now()->subHour()]);
        $habit = Habit::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => now()->subDays(30)->toDateString(),
        ]);
        $habit->schedule()->create(['tipo' => 'diario']);

        // Día 0 (Hoy)
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Día 7 (Hoy - 7 días: fuera de la ventana free)
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->subDays(7)->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        // Usuario vencido solo debe ver la ventana de 7 días (1 solo registro)
        $response = $this->actingAs($user)
            ->getJson("/api/habits/{$habit->id}/statistics")
            ->assertOk();

        $response->assertJsonPath('range_start', '2026-06-19');
        $this->assertCount(1, $response->json('records'));

        Carbon::setTestNow();
    }

    private function validHabitPayload(): array
    {
        return [
            'titulo' => 'Tomar agua',
            'descripcion' => 'Dos litros por dia',
            'fecha_inicio' => now()->toDateString(),
            'schedule' => [
                'tipo' => 'diario',
            ],
        ];
    }
}
