<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $pipelineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->pipelineData = [
            'name' => 'Pipeline de Vendas',
            'slug' => 'pipeline-vendas',
            'description' => 'Pipeline padrão de vendas',
            'is_default' => true,
            'is_active' => true,
        ];
    }

    /**
     * Teste: listar pipelines com sucesso (index).
     */
    public function test_list_pipelines_success(): void
    {
        foreach (range(1, 3) as $i) {
            Pipeline::create([
                'name' => "Pipeline {$i}",
                'slug' => "pipeline-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/pipelines');

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
     * Teste: buscar pipeline por ID com sucesso (show).
     */
    public function test_show_pipeline_success(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Pipeline para Buscar',
            'slug' => 'pipeline-para-buscar',
        ]);

        $response = $this->getJson("/api/v1/pipelines/{$pipeline->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Pipeline encontrado.',
        ]);
    }

    /**
     * Teste: buscar pipeline inexistente retorna 404.
     */
    public function test_show_pipeline_not_found(): void
    {
        $response = $this->getJson('/api/v1/pipelines/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar pipeline com sucesso (store).
     */
    public function test_store_pipeline_success(): void
    {
        $response = $this->postJson('/api/v1/pipelines', $this->pipelineData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Pipeline criado com sucesso.',
        ]);
        $this->assertDatabaseHas('pipelines', [
            'name' => 'Pipeline de Vendas',
        ]);
    }

    /**
     * Teste: criar pipeline com dados inválidos retorna erro de validação.
     */
    public function test_store_pipeline_validation_error(): void
    {
        $invalidData = [
            'name' => '',
        ];

        $response = $this->postJson('/api/v1/pipelines', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    /**
     * Teste: atualizar pipeline com sucesso (update).
     */
    public function test_update_pipeline_success(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Pipeline Original',
            'slug' => 'pipeline-original',
        ]);

        $updateData = [
            'name' => 'Pipeline Atualizado',
        ];

        $response = $this->putJson("/api/v1/pipelines/{$pipeline->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pipeline atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline->id,
            'name' => 'Pipeline Atualizado',
        ]);
    }

    /**
     * Teste: atualizar pipeline inexistente retorna 404.
     */
    public function test_update_pipeline_not_found(): void
    {
        $response = $this->putJson('/api/v1/pipelines/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir pipeline com sucesso (destroy).
     */
    public function test_destroy_pipeline_success(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Pipeline para Excluir',
            'slug' => 'pipeline-para-excluir',
        ]);

        $response = $this->deleteJson("/api/v1/pipelines/{$pipeline->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pipeline removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('pipelines', [
            'id' => $pipeline->id,
        ]);
    }

    /**
     * Teste: excluir pipeline inexistente retorna 404.
     */
    public function test_destroy_pipeline_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/pipelines/99999');

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

        $response = $this->getJson('/api/v1/pipelines');

        $response->assertStatus(401);
    }
}