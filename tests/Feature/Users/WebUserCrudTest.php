<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebUserCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(array $attributes = []): User
    {
        $admin = User::factory()->create(array_merge(['role' => 'admin'], $attributes));
        $this->actingAs($admin);

        return $admin;
    }

    public function test_index_requires_authentication(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_index_lists_users_and_applies_filters(): void
    {
        $this->actingAsAdmin();

        User::factory()->create(['name' => 'Ana Silva', 'email' => 'ana@example.com', 'role' => 'manager']);
        User::factory()->create(['name' => 'Bruno Costa', 'email' => 'bruno@example.com', 'role' => 'sales_rep']);

        $this->get('/users')
            ->assertOk()
            ->assertSee('Ana Silva')
            ->assertSee('Bruno Costa');

        $this->get('/users?q=Ana')
            ->assertOk()
            ->assertSee('Ana Silva')
            ->assertDontSee('bruno@example.com');

        $this->get('/users?role=manager')
            ->assertOk()
            ->assertSee('ana@example.com')
            ->assertDontSee('bruno@example.com');

        $this->get('/users?status=inactive')
            ->assertOk()
            ->assertSee('Nenhum usuário encontrado');
    }

    public function test_create_and_edit_forms_are_accessible(): void
    {
        $admin = $this->actingAsAdmin();

        $this->get('/users/create')->assertOk()->assertSee('Criar usuário');
        $this->get("/users/{$admin->id}/edit")->assertOk()->assertSee('Salvar alterações');
    }

    public function test_store_creates_user_with_hashed_password(): void
    {
        $this->actingAsAdmin();

        $this->post('/users', [
            'name' => 'Carla Dias',
            'email' => 'carla@example.com',
            'password' => 'senha123456',
            'role' => 'manager',
            'phone' => '11999999999',
            'is_active' => 1,
        ])
            ->assertRedirect('/users')
            ->assertSessionHas('status', 'Usuário criado com sucesso.');

        $user = User::where('email', 'carla@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('senha123456', $user->password));
        $this->assertTrue($user->is_active);
        $this->assertSame('manager', $user->role);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->from('/users/create')
            ->post('/users', [])
            ->assertRedirect('/users/create')
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_update_keeps_current_password_when_left_blank(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha123456')]);
        $originalPassword = $user->password;

        $this->actingAs($user)
            ->put("/users/{$user->id}", [
                'name' => 'Nome Atualizado',
                'email' => $user->email,
                'password' => '',
                'role' => 'sales_rep',
                'is_active' => 1,
            ])
            ->assertRedirect('/users')
            ->assertSessionHas('status', 'Usuário atualizado com sucesso.');

        $this->assertSame('Nome Atualizado', $user->fresh()->name);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_admin_and_manager_can_update_other_users(): void
    {
        foreach (['admin', 'manager'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $target = User::factory()->create();

            $this->actingAs($actor)
                ->put("/users/{$target->id}", ['name' => 'Alterado por gestor'])
                ->assertRedirect('/users');

            $this->assertSame('Alterado por gestor', $target->fresh()->name);
        }
    }

    public function test_sales_rep_cannot_update_or_delete_other_users(): void
    {
        $actor = User::factory()->create(['role' => 'sales_rep']);
        $target = User::factory()->create();

        $this->actingAs($actor);

        $this->put("/users/{$target->id}", ['name' => 'Alterado'])->assertForbidden();
        $this->delete("/users/{$target->id}")->assertForbidden();
    }

    public function test_destroy_soft_deletes_user(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->delete("/users/{$target->id}")
            ->assertRedirect('/users')
            ->assertSessionHas('status', 'Usuário removido com sucesso.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_destroy_with_dependencies_shows_error(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->createCompany(['owner_id' => $target->id]);

        $this->delete("/users/{$target->id}")
            ->assertRedirect('/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }
}
