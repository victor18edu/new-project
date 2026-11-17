<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\LostReason;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Segment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Segment $segment;
    protected Company $company;
    protected Pipeline $pipeline;
    protected PipelineStage $stage;
    protected ServiceCategory $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->segment = Segment::create([
            'name' => 'Segmento Validação',
            'slug' => 'segmento-validacao',
        ]);

        $this->company = $this->createCompany(['segment_id' => $this->segment->id]);

        $this->pipeline = Pipeline::create([
            'name' => 'Pipeline Validação',
            'slug' => 'pipeline-validacao',
        ]);

        $this->stage = PipelineStage::create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Estágio Validação',
            'slug' => 'estagio-validacao',
        ]);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Categoria Validação',
            'slug' => 'categoria-validacao',
        ]);
    }

    /**
     * Teste: canal de fonte de lead fora do ENUM é rejeitado.
     */
    public function test_lead_source_channel_type_must_be_valid_enum(): void
    {
        $response = $this->postJson('/api/v1/lead-sources', [
            'name' => 'Fonte Inválida',
            'slug' => 'fonte-invalida',
            'channel_type' => 'canal-inexistente',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('channel_type');
    }

    /**
     * Teste: nome do motivo de perda é único.
     */
    public function test_lost_reason_name_must_be_unique(): void
    {
        LostReason::create(['name' => 'Preço elevado']);

        $response = $this->postJson('/api/v1/lost-reasons', [
            'name' => 'Preço elevado',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    /**
     * Teste: e-mail de contato pode se repetir entre empresas.
     */
    public function test_contact_email_is_not_unique(): void
    {
        $data = [
            'name' => 'Contato Comum',
            'email' => 'comum@teste.com',
            'company_id' => $this->company->id,
        ];

        $this->postJson('/api/v1/contacts', $data)->assertStatus(201);
        $this->postJson('/api/v1/contacts', $data)->assertStatus(201);

        $this->assertSame(2, Contact::query()->where('email', 'comum@teste.com')->count());
    }

    /**
     * Teste: valores monetários devem ser numéricos com até 2 decimais.
     */
    public function test_monetary_value_must_be_numeric(): void
    {
        $response = $this->postJson('/api/v1/opportunities', [
            'code' => 'OP-VALIDACAO',
            'title' => 'Oportunidade Validação',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->stage->id,
            'status' => 'open',
            'estimated_one_off_value' => 'mil reais',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('estimated_one_off_value');
    }

    /**
     * Teste: estado deve ter exatamente 2 caracteres.
     */
    public function test_company_state_must_have_two_characters(): void
    {
        $response = $this->postJson('/api/v1/companies', [
            'corporate_name' => 'Empresa Validação Ltda',
            'trade_name' => 'Empresa Validação',
            'tax_id' => '12345678000199',
            'segment_id' => $this->segment->id,
            'owner_id' => $this->user->id,
            'city' => 'São Paulo',
            'state' => 'SPP',
            'status' => 'prospecting',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('state');
    }

    /**
     * Teste: tipo de precificação fora do ENUM é rejeitado.
     */
    public function test_service_pricing_type_must_be_valid_enum(): void
    {
        $response = $this->postJson('/api/v1/services', [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço Inválido',
            'slug' => 'servico-invalido',
            'pricing_type' => 'fixo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pricing_type');
    }
}
