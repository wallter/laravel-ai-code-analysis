<?php

namespace Tests\Unit\Services\Export;

use App\Services\Export\JsonExportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonExportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock the File and Log facades
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('put')->andReturn(true);
    }

    #[Test]
    public function it_exports_collection_to_json_successfully()
    {
        $items = collect(['key' => 'value']);
        $filePath = '/path/to/export.json';

        File::shouldReceive('exists')->with('/path/to')->andReturn(true);
        File::shouldReceive('put')->with($filePath, json_encode($items->toArray(), JSON_PRETTY_PRINT))->andReturn(true);
        Log::shouldReceive('info')->with("JsonExportService: Successfully exported data to {$filePath}");

        $service = new JsonExportService();
        $service->export($items, $filePath);
    }

    #[Test]
    public function it_logs_warning_when_json_encoding_fails()
    {
        $items = collect([resource]); // Resources cannot be JSON encoded
        $filePath = '/path/to/export.json';

        $service = new JsonExportService();
        $service->export($items, $filePath);

        Log::shouldReceive('warning')
            ->withArgs(function ($message) {
                return str_contains($message, 'Failed to encode items to JSON');
            })->once();
    }

    #[Test]
    public function it_creates_directory_if_not_exists()
    {
        $items = collect(['key' => 'value']);
        $filePath = '/new/path/export.json';

        File::shouldReceive('exists')->with('/new/path')->andReturn(false);
        File::shouldReceive('makeDirectory')->with('/new/path', 0755, true)->andReturn(true);
        File::shouldReceive('put')->with($filePath, json_encode($items->toArray(), JSON_PRETTY_PRINT))->andReturn(true);
        Log::shouldReceive('info')->with("JsonExportService: Successfully exported data to {$filePath}");

        $service = new JsonExportService();
        $service->export($items, $filePath);
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
        Log::shouldReceive('error')->with("JsonExportService: Failed to write to {$filePath}: " . $exception->getMessage());

        $service = new JsonExportService();
        $service->export($items, $filePath);
    }
}
