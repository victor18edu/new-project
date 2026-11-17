<?php

namespace Tests\Feature;

use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SegmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $segmentData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->segmentData = [
            'name' => 'Segmento Teste',
            'slug' => 'segmento-teste',
            'description' => 'Descrição do segmento',
            'is_active' => true,
        ];
    }

    /**
     * Teste: listar segmentos com sucesso (index).
     */
    public function test_list_segments_success(): void
    {
        foreach (range(1, 3) as $i) {
            Segment::create([
                'name' => "Segmento {$i}",
                'slug' => "segmento-{$i}",
            ]);
        }

        $response = $this->getJson('/api/v1/segments');

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
     * Teste: buscar segmento por ID com sucesso (show).
     */
    public function test_show_segment_success(): void
    {
        $segment = Segment::create([
            'name' => 'Segmento para Buscar',
            'slug' => 'segmento-para-buscar',
        ]);

        $response = $this->getJson("/api/v1/segments/{$segment->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Segmento encontrado.',
        ]);
    }

    /**
     * Teste: buscar segmento inexistente retorna 404.
     */
    public function test_show_segment_not_found(): void
    {
        $response = $this->getJson('/api/v1/segments/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar segmento com sucesso (store).
     */
    public function test_store_segment_success(): void
    {
        $response = $this->postJson('/api/v1/segments', $this->segmentData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Segmento criado com sucesso.',
        ]);
        $this->assertDatabaseHas('segments', [
            'name' => 'Segmento Teste',
        ]);
    }

    /**
     * Teste: criar segmento com dados inválidos retorna erro de validação.
     */
    public function test_store_segment_validation_error(): void
    {
        $invalidData = [
            'name' => '',
        ];

        $response = $this->postJson('/api/v1/segments', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    /**
     * Teste: atualizar segmento com sucesso (update).
     */
    public function test_update_segment_success(): void
    {
        $segment = Segment::create([
            'name' => 'Segmento Original',
            'slug' => 'segmento-original',
        ]);

        $updateData = [
            'name' => 'Segmento Atualizado',
        ];

        $response = $this->putJson("/api/v1/segments/{$segment->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Segmento atualizado com sucesso.',
        ]);
        $this->assertDatabaseHas('segments', [
            'id' => $segment->id,
            'name' => 'Segmento Atualizado',
        ]);
    }

    /**
     * Teste: atualizar segmento inexistente retorna 404.
     */
    public function test_update_segment_not_found(): void
    {
        $response = $this->putJson('/api/v1/segments/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir segmento com sucesso (destroy).
     */
    public function test_destroy_segment_success(): void
    {
        $segment = Segment::create([
            'name' => 'Segmento para Excluir',
            'slug' => 'segmento-para-excluir',
        ]);

        $response = $this->deleteJson("/api/v1/segments/{$segment->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Segmento removido com sucesso.',
        ]);
        $this->assertDatabaseMissing('segments', [
            'id' => $segment->id,
        ]);
    }

    /**
     * Teste: excluir segmento inexistente retorna 404.
     */
    public function test_destroy_segment_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/segments/99999');

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

        $response = $this->getJson('/api/v1/segments');

        $response->assertStatus(401);
    }
}