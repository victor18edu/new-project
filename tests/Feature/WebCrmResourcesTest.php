<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Segment;
use App\Models\User;
use App\Support\CrmResources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebCrmResourcesTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_rotas_de_recursos_exigem_autenticacao(): void
    {
        $this->get('/companies')->assertRedirect('/login');
        $this->get('/opportunities/create')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_apresenta_links_para_todos_os_modulos(): void
    {
        $this->actingAsUser();

        $response = $this->get('/dashboard')->assertOk()->assertSee('Painel')->assertSee('Usuários');

        foreach (CrmResources::all() as $definition) {
            $response->assertSee($definition->label);
            $response->assertSee(route($definition->indexRoute()), false);
        }
    }

    public function test_index_e_formulario_de_criacao_de_todos_os_recursos(): void
    {
        $this->actingAsUser();

        foreach (CrmResources::all() as $key => $definition) {
            $this->get('/'.$key)->assertOk()->assertSee($definition->label);
            $this->get('/'.$key.'/create')->assertOk()->assertSee('Dados do '.mb_strtolower($definition->singularLabel));
        }
    }

    public function test_cria_edita_e_remove_segmento(): void
    {
        $this->actingAsUser();

        $this->post('/segments', [
            'name' => 'Saúde',
            'slug' => 'saude',
            'is_active' => 1,
        ])->assertRedirect('/segments')->assertSessionHas('status', 'Segmento criado com sucesso.');

        $segment = Segment::where('slug', 'saude')->firstOrFail();

        $this->put("/segments/{$segment->id}", [
            'name' => 'Saúde e bem-estar',
            'slug' => 'saude',
            'is_active' => 0,
        ])->assertRedirect('/segments')->assertSessionHas('status', 'Segmento atualizado com sucesso.');

        $this->assertSame('Saúde e bem-estar', $segment->fresh()->name);

        $this->delete("/segments/{$segment->id}")
            ->assertRedirect('/segments')
            ->assertSessionHas('status', 'Segmento removido com sucesso.');

        $this->assertDatabaseMissing('segments', ['id' => $segment->id]);
    }

    public function test_validacao_de_slug_duplicado_no_segmento(): void
    {
        $this->actingAsUser();

        Segment::create(['name' => 'Existente', 'slug' => 'existente', 'is_active' => true]);

        $this->post('/segments', ['name' => 'Novo', 'slug' => 'existente'])
            ->assertSessionHasErrors('slug');
    }

    public function test_nao_remove_segmento_com_empresas_vinculadas(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Tecnologia', 'slug' => 'tecnologia', 'is_active' => true]);
        $this->createCompany(['segment_id' => $segment->id]);

        $this->delete("/segments/{$segment->id}")
            ->assertRedirect('/segments')
            ->assertSessionHas('error', 'Não é possível remover este segmento porque existem empresas vinculadas.');

        $this->assertDatabaseHas('segments', ['id' => $segment->id]);
    }

    public function test_criacao_de_atividade_preenche_responsavel_e_data_padrao(): void
    {
        $user = $this->actingAsUser();

        $type = ActivityType::create(['name' => 'Reunião', 'slug' => 'reuniao']);

        $this->post('/activities', [
            'activity_type_id' => $type->id,
            'title' => 'Reunião inicial',
            'status' => 'pending',
            'due_date' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect('/activities')->assertSessionHas('status', 'Atividade criada com sucesso.');

        $activity = Activity::where('title', 'Reunião inicial')->firstOrFail();

        $this->assertSame($user->id, $activity->assigned_to);
        $this->assertSame($user->id, $activity->created_by);
        $this->assertNotNull($activity->due_date);
    }

    public function test_alteracao_de_estagio_da_oportunidade_gera_historico(): void
    {
        $user = $this->actingAsUser();

        $company = $this->createCompany();
        $pipeline = Pipeline::create(['name' => 'Vendas', 'slug' => 'vendas', 'is_active' => true]);
        $from = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Prospecção',
            'slug' => 'prospeccao',
            'order_index' => 1,
        ]);
        $to = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Proposta',
            'slug' => 'proposta',
            'order_index' => 2,
        ]);

        $opportunity = Opportunity::create([
            'code' => 'OP-001',
            'title' => 'Nova oportunidade',
            'company_id' => $company->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $from->id,
            'owner_id' => $user->id,
            'status' => 'open',
        ]);

        $this->put("/opportunities/{$opportunity->id}", ['stage_id' => $to->id])
            ->assertRedirect('/opportunities')
            ->assertSessionHas('status', 'Oportunidade atualizada com sucesso.');

        $this->assertDatabaseHas('opportunity_stage_history', [
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $from->id,
            'to_stage_id' => $to->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_index_renderiza_com_registros_e_relacionamentos(): void
    {
        $user = $this->actingAsUser();

        $type = ActivityType::create(['name' => 'Ligação', 'slug' => 'ligacao']);
        $company = $this->createCompany();
        Contact::create(['name' => 'Contato Teste', 'company_id' => $company->id]);
        Segment::create(['name' => 'Outro segmento', 'slug' => 'outro-segmento']);
        $pipeline = Pipeline::create(['name' => 'Comercial', 'slug' => 'comercial', 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Negociação',
            'slug' => 'negociacao',
            'order_index' => 1,
        ]);
        $opportunity = Opportunity::create([
            'code' => 'OP-002',
            'title' => 'Oportunidade com dados',
            'company_id' => $company->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $user->id,
            'status' => 'open',
        ]);
        $proposal = Proposal::create([
            'proposal_number' => 'PR-002',
            'opportunity_id' => $opportunity->id,
            'company_id' => $company->id,
            'owner_id' => $user->id,
            'status' => 'draft',
            'valid_until' => now()->addDays(10),
        ]);
        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'item_name' => 'Consultoria',
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);
        Activity::create([
            'activity_type_id' => $type->id,
            'company_id' => $company->id,
            'title' => 'Atividade com dados',
            'status' => 'pending',
            'due_date' => now()->addDay(),
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);
        OpportunityStageHistory::create([
            'opportunity_id' => $opportunity->id,
            'to_stage_id' => $stage->id,
            'user_id' => $user->id,
        ]);

        foreach (array_keys(CrmResources::all()) as $key) {
            $this->get('/'.$key)->assertOk();
        }
    }

    public function test_edicao_de_empresa_com_slug_e_campo_unico_inalterado(): void
    {
        $this->actingAsUser();

        $company = $this->createCompany(['tax_id' => '12345678901234']);

        $this->put("/companies/{$company->id}", [
            'trade_name' => 'Nome atualizado',
            'tax_id' => '12345678901234',
        ])->assertRedirect('/companies')->assertSessionHas('status', 'Empresa atualizada com sucesso.');

        $this->assertSame('Nome atualizado', $company->fresh()->trade_name);
    }
}
