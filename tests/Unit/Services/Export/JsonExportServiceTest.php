<?php

namespace Tests\Unit\Services\Export;

use App\Services\Export\JsonExportService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JsonExportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Removed global mocks to allow individual test setups
    }

    #[Test]
    public function it_exports_collection_to_json_successfully()
    {
        $items = collect(['key' => 'value']);
        $filePath = '/path/to/export.json';

        File::shouldReceive('exists')->with('/path/to')->andReturn(true);
        File::shouldReceive('put')->with($filePath, json_encode($items->toArray(), JSON_PRETTY_PRINT))->andReturn(true);
        Log::shouldReceive('info')->with("JsonExportService: Successfully exported data to {$filePath}")->once();

        $service = new JsonExportService;
        $service->export($items, $filePath);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_warning_when_json_encoding_fails()
    {
        $items = collect([fopen('php://memory', 'r')]); // Resources cannot be JSON encoded
        $filePath = '/path/to/export.json';

        // Set the expectation before calling the export method
        Log::shouldReceive('warning')
            ->withArgs(fn($message) => str_contains((string) $message, 'Failed to encode items to JSON'))->once();

        $service = new JsonExportService;
        $service->export($items, $filePath);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_creates_directory_if_not_exists()
    {
        $items = collect(['key' => 'value']);
        $filePath = '/new/path/export.json';

        File::shouldReceive('exists')->with('/new/path')->andReturn(false);
        File::shouldReceive('makeDirectory')->with('/new/path', 0755, true)->andReturn(true);
        File::shouldReceive('put')->with($filePath, json_encode($items->toArray(), JSON_PRETTY_PRINT))->andReturn(true);
        Log::shouldReceive('info')->with("JsonExportService: Successfully exported data to {$filePath}")->once();

        $service = new JsonExportService;
        $service->export($items, $filePath);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_error_when_file_write_fails()
    {
        $items = collect(['key' => 'value']);
        $filePath = '/path/to/export.json';
        $exception = new \Exception('Disk is full');

        File::shouldReceive('exists')->with('/path/to')->andReturn(true);
        File::shouldReceive('put')->with($filePath, json_encode($items->toArray(), JSON_PRETTY_PRINT))
            ->andThrow($exception);
        Log::shouldReceive('error')->with("JsonExportService: Failed to write to {$filePath}: ".$exception->getMessage())->once();

        $service = new JsonExportService;
        $service->export($items, $filePath);

        $this->assertTrue(true);
    }
}
