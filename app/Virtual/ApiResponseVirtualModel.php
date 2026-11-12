<?php

namespace App\Virtual;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/example/success",
    summary: "Exemplo de resposta de sucesso",
)]
#[OA\Response(response: 200, description: "Success")]
#[OA\Response(response: 400, description: "Exemplo de resposta de erro")]
#[OA\Response(response: 200, description: "Exemplo de resposta paginada")]
class ApiResponseVirtualModel
{
    /**
     * Virtual model used only to anchor API response examples for Swagger documentation.
     */
    public function __construct() {}
}