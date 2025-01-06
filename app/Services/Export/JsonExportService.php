<?php

namespace App\Services\Export;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class JsonExportService
{
    /**
     * Export the given items to a JSON file.
     *
     * @param Collection $items
     * @param string $filePath
     * @return void
     */
    public function export(Collection $items, string $filePath): void
    {
        $json = json_encode($items->toArray(), JSON_PRETTY_PRINT);
        if (!$json) {
            Log::warning('JsonExportService: Failed to encode items to JSON: ' . json_last_error_msg());
            return;
        }

        try {
            $directory = dirname($filePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($filePath, $json);
            Log::info("JsonExportService: Successfully exported data to {$filePath}");
        } catch (\Exception $e) {
            Log::error("JsonExportService: Failed to write to {$filePath}: " . $e->getMessage());
        }
    }
}
