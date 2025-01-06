<?php

namespace App\Services\Parsing;

use Illuminate\Support\Facades\Log;

class FileProcessorService
{
    public function __construct(protected ParserService $parserService)
    {
    }

    /**
     * Process a PHP file by parsing and storing its contents.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function process(string $filePath, bool $isVerbose = false): bool
    {
        try {
            $this->parserService->parseFile($filePath);

            if ($isVerbose) {
                Log::info("FileProcessorService: Successfully parsed and stored: {$filePath}");
            }

            return true;
        } catch (\Throwable $throwable) {
            Log::error("FileProcessorService: Parse error on {$filePath} - ".$throwable->getMessage());

            return false;
        }
    }
}
