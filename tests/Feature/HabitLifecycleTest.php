<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Habit;
use App\Models\HabitTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HabitLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-02 — Cronjob de cierre con múltiples timezones (Integration Test)
     */
    public function test_cronjob_closes_pending_habits_according_to_user_timezone(): void
    {
        // Set fixed global time: 2026-06-25 03:05:00 UTC (corresponds to 00:05:00 in America/Argentina/Buenos_Aires and 23:05:00 in America/New_York)
        Carbon::setTestNow(Carbon::create(2026, 6, 25, 3, 5, 0, 'UTC'));

        // User A (Buenos Aires, UTC-3) -> local time is 00:05 (midnight hour!)
        $userA = User::factory()->create(['timezone' => 'America/Argentina/Buenos_Aires']);
        $habitA = Habit::factory()->create(['usuario_id' => $userA->id, 'estado' => true, 'fecha_inicio' => '2026-06-20']);
        $habitA->schedule()->create(['tipo' => 'diario']);

        // User B (New York, UTC-4) -> local time is 23:05 (not midnight yet)
        $userB = User::factory()->create(['timezone' => 'America/New_York']);
        $habitB = Habit::factory()->create(['usuario_id' => $userB->id, 'estado' => true, 'fecha_inicio' => '2026-06-20']);
        $habitB->schedule()->create(['tipo' => 'diario']);

        // Run scheduler command
        $this->artisan('habits:close-day')->assertExitCode(0);

        // User A should have a system-generated omission for yesterday (2026-06-24)
        $tracking = HabitTracking::where('habito_id', $habitA->id)->first();
        $this->assertNotNull($tracking);
        $this->assertEquals('2026-06-24', Carbon::parse($tracking->fecha_registro)->toDateString());
        $this->assertEquals('omitido', $tracking->estado_cumplimiento);
        $this->assertTrue($tracking->registrado_por_sistema);

        // User B should NOT have any new tracking records
        $this->assertDatabaseCount('habit_tracking', 1); // Only A's record exists

        Carbon::setTestNow(); // Reset time
    }

    /**
     * TC-03 — Cálculo de racha por ocurrencias individuales (Unit Test)
     */
    public function test_streak_calculation_based_on_scheduled_occurrences(): void
    {
        Carbon::setTestNow('2026-06-29 12:00:00'); // It's a Monday

        $user = User::factory()->create(['timezone' => 'America/Argentina/Buenos_Aires']);
        // Habit: Mon, Wed, Fri
        $habit = Habit::factory()->create([
            'usuario_id' => $user->id,
            'estado' => true,
            'fecha_inicio' => '2026-06-22', // Started previous Monday
        ]);
        $habit->schedule()->create([
            'tipo' => 'semanal',
            'dias_semana' => [1, 3, 5],
        ]);

        // History:
        // Mon 22: completado
        $habit->tracking()->create(['fecha_registro' => '2026-06-22', 'estado_cumplimiento' => 'completado']);
        // Wed 24: completado
        $habit->tracking()->create(['fecha_registro' => '2026-06-24', 'estado_cumplimiento' => 'completado']);
        // Fri 26: omitido (system) -> breaks streak!
        $habit->tracking()->create(['fecha_registro' => '2026-06-26', 'estado_cumplimiento' => 'omitido', 'registrado_por_sistema' => true]);
        // Mon 29: completado
        $habit->tracking()->create(['fecha_registro' => '2026-06-29', 'estado_cumplimiento' => 'completado']);

        // Check stats
        $stats = app(\App\Services\HabitStatisticsService::class)->forHabit($habit);

        $this->assertEquals(1, $stats['current_streak']); // Only Mon 29 is active
        $this->assertEquals(2, $stats['max_streak']); // Mon 22 & Wed 24

        Carbon::setTestNow();
    }

    /**
     * TC-07 — Estado vacío del dashboard (Feature Test)
     */
    public function test_empty_dashboard_displays_motivational_message_correctly(): void
    {
        Carbon::setTestNow('2026-06-25 12:00:00'); // Thursday

        $user = User::factory()->create();
        
        // Habit scheduled only for Wednesday (3) -> today is Thursday, so it should not appear
        $habit = Habit::factory()->create(['usuario_id' => $user->id, 'estado' => true, 'fecha_inicio' => '2026-06-20']);
        $habit->schedule()->create([
            'tipo' => 'semanal',
            'dias_semana' => [3],
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('No tienes hábitos programados para hoy')
            ->assertSee('Ir a estadísticas')
            ->assertSee('Crear hábito');

        Carbon::setTestNow();
    }

    /**
     * TC-08 — Validación de timezone en registro (Feature Test)
     */
    public function test_registration_validates_timezone_values(): void
    {
        // Invalid Timezone
        $response = $this->post('/register', [
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'Invalid/Timezone_String',
        ]);

        $response->assertSessionHasErrors('timezone');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);

        // Valid Timezone
        $response2 = $this->post('/register', [
            'nombre' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $response2->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'test2@example.com', 'timezone' => 'America/Argentina/Buenos_Aires']);
    }
}
