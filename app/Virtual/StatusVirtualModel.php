<?php

namespace App\Virtual;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/status',
    summary: 'Verifica o status da API',
    tags: ['Status']
)]
#[OA\Response(response: 200, description: 'API is running')]
class StatusVirtualModel
{
    /**
     * Virtual model used only to anchor Status endpoint documentation for Swagger.
     */
    public function __construct() {}
}