<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected array $contactData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $this->company = $this->createCompany([
            'corporate_name' => 'Empresa Teste Ltda',
            'trade_name' => 'Empresa Teste',
            'tax_id' => '12345678000190',
            'status' => 'prospecting',
        ]);

        $this->contactData = [
            'name' => 'João Teste',
            'role_title' => 'Gerente',
            'email' => 'joao@teste.com',
            'phone' => '11999999999',
            'whatsapp' => '11999999999',
            'is_decision_maker' => true,
            'is_primary' => true,
            'company_id' => $this->company->id,
            'notes' => 'Notas de teste',
        ];
    }

    /**
     * Teste: listar contatos com sucesso (index).
     */
    public function test_list_contacts_success(): void
    {
        foreach (range(1, 3) as $i) {
            Contact::create([
                'name' => "Contato {$i}",
                'email' => "contato{$i}@teste.com",
                'company_id' => $this->company->id,
            ]);
        }

        $response = $this->getJson('/api/v1/contacts');

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
     * Teste: buscar contato por ID com sucesso (show).
     */
    public function test_show_contact_success(): void
    {
        $contact = Contact::create([
            'name' => 'Contato para Buscar',
            'email' => 'buscar@teste.com',
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 200,
            'message' => 'Contato encontrado.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'company_id',
            ],
            'message',
            'status',
        ]);
    }

    /**
     * Teste: buscar contato inexistente retorna 404.
     */
    public function test_show_contact_not_found(): void
    {
        $response = $this->getJson('/api/v1/contacts/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: criar contato com sucesso (store).
     */
    public function test_store_contact_success(): void
    {
        $response = $this->postJson('/api/v1/contacts', $this->contactData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status' => 201,
            'message' => 'Contato criado com sucesso.',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'company_id',
            ],
            'message',
            'status',
        ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'João Teste',
            'email' => 'joao@teste.com',
        ]);
    }

    /**
     * Teste: criar contato com dados inválidos retorna erro de validação.
     */
    public function test_store_contact_validation_error(): void
    {
        $invalidData = [
            'name' => '',
            'company_id' => 99999,
        ];

        $response = $this->postJson('/api/v1/contacts', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'company_id',
        ]);
    }

    /**
     * Teste: atualizar contato com sucesso (update).
     */
    public function test_update_contact_success(): void
    {
        $contact = Contact::create([
            'name' => 'Contato Original',
            'email' => 'original@teste.com',
            'company_id' => $this->company->id,
        ]);

        $updateData = [
            'name' => 'Contato Atualizado',
            'phone' => '11888888888',
        ];

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Contato atualizado com sucesso.',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Contato Atualizado',
        ]);
    }

    /**
     * Teste: atualizar contato inexistente retorna 404.
     */
    public function test_update_contact_not_found(): void
    {
        $response = $this->putJson('/api/v1/contacts/99999', [
            'name' => 'Novo Nome',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 404,
        ]);
    }

    /**
     * Teste: excluir contato com sucesso (destroy).
     */
    public function test_destroy_contact_success(): void
    {
        $contact = Contact::create([
            'name' => 'Contato para Excluir',
            'email' => 'excluir@teste.com',
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Contato removido com sucesso.',
        ]);

        $this->assertSoftDeleted('contacts', [
            'id' => $contact->id,
        ]);
    }

    /**
     * Teste: excluir contato inexistente retorna 404.
     */
    public function test_destroy_contact_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/99999');

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

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(401);
    }
}