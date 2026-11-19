<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $password = config('initial-admin.password');

        if (! $password) {
            throw new RuntimeException('Defina ADMIN_PASSWORD no .env antes de rodar as migrations.');
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
        ];

        $existingAdminId = DB::table('users')->where('email', $email)->value('id');

        if ($existingAdminId !== null) {
            DB::table('users')
                ->where('id', $existingAdminId)
                ->update(array_merge($attributes, ['updated_at' => $now]));

            return;
        }

        DB::table('users')->insert(array_merge($attributes, [
            'email' => $email,
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('email', config('initial-admin.email'))->delete();
    }
};
