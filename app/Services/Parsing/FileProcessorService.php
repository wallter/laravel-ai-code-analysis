<?php

namespace App\Services\Parsing;

use Illuminate\Support\Facades\Log;

class FileProcessorService
{
    protected ParserService $parserService;

    public function __construct(ParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    /**
     * Process a PHP file by parsing and storing its contents.
     *
     * @param string $filePath
     * @param bool $isVerbose
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
        } catch (\Throwable $e) {
            Log::error("FileProcessorService: Parse error on {$filePath} - " . $e->getMessage());
            return false;
        }
    }
}
