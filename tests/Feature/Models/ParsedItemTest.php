<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\ParsedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ParsedItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
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

    #[Test]
    public function it_creates_class_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeClass()->create();

        $this->assertEquals('class', $parsedItem->type);
        $this->assertNotNull($parsedItem->class_name);
        $this->assertEquals('App\\Models', $parsedItem->namespace);
        $this->assertEquals('public', $parsedItem->visibility);
    }

    #[Test]
    public function it_creates_method_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeMethod()->create();

        $this->assertEquals('method', $parsedItem->type);
        $this->assertEquals('SampleClass', $parsedItem->class_name);
        $this->assertEquals('App\\Services', $parsedItem->namespace);
        $this->assertContains($parsedItem->visibility, ['public', 'protected']);
    }

    #[Test]
    public function it_creates_function_type_parsed_items()
    {
        $parsedItem = ParsedItem::factory()->typeFunction()->create();

        $this->assertEquals('function', $parsedItem->type);
        $this->assertNull($parsedItem->class_name);
        $this->assertEquals('App\\Helpers', $parsedItem->namespace);
        $this->assertEquals('public', $parsedItem->visibility);
    }

    #[Test]
    public function it_creates_parsed_items_with_detailed_ast()
    {
        $parsedItem = ParsedItem::factory()->detailedAst()->create();

        $this->assertIsArray($parsedItem->ast);
        $this->assertEquals('Stmt_Class', $parsedItem->ast['nodeType']);
        $this->assertArrayHasKey('startLine', $parsedItem->ast['attributes']);
        $this->assertArrayHasKey('endLine', $parsedItem->ast['attributes']);
    }
}
