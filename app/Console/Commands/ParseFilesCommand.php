<?php

namespace App\Console\Commands;

use App\Models\ParsedItem;
use App\Services\Parsing\ParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * ParseFilesCommand parses PHP files and stores the discovered classes, traits, and functions into the database.
 */
class ParseFilesCommand extends FilesCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:files
        {--filter= : Filter by item name}
        {--output-file= : Where to export JSON results}
        {--limit-class=0 : Limit how many "Class" or "Trait" items to keep}
        {--limit-method=0 : Limit how many methods per class/trait to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse PHP files and store discovered classes, traits, functions into the database.';

    /**
     * @var ParserService
     */
    public function __construct(protected ParserService $parserService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int Exit status code.
     */
    public function handle(): int
    {
        $phpFiles = $this->parserService->collectPhpFiles();
        $outputFile = $this->option('output-file') ?: null;
        $limitClass = (int) $this->option('limit-class');
        $limitMethod = (int) $this->option('limit-method');
        $filter = $this->option('filter') ?: '';

        Log::info('ParseFilesCommand starting.', [
            'file_count' => $phpFiles->count(),
            'limit_class' => $limitClass,
            'limit_method' => $limitMethod,
            'output_file' => $outputFile,
        ]);

        $this->info(sprintf(
            'Found [%d] PHP files to parse. limit-class=%d, limit-method=%d',
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

                // Save parsed items to the database
                foreach ($items as $item) {
                    ParsedItem::create([
                        'type' => $item['type'] ?? null,
                        'name' => $item['name'] ?? null,
                        'file_path' => $filePath,
                        'line_number' => $item['line_number'] ?? null,
                        'annotations' => $item['annotations'] ?? [],
                        'attributes' => $item['attributes'] ?? [],
                        'details' => $item['details'] ?? [],
                        'class_name' => $item['class_name'] ?? null,
                        'namespace' => $item['namespace'] ?? null,
                        'visibility' => $item['visibility'] ?? null,
                        'is_static' => $item['is_static'] ?? false,
                        'fully_qualified_name' => $item['fully_qualified_name'] ?? null,
                        'operation_summary' => $item['operation_summary'] ?? null,
                        'called_methods' => $item['called_methods'] ?? [],
                        'ast' => $item['ast'] ?? [],
                    ]);
                }

                if ($this->isVerbose()) {
                    $this->info("Successfully parsed and stored: {$filePath}");
                }
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
                if (isset($item['type']) && in_array($item['type'], ['Class', 'Trait', 'Interface'], true) && !empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }

                return $item;
            });
        }

        // Apply optional filter
        if ($filter !== '') {
            $collectedItems = $collectedItems->filter(fn($item) => stripos($item['name'] ?? '', (string) $filter) !== false);
        }

        $this->info('Initial collected items: '.$collectedItems->count());

        if ($outputFile) {
            $this->exportJson($collectedItems->values(), $outputFile);
        }

        return 0;
    }

    /**
     * Export collected items to a JSON file.
     *
     * @param  Collection  $items  The collection of items to export.
     * @param  string  $filePath  The file path to export the JSON to.
     */
    protected function exportJson(Collection $items, string $filePath): void
    {
        $json = json_encode($items->toArray(), JSON_PRETTY_PRINT);
        if (! $json) {
            $this->warn('Failed to encode to JSON: '.json_last_error_msg());

            return;
        }

        @mkdir(dirname($filePath), 0777, true);
        File::put($filePath, $json);
        $this->info("Output written to {$filePath}");
    }
}
