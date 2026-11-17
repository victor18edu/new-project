<?php

namespace Tests\Feature;

use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadSourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $leadSourceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->leadSourceData = [
            'name' => 'Indicação',
            'slug' => 'indicacao',
            'description' => 'Clientes via indicação',
            'channel_type' => 'platform',
            'is_active' => true,
        ];
    }

    /**
     * Teste: listar fontes de lead com sucesso (index).
     */
    public function test_list_lead_sources_success(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createLeadSource([
                'name' => "Fonte {$i}",
                'slug' => "fonte-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/lead-sources');

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
     * Teste: buscar fonte de lead por ID com sucesso (show).
     */
    public function test_show_lead_source_success(): void
    {
        $leadSource = $this->createLeadSource([
            'name' => 'Fonte para Buscar',
            'slug' => 'fonte-para-buscar',
        ]);

        $response = $this->getJson("/api/v1/lead-sources/{$leadSource->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Fonte de lead encontrada.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'channel_type',
                'is_active',
            ],
            'message',
            'status',
        ]);
    }

    /**
     * Teste: buscar fonte de lead inexistente retorna 404.
     */
    public function test_show_lead_source_not_found(): void
    {
        $response = $this->getJson('/api/v1/lead-sources/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar fonte de lead com sucesso (store).
     */
    public function test_store_lead_source_success(): void
    {
        $response = $this->postJson('/api/v1/lead-sources', $this->leadSourceData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Fonte de lead criada com sucesso.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'channel_type',
                'is_active',
            ],
            'message',
            'status',
        ]);

        $this->assertDatabaseHas('lead_sources', [
            'name' => 'Indicação',
            'slug' => 'indicacao',
        ]);
    }

    /**
     * Teste: criar fonte de lead com dados inválidos retorna erro de validação.
     */
    public function test_store_lead_source_validation_error(): void
    {
        $invalidData = [
            'name' => '',
        ];

        $response = $this->postJson('/api/v1/lead-sources', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    /**
     * Teste: atualizar fonte de lead com sucesso (update).
     */
    public function test_update_lead_source_success(): void
    {
        $leadSource = $this->createLeadSource([
            'name' => 'Fonte Original',
            'slug' => 'fonte-original',
        ]);

        $updateData = [
            'name' => 'Fonte Atualizada',
            'slug' => 'fonte-atualizada',
        ];

        $response = $this->putJson("/api/v1/lead-sources/{$leadSource->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Fonte de lead atualizada com sucesso.',
        ]);

        $this->assertDatabaseHas('lead_sources', [
            'id' => $leadSource->id,
            'name' => 'Fonte Atualizada',
        ]);
    }

    /**
     * Teste: atualizar fonte de lead inexistente retorna 404.
     */
    public function test_update_lead_source_not_found(): void
    {
        $response = $this->putJson('/api/v1/lead-sources/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir fonte de lead com sucesso (destroy).
     */
    public function test_destroy_lead_source_success(): void
    {
        $leadSource = $this->createLeadSource([
            'name' => 'Fonte para Excluir',
            'slug' => 'fonte-para-excluir',
        ]);

        $response = $this->deleteJson("/api/v1/lead-sources/{$leadSource->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Fonte de lead removida com sucesso.',
        ]);

        $this->assertDatabaseMissing('lead_sources', [
            'id' => $leadSource->id,
        ]);
    }

    /**
     * Teste: excluir fonte de lead inexistente retorna 404.
     */
    public function test_destroy_lead_source_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/lead-sources/99999');

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

         $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/lead-sources');

        $response->assertStatus(401);
    }
}