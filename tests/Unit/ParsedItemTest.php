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
}
