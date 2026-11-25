<?php

namespace App\Support;

use App\Http\Requests\V1\StoreActivityRequest;
use App\Http\Requests\V1\StoreActivityTypeRequest;
use App\Http\Requests\V1\StoreCompanyRequest;
use App\Http\Requests\V1\StoreContactRequest;
use App\Http\Requests\V1\StoreCustomerCategoryRequest;
use App\Http\Requests\V1\StoreLeadSourceRequest;
use App\Http\Requests\V1\StoreLostReasonRequest;
use App\Http\Requests\V1\StoreOpportunityRequest;
use App\Http\Requests\V1\StoreOpportunityStageHistoryRequest;
use App\Http\Requests\V1\StorePipelineRequest;
use App\Http\Requests\V1\StorePipelineStageRequest;
use App\Http\Requests\V1\StoreProposalItemRequest;
use App\Http\Requests\V1\StoreProposalRequest;
use App\Http\Requests\V1\StoreSegmentRequest;
use App\Http\Requests\V1\StoreServiceCategoryRequest;
use App\Http\Requests\V1\StoreServiceRequest;
use App\Http\Requests\V1\UpdateActivityRequest;
use App\Http\Requests\V1\UpdateActivityTypeRequest;
use App\Http\Requests\V1\UpdateCompanyRequest;
use App\Http\Requests\V1\UpdateContactRequest;
use App\Http\Requests\V1\UpdateCustomerCategoryRequest;
use App\Http\Requests\V1\UpdateLeadSourceRequest;
use App\Http\Requests\V1\UpdateLostReasonRequest;
use App\Http\Requests\V1\UpdateOpportunityRequest;
use App\Http\Requests\V1\UpdateOpportunityStageHistoryRequest;
use App\Http\Requests\V1\UpdatePipelineRequest;
use App\Http\Requests\V1\UpdatePipelineStageRequest;
use App\Http\Requests\V1\UpdateProposalItemRequest;
use App\Http\Requests\V1\UpdateProposalRequest;
use App\Http\Requests\V1\UpdateSegmentRequest;
use App\Http\Requests\V1\UpdateServiceCategoryRequest;
use App\Http\Requests\V1\UpdateServiceRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerCategory;
use App\Models\LeadSource;
use App\Models\LostReason;
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
use Closure;
use Illuminate\Http\Request;

final class CrmResources
{
    private const ACTIVITY_STATUS = [
        'pending' => 'Pendente',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ];

    private const COMPANY_STATUS = [
        'prospecting' => 'Prospecção',
        'contacted' => 'Contatado',
        'qualified' => 'Qualificado',
        'customer' => 'Cliente',
        'churned' => 'Churn',
        'unviable' => 'Inválido',
    ];

    private const TEMPERATURE = [
        'cold' => 'Frio',
        'warm' => 'Morno',
        'hot' => 'Quente',
    ];

    private const WEBSITE_STATUS = [
        'none' => 'Sem site',
        'outdated' => 'Desatualizado',
        'broken' => 'Quebrado',
        'modern' => 'Moderno',
    ];

    private const CHANNEL_TYPE = [
        'outbound' => 'Outbound',
        'inbound' => 'Inbound',
        'platform' => 'Plataforma',
        'referral' => 'Indicação',
        'other' => 'Outro',
    ];

    private const OPPORTUNITY_STATUS = [
        'open' => 'Aberta',
        'won' => 'Ganha',
        'lost' => 'Perdida',
        'abandoned' => 'Abandonada',
    ];

    private const PROPOSAL_STATUS = [
        'draft' => 'Rascunho',
        'sent' => 'Enviada',
        'accepted' => 'Aceita',
        'rejected' => 'Rejeitada',
        'expired' => 'Expirada',
    ];

    private const PRICING_TYPE = [
        'one_off' => 'Pagamento único',
        'monthly' => 'Mensal',
        'hourly' => 'Por hora',
    ];

    private const YES_NO = [1 => 'Sim', 0 => 'Não'];

    /** @var array<string, CrmResourceDefinition>|null */
    private static ?array $definitions = null;

