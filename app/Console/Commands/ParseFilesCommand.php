<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\File;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FunctionAndClassVisitor;
use App\Models\ParsedItem;
use App\Models\CodeAnalysis; // Ensure this model is imported if not already

/**
 * Parses PHP files and outputs discovered classes & functions.
 */
class ParseFilesCommand extends BaseCodeCommand
{
    protected $signature = 'parse:files
        {--filter= : Filter item names}
        {--output-file=}
        {--limit-class=}
        {--limit-method=}';

    protected $description = 'Parse configured or specified files/directories and list discovered functions/classes.';

    public function __construct(protected ParserService $parserService)
    {
        parent::__construct();
    }

    /**
     * The main logic for this command, called by the parent's handle().
     */
    protected function executeCommand(): int
    {
        $phpFiles = $this->parserService->collectPhpFiles()->unique();

        if ($this->isVerbose()) {
            $this->info("Collected PHP files:");
            foreach ($phpFiles as $file) {
                $this->line(" - {$file}");
            }
        }

        if ($phpFiles->isEmpty()) {
            $this->info("No PHP files found.");
            return 0;
        }

        $filter      = $this->option('filter');
        $outputFile  = $this->getOutputFile();
        $limitClass  = $this->getClassLimit();
        $limitMethod = $this->getMethodLimit();

        $this->info("Found {$phpFiles->count()} files to parse.");

        $visitor = new FunctionAndClassVisitor();
        $parsedItems = collect();

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        foreach ($phpFiles as $filePath) {
            if ($this->isVerbose()) {
                $this->info("Parsing file: {$filePath}");
            }

            // Create ParsedItem for the file
            $parsedItem = ParsedItem::updateOrCreate(
                [
                    'type'      => 'File',
                    'name'      => basename($filePath),
                    'file_path' => $filePath,
                ],
                [
                    'line_number'           => 0,
                    'details'               => [],
                    'annotations'           => [],
                    'attributes'            => [],
                    'fully_qualified_name'  => null,
                ]
            );

            try {
                // Provide the parsedItem ID to associate CodeAnalysis later
                $ast = $this->parserService->parseFile(
                    filePath: $filePath,
                    visitors: [$visitor],
                    parsedItemId: $parsedItem->id
                );

                if ($this->isVerbose()) {
                    $this->info("Successfully parsed: {$filePath}");
                }
            } catch (\Throwable $e) {
                $this->warn("Could not parse {$filePath}: {$e->getMessage()}");
                if ($this->isVerbose()) {
                    $this->error("Error details: " . $e->getMessage());
                }
            }

            $bar->advance();
        }
        $bar->finish();
        $this->line('');

        // Now retrieve discovered items
        $items = collect($visitor->getItems());

        if ($this->isVerbose()) {
            $this->info("Initial collected items: {$items->count()}");
        }

        // Optionally apply limit-class and limit-method, filter, etc.
        if ($limitClass > 0) {
            if ($this->isVerbose()) {
                $this->info("Applying class limit: {$limitClass}");
            }
            // This example: limit how many "Class" items are in the final set
            $classItems = $items->where('type', 'Class')->take($limitClass);
            $otherItems = $items->where('type', '!=', 'Class');
            $items = $otherItems->merge($classItems);
            if ($this->isVerbose()) {
                $this->info("After applying class limit: {$items->count()} items");
            }
        }

        if ($limitMethod > 0) {
            if ($this->isVerbose()) {
                $this->info("Applying method limit: {$limitMethod}");
            }
            $items = $items->map(function ($item) use ($limitMethod) {
                if ($item['type'] === 'Class' && !empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }
                return $item;
            });
            if ($this->isVerbose()) {
                $this->info("After applying method limit.");
            }
        }

        if ($filter) {
            if ($this->isVerbose()) {
                $this->info("Applying filter: '{$filter}'");
            }
            $items = $items->filter(fn ($item) => stripos($item['name'], $filter) !== false);
            if ($this->isVerbose()) {
                $this->info("After applying filter: {$items->count()} items");
            }
        }

        // Example storing in DB
        if ($this->isVerbose()) {
            $this->info("Storing parsed items in the database.");
        }
        $items->each(function ($item) use ($parsedItem) { // Associate items with the parsedItem
            ParsedItem::updateOrCreate(
                [
                    'type'      => $item['type'],
                    'name'      => $item['name'],
                    'file_path' => $item['file'],
                ],
                [
                    'line_number'           => $item['line'] ?? 0, // Changed from null to 0
                    'details'               => $item['details'] ?? [],
                    'annotations'           => $item['annotations'] ?? [],
                    'attributes'            => $item['attributes'] ?? [],
                    'fully_qualified_name'  => $item['fully_qualified_name'] ?? null,
                ]
            );
        });

        $this->info("Collected {$items->count()} items from parsing.");

        // Output to .json if requested
        if ($outputFile) {
            if ($this->isVerbose()) {
                $this->info("Exporting parsed items to JSON file: {$outputFile}");
            }
            $this->exportJson($items->values()->toArray(), $outputFile);
        } else {
            if ($this->isVerbose()) {
                $this->info("Displaying parsed items in table format.");
            }
            $this->displayTable($items->all());
        }

        return 0;
    }

    protected function exportJson(array $items, string $filePath)
    {
        $json = json_encode($items, JSON_PRETTY_PRINT);
        if (!$json) {
            $this->warn("Failed to encode to JSON.");
            if ($this->isVerbose()) {
                $this->warn("JSON encoding errors: " . json_last_error_msg());
            }
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        File::put($filePath, $json);
        $this->info("Output written to {$filePath}");
    }

    /**
     * Simple table display of the items
     */
    protected function displayTable(array $items)
    {
        $this->table(
            ['Type', 'Name', 'Params/Methods', 'File', 'Line'],
            collect($items)->map(function ($item) {
                if ($item['type'] === 'Class') {
                    $methodsStr = collect($item['details']['methods'] ?? [])
                        ->map(fn($m) => $m['name'])
                        ->implode(', ');
                    return [
                        $item['type'],
                        $item['name'],
                        $methodsStr,
                        $item['file'],
                        $item['line'],
                    ];
                } elseif ($item['type'] === 'Function') {
                    $paramStr = collect($item['details']['params'] ?? [])->map(
                        fn($p) => $p['type'].' '.$p['name']
                    )->implode(', ');
                    return [
                        $item['type'],
                        $item['name'],
                        $paramStr,
                        $item['file'],
                        $item['line'],
                    ];
                }
                return [
                    $item['type'],
                    $item['name'],
                    '',
                    $item['file'],
                    $item['line'],
                ];
            })->toArray()
        );
    }
}
