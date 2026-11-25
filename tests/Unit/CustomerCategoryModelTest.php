<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_active_is_cast_to_boolean(): void
    {
        $active = CustomerCategory::factory()->create(['is_active' => 1])->refresh();
        $inactive = CustomerCategory::factory()->create(['is_active' => 0])->refresh();

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
    }

    public function test_contacts_relationship_returns_only_the_categorys_contacts(): void
    {
        $category = CustomerCategory::factory()->create();
        $contact = Contact::factory()->create(['customer_category_id' => $category->id]);
        Contact::factory()->create(['customer_category_id' => CustomerCategory::factory()->create()->id]);

        $this->assertCount(1, $category->contacts);
        $this->assertSame($contact->id, $category->contacts->first()->id);
    }
}
