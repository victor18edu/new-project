<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdminUserMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_active_admin_user(): void
    {
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(true, Hash::check('password', $admin->password));
    }
}
