<?php

namespace Database\Factories;

use App\Models\AIResult;
use App\Models\CodeAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIResultFactory extends Factory
{
    protected $model = AIResult::class;

    public function definition()
    {
        return [
            'code_analysis_id' => CodeAnalysis::factory(),
            'pass_name' => $this->faker->randomElement(['initial_analysis', 'complexity_evaluation', 'security_review']),
            'prompt_text' => $this->faker->sentence(),
            'response_text' => $this->faker->paragraph(),
            'metadata' => [],
        ];
    }

    /**
     * State for successful AI results.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'duration' => $this->faker->numberBetween(1, 5),
                'status' => 'success',
            ],
        ]);
    }

    /**
     * State for failed AI results.
     */
    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'duration' => $this->faker->numberBetween(6, 10),
                'status' => 'failure',
            ],
            'response_text' => null,
        ]);
    }
}
