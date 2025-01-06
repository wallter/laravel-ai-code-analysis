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
            'annotations' => [$this->faker->optional()->word()],
            'attributes' => [$this->faker->optional()->word()],
            'details' => ['description' => $this->faker->sentence()],
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

    /**
     * State for class type.
     */
    public function typeClass(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'class',
            'class_name' => $this->faker->word(),
            'namespace' => 'App\\Models',
            'visibility' => 'public',
        ]);
    }

    /**
     * State for method type.
     */
    public function typeMethod(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'method',
            'class_name' => 'SampleClass',
            'namespace' => 'App\\Services',
            'visibility' => $this->faker->randomElement(['public', 'protected']),
        ]);
    }

    /**
     * State for function type.
     */
    public function typeFunction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'function',
            'class_name' => null,
            'namespace' => 'App\\Helpers',
            'visibility' => 'public',
        ]);
    }

    /**
     * State for detailed AST.
     */
    public function detailedAst(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'ast' => [
                    'nodeType' => 'Stmt_Class',
                    'attributes' => [
                        'startLine' => $this->faker->numberBetween(1, 100),
                        'endLine' => $this->faker->numberBetween(101, 200),
                    ],
                ],
            ];
        });
    }
}
