<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Demo GM',
            'email' => 'gm@example.com',
            'password' => 'password',
            'locale' => 'en',
            'timezone' => 'UTC',
            'theme' => 'dark',
        ]);

        User::factory()->create([
            'name' => 'Demo Player',
            'email' => 'player@example.com',
            'password' => 'password',
            'locale' => 'ro',
            'timezone' => 'Europe/Bucharest',
            'theme' => 'light',
            'high_contrast' => true,
            'font_scale' => 110,
        ]);
    }
}
