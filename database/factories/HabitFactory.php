<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'categoria_id' => null,
            'titulo' => fake()->words(3, true),
            'descripcion' => fake()->optional()->sentence(),
            'estado' => true,
            'fecha_inicio' => now()->toDateString(),
        ];
    }
}
