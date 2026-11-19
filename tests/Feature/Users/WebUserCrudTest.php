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

    public function test_show_requires_authentication_and_does_not_expose_password(): void
    {
        $target = User::factory()->create([
            'name' => 'Usuário Detalhado',
            'email' => 'detalhes@example.com',
            'password' => Hash::make('Senha@123456'),
        ]);

        $this->get("/users/{$target->id}")->assertRedirect('/login');

        $this->actingAsAdmin();

        $this->get("/users/{$target->id}")
            ->assertOk()
            ->assertSee('Usuário Detalhado')
            ->assertSee('detalhes@example.com')
            ->assertDontSee($target->password)
            ->assertSee(route('web.users.edit', $target), false);
    }

    public function test_store_creates_user_with_hashed_password(): void
    {
        $this->actingAsAdmin();

        $this->post('/users', [
            'name' => 'Carla Dias',
            'email' => 'carla@example.com',
            'password' => 'Senha@123456',
            'role' => 'manager',
            'phone' => '11999999999',
            'is_active' => 1,
        ])
            ->assertRedirect('/users')
            ->assertSessionHas('status', 'Usuário criado com sucesso.');

        $user = User::where('email', 'carla@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('Senha@123456', $user->password));
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

    public function test_store_rejects_weak_password(): void
    {
        $this->actingAsAdmin();

        $this->post('/users', [
            'name' => 'Senha Fraca',
            'email' => 'fraca@example.com',
            'password' => 'senha123456',
            'role' => 'sales_rep',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'fraca@example.com']);
    }

    public function test_update_keeps_current_password_when_left_blank(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Senha@123456')]);
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

    public function test_update_rejects_email_belonging_to_another_user(): void
    {
        $actor = $this->actingAsAdmin();
        $other = User::factory()->create(['email' => 'outro@example.com']);
        $originalEmail = $actor->email;

        $this->put("/users/{$actor->id}", ['email' => $other->email])
            ->assertSessionHasErrors('email');

        $this->assertSame($originalEmail, $actor->fresh()->email);
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

    public function test_formularios_de_usuario_tem_mascara_de_telefone(): void
    {
        $admin = $this->actingAsAdmin();

        $this->get('/users/create')->assertOk()->assertSee('data-phone-mask', false);
        $this->get("/users/{$admin->id}/edit")->assertOk()->assertSee('data-phone-mask', false);
    }

    public function test_formularios_de_usuario_aplicam_limite_de_caracteres(): void
    {
        $admin = $this->actingAsAdmin();

        foreach (['/users/create', "/users/{$admin->id}/edit"] as $url) {
            $content = $this->get($url)->assertOk()->getContent();

            foreach (['name' => 150, 'email' => 150, 'phone' => 20] as $field => $max) {
                preg_match('/<input\b[^>]*name="'.$field.'"[^>]*>/', $content, $matches);

                $this->assertNotEmpty($matches, "Campo {$field} ausente em {$url}.");
                $this->assertStringContainsString('maxlength="'.$max.'"', $matches[0]);
            }
        }
    }

    public function test_exclusao_de_usuario_usa_modal_personalizado(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->get('/users')
            ->assertOk()
            ->assertSee('Tem certeza que deseja remover este usuário?')
            ->assertSee('crm-confirm')
            ->assertDontSee('onsubmit="return confirm');

        $this->get("/users/{$target->id}")
            ->assertOk()
            ->assertSee('Tem certeza que deseja remover este usuário?')
            ->assertDontSee('onsubmit="return confirm');
    }

    public function test_usuario_principal_nao_pode_ser_excluido(): void
    {
        $admin = User::findOrFail(User::PROTECTED_ID);
        $this->actingAs($admin);

        $this->delete('/users/'.$admin->id)
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'O usuário principal do sistema não pode ser removido.');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_usuario_principal_so_atualiza_nome_email_e_senha(): void
    {
        $admin = User::findOrFail(User::PROTECTED_ID);
        $originalRole = $admin->role;
        $originalPhone = $admin->phone;

        $this->actingAs($admin)
            ->put('/users/'.$admin->id, [
                'name' => 'Admin Atualizado',
                'email' => 'admin-novo@example.com',
                'password' => 'Senha@123456',
                'role' => 'sales_rep',
                'is_active' => 0,
                'phone' => '11988887777',
            ])
            ->assertRedirect('/users')
            ->assertSessionHas('status', 'Usuário atualizado com sucesso.');

        $admin->refresh();

        $this->assertSame('Admin Atualizado', $admin->name);
        $this->assertSame('admin-novo@example.com', $admin->email);
        $this->assertSame($originalRole, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertSame($originalPhone, $admin->phone);
    }

    public function test_formulario_do_usuario_principal_oculta_perfil_status_e_telefone(): void
    {
        $admin = User::findOrFail(User::PROTECTED_ID);
        $this->actingAs($admin);

        $this->get('/users/'.$admin->id.'/edit')
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('O perfil do usuário principal não pode ser alterado.')
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="is_active"', false)
            ->assertDontSee('name="phone"', false);
    }

    public function test_usuario_principal_nao_exibe_botao_de_excluir(): void
    {
        $admin = User::findOrFail(User::PROTECTED_ID);
        User::factory()->create();
        $this->actingAs($admin);

        $this->get('/users')
            ->assertOk()
            ->assertDontSee('data-confirm-form-id="delete-user-'.$admin->id.'"', false);

        $this->get('/users/'.$admin->id)
            ->assertOk()
            ->assertDontSee('data-confirm-form-id="delete-user-'.$admin->id.'"', false);
    }

    public function test_formularios_de_usuario_marcam_apenas_campos_obrigatorios(): void
    {
        $admin = $this->actingAsAdmin();

        $create = $this->get('/users/create')->assertOk()->getContent();

        foreach (['name', 'email', 'password', 'role'] as $field) {
            $this->assertRequiredLabel($create, $field, true);
        }

        foreach (['phone', 'is_active'] as $field) {
            $this->assertRequiredLabel($create, $field, false);
        }

        $edit = $this->get("/users/{$admin->id}/edit")->assertOk()->getContent();

        foreach (['name', 'email', 'role'] as $field) {
            $this->assertRequiredLabel($edit, $field, true);
        }

        foreach (['password', 'phone', 'is_active'] as $field) {
            $this->assertRequiredLabel($edit, $field, false);
        }

        $protected = User::findOrFail(User::PROTECTED_ID);
        $this->actingAs($protected);

        $this->assertRequiredLabel(
            $this->get("/users/{$protected->id}/edit")->assertOk()->getContent(),
            'role',
            false
        );
    }

    private function assertRequiredLabel(string $content, string $field, bool $required): void
    {
        preg_match('/<label\b[^>]*for="'.preg_quote($field, '/').'"[^>]*>(.*?)<\/label>/s', $content, $matches);

        $this->assertNotEmpty($matches, "Rótulo de {$field} ausente.");

        if ($required) {
            $this->assertStringContainsString('<span class="text-red-600" aria-hidden="true">*</span>', $matches[1]);
            $this->assertStringContainsString('(obrigatório)', $matches[1]);
        } else {
            $this->assertStringNotContainsString('text-red-600', $matches[1]);
            $this->assertStringNotContainsString('(obrigatório)', $matches[1]);
        }
    }
}
