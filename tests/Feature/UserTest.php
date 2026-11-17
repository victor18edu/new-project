<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    /**
     * Teste: listar usuários com sucesso (index).
     */
    public function test_list_users_success(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
        ]);
        $response->assertJsonStructure([
            'data' => [
                'items',
                'pagination',
            ],
            'message',
            'status',
        ]);
    }

    /**
     * Teste: endpoint do usuário autenticado continua disponível.
     */
    public function test_current_user_success(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->user->id);
        $response->assertJsonPath('email', $this->user->email);
    }

    /**
     * Teste: buscar usuário por ID com sucesso (show).
     */
    public function test_show_user_success(): void
    {
        $other = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$other->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Usuário encontrado.',
        ]);
    }

    /**
     * Teste: buscar usuário inexistente retorna 404.
     */
    public function test_show_user_not_found(): void
    {
        $response = $this->getJson('/api/v1/users/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar usuário com sucesso (store).
     */
    public function test_store_user_success(): void
    {
        $response = $this->postJson('/api/v1/users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@teste.com',
            'password' => 'senha12345',
            'role' => 'sales_rep',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Usuário criado com sucesso.',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'novo@teste.com',
            'role' => 'sales_rep',
        ]);
    }

    /**
     * Teste: criar usuário com dados inválidos retorna erro de validação.
     */
    public function test_store_user_validation_error(): void
    {
        $response = $this->postJson('/api/v1/users', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'role',
        ]);
    }

    /**
     * Teste: o próprio usuário pode atualizar seus dados (update).
     */
    public function test_update_self_success(): void
    {
        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'name' => 'Nome Atualizado',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Nome Atualizado',
        ]);
    }

    /**
     * Teste: usuário não pode atualizar outro usuário (policy).
     */
    public function test_update_other_user_is_forbidden(): void
    {
        $other = User::factory()->create();

        $response = $this->putJson("/api/v1/users/{$other->id}", [
            'name' => 'Nome Alterado',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'status' => 403,
        ]);
    }

    /**
     * Teste: excluir a si mesmo sem dependências (soft delete).
     */
    public function test_destroy_self_success(): void
    {
        $response = $this->deleteJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Usuário removido com sucesso.',
        ]);
        $this->assertSoftDeleted('users', [
            'id' => $this->user->id,
        ]);
    }

    /**
     * Teste: exclusão com dependências RESTRICT retorna 409.
     */
    public function test_destroy_with_dependencies_returns_409(): void
    {
        $this->createCompany(['owner_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'status' => 409,
        ]);
        $response->assertJsonFragment([
            'message' => 'Não é possível remover este usuário porque existem registros vinculados: empresas.',
        ]);
    }

    /**
     * Teste: rota sem autenticação retorna 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }
}
