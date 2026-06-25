<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'nombre' => 'Usuario Gratuito',
            'email' => 'gratis@example.com',
            'plan_expires_at' => null,
        ]);

        User::factory()->create([
            'nombre' => 'Usuario Premium',
            'email' => 'premium@example.com',
            'plan_expires_at' => now()->addDays(14),
        ]);
    }
}
