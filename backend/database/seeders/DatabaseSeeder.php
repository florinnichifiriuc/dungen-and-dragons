<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        $this->seedDemoAccounts();
    }

    protected function seedDemoAccounts(): void
    {
        $demoUsers = [
            [
                'email' => env('SEED_GM_EMAIL', 'gm@realm.test'),
                'name' => env('SEED_GM_NAME', 'Demo GM'),
                'password' => env('SEED_GM_PASSWORD', 'guide-secret'),
                'locale' => 'en',
                'timezone' => 'UTC',
                'theme' => 'dark',
                'account_role' => 'guide',
                'is_support_admin' => false,
            ],
            [
                'email' => env('SEED_PLAYER_EMAIL', 'player@realm.test'),
                'name' => env('SEED_PLAYER_NAME', 'Demo Player'),
                'password' => env('SEED_PLAYER_PASSWORD', 'player-secret'),
                'locale' => 'ro',
                'timezone' => 'Europe/Bucharest',
                'theme' => 'light',
                'high_contrast' => true,
                'font_scale' => 110,
                'account_role' => 'player',
                'is_support_admin' => false,
            ],
        ];

        foreach ($demoUsers as $demo) {
            $user = User::query()->firstOrNew(['email' => $demo['email']]);

            $user->fill([
                'name' => $demo['name'],
                'locale' => $demo['locale'],
                'timezone' => $demo['timezone'],
                'theme' => $demo['theme'],
                'account_role' => $demo['account_role'],
                'is_support_admin' => $demo['is_support_admin'],
                'high_contrast' => $demo['high_contrast'] ?? false,
                'font_scale' => $demo['font_scale'] ?? 100,
            ]);

            if (! $user->exists) {
                $user->email_verified_at = now();
            }

            if (! Hash::check($demo['password'], (string) $user->password)) {
                $user->password = Hash::make($demo['password']);
            }

            $user->save();
        }
    }
}
