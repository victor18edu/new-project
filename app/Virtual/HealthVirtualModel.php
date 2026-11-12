<?php

namespace App\Virtual;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/api/v1/health",
    summary: "Verifica o status da API",
    description: "Endpoint público que retorna status 200 indicando que a API está funcionando.",
    tags: ["Health"],
    responses: [
        new OA\Response(
            response: 200,
            description: "API funcionando normalmente",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(property: "status", type: "string", example: "up"),
                        ]
                    ),
                    new OA\Property(property: "message", type: "string", example: "API está funcionando."),
                    new OA\Property(property: "status", type: "integer", example: 200),
                ]
            )
        ),
        new OA\Response(
            response: 500,
            description: "Erro interno do servidor",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: false),
                    new OA\Property(property: "data", type: "object", example: null),
                    new OA\Property(property: "message", type: "string", example: "Erro interno do servidor."),
                    new OA\Property(property: "status", type: "integer", example: 500),
                ]
            )
        ),
    ]
)]
class HealthVirtualModel
{
    /**
     * Virtual model used only to anchor Health endpoint documentation for Swagger.
     */
    public function __construct() {}
}