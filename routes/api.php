<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Rota pública para teste de status
    Route::get('/status', function () {
        return response()->json(['status' => 'API v1 is running via Docker!']);
    });

    // Endpoint de health da API (público, sem autenticação)
    Route::get('/health', [HealthController::class, 'index']);

    // Rotas protegidas por autenticação (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });

});