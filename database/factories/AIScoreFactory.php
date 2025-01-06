<?php

namespace Database\Factories;

use App\Models\AIScore;
use App\Models\CodeAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIScore>
 */
class AIScoreFactory extends Factory
{
    protected $model = AIScore::class;

    public function definition()
    {
        return [
            'code_analysis_id' => CodeAnalysis::factory(),
            'operation' => fake()->randomElement(['complexity', 'readability', 'security']),
            'score' => fake()->randomFloat(2, 0, 100),
            'summary' => fake()->sentence(), // not nullable
        ];
    }

    /**
     * State for high scores.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->randomFloat(2, 80, 100),
        ]);
    }

    /**
     * State for medium scores.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->randomFloat(2, 50, 79),
        ]);
    }

    /**
     * State for low scores.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->randomFloat(2, 0, 49),
        ]);
    }
}
