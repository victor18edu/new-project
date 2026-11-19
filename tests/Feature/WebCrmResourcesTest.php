<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Segment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\CrmResources;
use App\Support\FormLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function test_show_de_recursos_genericos_exige_autenticacao(): void
    {
        $this->get('/segments/1')->assertRedirect('/login');
    }

    public function test_todos_os_recursos_genericos_tem_rota_de_detalhes(): void
    {
        foreach (CrmResources::all() as $key => $definition) {
            $this->assertStringContainsString('/'.$key.'/123', route($definition->route('show'), 123));
        }
    }

    public function test_show_de_segmento_exibe_dados_acoes_e_link_na_listagem(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Saúde', 'slug' => 'saude', 'is_active' => true]);

        $this->get("/segments/{$segment->id}")
            ->assertOk()
            ->assertSee('Detalhes do segmento')
            ->assertSee('Saúde')
            ->assertSee(route('web.segments.edit', $segment), false);

        $this->get('/segments')
            ->assertOk()
            ->assertSee(route('web.segments.show', $segment), false);
    }

    public function test_show_de_empresa_exibe_relacionamento(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Tecnologia', 'slug' => 'tecnologia', 'is_active' => true]);
        $company = $this->createCompany([
            'segment_id' => $segment->id,
            'trade_name' => 'Acme Ltda',
        ]);

        $this->get("/companies/{$company->id}")
            ->assertOk()
            ->assertSee('Acme Ltda')
            ->assertSee('Tecnologia');
    }

    public function test_show_nao_exibe_registro_removido(): void
    {
        $this->actingAsUser();

        $company = $this->createCompany();
        $company->delete();

        $this->get("/companies/{$company->id}")->assertNotFound();
    }

    public function test_formulario_de_contatos_tem_mascara_de_telefone(): void
    {
        $this->actingAsUser();

        $this->get('/contacts/create')
            ->assertOk()
            ->assertSee('data-phone-mask', false);
    }

    public function test_exclusao_de_recursos_usa_modal_personalizado(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Saúde', 'slug' => 'saude', 'is_active' => true]);

        $this->get('/segments')
            ->assertOk()
            ->assertSee('Tem certeza que deseja remover este registro?')
            ->assertSee('crm-confirm')
            ->assertSee('data-confirm-message="Tem certeza que deseja remover este registro?"', false)
            ->assertSee('data-confirm-form-id="delete-segments-'.$segment->id.'"', false)
            ->assertDontSee('onsubmit="return confirm');

        $this->get("/segments/{$segment->id}")
            ->assertOk()
            ->assertSee('Tem certeza que deseja remover este registro?');
    }

    public function test_formularios_de_criacao_nao_renderizam_componentes_blade_literais(): void
    {
        $this->actingAsUser();

        foreach (array_keys(CrmResources::all()) as $key) {
            $this->get('/'.$key.'/create')
                ->assertOk()
                ->assertDontSee('<x-', false);
        }
    }

    public function test_formulario_de_tipo_de_atividade_renderiza_nome_e_slug(): void
    {
        $this->actingAsUser();

        $this->get('/activity-types/create')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="slug"', false);
    }

    public function test_formularios_de_criacao_aplicam_limite_de_caracteres_das_regras(): void
    {
        $this->actingAsUser();

        foreach (CrmResources::all() as $key => $definition) {
            $limits = FormLimits::from(Request::create('/'), $definition->storeRequestClass);
            $content = $this->get('/'.$key.'/create')->assertOk()->getContent();

            foreach ($limits as $field => $max) {
                preg_match('/<(?:input|textarea)\b[^>]*name="'.preg_quote($field, '/').'"[^>]*>/', $content, $matches);

                $this->assertNotEmpty($matches, "Campo {$field} ausente no formulário de {$key}.");
                $this->assertStringContainsString(
                    'maxlength="'.$max.'"',
                    $matches[0],
                    "Campo {$field} do formulário de {$key} deveria limitar a {$max} caracteres."
                );
            }
        }
    }

    public function test_formularios_de_criacao_aplicam_mascara_de_limite_numerico(): void
    {
        $this->actingAsUser();

        foreach (CrmResources::all() as $key => $definition) {
            $limits = FormLimits::numericMaxFrom(Request::create('/'), $definition->storeRequestClass);
            $content = $this->get('/'.$key.'/create')->assertOk()->getContent();

            foreach ($limits as $field => $max) {
                preg_match('/<(?:input|textarea)\b[^>]*name="'.preg_quote($field, '/').'"[^>]*>/', $content, $matches);

                $this->assertNotEmpty($matches, "Campo {$field} ausente no formulário de {$key}.");
                $this->assertStringContainsString(
                    'data-numeric-mask="'.$max.'"',
                    $matches[0],
                    "Campo {$field} do formulário de {$key} deveria aplicar a máscara até {$max}."
                );
            }
        }
    }

    public function test_formulario_de_edicao_de_servico_aplica_mascara_de_limite_numerico(): void
    {
        $this->actingAsUser();

        $category = ServiceCategory::create(['name' => 'Consultoria', 'slug' => 'consultoria']);
        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Consultoria em vendas',
            'slug' => 'consultoria-em-vendas',
            'base_price' => '150.00',
        ]);

        $content = $this->get("/services/{$service->id}/edit")->assertOk()->getContent();

        preg_match('/<input\b[^>]*name="base_price"[^>]*>/', $content, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('data-numeric-mask="9999999999.99"', $matches[0]);
    }

    public function test_formulario_de_edicao_de_segmento_limita_descricao(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Saúde', 'slug' => 'saude', 'is_active' => true]);

        $this->get("/segments/{$segment->id}/edit")
            ->assertOk()
            ->assertSee('maxlength="1000"', false)
            ->assertSee('name="description"', false);
    }

    public function test_descricao_acima_do_limite_retorna_erro_com_limite_na_tela(): void
    {
        $this->actingAsUser();

        $segment = Segment::create(['name' => 'Saúde', 'slug' => 'saude', 'is_active' => true]);
        $description = str_repeat('a', 1001);

        $this->from("/segments/{$segment->id}/edit")
            ->followingRedirects()
            ->put("/segments/{$segment->id}", [
                'name' => 'Saúde',
                'slug' => 'saude',
                'description' => $description,
            ])
            ->assertOk()
            ->assertSee('O campo descrição não pode ter mais que 1000 caracteres.');

        $this->assertDatabaseMissing('segments', [
            'id' => $segment->id,
            'description' => $description,
        ]);
    }

    public function test_show_de_origem_de_lead_exibe_descricao_completa(): void
    {
        $this->actingAsUser();

        $description = str_repeat('Descrição detalhada da origem de lead. ', 10);

        $source = LeadSource::create([
            'name' => 'Indicação',
            'slug' => 'indicacao',
            'description' => $description,
            'channel_type' => 'referral',
            'is_active' => true,
        ]);

        $this->get("/lead-sources/{$source->id}")
            ->assertOk()
            ->assertSee('Descrição')
            ->assertSee($description);
    }

    public function test_detalhes_de_todos_os_recursos_incluem_todos_os_campos_do_cadastro(): void
    {
        $this->actingAsUser();

        foreach (CrmResources::all() as $definition) {
            $record = new ($definition->modelClass);
            $rowKeys = array_column($definition->detailRows($record), 'key');

            foreach ($definition->fields as $field) {
                $fieldKey = $field['name'];
                $isCovered = in_array($fieldKey, $rowKeys, true);

                if (! $isCovered) {
                    foreach ($rowKeys as $rowKey) {
                        if (! str_contains($rowKey, '.')) {
                            continue;
                        }

                        $relation = Str::snake(explode('.', $rowKey, 2)[0]);

                        if ($fieldKey === $relation || $fieldKey === $relation.'_id') {
                            $isCovered = true;

                            break;
                        }
                    }
                }

                $this->assertTrue(
                    $isCovered,
                    "O campo {$fieldKey} de {$definition->key} não aparece nos detalhes."
                );
            }
        }
    }

    public function test_formularios_de_crm_marcam_apenas_campos_obrigatorios(): void
    {
        $this->actingAsUser();

        $content = $this->get('/companies/create')->assertOk()->getContent();

        foreach (['trade_name', 'corporate_name', 'segment_id', 'owner_id', 'city', 'state', 'status'] as $field) {
            $this->assertRequiredLabel($content, $field, true);
        }

        foreach (['tax_id', 'website_url', 'postal_code', 'temperature'] as $field) {
            $this->assertRequiredLabel($content, $field, false);
        }

        $leadSource = $this->get('/lead-sources/create')->assertOk()->getContent();
        $this->assertRequiredLabel($leadSource, 'channel_type', true);

        $activity = $this->get('/activities/create')->assertOk()->getContent();
        $this->assertRequiredLabel($activity, 'due_date', true);
    }

    private function assertRequiredLabel(string $content, string $field, bool $required): void
    {
        preg_match('/<label\b[^>]*for="'.preg_quote($field, '/').'"[^>]*>(.*?)<\/label>/s', $content, $matches);

        $this->assertNotEmpty($matches, "Rótulo de {$field} ausente.");

        if ($required) {
            $this->assertStringContainsString('<span class="text-red-600" aria-hidden="true">*</span>', $matches[1]);
            $this->assertStringContainsString('(obrigatório)', $matches[1]);
        } else {
            $this->assertStringNotContainsString('text-red-600', $matches[1]);
            $this->assertStringNotContainsString('(obrigatório)', $matches[1]);
        }
    }
}
