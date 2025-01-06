<?php

namespace Tests\Unit\Services\Parsing;

use App\Services\Parsing\ParserService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Parsing\VisitorInterface;
use Mockery;
use Illuminate\Support\Facades\Cache;

class ParserServiceTest extends TestCase
{
    #[Test]
    public function it_parses_file_correctly()
    {
        // Arrange
        $filePath = '/path/to/file.php';
        $parserService = new ParserService();
        $visitors = []; // Define necessary visitors

        // Act
        $result = $parserService->parseFile($filePath, $visitors, false);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('classes', $result);
        $this->assertNotEmpty($result['classes']);
    }

    #[Test]
    public function it_uses_cache_when_enabled()
    {
        // Arrange
        $filePath = '/path/to/file.php';
        $parserService = new ParserService();
        $visitors = []; // Define necessary visitors

        // Act
        $result = $parserService->parseFile($filePath, $visitors, true);

        // Assert
        Cache::shouldReceive('has')
            ->once()
            ->with('parsed_file_' . md5($filePath))
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('parsed_file_' . md5($filePath))
            ->andReturn(['cached' => 'data']);

        $this->assertIsArray($result);
        $this->assertEquals(['cached' => 'data'], $result);
    }

    #[Test]
    public function it_handles_parsing_errors_gracefully()
    {
        // Arrange
        $filePath = '/invalid/path/file.php';
        $visitors = [];
        $parserService = new ParserService();

        // Act
        $result = $parserService->parseFile($filePath, $visitors, false);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_invokes_visitors_during_parsing()
    {
        // Arrange
        $filePath = '/path/to/file.php';
        $visitorMock = Mockery::mock(VisitorInterface::class);
        $visitorMock->shouldReceive('visit')
            ->once()
            ->with(Mockery::type(\PhpParser\Node::class));

        $visitors = [$visitorMock];
        $parserService = new ParserService();

        // Act
        $result = $parserService->parseFile($filePath, $visitors, false);

        // Assert
        $this->assertIsArray($result);
    }
    {
        Mockery::close();
        parent::tearDown();
    }
}
