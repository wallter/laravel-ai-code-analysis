<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ParsedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParsedItemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $parsedItem = ParsedItem::factory()->make();

        $this->assertEquals([
            'type',
            'name',
            'file_path',
            'line_number',
            'annotations',
            'attributes',
            'details',
            'class_name',
            'namespace',
            'visibility',
            'is_static',
            'fully_qualified_name',
            'operation_summary',
            'called_methods',
            'ast',
        ], $parsedItem->getFillable());
    }

    // Add relationship tests if ParsedItem has any relationships
    /** @test */
    public function it_creates_class_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeClass()->create();

        $this->assertEquals('class', $parsedItem->type);
        $this->assertNotNull($parsedItem->class_name);
        $this->assertEquals('App\\Models', $parsedItem->namespace);
        $this->assertEquals('public', $parsedItem->visibility);
    }

    /** @test */
    public function it_creates_method_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeMethod()->create();

        $this->assertEquals('method', $parsedItem->type);
        $this->assertEquals('SampleClass', $parsedItem->class_name);
        $this->assertEquals('App\\Services', $parsedItem->namespace);
        $this->assertContains($parsedItem->visibility, ['public', 'protected']);
    }

    /** @test */
    public function it_creates_function_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeFunction()->create();

        $this->assertEquals('function', $parsedItem->type);
        $this->assertNull($parsedItem->class_name);
        $this->assertEquals('App\\Helpers', $parsedItem->namespace);
        $this->assertEquals('public', $parsedItem->visibility);
    }
}
