<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company_id' => Company::factory(),
            'role_title' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('11#########'),
            'whatsapp' => fake()->unique()->numerify('119########'),
            'is_decision_maker' => false,
            'is_primary' => false,
        ];
    }
}