    public static function all(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        return self::$definitions = [
            'activity-types' => new CrmResourceDefinition(
                key: 'activity-types',
                label: 'Tipos de atividade',
                singularLabel: 'Tipo de atividade',
                group: 'Cadastros',
                modelClass: ActivityType::class,
                storeRequestClass: StoreActivityTypeRequest::class,
                updateRequestClass: UpdateActivityTypeRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Descrição', 'key' => 'description'],
                    ['label' => 'Ícone', 'key' => 'icon'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true, 'help' => 'Identificador único, ex.: reuniao-inicial'],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'icon', 'label' => 'Ícone', 'help' => 'Ex.: bi-check-circle'],
                ],
                searchable: ['name', 'slug'],
                dependencies: [
                    ['model' => Activity::class, 'column' => 'activity_type_id', 'label' => 'atividades'],
                ],
                dependencyMessage: 'Não é possível remover este tipo de atividade porque existem atividades vinculadas.',
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'segments' => new CrmResourceDefinition(
                key: 'segments',
                label: 'Segmentos',
                singularLabel: 'Segmento',
                group: 'Cadastros',
                modelClass: Segment::class,
                storeRequestClass: StoreSegmentRequest::class,
                updateRequestClass: UpdateSegmentRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Descrição', 'key' => 'description'],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Segmento ativo'],
                ],
                filters: [
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                dependencies: [
                    ['model' => Company::class, 'column' => 'segment_id', 'label' => 'empresas'],
                ],
                dependencyMessage: 'Não é possível remover este segmento porque existem empresas vinculadas.',
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'lead-sources' => new CrmResourceDefinition(
                key: 'lead-sources',
                label: 'Origens de lead',
                singularLabel: 'Origem de lead',
                group: 'Cadastros',
                modelClass: LeadSource::class,
                storeRequestClass: StoreLeadSourceRequest::class,
                updateRequestClass: UpdateLeadSourceRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Canal', 'key' => 'channel_type', 'format' => 'badge', 'options' => self::CHANNEL_TYPE],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'channel_type', 'label' => 'Canal', 'type' => 'select', 'required' => true, 'options' => self::CHANNEL_TYPE],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Origem ativa'],
                ],
                filters: [
                    ['name' => 'channel_type', 'label' => 'Canal', 'options' => self::CHANNEL_TYPE],
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                seed: ['channel_type' => 'other'],
                orderBy: 'name',
                orderDirection: 'asc',
                gender: 'f',
            ),

