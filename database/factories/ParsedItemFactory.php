<?php

namespace Database\Factories;

use App\Models\ParsedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParsedItem>
 */
class ParsedItemFactory extends Factory
{
    protected $model = ParsedItem::class;

    public function definition()
    {
        return [
            'type' => fake()->randomElement(['class', 'method', 'function']),
            'name' => fake()->word(),
            'file_path' => fake()->filePath(),
            'line_number' => fake()->numberBetween(1, 500),
            'annotations' => [fake()->optional()->word()],
            'attributes' => [fake()->optional()->word()],
            'details' => ['description' => fake()->sentence()],
            'class_name' => fake()->word(),
            'namespace' => 'App\\Models',
            'visibility' => fake()->randomElement(['public', 'protected', 'private']),
            'is_static' => fake()->boolean(),
            'fully_qualified_name' => 'App\\Models\\'.fake()->word(),
            'operation_summary' => fake()->sentence(),
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
            'class_name' => fake()->word(),
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
            'visibility' => fake()->randomElement(['public', 'protected']),
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
        return $this->state(fn(array $attributes) => [
            'ast' => [
                'nodeType' => 'Stmt_Class',
                'attributes' => [
                    'startLine' => fake()->numberBetween(1, 100),
                    'endLine' => fake()->numberBetween(101, 200),
                ],
            ],
        ]);
    }
}
