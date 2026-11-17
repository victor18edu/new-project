<?php

namespace Tests\Feature;

use App\Models\LostReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LostReasonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $lostReasonData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->lostReasonData = [
            'name' => 'Preço elevado',
            'description' => 'O cliente achou o preço muito alto',
            'is_active' => true,
        ];
    }

    /**
     * Teste: listar motivos de perda com sucesso (index).
     */
    public function test_list_lost_reasons_success(): void
    {
        foreach (range(1, 3) as $i) {
            LostReason::create([
                'name' => "Motivo {$i}",
                'description' => "Descrição do motivo {$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/lost-reasons');

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
     * Teste: buscar motivo de perda por ID com sucesso (show).
     */
    public function test_show_lost_reason_success(): void
    {
        $lostReason = LostReason::create([
            'name' => 'Motivo para Buscar',
            'description' => 'Descrição do motivo para buscar',
        ]);

        $response = $this->getJson("/api/v1/lost-reasons/{$lostReason->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Motivo de perda encontrado.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'is_active',
            ],
            'message',
            'status',
        ]);
    }

    /**
     * Teste: buscar motivo de perda inexistente retorna 404.
     */
    public function test_show_lost_reason_not_found(): void
    {
        $response = $this->getJson('/api/v1/lost-reasons/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar motivo de perda com sucesso (store).
     */
    public function test_store_lost_reason_success(): void
    {
        $response = $this->postJson('/api/v1/lost-reasons', $this->lostReasonData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Motivo de perda criado com sucesso.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'is_active',
            ],
            'message',
            'status',
        ]);

        $this->assertDatabaseHas('lost_reasons', [
            'name' => 'Preço elevado',
        ]);
    }

    /**
     * Teste: criar motivo de perda com dados inválidos retorna erro de validação.
     */
    public function test_store_lost_reason_validation_error(): void
    {
        $invalidData = [
            'name' => '',
        ];

        $response = $this->postJson('/api/v1/lost-reasons', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    /**
     * Teste: atualizar motivo de perda com sucesso (update).
     */
    public function test_update_lost_reason_success(): void
    {
        $lostReason = LostReason::create([
            'name' => 'Motivo Original',
            'description' => 'Descrição original',
        ]);

        $updateData = [
            'name' => 'Motivo Atualizado',
            'description' => 'Descrição atualizada',
        ];

        $response = $this->putJson("/api/v1/lost-reasons/{$lostReason->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Motivo de perda atualizado com sucesso.',
        ]);

        $this->assertDatabaseHas('lost_reasons', [
            'id' => $lostReason->id,
            'name' => 'Motivo Atualizado',
        ]);
    }

    /**
     * Teste: atualizar motivo de perda inexistente retorna 404.
     */
    public function test_update_lost_reason_not_found(): void
    {
        $response = $this->putJson('/api/v1/lost-reasons/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir motivo de perda com sucesso (destroy).
     */
    public function test_destroy_lost_reason_success(): void
    {
        $lostReason = LostReason::create([
            'name' => 'Motivo para Excluir',
            'description' => 'Descrição para excluir',
        ]);

        $response = $this->deleteJson("/api/v1/lost-reasons/{$lostReason->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Motivo de perda removido com sucesso.',
        ]);

        $this->assertDatabaseMissing('lost_reasons', [
            'id' => $lostReason->id,
        ]);
    }

    /**
     * Teste: excluir motivo de perda inexistente retorna 404.
     */
    public function test_destroy_lost_reason_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/lost-reasons/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: rota sem autenticação retorna 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/lost-reasons');

        $response->assertStatus(401);
    }
}