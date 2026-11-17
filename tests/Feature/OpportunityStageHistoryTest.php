<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityStageHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Pipeline $pipeline;
    protected PipelineStage $stageFrom;
    protected PipelineStage $stageTo;
    protected Company $company;
    protected Opportunity $opportunity;
    protected array $historyData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Teste',
            'slug' => 'pipeline-teste',
        ]);

        $this->stageFrom = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Prospecção',
            'slug' => 'prospeccao',
        ]);

        $this->stageTo = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Negociação',
            'slug' => 'negociacao',
        ]);

        $this->company = $this->createCompany([
            'corporate_name' => 'Empresa Teste Ltda',
            'trade_name' => 'Empresa Teste',
            'tax_id' => '12345678000190',
            'status' => 'prospecting',
        ]);

        $this->opportunity = $this->createOpportunity([
            'code' => 'OP-HIST',
            'title' => 'Oportunidade Teste',
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stageTo->id,
            'status' => 'open',
        ]);

        $this->historyData = [
            'opportunity_id' => $this->opportunity->id,
            'from_stage_id' => $this->stageFrom->id,
            'to_stage_id' => $this->stageTo->id,
            'user_id' => $this->user->id,
            'duration_in_seconds' => 3600,
        ];
    }

    /**
     * Teste: listar históricos de estágio com sucesso (index).
     */
    public function test_list_opportunity_stage_histories_success(): void
    {
        foreach (range(1, 3) as $i) {
            OpportunityStageHistory::create([
                'opportunity_id' => $this->opportunity->id,
                'from_stage_id' => $this->stageFrom->id,
                'to_stage_id' => $this->stageTo->id,
                'user_id' => $this->user->id,
                'duration_in_seconds' => 3600 * $i,
            ]);
        }

        $response = $this->getJson('/api/v1/opportunity-stage-histories');

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
     * Teste: buscar histórico de estágio por ID com sucesso (show).
     */
    public function test_show_opportunity_stage_history_success(): void
    {
        $history = OpportunityStageHistory::create([
            'opportunity_id' => $this->opportunity->id,
            'from_stage_id' => $this->stageFrom->id,
            'to_stage_id' => $this->stageTo->id,
            'user_id' => $this->user->id,
            'duration_in_seconds' => 3600,
        ]);

        $response = $this->getJson("/api/v1/opportunity-stage-histories/{$history->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Histórico de estágio encontrado.',
        ]);
    }

    /**
     * Teste: buscar histórico de estágio inexistente retorna 404.
     */
    public function test_show_opportunity_stage_history_not_found(): void
    {
        $response = $this->getJson('/api/v1/opportunity-stage-histories/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar histórico de estágio com sucesso (store).
     */
    public function test_store_opportunity_stage_history_success(): void
    {
        $response = $this->postJson('/api/v1/opportunity-stage-histories', $this->historyData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Histórico de estágio criado com sucesso.',
        ]);
        $this->assertDatabaseHas('opportunity_stage_history', [
            'opportunity_id' => $this->opportunity->id,
        ]);
    }

    /**
     * Teste: criar histórico de estágio com dados inválidos retorna erro de validação.
     */
    public function test_store_opportunity_stage_history_validation_error(): void
    {
        $invalidData = [
            'opportunity_id' => '',
            'to_stage_id' => '',
            'user_id' => '',
        ];

        $response = $this->postJson('/api/v1/opportunity-stage-histories', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'opportunity_id',
            'to_stage_id',
            'user_id',
        ]);
    }

    /**
     * Teste: atualizar histórico de estágio com sucesso (update).
     */
    public function test_update_opportunity_stage_history_success(): void
    {
        $history = OpportunityStageHistory::create([
            'opportunity_id' => $this->opportunity->id,
            'from_stage_id' => $this->stageFrom->id,
            'to_stage_id' => $this->stageTo->id,
            'user_id' => $this->user->id,
            'duration_in_seconds' => 3600,
        ]);

        $updateData = [
            'duration_in_seconds' => 7200,
        ];

        $response = $this->putJson("/api/v1/opportunity-stage-histories/{$history->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Histórico de estágio atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('opportunity_stage_history', [
            'id' => $history->id,
            'duration_in_seconds' => 7200,
        ]);
    }

    /**
     * Teste: atualizar histórico de estágio inexistente retorna 404.
     */
    public function test_update_opportunity_stage_history_not_found(): void
    {
        $response = $this->putJson('/api/v1/opportunity-stage-histories/99999', [
            'duration_in_seconds' => 7200,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir histórico de estágio com sucesso (destroy).
     */
    public function test_destroy_opportunity_stage_history_success(): void
    {
        $history = OpportunityStageHistory::create([
            'opportunity_id' => $this->opportunity->id,
            'from_stage_id' => $this->stageFrom->id,
            'to_stage_id' => $this->stageTo->id,
            'user_id' => $this->user->id,
            'duration_in_seconds' => 3600,
        ]);

        $response = $this->deleteJson("/api/v1/opportunity-stage-histories/{$history->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Histórico de estágio removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('opportunity_stage_history', [
            'id' => $history->id,
        ]);
    }

    /**
     * Teste: excluir histórico de estágio inexistente retorna 404.
     */
    public function test_destroy_opportunity_stage_history_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/opportunity-stage-histories/99999');

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

        $response = $this->getJson('/api/v1/opportunity-stage-histories');

        $response->assertStatus(401);
    }
}