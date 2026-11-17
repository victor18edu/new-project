<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Pipeline $pipeline;
    protected PipelineStage $pipelineStage;
    protected Company $company;
    protected Contact $contact;
    protected Opportunity $opportunity;
    protected array $proposalData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Teste',
            'slug' => 'pipeline-teste',
        ]);

        $this->pipelineStage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Prospecção',
            'slug' => 'prospeccao',
        ]);

        $this->company = $this->createCompany([
            'corporate_name' => 'Empresa Teste Ltda',
            'trade_name' => 'Empresa Teste',
            'tax_id' => '12345678000190',
            'status' => 'prospecting',
        ]);

        $this->contact = Contact::create([
            'name' => 'Contato Teste',
            'email' => 'contato@teste.com',
            'company_id' => $this->company->id,
        ]);

        $this->opportunity = $this->createOpportunity([
            'code' => 'OP-PROP',
            'title' => 'Oportunidade Teste',
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->pipelineStage->id,
            'status' => 'open',
        ]);

        $this->proposalData = [
            'proposal_number' => 'PROP-001',
            'opportunity_id' => $this->opportunity->id,
            'company_id' => $this->company->id,
            'contact_id' => $this->contact->id,
            'owner_id' => $this->user->id,
            'status' => 'draft',
            'total_one_off' => '1000.00',
            'total_recurring' => '500.00',
            'payment_terms' => '30 dias',
            'valid_until' => '2026-12-31',
            'notes' => 'Notas da proposta',
        ];
    }

    /**
     * Teste: listar propostas com sucesso (index).
     */
    public function test_list_proposals_success(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createProposal([
                'proposal_number' => "PROP-00{$i}",
                'opportunity_id' => $this->opportunity->id,
                'company_id' => $this->company->id,
                'status' => 'draft',
            ]);
        }

        $response = $this->getJson('/api/v1/proposals');

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
     * Teste: buscar proposta por ID com sucesso (show).
     */
    public function test_show_proposal_success(): void
    {
        $proposal = $this->createProposal([
            'proposal_number' => 'PROP-BUSCA',
            'opportunity_id' => $this->opportunity->id,
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $response = $this->getJson("/api/v1/proposals/{$proposal->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Proposta encontrada.',
        ]);
    }

    /**
     * Teste: buscar proposta inexistente retorna 404.
     */
    public function test_show_proposal_not_found(): void
    {
        $response = $this->getJson('/api/v1/proposals/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar proposta com sucesso (store).
     */
    public function test_store_proposal_success(): void
    {
        $response = $this->postJson('/api/v1/proposals', $this->proposalData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Proposta criada com sucesso.',
        ]);
        $this->assertDatabaseHas('proposals', [
            'proposal_number' => 'PROP-001',
        ]);
    }

    /**
     * Teste: criar proposta com dados inválidos retorna erro de validação.
     */
    public function test_store_proposal_validation_error(): void
    {
        $invalidData = [
            'proposal_number' => '',
            'opportunity_id' => '',
            'company_id' => '',
            'status' => '',
        ];

        $response = $this->postJson('/api/v1/proposals', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'proposal_number',
            'opportunity_id',
            'company_id',
            'status',
        ]);
    }

    /**
     * Teste: atualizar proposta com sucesso (update).
     */
    public function test_update_proposal_success(): void
    {
        $proposal = $this->createProposal([
            'proposal_number' => 'PROP-ORIGINAL',
            'opportunity_id' => $this->opportunity->id,
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $updateData = [
            'status' => 'sent',
        ];

        $response = $this->putJson("/api/v1/proposals/{$proposal->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Proposta atualizada com sucesso.',
        ]);
        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'sent',
        ]);
    }

    /**
     * Teste: atualizar proposta inexistente retorna 404.
     */
    public function test_update_proposal_not_found(): void
    {
        $response = $this->putJson('/api/v1/proposals/99999', [
            'status' => 'sent',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir proposta com sucesso (destroy).
     */
    public function test_destroy_proposal_success(): void
    {
        $proposal = $this->createProposal([
            'proposal_number' => 'PROP-EXCLUIR',
            'opportunity_id' => $this->opportunity->id,
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $response = $this->deleteJson("/api/v1/proposals/{$proposal->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Proposta removida com sucesso.',
        ]);
        $this->assertSoftDeleted('proposals', [
            'id' => $proposal->id,
        ]);
    }

    /**
     * Teste: excluir proposta inexistente retorna 404.
     */
    public function test_destroy_proposal_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/proposals/99999');

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

        $response = $this->getJson('/api/v1/proposals');

        $response->assertStatus(401);
    }
}