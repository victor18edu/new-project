<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'corporate_name' => fake()->company(),
            'trade_name' => fake()->companySuffix(),
            'tax_id' => fake()->numerify('################'),
            'segment_id' => Segment::factory(),
            'owner_id' => User::factory(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA']),
            'status' => 'prospecting',
            'temperature' => 'cold',
        ];
    }
}