            'lost-reasons' => new CrmResourceDefinition(
                key: 'lost-reasons',
                label: 'Motivos de perda',
                singularLabel: 'Motivo de perda',
                group: 'Cadastros',
                modelClass: LostReason::class,
                storeRequestClass: StoreLostReasonRequest::class,
                updateRequestClass: UpdateLostReasonRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Descrição', 'key' => 'description'],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição'],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Motivo ativo'],
                ],
                filters: [
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name'],
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'service-categories' => new CrmResourceDefinition(
                key: 'service-categories',
                label: 'Categorias de serviço',
                singularLabel: 'Categoria de serviço',
                group: 'Cadastros',
                modelClass: ServiceCategory::class,
                storeRequestClass: StoreServiceCategoryRequest::class,
                updateRequestClass: UpdateServiceCategoryRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Descrição', 'key' => 'description'],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Categoria ativa'],
                ],
                filters: [
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                dependencies: [
                    ['model' => Service::class, 'column' => 'category_id', 'label' => 'serviços'],
                ],
                dependencyMessage: 'Não é possível remover esta categoria porque existem serviços vinculados.',
                orderBy: 'name',
                orderDirection: 'asc',
                gender: 'f',
            ),

            'customer-categories' => new CrmResourceDefinition(
                key: 'customer-categories',
                label: 'Categorias de cliente',
                singularLabel: 'Categoria de cliente',
                group: 'Cadastros',
                modelClass: CustomerCategory::class,
                storeRequestClass: StoreCustomerCategoryRequest::class,
                updateRequestClass: UpdateCustomerCategoryRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Descrição', 'key' => 'description'],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Categoria ativa'],
                ],
                filters: [
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                dependencies: [
                    ['model' => Contact::class, 'column' => 'customer_category_id', 'label' => 'contatos'],
                ],
                dependencyMessage: 'Não é possível remover esta categoria porque existem contatos vinculados.',
                orderBy: 'name',
                orderDirection: 'asc',
                gender: 'f',
            ),

            'services' => new CrmResourceDefinition(
                key: 'services',
                label: 'Serviços',
                singularLabel: 'Serviço',
                group: 'Cadastros',
                modelClass: Service::class,
                storeRequestClass: StoreServiceRequest::class,
                updateRequestClass: UpdateServiceRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Categoria', 'key' => 'category.name'],
                    ['label' => 'Preço base', 'key' => 'base_price', 'format' => 'currency'],
                    ['label' => 'Tipo', 'key' => 'pricing_type', 'format' => 'badge', 'options' => self::PRICING_TYPE],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'category_id', 'label' => 'Categoria', 'type' => 'select', 'required' => true, 'options' => self::options(ServiceCategory::class, 'name')],
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'base_price', 'label' => 'Preço base', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'pricing_type', 'label' => 'Tipo de preço', 'type' => 'select', 'options' => self::PRICING_TYPE],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Serviço ativo'],
                ],
                filters: [
                    ['name' => 'category_id', 'label' => 'Categoria', 'options' => self::options(ServiceCategory::class, 'name')],
                    ['name' => 'pricing_type', 'label' => 'Tipo', 'options' => self::PRICING_TYPE],
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                with: ['category'],
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'pipelines' => new CrmResourceDefinition(
                key: 'pipelines',
                label: 'Pipelines',
                singularLabel: 'Pipeline',
                group: 'Cadastros',
                modelClass: Pipeline::class,
                storeRequestClass: StorePipelineRequest::class,
                updateRequestClass: UpdatePipelineRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Padrão', 'key' => 'is_default', 'format' => 'boolean'],
                    ['label' => 'Ativo', 'key' => 'is_active', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea'],
                    ['name' => 'is_default', 'label' => 'Padrão', 'type' => 'checkbox', 'help' => 'Pipeline padrão'],
                    ['name' => 'is_active', 'label' => 'Status', 'type' => 'checkbox', 'help' => 'Pipeline ativo'],
                ],
                filters: [
                    ['name' => 'is_active', 'label' => 'Status', 'options' => self::YES_NO],
                ],
                searchable: ['name', 'slug'],
                dependencies: [
                    ['model' => Opportunity::class, 'column' => 'pipeline_id', 'label' => 'oportunidades'],
                ],
                dependencyMessage: 'Não é possível remover este pipeline porque existem oportunidades vinculadas.',
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'pipeline-stages' => new CrmResourceDefinition(
                key: 'pipeline-stages',
                label: 'Estágios de pipeline',
                singularLabel: 'Estágio de pipeline',
                group: 'Cadastros',
                modelClass: PipelineStage::class,
                storeRequestClass: StorePipelineStageRequest::class,
                updateRequestClass: UpdatePipelineStageRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Pipeline', 'key' => 'pipeline.name'],
                    ['label' => 'Ordem', 'key' => 'order_index'],
                    ['label' => 'Probabilidade', 'key' => 'probability_percent'],
                    ['label' => 'Ganho', 'key' => 'is_won_stage', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'pipeline_id', 'label' => 'Pipeline', 'type' => 'select', 'required' => true, 'options' => self::options(Pipeline::class, 'name')],
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'required' => true, 'help' => 'Deve ser único dentro do pipeline'],
                    ['name' => 'order_index', 'label' => 'Ordem', 'type' => 'number', 'step' => '1'],
                    ['name' => 'probability_percent', 'label' => 'Probabilidade (%)', 'type' => 'number', 'step' => '1'],
                    ['name' => 'color_hex', 'label' => 'Cor', 'placeholder' => '#6c757d'],
                    ['name' => 'is_won_stage', 'label' => 'Estágio ganho', 'type' => 'checkbox', 'help' => 'Marca estágio como ganho'],
                    ['name' => 'is_lost_stage', 'label' => 'Estágio perdido', 'type' => 'checkbox', 'help' => 'Marca estágio como perdido'],
                ],
                filters: [
                    ['name' => 'pipeline_id', 'label' => 'Pipeline', 'options' => self::options(Pipeline::class, 'name')],
                ],
                searchable: ['name', 'slug'],
                with: ['pipeline'],
                dependencies: [
                    ['model' => Opportunity::class, 'column' => 'stage_id', 'label' => 'oportunidades'],
                    ['model' => OpportunityStageHistory::class, 'column' => 'to_stage_id', 'label' => 'históricos de estágio'],
                ],
                dependencyMessage: 'Não é possível remover este estágio porque existem registros vinculados.',
                orderBy: 'order_index',
                orderDirection: 'asc',
            ),

            'companies' => new CrmResourceDefinition(
                key: 'companies',
                label: 'Empresas',
                singularLabel: 'Empresa',
                group: 'Operação',
                modelClass: Company::class,
                storeRequestClass: StoreCompanyRequest::class,
                updateRequestClass: UpdateCompanyRequest::class,
                columns: [
                    ['label' => 'Nome fantasia', 'key' => 'trade_name'],
                    ['label' => 'Razão social', 'key' => 'corporate_name'],
                    ['label' => 'Segmento', 'key' => 'segment.name'],
                    ['label' => 'Cidade', 'key' => 'city'],
                    ['label' => 'Status', 'key' => 'status', 'format' => 'badge', 'options' => self::COMPANY_STATUS],
                ],
                fields: [
                    ['name' => 'trade_name', 'label' => 'Nome fantasia', 'required' => true],
                    ['name' => 'corporate_name', 'label' => 'Razão social', 'required' => true],
                    ['name' => 'tax_id', 'label' => 'CNPJ/CPF'],
                    ['name' => 'segment_id', 'label' => 'Segmento', 'type' => 'select', 'required' => true, 'options' => self::options(Segment::class, 'name')],
                    ['name' => 'lead_source_id', 'label' => 'Origem do lead', 'type' => 'select', 'options' => self::options(LeadSource::class, 'name')],
                    ['name' => 'owner_id', 'label' => 'Responsável', 'type' => 'select', 'required' => true, 'options' => self::options(User::class, 'name')],
                    ['name' => 'website_url', 'label' => 'Site', 'type' => 'url', 'placeholder' => 'https://...'],
                    ['name' => 'instagram_handle', 'label' => 'Instagram'],
                    ['name' => 'google_maps_url', 'label' => 'Google Maps', 'type' => 'url', 'placeholder' => 'https://...'],
                    ['name' => 'website_status', 'label' => 'Situação do site', 'type' => 'select', 'options' => self::WEBSITE_STATUS],
                    ['name' => 'is_mobile_responsive', 'label' => 'Site responsivo', 'type' => 'checkbox', 'help' => 'Compatível com dispositivos móveis'],
                    ['name' => 'has_whatsapp_button', 'label' => 'Botão WhatsApp', 'type' => 'checkbox', 'help' => 'Site possui botão de WhatsApp'],
                    ['name' => 'has_active_ads', 'label' => 'Anúncios ativos', 'type' => 'checkbox', 'help' => 'Possui anúncios ativos'],
                    ['name' => 'pain_points_notes', 'label' => 'Dores do cliente', 'type' => 'textarea', 'col' => 'full'],
                    ['name' => 'address', 'label' => 'Endereço'],
                    ['name' => 'city', 'label' => 'Cidade', 'required' => true],
                    ['name' => 'state', 'label' => 'UF', 'required' => true, 'help' => 'Sigla com 2 letras, ex.: SP'],
                    ['name' => 'postal_code', 'label' => 'CEP'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => self::COMPANY_STATUS],
                    ['name' => 'temperature', 'label' => 'Temperatura', 'type' => 'select', 'options' => self::TEMPERATURE],
                ],
                filters: [
                    ['name' => 'status', 'label' => 'Status', 'options' => self::COMPANY_STATUS],
                    ['name' => 'temperature', 'label' => 'Temperatura', 'options' => self::TEMPERATURE],
                    ['name' => 'segment_id', 'label' => 'Segmento', 'options' => self::options(Segment::class, 'name')],
                ],
                searchable: ['trade_name', 'corporate_name', 'tax_id'],
                with: ['segment'],
                dependencies: [
                    ['model' => Opportunity::class, 'column' => 'company_id', 'label' => 'oportunidades'],
                    ['model' => Proposal::class, 'column' => 'company_id', 'label' => 'propostas'],
                ],
                dependencyMessage: 'Não é possível remover esta empresa porque existem registros vinculados: oportunidades, propostas.',
                orderBy: 'trade_name',
                orderDirection: 'asc',
                gender: 'f',
            ),

            'contacts' => new CrmResourceDefinition(
                key: 'contacts',
                label: 'Contatos',
                singularLabel: 'Contato',
                group: 'Operação',
                modelClass: Contact::class,
                storeRequestClass: StoreContactRequest::class,
                updateRequestClass: UpdateContactRequest::class,
                columns: [
                    ['label' => 'Nome', 'key' => 'name'],
                    ['label' => 'Empresa', 'key' => 'company.trade_name'],
                    ['label' => 'Categoria', 'key' => 'customerCategory.name'],
                    ['label' => 'Cargo', 'key' => 'role_title'],
                    ['label' => 'E-mail', 'key' => 'email'],
                    ['label' => 'Telefone', 'key' => 'phone'],
                    ['label' => 'Responsável', 'key' => 'assignedUser.name'],
                    ['label' => 'Decisor', 'key' => 'is_decision_maker', 'format' => 'boolean'],
                ],
                fields: [
                    ['name' => 'name', 'label' => 'Nome', 'required' => true],
                    ['name' => 'company_id', 'label' => 'Empresa', 'type' => 'select', 'required' => true, 'options' => self::options(Company::class, 'trade_name')],
                    ['name' => 'customer_category_id', 'label' => 'Categoria', 'type' => 'select', 'options' => self::options(CustomerCategory::class, 'name')],
                    ['name' => 'assigned_user_id', 'label' => 'Responsável', 'type' => 'select', 'options' => self::options(User::class, 'name')],
                    ['name' => 'role_title', 'label' => 'Cargo'],
                    ['name' => 'email', 'label' => 'E-mail', 'type' => 'email'],
                    ['name' => 'phone', 'label' => 'Telefone', 'type' => 'tel', 'mask' => 'phone'],
                    ['name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'mask' => 'phone'],
                    ['name' => 'is_decision_maker', 'label' => 'Decisor', 'type' => 'checkbox', 'help' => 'É decisor'],
                    ['name' => 'is_primary', 'label' => 'Contato principal', 'type' => 'checkbox', 'help' => 'É contato principal'],
                    ['name' => 'notes', 'label' => 'Observações', 'type' => 'textarea', 'col' => 'full'],
                ],
                filters: [
                    ['name' => 'company_id', 'label' => 'Empresa', 'options' => self::options(Company::class, 'trade_name')],
                    ['name' => 'customer_category_id', 'label' => 'Categoria', 'options' => self::options(CustomerCategory::class, 'name')],
                    ['name' => 'assigned_user_id', 'label' => 'Responsável', 'options' => self::options(User::class, 'name')],
                ],
                searchable: ['name', 'email', 'phone'],
                with: ['company', 'customerCategory', 'assignedUser'],
                seed: ['assigned_user_id' => fn () => auth()->id()],
                orderBy: 'name',
                orderDirection: 'asc',
            ),

            'opportunities' => new CrmResourceDefinition(
                key: 'opportunities',
                label: 'Oportunidades',
                singularLabel: 'Oportunidade',
                group: 'Operação',
                modelClass: Opportunity::class,
                storeRequestClass: StoreOpportunityRequest::class,
                updateRequestClass: UpdateOpportunityRequest::class,
                columns: [
                    ['label' => 'Código', 'key' => 'code'],
                    ['label' => 'Título', 'key' => 'title'],
                    ['label' => 'Empresa', 'key' => 'company.trade_name'],
                    ['label' => 'Estágio', 'key' => 'stage.name'],
                    ['label' => 'Valor único', 'key' => 'estimated_one_off_value', 'format' => 'currency'],
                    ['label' => 'Status', 'key' => 'status', 'format' => 'badge', 'options' => self::OPPORTUNITY_STATUS],
                ],
                fields: [
                    ['name' => 'code', 'label' => 'Código', 'required' => true],
                    ['name' => 'title', 'label' => 'Título', 'required' => true],
                    ['name' => 'company_id', 'label' => 'Empresa', 'type' => 'select', 'required' => true, 'options' => self::options(Company::class, 'trade_name')],
                    ['name' => 'primary_contact_id', 'label' => 'Contato principal', 'type' => 'select', 'options' => self::options(Contact::class, 'name')],
                    ['name' => 'pipeline_id', 'label' => 'Pipeline', 'type' => 'select', 'required' => true, 'options' => self::options(Pipeline::class, 'name')],
                    ['name' => 'stage_id', 'label' => 'Estágio', 'type' => 'select', 'required' => true, 'options' => self::stageOptions()],
                    ['name' => 'owner_id', 'label' => 'Responsável', 'type' => 'select', 'required' => true, 'options' => self::options(User::class, 'name')],
                    ['name' => 'estimated_one_off_value', 'label' => 'Valor único', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'estimated_recurring_value', 'label' => 'Valor recorrente', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'currency', 'label' => 'Moeda', 'help' => 'Sigla com 3 letras, ex.: BRL'],
                    ['name' => 'expected_close_date', 'label' => 'Previsão de fechamento', 'type' => 'date'],
                    ['name' => 'closed_at', 'label' => 'Fechamento', 'type' => 'datetime-local'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => self::OPPORTUNITY_STATUS],
                    ['name' => 'lost_reason_id', 'label' => 'Motivo da perda', 'type' => 'select', 'options' => self::options(LostReason::class, 'name')],
                    ['name' => 'lost_reason_notes', 'label' => 'Observações da perda', 'type' => 'textarea', 'col' => 'full'],
                    ['name' => 'notes', 'label' => 'Observações', 'type' => 'textarea', 'col' => 'full'],
                ],
                filters: [
                    ['name' => 'status', 'label' => 'Status', 'options' => self::OPPORTUNITY_STATUS],
                    ['name' => 'pipeline_id', 'label' => 'Pipeline', 'options' => self::options(Pipeline::class, 'name')],
                    ['name' => 'owner_id', 'label' => 'Responsável', 'options' => self::options(User::class, 'name')],
                ],
                searchable: ['code', 'title'],
                with: ['company', 'stage'],
                dependencies: [
                    ['model' => Proposal::class, 'column' => 'opportunity_id', 'label' => 'propostas'],
                ],
                dependencyMessage: 'Não é possível remover esta oportunidade porque existem propostas vinculadas.',
                seed: ['owner_id' => fn () => auth()->id()],
                afterUpdate: self::opportunityStageHistory(),
                orderBy: 'id',
                orderDirection: 'desc',
                gender: 'f',
            ),

            'opportunity-stage-histories' => new CrmResourceDefinition(
                key: 'opportunity-stage-histories',
                label: 'Histórico de estágios',
                singularLabel: 'Histórico de estágio',
                group: 'Operação',
                modelClass: OpportunityStageHistory::class,
                storeRequestClass: StoreOpportunityStageHistoryRequest::class,
                updateRequestClass: UpdateOpportunityStageHistoryRequest::class,
                columns: [
                    ['label' => 'Oportunidade', 'key' => 'opportunity.title'],
                    ['label' => 'De', 'key' => 'fromStage.name'],
                    ['label' => 'Para', 'key' => 'toStage.name'],
                    ['label' => 'Usuário', 'key' => 'user.name'],
                    ['label' => 'Duração (s)', 'key' => 'duration_in_seconds'],
                    ['label' => 'Data', 'key' => 'created_at', 'format' => 'datetime'],
                ],
                fields: [
                    ['name' => 'opportunity_id', 'label' => 'Oportunidade', 'type' => 'select', 'required' => true, 'options' => self::options(Opportunity::class, 'title')],
                    ['name' => 'from_stage_id', 'label' => 'Estágio anterior', 'type' => 'select', 'options' => self::stageOptions()],
                    ['name' => 'to_stage_id', 'label' => 'Estágio atual', 'type' => 'select', 'required' => true, 'options' => self::stageOptions()],
                    ['name' => 'user_id', 'label' => 'Usuário', 'type' => 'select', 'required' => true, 'options' => self::options(User::class, 'name')],
                    ['name' => 'duration_in_seconds', 'label' => 'Duração (segundos)', 'type' => 'number', 'step' => '1'],
                ],
                filters: [
                    ['name' => 'opportunity_id', 'label' => 'Oportunidade', 'options' => self::options(Opportunity::class, 'title')],
                ],
                searchable: [],
                with: ['opportunity', 'fromStage', 'toStage', 'user'],
                seed: ['user_id' => fn () => auth()->id()],
                orderBy: 'created_at',
                orderDirection: 'desc',
            ),

            'proposals' => new CrmResourceDefinition(
                key: 'proposals',
                label: 'Propostas',
                singularLabel: 'Proposta',
                group: 'Operação',
                modelClass: Proposal::class,
                storeRequestClass: StoreProposalRequest::class,
                updateRequestClass: UpdateProposalRequest::class,
                columns: [
                    ['label' => 'Número', 'key' => 'proposal_number'],
                    ['label' => 'Oportunidade', 'key' => 'opportunity.title'],
                    ['label' => 'Empresa', 'key' => 'company.trade_name'],
                    ['label' => 'Validade', 'key' => 'valid_until', 'format' => 'date'],
                    ['label' => 'Total único', 'key' => 'total_one_off', 'format' => 'currency'],
                    ['label' => 'Status', 'key' => 'status', 'format' => 'badge', 'options' => self::PROPOSAL_STATUS],
                ],
                fields: [
                    ['name' => 'proposal_number', 'label' => 'Número', 'required' => true],
                    ['name' => 'opportunity_id', 'label' => 'Oportunidade', 'type' => 'select', 'required' => true, 'options' => self::options(Opportunity::class, 'title')],
                    ['name' => 'company_id', 'label' => 'Empresa', 'type' => 'select', 'required' => true, 'options' => self::options(Company::class, 'trade_name')],
                    ['name' => 'contact_id', 'label' => 'Contato', 'type' => 'select', 'options' => self::options(Contact::class, 'name')],
                    ['name' => 'owner_id', 'label' => 'Responsável', 'type' => 'select', 'required' => true, 'options' => self::options(User::class, 'name')],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => self::PROPOSAL_STATUS],
                    ['name' => 'total_one_off', 'label' => 'Total único', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'total_recurring', 'label' => 'Total recorrente', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'payment_terms', 'label' => 'Condições de pagamento'],
                    ['name' => 'valid_until', 'label' => 'Validade', 'type' => 'date', 'required' => true],
                    ['name' => 'sent_at', 'label' => 'Enviada em', 'type' => 'datetime-local'],
                    ['name' => 'accepted_at', 'label' => 'Aceita em', 'type' => 'datetime-local'],
                    ['name' => 'pdf_path', 'label' => 'Caminho do PDF'],
                    ['name' => 'notes', 'label' => 'Observações', 'type' => 'textarea', 'col' => 'full'],
                ],
                filters: [
                    ['name' => 'status', 'label' => 'Status', 'options' => self::PROPOSAL_STATUS],
                    ['name' => 'company_id', 'label' => 'Empresa', 'options' => self::options(Company::class, 'trade_name')],
                ],
                searchable: ['proposal_number'],
                with: ['opportunity', 'company'],
                seed: ['owner_id' => fn () => auth()->id()],
                orderBy: 'id',
                orderDirection: 'desc',
                gender: 'f',
            ),

            'proposal-items' => new CrmResourceDefinition(
                key: 'proposal-items',
                label: 'Itens de proposta',
                singularLabel: 'Item de proposta',
                group: 'Operação',
                modelClass: ProposalItem::class,
                storeRequestClass: StoreProposalItemRequest::class,
                updateRequestClass: UpdateProposalItemRequest::class,
                columns: [
                    ['label' => 'Item', 'key' => 'item_name'],
                    ['label' => 'Proposta', 'key' => 'proposal.proposal_number'],
                    ['label' => 'Serviço', 'key' => 'service.name'],
                    ['label' => 'Quantidade', 'key' => 'quantity'],
                    ['label' => 'Valor unitário', 'key' => 'unit_price', 'format' => 'currency'],
                    ['label' => 'Total', 'key' => 'total_price', 'format' => 'currency'],
                ],
                fields: [
                    ['name' => 'proposal_id', 'label' => 'Proposta', 'type' => 'select', 'required' => true, 'options' => self::options(Proposal::class, 'proposal_number')],
                    ['name' => 'service_id', 'label' => 'Serviço', 'type' => 'select', 'options' => self::options(Service::class, 'name')],
                    ['name' => 'item_name', 'label' => 'Nome do item', 'required' => true],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'col' => 'full'],
                    ['name' => 'quantity', 'label' => 'Quantidade', 'type' => 'decimal', 'required' => true, 'step' => '0.01'],
                    ['name' => 'unit_price', 'label' => 'Valor unitário', 'type' => 'decimal', 'required' => true, 'step' => '0.01'],
                    ['name' => 'discount_amount', 'label' => 'Desconto', 'type' => 'decimal', 'step' => '0.01'],
                    ['name' => 'total_price', 'label' => 'Total', 'type' => 'decimal', 'required' => true, 'step' => '0.01'],
                    ['name' => 'is_recurring', 'label' => 'Recorrente', 'type' => 'checkbox', 'help' => 'Item recorrente'],
                ],
                filters: [
                    ['name' => 'proposal_id', 'label' => 'Proposta', 'options' => self::options(Proposal::class, 'proposal_number')],
                ],
                searchable: ['item_name'],
                with: ['proposal', 'service'],
                orderBy: 'id',
                orderDirection: 'desc',
            ),

            'activities' => new CrmResourceDefinition(
                key: 'activities',
                label: 'Atividades',
                singularLabel: 'Atividade',
                group: 'Operação',
                modelClass: Activity::class,
                storeRequestClass: StoreActivityRequest::class,
                updateRequestClass: UpdateActivityRequest::class,
                columns: [
                    ['label' => 'Título', 'key' => 'title'],
                    ['label' => 'Tipo', 'key' => 'activityType.name'],
                    ['label' => 'Empresa', 'key' => 'company.trade_name'],
                    ['label' => 'Responsável', 'key' => 'assignedTo.name'],
                    ['label' => 'Vencimento', 'key' => 'due_date', 'format' => 'datetime'],
                    ['label' => 'Status', 'key' => 'status', 'format' => 'badge', 'options' => self::ACTIVITY_STATUS],
                ],
                fields: [
                    ['name' => 'title', 'label' => 'Título', 'required' => true],
                    ['name' => 'activity_type_id', 'label' => 'Tipo de atividade', 'type' => 'select', 'required' => true, 'options' => self::options(ActivityType::class, 'name')],
                    ['name' => 'company_id', 'label' => 'Empresa', 'type' => 'select', 'options' => self::options(Company::class, 'trade_name')],
                    ['name' => 'contact_id', 'label' => 'Contato', 'type' => 'select', 'options' => self::options(Contact::class, 'name')],
                    ['name' => 'opportunity_id', 'label' => 'Oportunidade', 'type' => 'select', 'options' => self::options(Opportunity::class, 'title')],
                    ['name' => 'assigned_to', 'label' => 'Responsável', 'type' => 'select', 'options' => self::options(User::class, 'name'), 'help' => 'Em branco = usuário logado'],
                    ['name' => 'created_by', 'label' => 'Criado por', 'type' => 'select', 'options' => self::options(User::class, 'name'), 'help' => 'Em branco = usuário logado'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => self::ACTIVITY_STATUS],
                    ['name' => 'due_date', 'label' => 'Vencimento', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'completed_at', 'label' => 'Concluída em', 'type' => 'datetime-local'],
                    ['name' => 'description', 'label' => 'Descrição', 'type' => 'textarea', 'col' => 'full'],
                    ['name' => 'outcome_notes', 'label' => 'Resultado', 'type' => 'textarea', 'col' => 'full'],
                ],
                filters: [
                    ['name' => 'status', 'label' => 'Status', 'options' => self::ACTIVITY_STATUS],
                    ['name' => 'activity_type_id', 'label' => 'Tipo', 'options' => self::options(ActivityType::class, 'name')],
                    ['name' => 'assigned_to', 'label' => 'Responsável', 'options' => self::options(User::class, 'name')],
                ],
                searchable: ['title', 'description'],
                with: ['activityType', 'company', 'assignedTo'],
                seed: [
                    'assigned_to' => fn () => auth()->id(),
                    'created_by' => fn () => auth()->id(),
                    'due_date' => fn () => now(),
                ],
                orderBy: 'due_date',
                orderDirection: 'desc',
                gender: 'f',
            ),
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    public static function get(string $key): CrmResourceDefinition
    {
        return self::all()[$key];
    }

    /** @return array<string, array<int, CrmResourceDefinition>> */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::all() as $definition) {
            $groups[$definition->group][] = $definition;
        }

        return $groups;
    }

    private static function options(string $modelClass, string $labelColumn): Closure
    {
        return fn () => $modelClass::query()->orderBy($labelColumn)->pluck($labelColumn, 'id')->all();
    }

    private static function stageOptions(): Closure
    {
        return fn () => PipelineStage::query()
            ->with('pipeline')
            ->orderBy('pipeline_id')
            ->orderBy('order_index')
            ->get()
            ->mapWithKeys(fn (PipelineStage $stage) => [
                $stage->id => $stage->pipeline->name.' — '.$stage->name,
            ])
            ->all();
    }

    private static function opportunityStageHistory(): Closure
    {
        return function (Opportunity $opportunity, array $data, Request $request, array $original): void {
            $fromStageId = $original['stage_id'] ?? null;

            if (! array_key_exists('stage_id', $data) || (int) $data['stage_id'] === (int) $fromStageId) {
                return;
            }

            $lastHistory = $opportunity->stageHistories()->latest('created_at')->first();
            $duration = $lastHistory?->created_at
                ? $lastHistory->created_at->diffInSeconds(now())
                : 0;

            $opportunity->stageHistories()->create([
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $opportunity->stage_id,
                'user_id' => $request->user()->id,
                'duration_in_seconds' => max(0, $duration),
            ]);
        };
    }
}
