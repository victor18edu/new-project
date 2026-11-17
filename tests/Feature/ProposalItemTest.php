<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProposalItemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Pipeline $pipeline;
    protected PipelineStage $pipelineStage;
    protected Company $company;
    protected Contact $contact;
    protected Opportunity $opportunity;
    protected Proposal $proposal;
    protected ServiceCategory $serviceCategory;
    protected Service $service;
    protected array $proposalItemData;

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
            'code' => 'OP-ITEM',
            'title' => 'Oportunidade Teste',
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->pipelineStage->id,
            'status' => 'open',
        ]);

        $this->proposal = $this->createProposal([
            'proposal_number' => 'PROP-ITEM',
            'opportunity_id' => $this->opportunity->id,
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
        ]);

        $this->service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço Teste',
            'slug' => 'servico-teste',
        ]);

        $this->proposalItemData = [
            'proposal_id' => $this->proposal->id,
            'service_id' => $this->service->id,
            'item_name' => 'Item de Teste',
            'description' => 'Descrição do item',
            'quantity' => 2,
            'unit_price' => '100.00',
            'discount_amount' => '0.00',
            'total_price' => '200.00',
            'is_recurring' => false,
        ];
    }

    /**
     * Teste: listar itens de proposta com sucesso (index).
     */
    public function test_list_proposal_items_success(): void
    {
        foreach (range(1, 3) as $i) {
            ProposalItem::create([
                'proposal_id' => $this->proposal->id,
                'item_name' => "Item {$i}",
                'quantity' => 1,
                'unit_price' => '50.00',
                'total_price' => '50.00',
            ]);
        }

        $response = $this->getJson('/api/v1/proposal-items');

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
     * Teste: buscar item de proposta por ID com sucesso (show).
     */
    public function test_show_proposal_item_success(): void
    {
        $proposalItem = ProposalItem::create([
            'proposal_id' => $this->proposal->id,
            'item_name' => 'Item para Buscar',
            'quantity' => 1,
            'unit_price' => '50.00',
            'total_price' => '50.00',
        ]);

        $response = $this->getJson("/api/v1/proposal-items/{$proposalItem->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Item de proposta encontrado.',
        ]);
    }

    /**
     * Teste: buscar item de proposta inexistente retorna 404.
     */
    public function test_show_proposal_item_not_found(): void
    {
        $response = $this->getJson('/api/v1/proposal-items/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar item de proposta com sucesso (store).
     */
    public function test_store_proposal_item_success(): void
    {
        $response = $this->postJson('/api/v1/proposal-items', $this->proposalItemData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Item de proposta criado com sucesso.',
        ]);
        $this->assertDatabaseHas('proposal_items', [
            'item_name' => 'Item de Teste',
        ]);
    }

    /**
     * Teste: criar item de proposta com dados inválidos retorna erro de validação.
     */
    public function test_store_proposal_item_validation_error(): void
    {
        $invalidData = [
            'proposal_id' => '',
            'item_name' => '',
            'quantity' => '',
            'unit_price' => '',
            'total_price' => '',
        ];

        $response = $this->postJson('/api/v1/proposal-items', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'proposal_id',
            'item_name',
            'quantity',
            'unit_price',
            'total_price',
        ]);
    }

    /**
     * Teste: atualizar item de proposta com sucesso (update).
     */
    public function test_update_proposal_item_success(): void
    {
        $proposalItem = ProposalItem::create([
            'proposal_id' => $this->proposal->id,
            'item_name' => 'Item Original',
            'quantity' => 1,
            'unit_price' => '50.00',
            'total_price' => '50.00',
        ]);

        $updateData = [
            'item_name' => 'Item Atualizado',
            'quantity' => 3,
        ];

        $response = $this->putJson("/api/v1/proposal-items/{$proposalItem->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Item de proposta atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('proposal_items', [
            'id' => $proposalItem->id,
            'item_name' => 'Item Atualizado',
        ]);
    }

    /**
     * Teste: atualizar item de proposta inexistente retorna 404.
     */
    public function test_update_proposal_item_not_found(): void
    {
        $response = $this->putJson('/api/v1/proposal-items/99999', [
            'item_name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir item de proposta com sucesso (destroy).
     */
    public function test_destroy_proposal_item_success(): void
    {
        $proposalItem = ProposalItem::create([
            'proposal_id' => $this->proposal->id,
            'item_name' => 'Item para Excluir',
            'quantity' => 1,
            'unit_price' => '50.00',
            'total_price' => '50.00',
        ]);

        $response = $this->deleteJson("/api/v1/proposal-items/{$proposalItem->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Item de proposta removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('proposal_items', [
            'id' => $proposalItem->id,
        ]);
    }

    /**
     * Teste: excluir item de proposta inexistente retorna 404.
     */
    public function test_destroy_proposal_item_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/proposal-items/99999');

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

        $response = $this->getJson('/api/v1/proposal-items');

        $response->assertStatus(401);
    }
}