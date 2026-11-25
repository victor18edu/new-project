<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create(), ['*']);
    }

    public function test_api_can_list_customer_categories(): void
    {
        CustomerCategory::factory()->count(3)->create();

        $this->getJson('/api/v1/customer-categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 3);
    }

    public function test_api_can_create_and_show_a_customer_category(): void
    {
        $response = $this->postJson('/api/v1/customer-categories', [
            'name' => 'Cliente premium',
            'slug' => 'cliente-premium',
            'description' => 'Clientes com contrato ativo',
            'is_active' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('customer_categories', [
            'name' => 'Cliente premium',
            'slug' => 'cliente-premium',
        ]);

        $this->getJson('/api/v1/customer-categories/'.$response->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.name', 'Cliente premium');
    }

    public function test_api_validates_customer_category_payload(): void
    {
        $this->postJson('/api/v1/customer-categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);

        CustomerCategory::factory()->create(['slug' => 'duplicada']);

        $this->postJson('/api/v1/customer-categories', [
            'name' => 'Outra categoria',
            'slug' => 'duplicada',
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_api_can_update_a_customer_category(): void
    {
        $category = CustomerCategory::factory()->create();

        $this->putJson('/api/v1/customer-categories/'.$category->id, [
            'name' => 'Nome atualizado',
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.name', 'Nome atualizado');

        $this->assertDatabaseHas('customer_categories', [
            'id' => $category->id,
            'name' => 'Nome atualizado',
            'is_active' => false,
        ]);
    }

    public function test_api_cannot_delete_a_category_linked_to_a_contact(): void
    {
        $category = CustomerCategory::factory()->create();
        $this->createContact(['customer_category_id' => $category->id]);

        $this->deleteJson('/api/v1/customer-categories/'.$category->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('customer_categories', ['id' => $category->id]);
    }

    public function test_api_can_delete_an_unused_category(): void
    {
        $category = CustomerCategory::factory()->create();

        $this->deleteJson('/api/v1/customer-categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('customer_categories', ['id' => $category->id]);
    }

    public function test_contact_accepts_a_customer_category(): void
    {
        $category = CustomerCategory::factory()->create();
        $contact = $this->createContact(['customer_category_id' => $category->id]);

        $this->assertTrue($contact->customerCategory->is($category));

        $this->getJson('/api/v1/contacts/'.$contact->id)
            ->assertOk()
            ->assertJsonPath('data.customer_category_id', $category->id);
    }

    public function test_web_crm_exposes_customer_categories(): void
    {
        CustomerCategory::factory()->create(['name' => 'Categoria Web']);

        $this->actingAs(User::factory()->create())
            ->get(route('web.customer-categories.index'))
            ->assertOk()
            ->assertSee('Categoria Web');
    }

    /** @param array<string, mixed> $attributes */
    private function createContact(array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'name' => 'Contato da categoria',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => '11988888888',
        ], $attributes));
    }
}
