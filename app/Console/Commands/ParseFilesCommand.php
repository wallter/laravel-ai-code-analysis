<?php

namespace App\Console\Commands;

use App\Services\Export\JsonExportService;
use App\Services\ParsedItemService;
use App\Services\Parsing\FileProcessorService;
use App\Services\Parsing\ParserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
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
    public function __construct(
        protected ParserService $parserService,
        protected ParsedItemService $parsedItemService,
        protected JsonExportService $jsonExportService,
        protected FileProcessorService $fileProcessorService
    ) {
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

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        $collectedItems = collect();
        foreach ($phpFiles as $filePath) {
            $this->processFile($filePath);
            $collectedItems->push($filePath); // Adjust based on actual return if needed
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Apply limits and filters
        $collectedItems = $this->applyLimits($collectedItems, $limitClass, $limitMethod);
        $collectedItems = $this->applyFilter($collectedItems, $filter);

        $this->info('Initial collected items: '.$collectedItems->count());

        if ($outputFile) {
            $this->jsonExportService->export($collectedItems->values(), $outputFile);
            $this->info("Output written to {$outputFile}");
        }

        return 0;
    }

    /**
     * Process and store parsed items for each PHP file.
     */
    protected function processFile(string $filePath): void
    {
        $basePath = Config::get('filesystems.base_path');
        $absolutePath = realpath($basePath.DIRECTORY_SEPARATOR.$filePath) ?: $filePath;

        $success = $this->fileProcessorService->process($absolutePath, $this->isVerbose());

        if (! $success) {
            $this->warn("Failed to parse and store: {$absolutePath}");
        }
    }

    /**
     * Apply class and method limits to the parsed items.
     */
    protected function applyLimits(Collection $collectedItems, int $limitClass, int $limitMethod): Collection
    {
        if ($limitClass > 0) {
            $collectedItems = $collectedItems->take($limitClass);
            $this->info("Applying limit-class: analyzing only the first {$limitClass} item(s).");
        }

        if ($limitMethod > 0) {
            $collectedItems = $collectedItems->map(function ($item) use ($limitMethod) {
                if (isset($item['type']) && in_array($item['type'], ['Class', 'Trait', 'Interface'], true) && ! empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }

                return $item;
            });
            $this->info("Applying limit-method: limiting methods to first {$limitMethod} per class/trait.");
        }

        return $collectedItems;
    }

    /**
     * Apply name filter to the collected parsed items.
     */
    protected function applyFilter(Collection $collectedItems, string $filter): Collection
    {
        if ($filter !== '') {
            $collectedItems = $collectedItems->filter(fn ($item) => stripos($item['name'] ?? '', $filter) !== false);
            $this->info("Applying filter: items containing '{$filter}' in their name.");
        }

        return $collectedItems;
    }
}
