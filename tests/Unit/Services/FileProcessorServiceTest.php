<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FileProcessorService;
use Illuminate\Support\Facades\Log;

class FileProcessorServiceTest extends TestCase
{
    #[Test]
    public function it_processes_file_correctly()
    {
        // Arrange
        $parserServiceMock = Mockery::mock(ParserService::class);
        $service = new FileProcessorService($parserServiceMock);
        $filePath = 'path/to/file.php';

        Storage::fake('local');
        Storage::disk('local')->put($filePath, '<?php echo "Hello World"; ?>');

        // Act
        $result = $service->processFile($filePath);

        // Assert
        $this->assertTrue($result);
        Storage::disk('local')->assertExists($filePath);
    }

    #[Test]
    public function it_handles_nonexistent_file_gracefully()
    {
        // Arrange
        $service = new FileProcessorService();
        $filePath = 'path/to/nonexistent.php';

        Storage::fake('local');

        // Act
        $result = $service->processFile($filePath);

        // Assert
        $this->assertFalse($result);
        Log::shouldReceive('warning')
            ->once()
            ->with("FileProcessorService: File {$filePath} does not exist.");
    }

    #[Test]
    public function it_handles_invalid_file_content()
    {
        // Arrange
        $filePath = 'path/to/invalid_file.php';
        Storage::fake('local');

        Storage::disk('local')->put($filePath, '<?php invalid php code ?>');

        Log::shouldReceive('error')
            ->once()
            ->with("FileProcessorService: Failed to process {$filePath}: Syntax error.");

        $service = new FileProcessorService();

        // Act
        $result = $service->processFile($filePath);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_processes_multiple_files_correctly()
    {
        // Arrange
        $service = new FileProcessorService();
        $filePaths = [
            'path/to/file1.php',
            'path/to/file2.php',
        ];

        foreach ($filePaths as $filePath) {
            Storage::disk('local')->put($filePath, '<?php echo "Hello World"; ?>');
        }

        // Act
        $results = [];
        foreach ($filePaths as $filePath) {
            $results[] = $service->processFile($filePath);
        }

        // Assert
        foreach ($results as $result) {
            $this->assertTrue($result);
        }
    }

    #[Test]
    public function it_handles_multiple_visitors()
    {
        // Arrange
        $filePath = '/path/to/file.php';
        $visitorMock1 = Mockery::mock(VisitorInterface::class);
        $visitorMock1->shouldReceive('visit')->once();
        $visitorMock2 = Mockery::mock(VisitorInterface::class);
        $visitorMock2->shouldReceive('visit')->once();

        $visitors = [$visitorMock1, $visitorMock2];
        $parserService = new ParserService();

        // Act
        $result = $parserService->parseFile($filePath, $visitors, false);

        // Assert
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
