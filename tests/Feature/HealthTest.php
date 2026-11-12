<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    /**
     * Teste: Health endpoint retorna 200 com JSON padronizado.
     */
    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'API está funcionando.',
        ]);
    }

    /**
     * Teste: Rota inexistente retorna 404 com JSON padronizado.
     */
    public function test_inexistent_route_returns_404_json(): void
    {
        $response = $this->get('/api/v1/nao-existe');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: Método HTTP não permitido retorna 405 com JSON padronizado.
     */
    public function test_method_not_allowed_returns_405_json(): void
    {
        $response = $this->post('/api/v1/health');

        $response->assertStatus(405);
        $response->assertJson([
            'success' => false,
            'status' => 405,
        ]);
    }

    /**
     * Teste: Rota sem autenticação retorna 401 com JSON padronizado.
     */
    public function test_unauthenticated_route_returns_401_json(): void
    {
        $response = $this->get('/api/v1/user');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'status' => 401,
        ]);
    }
}