<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineStageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Pipeline $pipeline;
    protected array $pipelineStageData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Teste',
            'slug' => 'pipeline-teste',
        ]);

        $this->pipelineStageData = [
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Prospecção',
            'order_index' => 1,
            'probability_percent' => 10,
            'slug' => 'prospeccao',
            'is_won_stage' => false,
            'is_lost_stage' => false,
            'color_hex' => '#3B82F6',
        ];
    }

    /**
     * Teste: listar estágios de pipeline com sucesso (index).
     */
    public function test_list_pipeline_stages_success(): void
    {
        foreach (range(1, 3) as $i) {
            PipelineStage::create([
                'pipeline_id' => $this->pipeline->id,
                'name' => "Estágio {$i}",
                'slug' => "estagio-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/pipeline-stages');

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
     * Teste: buscar estágio de pipeline por ID com sucesso (show).
     */
    public function test_show_pipeline_stage_success(): void
    {
        $pipelineStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Estágio para Buscar',
            'slug' => 'estagio-para-buscar',
        ]);

        $response = $this->getJson("/api/v1/pipeline-stages/{$pipelineStage->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Estágio de pipeline encontrado.',
        ]);
    }

    /**
     * Teste: buscar estágio de pipeline inexistente retorna 404.
     */
    public function test_show_pipeline_stage_not_found(): void
    {
        $response = $this->getJson('/api/v1/pipeline-stages/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar estágio de pipeline com sucesso (store).
     */
    public function test_store_pipeline_stage_success(): void
    {
        $response = $this->postJson('/api/v1/pipeline-stages', $this->pipelineStageData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Estágio de pipeline criado com sucesso.',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Prospecção',
        ]);
    }

    /**
     * Teste: criar estágio de pipeline com dados inválidos retorna erro de validação.
     */
    public function test_store_pipeline_stage_validation_error(): void
    {
        $invalidData = [
            'name' => '',
            'pipeline_id' => '',
        ];

        $response = $this->postJson('/api/v1/pipeline-stages', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'pipeline_id',
        ]);
    }

    /**
     * Teste: atualizar estágio de pipeline com sucesso (update).
     */
    public function test_update_pipeline_stage_success(): void
    {
        $pipelineStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Estágio Original',
            'slug' => 'estagio-original',
        ]);

        $updateData = [
            'name' => 'Estágio Atualizado',
        ];

        $response = $this->putJson("/api/v1/pipeline-stages/{$pipelineStage->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estágio de pipeline atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $pipelineStage->id,
            'name' => 'Estágio Atualizado',
        ]);
    }

    /**
     * Teste: atualizar estágio de pipeline inexistente retorna 404.
     */
    public function test_update_pipeline_stage_not_found(): void
    {
        $response = $this->putJson('/api/v1/pipeline-stages/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir estágio de pipeline com sucesso (destroy).
     */
    public function test_destroy_pipeline_stage_success(): void
    {
        $pipelineStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Estágio para Excluir',
            'slug' => 'estagio-para-excluir',
        ]);

        $response = $this->deleteJson("/api/v1/pipeline-stages/{$pipelineStage->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Estágio de pipeline removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('pipeline_stages', [
            'id' => $pipelineStage->id,
        ]);
    }

    /**
     * Teste: excluir estágio de pipeline inexistente retorna 404.
     */
    public function test_destroy_pipeline_stage_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/pipeline-stages/99999');

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

        $response = $this->getJson('/api/v1/pipeline-stages');

        $response->assertStatus(401);
    }
}