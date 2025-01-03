<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FunctionAndClassVisitor;
use App\Models\ParsedItem;
use Illuminate\Support\Facades\File;

/**
 * Simple parse command that shows discovered classes & functions.
 */
class ParseFilesCommand extends Command
{
    protected $signature = 'parse:files 
                           {--filter= : Filter items by name} 
                           {--output-file= : JSON output path}
                           {--limit-class= : Limit how many classes to parse} 
                           {--limit-method= : Limit how many methods per class}';

    protected $description = 'Parses configured or specified files/directories and displays discovered classes & functions';

    public function __construct(protected ParserService $parserService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $phpFiles    = $this->parserService->collectPhpFiles()->unique();
        $filter      = $this->option('filter');
        $outputFile  = $this->option('output-file');
        $limitClass  = intval($this->option('limit-class'))  ?: 0;
        $limitMethod = intval($this->option('limit-method')) ?: 0;

        if ($phpFiles->isEmpty()) {
            $this->info("No PHP files found.");
            return 0;
        }

        // We'll parse all files with our single "FunctionAndClassVisitor".
        $visitor = new FunctionAndClassVisitor();

        $this->info("Parsing {$phpFiles->count()} PHP file(s)...");

        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        $parsedItems = collect();

        foreach ($phpFiles as $phpFile) {
            $visitor->setCurrentFile($phpFile);

            // parseFile() with $visitor
            try {
                $this->parserService->parseFile(
                    filePath: $phpFile,
                    visitors: [$visitor],
                    useCache: false
                );
            } catch (\Throwable $e) {
                $this->warn("Could not parse {$phpFile}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        // Gather the discovered data
        $allItems = collect($visitor->getItems());

        if ($limitClass) {
            $allItems = $allItems->map(function ($item) use ($limitClass, $limitMethod) {
                if ($item['type'] === 'Class') {
                    // For example, you might skip if you exceed $limitClass
                    // or you can do "top X classes" logic. Up to you.
                }
                if ($limitMethod && !empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }
                return $item;
            });
        }

        // apply filter if set
        if ($filter) {
            $allItems = $allItems->filter(fn($item) => stripos($item['name'], $filter) !== false);
        }

        // Store or display them. For demonstration, let's store in DB:
        $allItems->each(function ($item) {
            ParsedItem::updateOrCreate(
                [
                    'type'      => $item['type'],
                    'name'      => $item['name'],
                    'file_path' => $item['file'],
                ],
                [
                    'line_number'  => $item['line'],
                    'details'      => $item['details'] ?? [],
                    'annotations'  => $item['annotations'] ?? [],
                    'attributes'   => $item['attributes'] ?? [],
                    'fully_qualified_name' => $item['fully_qualified_name'] ?? null,
                ]
            );
        });

        $this->info("Parsed {$allItems->count()} items total.");

        // If user wants JSON output
        if ($outputFile) {
            $this->exportJson($allItems->values()->toArray(), $outputFile);
        } else {
            // Or display in table
            $this->displaySummary($allItems->values()->toArray());
        }

        return 0;
    }

    protected function exportJson(array $items, string $filePath)
    {
        $encoded = json_encode($items, JSON_PRETTY_PRINT);
        if (!$encoded) {
            $this->warn("Failed to encode to JSON.");
            return;
        }
        @mkdir(dirname($filePath), 0777, true);
        File::put($filePath, $encoded);
        $this->info("Output written to {$filePath}");
    }

    protected function displaySummary(array $items)
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