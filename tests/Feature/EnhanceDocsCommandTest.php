<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use App\Models\ParsedItem;
use App\Services\AI\DocEnhancer;

class EnhanceDocsCommandTest extends TestCase
{
    use RefreshDatabase;
    public function test_doc_enhancement_command_updates_parsed_items(): void
    {
        // Seed ParsedItem data that needs enhancement
        $parsedItem = ParsedItem::create([
            'type' => 'Class',
            'name' => 'TestClass',
            'file_path' => 'test/path/TestClass.php',
            'details' => [
                'description' => null,
            ],
        ]);

        // Mock the DocEnhancer service
        $this->mock(DocEnhancer::class, function ($mock) use ($parsedItem) {
            $mock->shouldReceive('enhanceDescription')
                 ->once()
                 ->with(\Mockery::on(function($arg) use ($parsedItem) {
                     return $arg->id === $parsedItem->id;
                 }))
                 ->andReturn('Enhanced description');
        });

        // Call the doc:enhance Artisan command
        $this->artisan('doc:enhance')
             ->expectsOutput("Enhancing documentation for: {$parsedItem->type} {$parsedItem->name}")
             ->expectsOutput("Updated description for {$parsedItem->name}.")
             ->assertExitCode(0);

        // Assert the item's description is updated
        $this->assertEquals('Enhanced description', $parsedItem->fresh()->details['description']);
    }
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
