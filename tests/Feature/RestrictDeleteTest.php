<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Segment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestrictDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Segment $segment;
    protected Pipeline $pipeline;
    protected PipelineStage $stage;
    protected ActivityType $activityType;
    protected ServiceCategory $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->segment = Segment::create([
            'name' => 'Segmento Base',
            'slug' => 'segmento-base',
        ]);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Base',
            'slug' => 'pipeline-base',
        ]);

        $this->stage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Estágio Base',
            'slug' => 'estagio-base',
        ]);

        $this->activityType = ActivityType::create([
            'name' => 'Tipo Base',
            'slug' => 'tipo-base',
        ]);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Categoria Base',
            'slug' => 'categoria-base',
        ]);
    }

    /**
     * Teste: segmento com empresas vinculadas não pode ser removido.
     */
    public function test_segment_with_companies_returns_409(): void
    {
        $this->createCompany(['segment_id' => $this->segment->id]);

        $response = $this->deleteJson("/api/v1/segments/{$this->segment->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: empresa com oportunidades vinculadas não pode ser removida.
     */
    public function test_company_with_opportunities_returns_409(): void
    {
        $company = $this->createCompany(['segment_id' => $this->segment->id]);
        $this->createOpportunity([
            'code' => 'OP-CONFLITO',
            'company_id' => $company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->deleteJson("/api/v1/companies/{$company->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: pipeline com oportunidades vinculadas não pode ser removido.
     */
    public function test_pipeline_with_opportunities_returns_409(): void
    {
        $company = $this->createCompany(['segment_id' => $this->segment->id]);
        $this->createOpportunity([
            'code' => 'OP-PIPELINE',
            'company_id' => $company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->deleteJson("/api/v1/pipelines/{$this->pipeline->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: estágio com oportunidades vinculadas não pode ser removido.
     */
    public function test_pipeline_stage_with_opportunities_returns_409(): void
    {
        $company = $this->createCompany(['segment_id' => $this->segment->id]);
        $this->createOpportunity([
            'code' => 'OP-STAGE',
            'company_id' => $company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);

        $response = $this->deleteJson("/api/v1/pipeline-stages/{$this->stage->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: tipo de atividade com atividades vinculadas não pode ser removido.
     */
    public function test_activity_type_with_activities_returns_409(): void
    {
        $this->createActivity([
            'activity_type_id' => $this->activityType->id,
            'company_id' => $this->createCompany(['segment_id' => $this->segment->id])->id,
        ]);

        $response = $this->deleteJson("/api/v1/activity-types/{$this->activityType->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: categoria de serviço com serviços vinculados não pode ser removida.
     */
    public function test_service_category_with_services_returns_409(): void
    {
        Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço Vinculado',
            'slug' => 'servico-vinculado',
        ]);

        $response = $this->deleteJson("/api/v1/service-categories/{$this->serviceCategory->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: oportunidade com propostas vinculadas não pode ser removida.
     */
    public function test_opportunity_with_proposals_returns_409(): void
    {
        $company = $this->createCompany(['segment_id' => $this->segment->id]);
        $opportunity = $this->createOpportunity([
            'code' => 'OP-PROPOSTA',
            'company_id' => $company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
        ]);
        $this->createProposal([
            'proposal_number' => 'PROP-CONFLITO',
            'opportunity_id' => $opportunity->id,
            'company_id' => $company->id,
        ]);

        $response = $this->deleteJson("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'status' => 409]);
    }

    /**
     * Teste: usuário sem dependências pode ser removido (soft delete).
     */
    public function test_user_without_dependencies_can_be_deleted(): void
    {
        $other = User::factory()->create();

        Sanctum::actingAs($other, ['*']);

        $response = $this->deleteJson("/api/v1/users/{$other->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $other->id]);
    }
}
