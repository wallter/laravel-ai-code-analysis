<?php

namespace Tests\Unit\Services;

use App\Services\ParsedItemService;
use App\Models\ParsedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParsedItemServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_parsed_item_successfully()
    {
        // Arrange
        $service = new ParsedItemService();
        $data = [
            'name' => 'Test Item',
            'description' => 'A test parsed item.',
            // Add other necessary fields
        ];

        // Act
        $parsedItem = $service->createParsedItem($data);

        // Assert
        $this->assertInstanceOf(ParsedItem::class, $parsedItem);
        $this->assertDatabaseHas('parsed_items', [
            'name' => 'Test Item',
            // Add other necessary fields
        ]);
    }

    #[Test]
    public function it_returns_null_when_creation_fails()
    {
        // Arrange
        $service = new ParsedItemService();
        $data = []; // Missing required fields

        // Act
        $parsedItem = $service->createParsedItem($data);

        // Assert
        $this->assertNull($parsedItem);
    }

    // Add more tests covering different scenarios and edge cases
}
