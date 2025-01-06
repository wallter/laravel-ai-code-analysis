<?php

namespace Tests\Unit\Services;

use App\Services\ParsedItemService;
use App\Models\ParsedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use App\Repositories\ParsedItemRepositoryInterface;

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

    #[Test]
    public function it_creates_parsed_item_successfully_with_repository()
    {
        // Arrange
        $data = [
            'name' => 'Test Item',
            'description' => 'A test parsed item.',
        ];

        $parsedItemMock = Mockery::mock(ParsedItem::class);
        $parsedItemMock->shouldReceive('save')->once()->andReturn(true);

        $repositoryMock = Mockery::mock(ParsedItemRepositoryInterface::class);
        $repositoryMock->shouldReceive('create')->with($data)->andReturn($parsedItemMock);

        $service = new ParsedItemService($repositoryMock);

        // Act
        $parsedItem = $service->createParsedItem($data);

        // Assert
        $this->assertInstanceOf(ParsedItem::class, $parsedItem);
        $this->assertEquals('Test Item', $parsedItem->name);
    }

    #[Test]
    public function it_validates_data_before_creating_parsed_item()
    {
        // Arrange
        $service = new ParsedItemService();
        $data = [
            'name' => '', // Invalid name
            'description' => 'A test parsed item.',
        ];

        // Act
        $parsedItem = $service->createParsedItem($data);

        // Assert
        $this->assertNull($parsedItem);
        $this->assertDatabaseMissing('parsed_items', [
            'description' => 'A test parsed item.',
        ]);
    }

    #[Test]
    public function it_handles_duplicate_parsed_item_creation()
    {
        // Arrange
        $service = new ParsedItemService();
        $data = [
            'name' => 'Duplicate Item',
            'description' => 'First creation.',
        ];

        // Create the first parsed item
        $service->createParsedItem($data);

        // Attempt to create a duplicate
        $parsedItem = $service->createParsedItem($data);

        // Assert
        $this->assertNull($parsedItem);
        $this->assertCount(1, ParsedItem::where('name', 'Duplicate Item')->get());
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
