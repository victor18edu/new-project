<?php

namespace Tests\Unit;

use App\Http\Requests\V1\StoreCustomerCategoryRequest;
use App\Http\Requests\V1\UpdateCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CustomerCategoryRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_allows_any_caller(): void
    {
        $this->assertTrue($this->storeRequest([])->authorize());
        $this->assertTrue($this->updateRequest(CustomerCategory::factory()->create(), [])->authorize());
    }

    public function test_store_rules_require_name_and_slug_with_portuguese_messages(): void
    {
        $request = $this->storeRequest([]);
        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame('O nome é obrigatório.', $validator->errors()->first('name'));
        $this->assertSame('O slug é obrigatório.', $validator->errors()->first('slug'));
    }

    public function test_store_rules_reject_a_duplicate_slug(): void
    {
        CustomerCategory::factory()->create(['slug' => 'existente']);
        $request = $this->storeRequest([]);

        $validator = Validator::make(
            ['name' => 'Nova categoria', 'slug' => 'existente'],
            $request->rules(),
            $request->messages()
        );

        $this->assertFalse($validator->passes());
        $this->assertSame('O slug informado já está em uso.', $validator->errors()->first('slug'));
    }

    public function test_store_rules_accept_a_valid_payload(): void
    {
        $validator = Validator::make([
            'name' => 'Nova categoria',
            'slug' => 'nova-categoria',
            'description' => 'Descrição da categoria',
            'is_active' => true,
        ], $this->storeRequest([])->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_store_rules_enforce_maximum_lengths(): void
    {
        $request = $this->storeRequest([]);
        $validator = Validator::make([
            'name' => str_repeat('a', 101),
            'slug' => str_repeat('b', 121),
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame('O nome não pode exceder 100 caracteres.', $validator->errors()->first('name'));
        $this->assertSame('O slug não pode exceder 120 caracteres.', $validator->errors()->first('slug'));
    }

    public function test_update_rules_ignore_the_current_records_slug(): void
    {
        $category = CustomerCategory::factory()->create(['slug' => 'atual']);
        $request = $this->updateRequest($category, []);

        $validator = Validator::make(['slug' => 'atual'], $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_update_rules_reject_a_slug_used_by_another_record(): void
    {
        $category = CustomerCategory::factory()->create(['slug' => 'atual']);
        CustomerCategory::factory()->create(['slug' => 'outro']);
        $request = $this->updateRequest($category, []);

        $validator = Validator::make(['slug' => 'outro'], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame('O slug informado já está em uso.', $validator->errors()->first('slug'));
    }

    public function test_update_rules_allow_a_partial_payload(): void
    {
        $request = $this->updateRequest(CustomerCategory::factory()->create(), []);

        $this->assertTrue(Validator::make([], $request->rules())->passes());
    }

    private function storeRequest(array $data): StoreCustomerCategoryRequest
    {
        return StoreCustomerCategoryRequest::create('/test', 'POST', $data);
    }

    private function updateRequest(CustomerCategory $category, array $data): UpdateCustomerCategoryRequest
    {
        $route = new Route('PUT', '/test', []);
        $route->bind(HttpRequest::create('/test', 'PUT'));
        $route->setParameter('customer_category', $category->id);

        $request = UpdateCustomerCategoryRequest::create('/test', 'PUT', $data);
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }
}
