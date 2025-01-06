<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ParsedItem;

class ParsedItemFactory extends Factory
{
    protected $model = ParsedItem::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['class', 'method', 'function']),
            'name' => $this->faker->word(),
            'file_path' => $this->faker->filePath(),
            'line_number' => $this->faker->numberBetween(1, 500),
            'annotations' => [],
            'attributes' => [],
            'details' => [],
            'class_name' => $this->faker->word(),
            'namespace' => 'App\\Models',
            'visibility' => $this->faker->randomElement(['public', 'protected', 'private']),
            'is_static' => $this->faker->boolean(),
            'fully_qualified_name' => 'App\\Models\\' . $this->faker->word(),
            'operation_summary' => $this->faker->sentence(),
            'called_methods' => [],
            'ast' => [],
        ];
    }
}
