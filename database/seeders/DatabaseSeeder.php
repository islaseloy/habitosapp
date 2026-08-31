<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Habit;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Categories
        $categories = [
            ['nombre' => 'Salud', 'color_icono' => '#3a9b72'],
            ['nombre' => 'Estudio', 'color_icono' => '#608bc4'],
            ['nombre' => 'Bienestar', 'color_icono' => '#f4c95d'],
            ['nombre' => 'Finanzas', 'color_icono' => '#e77b68'],
        ];

        $seededCategories = [];
        foreach ($categories as $cat) {
            $seededCategories[$cat['nombre']] = Category::create($cat);
        }

        // 2. Seed Users
        $freeUser = User::create([
            'nombre' => 'Usuario Gratuito',
            'email' => 'gratis@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'plan_expires_at' => null,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $premiumUser = User::create([
            'nombre' => 'Usuario Premium',
            'email' => 'premium@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'plan_expires_at' => now()->addDays(14),
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        // 3. Seed Habits for Free User
        $habit1 = Habit::create([
            'usuario_id' => $freeUser->id,
            'categoria_id' => $seededCategories['Estudio']->id,
            'titulo' => 'Estudiar Laravel',
            'descripcion' => 'Aprender Blade, Routing y Controllers en profundidad',
            'estado' => true,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
        ]);
        $habit1->schedule()->create([
            'tipo' => 'semanal',
            'dias_semana' => [1, 3, 5], // Lunes, Miércoles, Viernes
        ]);

        $habit2 = Habit::create([
            'usuario_id' => $freeUser->id,
            'categoria_id' => $seededCategories['Salud']->id,
            'titulo' => 'Caminar 30 min',
            'descripcion' => 'Caminata rápida por la plaza',
            'estado' => true,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
        ]);
        $habit2->schedule()->create([
            'tipo' => 'diario',
        ]);

        // Seed some tracking data for free user habits to simulate streaks and history
        $startDate = now()->subDays(10);
        for ($i = 0; $i < 10; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // For Study Habit (Mon, Wed, Fri only)
            $dayOfWeek = $date->dayOfWeekIso;
            if (in_array($dayOfWeek, [1, 3, 5])) {
                $status = ($i % 3 === 0) ? 'fallado' : 'completado';
                if ($i !== 9) { // Leave today empty
                    $habit1->tracking()->create([
                        'fecha_registro' => $date->toDateString(),
                        'estado_cumplimiento' => $status,
                        'registrado_por_sistema' => false,
                    ]);
                }
            }

            // For Walking Habit (Daily)
            if ($i !== 9) { // Leave today empty
                $status = ($i === 5) ? 'omitido' : 'completado';
                $habit2->tracking()->create([
                    'fecha_registro' => $date->toDateString(),
                    'estado_cumplimiento' => $status,
                    'registrado_por_sistema' => ($status === 'omitido'),
                ]);
            }
        }
    }
}
