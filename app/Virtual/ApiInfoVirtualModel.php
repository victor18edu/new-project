<?php

namespace App\Virtual;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Documentação da API REST (Docker + Laravel)",
    title: "Laravel Backend API V1"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Servidor Local Docker"
)]
class ApiInfoVirtualModel
{
    /**
     * Virtual model used only to anchor API metadata (Info/Server) for Swagger documentation.
     */
    public function __construct() {}
}