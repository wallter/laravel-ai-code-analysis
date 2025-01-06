<?php

namespace Database\Factories;

use App\Models\CodeAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CodeAnalysis>
 */
class CodeAnalysisFactory extends Factory
{
    protected $model = CodeAnalysis::class;

    public function definition()
    {
        return [
            'file_path' => fake()->filePath(),
            'ast' => fake()->text(200), // Simplified AST representation
            'analysis' => [
                'complexity' => fake()->numberBetween(1, 10),
                'readability' => fake()->numberBetween(1, 10),
            ],
            'ai_output' => [
                'summary' => fake()->sentence(),
                'recommendations' => fake()->paragraph(),
            ],
            'current_pass' => 1,
            'completed_passes' => [],
        ];
    }

    /**
     * State for completed analysis.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_pass' => 3,
            'completed_passes' => ['pass1', 'pass2'],
        ]);
    }

    /**
     * State with AI output.
     */
    public function withAiOutput(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_output' => [
                'summary' => fake()->sentence(),
                'recommendations' => fake()->paragraph(),
            ],
        ]);
    }
}
