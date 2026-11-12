<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->successResponse(
            message: 'API está funcionando.',
            data: ['status' => 'up'],
            status: 200
        );
    }
}