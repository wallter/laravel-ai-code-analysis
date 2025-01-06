<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AIScore;
use App\Models\CodeAnalysis;

class AIScoreFactory extends Factory
{
    protected $model = AIScore::class;

    public function definition()
    {
        return [
            'code_analysis_id' => CodeAnalysis::factory(),
            'operation' => $this->faker->randomElement(['complexity', 'readability', 'security']),
            'score' => $this->faker->randomFloat(2, 0, 100),
            'summary' => $this->faker->sentence(), // Added summary field
        ];
    }

    /**
     * State for high scores.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 80, 100),
            'summary' => $this->faker->sentence(), // Ensure summary is set
        ]);
    }

    /**
     * State for medium scores.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 50, 79),
            'summary' => $this->faker->sentence(), // Ensure summary is set
        ]);
    }

    /**
     * State for low scores.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 0, 49),
            'summary' => $this->faker->sentence(), // Ensure summary is set
        ]);
    }
}
