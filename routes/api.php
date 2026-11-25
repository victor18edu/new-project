<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ActivityTypeController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CustomerCategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LeadSourceController;
use App\Http\Controllers\Api\V1\LostReasonController;
use App\Http\Controllers\Api\V1\OpportunityController;
use App\Http\Controllers\Api\V1\OpportunityStageHistoryController;
use App\Http\Controllers\Api\V1\PipelineController;
use App\Http\Controllers\Api\V1\PipelineStageController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\ProposalItemController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WhatsAppAttendanceController;
use App\Http\Controllers\Api\V1\WhatsAppMessageController;
use App\Http\Controllers\Api\V1\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Rota pública para teste de status
    Route::get('/status', function () {
        return response()->json(['status' => 'API v1 is running via Docker!']);
    });

    // Endpoint de health da API (público, sem autenticação)
    Route::get('/health', [HealthController::class, 'index']);

    // Webhook da Evolution API: eventos recebidos (POST), público e protegido por token
    Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
        ->middleware('throttle:60,1')
        ->name('whatsapp.webhook.handle');

    // Autenticação - rotas públicas com throttle
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // Autenticação - rotas protegidas por token Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });

    // Rotas protegidas por autenticação (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Histórico e envio de mensagens WhatsApp por contato
        Route::get('/contacts/{contact}/whatsapp-messages', [WhatsAppMessageController::class, 'index']);
        Route::post('/contacts/{contact}/whatsapp-messages', [WhatsAppMessageController::class, 'store']);
        Route::put('/contacts/{contact}/whatsapp-messages/{message}', [WhatsAppMessageController::class, 'update']);
        Route::post('/contacts/{contact}/whatsapp-messages/{message}/retry', [WhatsAppMessageController::class, 'retry']);
        Route::delete('/contacts/{contact}/whatsapp-messages/{message}', [WhatsAppMessageController::class, 'destroy']);

        // Histórico de atendimentos WhatsApp
        Route::get('/whatsapp-attendances', [WhatsAppAttendanceController::class, 'index']);
        Route::post('/contacts/{contact}/whatsapp-attendances', [WhatsAppAttendanceController::class, 'store']);
        Route::get('/whatsapp-attendances/{attendance}', [WhatsAppAttendanceController::class, 'show']);
        Route::post('/whatsapp-attendances/{attendance}/close', [WhatsAppAttendanceController::class, 'close']);

        // CRUD - Activities
        Route::apiResource('/activities', ActivityController::class);

        // CRUD - Activity Types
        Route::apiResource('/activity-types', ActivityTypeController::class);

        // CRUD - Companies
        Route::apiResource('/companies', CompanyController::class);

        // CRUD - Contacts
        Route::apiResource('/contacts', ContactController::class);

        // CRUD - Customer Categories
        Route::apiResource('/customer-categories', CustomerCategoryController::class);

        // CRUD - Lead Sources
        Route::apiResource('/lead-sources', LeadSourceController::class);

        // CRUD - Lost Reasons
        Route::apiResource('/lost-reasons', LostReasonController::class);

        // CRUD - Opportunities
        Route::apiResource('/opportunities', OpportunityController::class);

        // CRUD - Opportunity Stage Histories
        Route::apiResource('/opportunity-stage-histories', OpportunityStageHistoryController::class);

        // CRUD - Pipelines
        Route::apiResource('/pipelines', PipelineController::class);

        // CRUD - Pipeline Stages
        Route::apiResource('/pipeline-stages', PipelineStageController::class);

        // CRUD - Proposals
        Route::apiResource('/proposals', ProposalController::class);

        // CRUD - Proposal Items
        Route::apiResource('/proposal-items', ProposalItemController::class);

        // CRUD - Segments
        Route::apiResource('/segments', SegmentController::class);

        // CRUD - Services
        Route::apiResource('/services', ServiceController::class);

        // CRUD - Service Categories
        Route::apiResource('/service-categories', ServiceCategoryController::class);

        // CRUD - Users
        Route::apiResource('/users', UserController::class);
    });

});
