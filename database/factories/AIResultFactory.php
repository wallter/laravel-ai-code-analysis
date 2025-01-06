<?php

namespace Database\Factories;

use App\Models\AIResult;
use App\Models\CodeAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIResult>
 */
class AIResultFactory extends Factory
{
    protected $model = AIResult::class;

    public function definition()
    {
        return [
            'code_analysis_id' => CodeAnalysis::factory(),
            'pass_name' => fake()->randomElement(['initial_analysis', 'complexity_evaluation', 'security_review']),
            'prompt_text' => fake()->sentence(),
            'response_text' => fake()->paragraph(),
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
                'duration' => fake()->numberBetween(1, 5),
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
                'duration' => fake()->numberBetween(6, 10),
                'status' => 'failure',
            ],
            'response_text' => null,
        ]);
    }
}
