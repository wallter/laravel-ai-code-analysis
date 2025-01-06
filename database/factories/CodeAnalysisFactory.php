<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CodeAnalysis;

class CodeAnalysisFactory extends Factory
{
    protected $model = CodeAnalysis::class;

    public function definition()
    {
        return [
            'file_path' => $this->faker->filePath(),
            'ast' => [], // Adjust as needed
            'analysis' => [], // Adjust as needed
            'ai_output' => [], // Adjust as needed
            'current_pass' => $this->faker->word(),
            'completed_passes' => [],
        ];
    }
}
