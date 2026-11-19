<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create or update the initial admin user from configuration.
     */
    public function run(): void
    {
        $password = config('initial-admin.password');

        if (! $password) {
            throw new RuntimeException('Defina ADMIN_PASSWORD no .env antes de rodar o seeder.');
        }

        $now = now();
        $email = config('initial-admin.email');

        $attributes = [
            'name' => config('initial-admin.name'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => $now,
            'deleted_at' => null,
            'updated_at' => $now,
        ];

        $existingAdminId = DB::table('users')->where('email', $email)->value('id');

        if ($existingAdminId !== null) {
            DB::table('users')->where('id', $existingAdminId)->update($attributes);

            return;
        }

        DB::table('users')->insert(array_merge($attributes, [
            'email' => $email,
            'created_at' => $now,
        ]));
    }
}
