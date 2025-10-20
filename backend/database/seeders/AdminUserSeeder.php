<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@realm.test');
        $password = env('SEED_ADMIN_PASSWORD', 'secret');
        $name = env('SEED_ADMIN_NAME', 'Edgewatch Steward');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'remember_token' => Str::random(40),
                'locale' => 'en',
                'timezone' => 'UTC',
                'theme' => 'dark',
                'account_role' => 'admin',
                'is_support_admin' => true,
            ]
        );

        $updates = [];

        if ($user->name !== $name) {
            $updates['name'] = $name;
        }

        if (! Hash::check($password, $user->password)) {
            $updates['password'] = Hash::make($password);
        }

        if ($user->account_role !== 'admin') {
            $updates['account_role'] = 'admin';
        }

        if (! $user->is_support_admin) {
            $updates['is_support_admin'] = true;
        }

        if (! empty($updates)) {
            $user->forceFill($updates)->save();
        }
    }
}
