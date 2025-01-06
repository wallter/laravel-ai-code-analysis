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
            'operation' => $this->faker->word(),
            'score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
