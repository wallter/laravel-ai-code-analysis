<?php

namespace Database\Factories;

use App\Models\CodeAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeAnalysisFactory extends Factory
{
    protected $model = CodeAnalysis::class;

    public function definition()
    {
        return [
            'file_path' => $this->faker->filePath(),
            'ast' => $this->faker->text(200), // Simplified AST representation
            'analysis' => [
                'complexity' => $this->faker->numberBetween(1, 10),
                'readability' => $this->faker->numberBetween(1, 10),
            ],
            'ai_output' => [
                'summary' => $this->faker->sentence(),
                'recommendations' => $this->faker->paragraph(),
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
                'summary' => $this->faker->sentence(),
                'recommendations' => $this->faker->paragraph(),
            ],
        ]);
    }
}
