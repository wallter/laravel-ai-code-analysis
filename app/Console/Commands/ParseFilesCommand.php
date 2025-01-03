<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Parsing\ParserService;
use App\Services\Parsing\FunctionAndClassVisitor;
use App\Models\ParsedItem;
use Illuminate\Support\Facades\File;

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
        $filePaths   = config('parsing.files', []);
        $folderPaths = config('parsing.folders', []);

        $filter     = $this->option('filter');
        $outputFile = $this->option('output-file');
        if ($outputFile && substr($outputFile, -5) !== '.json') {
            $outputFile .= '.json';
        }
        $limitClass = $this->option('limit-class');
        $limitMethod = $this->option('limit-method');

        // 2) Setup parser & traverser using ParserService
        $parser = $this->parserService->createParser();
        $traverser = $this->parserService->createTraverser();
        $visitor = new FunctionAndClassVisitor();
        $traverser->addVisitor($visitor);

        $phpFiles = [];

        // 3) Collect all PHP files from the folders
        foreach ($folderPaths as $folderPath) {
            $realPath = $this->parserService->normalizePath($folderPath);
            $this->info("Scanning folder: $realPath");
            if (!File::isDirectory($realPath)) {
                $this->warn("Folder not found: {$realPath}");
                continue;
            }
            $folderPhpFiles = $this->parserService->getPhpFiles($realPath);
            $phpFiles = array_merge($phpFiles, $folderPhpFiles);
        }

        // Add individual files to the list
        foreach ($filePaths as $filePath) {
            $realPath = $this->parserService->normalizePath($filePath);
            if (!File::exists($realPath)) {
                $this->warn("File not found: {$realPath}");
                continue;
            }
            $phpFiles[] = $realPath;
        }

        // Remove duplicates
        $phpFiles = array_unique($phpFiles);

        $this->info("Found " . count($phpFiles) . " PHP files to parse.");

        // 4) Parse each PHP file
        foreach ($phpFiles as $phpFile) {
            if ($this->output->isVerbose()) {
                $this->info("Parsing file: $phpFile");
            }
            $this->parseOneFile($phpFile, $parser, $traverser, $visitor);
        }

        $items = $visitor->getItems();

        // Display warnings
        $warnings = $visitor->getWarnings();
        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($limitClass) {
            $classItems = array_filter($items, function ($item) {
                return $item['type'] === 'Class';
            });
            $nonClassItems = array_filter($items, function ($item) {
                return $item['type'] !== 'Class';
            });
            $classItems = array_slice($classItems, 0, $limitClass);
            $items = array_merge($classItems, $nonClassItems);
        }

        if ($limitMethod) {
            foreach ($items as &$item) {
                if ($item['type'] === 'Class' && !empty($item['details']['methods'])) {
                    $item['details']['methods'] = array_slice($item['details']['methods'], 0, $limitMethod);
                }
            }
        }

        $this->info("Collected " . count($items) . " items from parsing.");

        // 4) Apply filter if given
        if ($filter) {
            $items = array_filter($items, function($item) use ($filter) {
                return stripos($item['name'], $filter) !== false;
            });
        }

        // 6) Store parsed items in the database
        foreach ($items as $item) {
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
                    'details' => array_merge($item['details'] ?: [], [
                        'restler_tags' => $item['restler_tags'] ?? [],
                    ]),
                    'ast' => $item['ast'] ?? null,
                ]
            );

            // If item is a class and has methods, store each method as a separate ParsedItem
            if ($item['type'] === 'Class' && !empty($item['details']['methods'])) {
                foreach ($item['details']['methods'] as $method) {
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
                            'fully_qualified_name' => ($item['fullyQualifiedName'] ?? '') . '::' . $method['name'],
                            'operation_summary' => $method['operation_summary'] ?? '',
                            'called_methods' => $method['called_methods'] ?? [],
                            'ast' => $method['ast'] ?? null,
                        ]
                    );
                }
            }
        }

        // 5) Output
        if (empty($items)) {
            $this->info('No functions or classes found.');
            return 0;
        }

        if ($outputFile) {
            $this->persistJsonOutput($items, $outputFile);
        } else {
            $this->displayTable($items);
        }

        return 0;
    }


    /**
     * Return all PHP files recursively in the given directory.
     */
    private function getPhpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $phpFiles[] = $file->getRealPath();
            }
        }
        return $phpFiles;
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
                    $allAnnotations = array_merge_recursive($item['annotations'], $item['restler_tags'] ?? []);
                    $annotations = '';
                    foreach ($allAnnotations as $tag => $values) {
                        foreach ($values as $value) {
                            $annotations .= "@{$tag} {$value}\n";
                        }
                    }
                    $attributes  = implode("\n", $item['attributes']);
                    $location    = $item['file'] . ':' . $item['line'];
                    return [$item['type'], $item['name'], $details, $annotations, $attributes, $location];
                } else { // 'Class'
                    $methods = collect($item['details']['methods'])->map(function($m) {
                        $params = collect($m['params'])->map(fn($p) => "{$p['type']} {$p['name']}")->implode(', ');
                        $desc   = $m['description'] ? ' - ' . $m['description'] : '';
                        $methodAnnotations = '';
                        if (!empty($m['annotations'])) {
                            foreach ($m['annotations'] as $tag => $values) {
                                if (is_array($values)) {
                                    foreach ($values as $value) {
                                        $methodAnnotations .= "@{$tag} {$value}\n";
                                    }
                                } else {
                                    $methodAnnotations .= "@{$tag} {$values}\n";
                                }
                            }
                        }
                        return "{$m['name']}($params)$desc\n$methodAnnotations";
                    })->implode(', ');
                    if (!empty($item['details']['description'])) {
                        $methods .= ' - ' . $item['details']['description'];
                    }
                    $annotations = implode("\n", $item['annotations']);
                    $attributes  = implode("\n", $item['attributes']);
                    $location    = $item['file'] . ':' . $item['line'];
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
        $items = $this->decodeAstProperties($items);
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
     * Normalizes path if it's relative, otherwise returns as is.
     */
    private function normalizePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }
        return base_path($path);
    }

    /**
     * Determines if a given path is absolute.
     *
     * @param string $path
     * @return bool
     */
    private function isAbsolutePath(string $path): bool
    {
        // Check for Unix-like or Windows absolute path
        return preg_match('/^(\/|[A-Za-z]:[\/\\\\])/', $path) === 1;
    }
    
    /**
     * Decode the 'ast' property in items to avoid escaping in output JSON.
     */
    private function decodeAstProperties(array $items): array
    {
        foreach ($items as &$item) {
            if (isset($item['ast']) && is_string($item['ast'])) {
                $item['ast'] = json_decode($item['ast'], true);
            }
            if (isset($item['details']['methods']) && is_array($item['details']['methods'])) {
                foreach ($item['details']['methods'] as &$method) {
                    if (isset($method['ast']) && is_string($method['ast'])) {
                        $method['ast'] = json_decode($method['ast'], true);
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Parse a single PHP file.
     *
     * @param string $filePath
     * @param mixed $parser
     * @param mixed $traverser
     * @param FunctionAndClassVisitor $visitor
     */
    private function parseOneFile(string $filePath, $parser, $traverser, $visitor)
    {
        $code = File::get($filePath);
        try {
            $ast = $parser->parse($code);
            $traverser->traverse($ast);
        } catch (\Exception $e) {
            $this->error("Error parsing file {$filePath}: " . $e->getMessage());
        }
    }
}
