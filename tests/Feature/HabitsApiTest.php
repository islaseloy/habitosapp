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

    public function test_user_cannot_access_another_users_habit(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->getJson("/api/habits/{$habit->id}")
            ->assertForbidden();
    }

    public function test_free_user_statistics_are_limited_to_last_seven_days(): void
    {
        Carbon::setTestNow('2026-06-25 12:00:00');

        $user = User::factory()->create(['plan_expires_at' => null]);
        $habit = Habit::factory()->create([
            'usuario_id' => $user->id,
            'fecha_inicio' => now()->subDays(30)->toDateString(),
        ]);
        $habit->schedule()->create(['tipo' => 'diario']);

        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->subDays(20)->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);
        HabitTracking::query()->create([
            'habito_id' => $habit->id,
            'fecha_registro' => now()->toDateString(),
            'estado_cumplimiento' => 'completado',
        ]);

        $this->actingAs($user)
            ->getJson("/api/habits/{$habit->id}/statistics")
            ->assertOk()
            ->assertJsonPath('range_start', '2026-06-19')
            ->assertJsonCount(1, 'records');

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
