<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FunctionAndClassVisitor;
use App\Models\ParsedItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

/**
 * Parses PHP files and outputs discovered classes & functions.
 */
class ParseFilesCommand extends Command
{
    protected $signature = 'parse:files {--filter=} {--output-file=} {--limit-class=} {--limit-method=}';
    protected $description = 'Parses configured or specified files/directories and lists discovered functions and classes';

    /**
     * @var ParserService
     */
    protected ParserService $parserService;

    /**
     * ParseFilesCommand constructor.
     *
     * @param ParserService $parserService
     */
    public function __construct(ParserService $parserService)
    {
        parent::__construct();
        $this->parserService = $parserService;
    }

    public function handle()
    {
        // 1) Collect files and folders from config
        $phpFiles = $this->parserService->collectPhpFiles()->unique();

        $filter     = $this->option('filter');
        $outputFile = $this->option('output-file');
        if ($outputFile && substr($outputFile, -5) !== '.json') {
            $outputFile .= '.json';
        }
        $limitClass = $this->option('limit-class');
        $limitMethod = $this->option('limit-method');

        // 2) Setup parser & traverser using ParserService
        $this->parserService->setupParserTraversal();
        $parser = $this->parserService->createParser();
        $traverser = $this->parserService->createTraverser();
        $visitor = new FunctionAndClassVisitor();
        $traverser->addVisitor($visitor);

        $this->info("Found " . $phpFiles->count() . " PHP files to parse.");

        // Initialize progress bar
        $bar = $this->output->createProgressBar($phpFiles->count());
        $bar->start();

        // 3) Parse each PHP file
        $phpFiles->each(function ($phpFile) use ($parser, $traverser, $visitor, $bar) {
            if ($this->output->isVerbose()) {
                $this->info("Parsing file: $phpFile");
            }
            $visitor->setCurrentFile($phpFile);
            $this->parseOneFile($phpFile, $parser, $traverser, $visitor);
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        $items = collect($visitor->getItems());

        // Display warnings
        collect($visitor->getWarnings())->each(function ($warning) {
            $this->warn($warning);
        });

        if ($limitClass) {
            $items = $items->filter(fn($item) => $item['type'] !== 'Class')
                ->merge(
                    $items->where('type', 'Class')->take($limitClass)
                );
        }

        if ($limitMethod) {
            $items = $items->map(function ($item) use ($limitMethod) {
                if ($item['type'] === 'Class' && !empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }
                return $item;
            });
        }

        $this->info("Collected " . $items->count() . " items from parsing.");

        // 4) Apply filter if given
        if ($filter) {
            $items = $items->filter(function($item) use ($filter) {
                return stripos($item['name'], $filter) !== false;
            });
        }

        // 5) Store parsed items in the database
        $items->each(function ($item) {
            // Store the class or function
            ParsedItem::updateOrCreate(
                [
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'file_path' => $item['file'],
                ],
                [
                    'line_number' => $item['line'],
                    'annotations' => $item['annotations'] ?: [],
                    'attributes' => $item['attributes'] ?: [],
                    'details' => $item['details'] ?? [],
                    'ast' => $item['ast'] ?? null,
                    'fully_qualified_name' => $item['fully_qualified_name'] ?? null,
                ]
            );

            // If item is a class and has methods, store each method as a separate ParsedItem
            if ($item['type'] === 'Class' && !empty($item['details']['methods'])) {
                collect($item['details']['methods'])->each(function ($method) use ($item) {
                    ParsedItem::updateOrCreate(
                        [
                            'type' => 'Method',
                            'name' => $method['name'],
                            'file_path' => $item['file'],
                        ],
                        [
                            'line_number' => $method['line'] ?? null,
                            'annotations' => $method['annotations'] ?: [],
                            'attributes' => $method['attributes'] ?: [],
                            'details' => [
                                'params' => $method['params'] ?? [],
                                'description' => $method['description'] ?? '',
                            ],
                            'class_name' => $method['class'] ?? '',
                            'namespace' => $method['namespace'] ?? '',
                            'visibility' => $method['visibility'] ?? '',
                            'is_static' => $method['isStatic'] ?? false,
                            'fully_qualified_name' => ($item['fully_qualified_name'] ?? '') . '::' . $method['name'],
                            'operation_summary' => $method['operation_summary'] ?? '',
                            'called_methods' => $method['called_methods'] ?? [],
                            'ast' => $method['ast'] ?? null,
                        ]
                    );
                });
            }
        });

        // 6) Output
        if ($items->isEmpty()) {
            $this->info('No functions or classes found.');
            return 0;
        }

        if ($outputFile) {
            $this->persistJsonOutput($items->all(), $outputFile);
        } else {
            $this->displayTable($items->all());
        }

        return 0;
    }


    /**
     * Return all PHP files recursively in the given directory.
     */
    private function getPhpFiles(string $directory): array
    {
        return $this->parserService->getPhpFiles($directory);
    }

    /**
     * Display results in a table in the console.
     */
    private function displayTable(array $items)
    {
        $this->table(
            ['Type', 'Name', 'Details/Params', 'Annotations', 'Attributes', 'Location'],
            collect($items)->map(function($item) {
                if ($item['type'] === 'Function') {
                    $paramStr   = collect($item['details']['params'])->map(fn($p) => "{$p['type']} {$p['name']}")->implode(', ');
                    $details    = $paramStr;
                    if (!empty($item['details']['description'])) {
                        $details .= ' - ' . $item['details']['description'];
                    }
                    $allAnnotations = collect($item['annotations'])->merge($item['details']['restler_tags'] ?? [])->toArray();
                    $annotations = collect($allAnnotations)->map(function($values, $tag) {
                        return collect($values)->map(fn($value) => "@{$tag} {$value}")->implode("\n");
                    })->implode("\n");
                    $attributes  = collect($item['attributes'])->implode("\n");
                    $location    = "{$item['file']}:{$item['line']}";
                    return [$item['type'], $item['name'], $details, $annotations, $attributes, $location];
                } else { // 'Class'
                    $methods = collect($item['details']['methods'])->map(function($m) {
                        $params = collect($m['params'])->map(fn($p) => "{$p['type']} {$p['name']}")->implode(', ');
                        $desc   = $m['description'] ? ' - ' . $m['description'] : '';
                        $methodAnnotations = collect($m['annotations'])->map(function($values, $tag) {
                            return collect($values)->map(fn($value) => "@{$tag} {$value}")->implode("\n");
                        })->implode("\n");
                        return "{$m['name']}($params)$desc\n$methodAnnotations";
                    })->implode(', ');
                    if (!empty($item['details']['description'])) {
                        $methods .= ' - ' . $item['details']['description'];
                    }
                    $annotations = collect($item['annotations'])->implode("\n");
                    $attributes  = collect($item['attributes'])->implode("\n");
                    $location    = "{$item['file']}:{$item['line']}";
                    return [$item['type'], $item['name'], $methods, $annotations, $attributes, $location];
                }
            })->toArray()
        );
    }

    /**
     * Write the output to a JSON file.
     */
    private function persistJsonOutput(array $items, string $outputFile)
    {
        $jsonData = json_encode($items, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            $this->error("Failed to encode data to JSON.");
            return;
        }

        $dir = dirname($outputFile);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }

        File::put($outputFile, $jsonData);
        $this->info("Output written to {$outputFile}");
    }

    /**
     * Parse a single PHP file.
     *
     * @param string $filePath
     * @param Parser $parser
     * @param NodeTraverser $traverser
     * @param FunctionAndClassVisitor $visitor
     */
    private function parseOneFile(string $filePath, $parser, $traverser, $visitor)
    {
        $code = File::get($filePath);
        try {
            $ast = $parser->parse($code);
            if ($ast === null) {
                $this->warn("No AST generated for file: {$filePath}");
                return;
            }
            $traverser->traverse($ast);
        } catch (\Exception $e) {
            $this->error("Error parsing file {$filePath}: " . $e->getMessage());
        }
    }
}
