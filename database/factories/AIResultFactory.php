<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AIResult;
use App\Models\CodeAnalysis;

class AIResultFactory extends Factory
{
    protected $model = AIResult::class;

    public function definition()
    {
        return [
            'code_analysis_id' => CodeAnalysis::factory(),
            'pass_name' => $this->faker->word(),
            'prompt_text' => $this->faker->sentence(),
            'response_text' => $this->faker->paragraph(),
            'metadata' => [],
        ];
    }
}
