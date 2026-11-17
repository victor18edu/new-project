<?php

namespace Database\Factories;

use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PipelineStage> */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'order_index' => 0,
        ];
    }
}
