<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ServiceCategory $serviceCategory;
    protected array $serviceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
        ]);

        $this->serviceData = [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço Teste',
            'slug' => 'servico-teste',
            'description' => 'Descrição do serviço',
            'base_price' => '150.00',
            'pricing_type' => 'one_off',
            'is_active' => true,
        ];
    }

    /**
     * Teste: listar serviços com sucesso (index).
     */
    public function test_list_services_success(): void
    {
        foreach (range(1, 3) as $i) {
            Service::create([
                'category_id' => $this->serviceCategory->id,
                'name' => "Serviço {$i}",
                'slug' => "servico-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/services');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
        ]);
        $response->assertJsonStructure([
            'data' => [
                'items',
                'pagination',
            ],
            'message',
            'status',
        ]);
    }

    /**
     * Teste: buscar serviço por ID com sucesso (show).
     */
    public function test_show_service_success(): void
    {
        $service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço para Buscar',
            'slug' => 'servico-para-buscar',
        ]);

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Serviço encontrado.',
        ]);
    }

    /**
     * Teste: buscar serviço inexistente retorna 404.
     */
    public function test_show_service_not_found(): void
    {
        $response = $this->getJson('/api/v1/services/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar serviço com sucesso (store).
     */
    public function test_store_service_success(): void
    {
        $response = $this->postJson('/api/v1/services', $this->serviceData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Serviço criado com sucesso.',
        ]);
        $this->assertDatabaseHas('services', [
            'name' => 'Serviço Teste',
        ]);
    }

    /**
     * Teste: criar serviço com dados inválidos retorna erro de validação.
     */
    public function test_store_service_validation_error(): void
    {
        $invalidData = [
            'name' => '',
        ];

        $response = $this->postJson('/api/v1/services', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    /**
     * Teste: atualizar serviço com sucesso (update).
     */
    public function test_update_service_success(): void
    {
        $service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço Original',
            'slug' => 'servico-original',
        ]);

        $updateData = [
            'name' => 'Serviço Atualizado',
        ];

        $response = $this->putJson("/api/v1/services/{$service->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Serviço atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Serviço Atualizado',
        ]);
    }

    /**
     * Teste: atualizar serviço inexistente retorna 404.
     */
    public function test_update_service_not_found(): void
    {
        $response = $this->putJson('/api/v1/services/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir serviço com sucesso (destroy).
     */
    public function test_destroy_service_success(): void
    {
        $service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Serviço para Excluir',
            'slug' => 'servico-para-excluir',
        ]);

        $response = $this->deleteJson("/api/v1/services/{$service->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Serviço removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    /**
     * Teste: excluir serviço inexistente retorna 404.
     */
    public function test_destroy_service_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/services/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: rota sem autenticação retorna 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
               $this->app['auth']->forgetGuards();

         $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/services');

        $response->assertStatus(401);
    }
}