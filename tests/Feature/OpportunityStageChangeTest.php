<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityStageChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Pipeline $pipeline;
    protected PipelineStage $fromStage;
    protected PipelineStage $toStage;
    protected Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Histórico',
            'slug' => 'pipeline-historico',
        ]);

        $this->fromStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Prospecção',
            'slug' => 'prospeccao-historico',
        ]);

        $this->toStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Proposta',
            'slug' => 'proposta-historico',
        ]);

        $this->company = $this->createCompany();

        $this->opportunity = $this->createOpportunity([
            'code' => 'OP-HISTORICO',
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->fromStage->id,
        ]);
    }

    /**
     * Teste: alterar o estágio registra automaticamente o histórico.
     */
    public function test_stage_change_creates_history_entry(): void
    {
        $response = $this->putJson("/api/v1/opportunities/{$this->opportunity->id}", [
            'stage_id' => $this->toStage->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('opportunity_stage_history', [
            'opportunity_id' => $this->opportunity->id,
            'from_stage_id' => $this->fromStage->id,
            'to_stage_id' => $this->toStage->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Teste: atualização sem troca de estágio não cria histórico.
     */
    public function test_update_without_stage_change_does_not_create_history(): void
    {
        $response = $this->putJson("/api/v1/opportunities/{$this->opportunity->id}", [
            'title' => 'Título Atualizado',
            'stage_id' => $this->fromStage->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('opportunity_stage_history', [
            'opportunity_id' => $this->opportunity->id,
        ]);
    }
}
