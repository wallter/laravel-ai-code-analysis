<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use App\Services\Parsing\ParserService;

/**
 * Example command that parses PHP files (including traits).
 */
class ParseFilesCommand extends Command
{
    protected $signature = 'parse:files
        {--filter= : Filter by item name}
        {--output-file= : Where to export JSON results}
        {--limit-class=0 : Limit how many "Class" or "Trait" items to keep}
        {--limit-method=0 : Limit how many methods per class/trait to keep}';

    protected $description = 'Parse PHP files and output discovered classes, traits, functions.';

    public function __construct(protected ParserService $parserService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $phpFiles   = $this->parserService->collectPhpFiles();
        $outputFile = $this->option('output-file') ?: null;
        $limitClass = (int) $this->option('limit-class');
        $limitMethod= (int) $this->option('limit-method');
        $filter     = $this->option('filter') ?: '';

        Log::info('ParseFilesCommand starting.', [
            'file_count'   => $phpFiles->count(),
            'limit_class'  => $limitClass,
            'limit_method' => $limitMethod,
            'output_file'  => $outputFile,
        ]);

        $this->info(sprintf(
            "Found [%d] PHP files to parse. limit-class=%d, limit-method=%d",
            $phpFiles->count(),
            $limitClass,
            $limitMethod
        ));

        if ($limitClass > 0 && $limitClass < $phpFiles->count()) {
            $phpFiles = $phpFiles->take($limitClass);
            $this->info("Applying limit-class: analyzing only the first {$limitClass} file(s).");
        }

        if ($phpFiles->isEmpty()) {
            $this->warn('No .php files to parse.');
            return 0;
        }

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        $collectedItems = collect();
        foreach ($phpFiles as $filePath) {
            try {
                // Parse and merge results
                $items = $this->parserService->parseFile($filePath);
                $collectedItems = $collectedItems->merge($items);

                $this->info("Successfully parsed: {$filePath}");
            } catch (\Throwable $e) {
                Log::error("Parse error: {$filePath}", ['error' => $e->getMessage()]);
                $this->warn("Could not parse {$filePath}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Apply optional method limit
        if ($limitMethod > 0) {
            $collectedItems = $collectedItems->map(function ($item) use ($limitMethod) {
                if (property_exists($item, 'type') && in_array($item->type, ['Class','Trait','Interface'], true) && !empty($item->details['methods'])) {
                    $item->details['methods'] = array_slice($item->details['methods'], 0, $limitMethod);
                }
                return $item;
            });
        }

        // Apply optional filter
        if ($filter !== '') {
            $collectedItems = $collectedItems->filter(function ($item) use ($filter) {
                return stripos($item->name, $filter) !== false;
            });
        }

        $this->info("Initial collected items: " . $collectedItems->count());

        if ($outputFile) {
            $this->exportJson($collectedItems->values(), $outputFile);
        }

        return 0;
    }

    protected function exportJson(Collection $items, string $filePath)
    {
        $json = json_encode($items->toArray(), JSON_PRETTY_PRINT);
        if (!$json) {
            $this->warn("Failed to encode to JSON: " . json_last_error_msg());
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        File::put($filePath, $json);
        $this->info("Output written to {$filePath}");
    }
}
