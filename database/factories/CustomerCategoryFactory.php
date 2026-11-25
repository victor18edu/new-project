<?php

namespace Database\Factories;

use App\Models\CustomerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerCategory> */
class CustomerCategoryFactory extends Factory
{
    protected $model = CustomerCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
