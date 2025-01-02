<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpParser\Error;
use App\Services\Parsing\ParserService;
use PhpParser\NodeTraverser;
use App\Services\Parsing\FunctionAndClassVisitor;
use Illuminate\Support\Facades\File;
use App\Models\ParsedItem;

/**
 * Parses PHP files and outputs discovered classes & functions.
 */
class ParseFilesCommand extends Command
{
    protected $signature = 'parse:files {--filter=} {--output-file=}';
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

        // 2) Setup parser & traverser using ParserService
        $parser     = $this->parserService->createParser();
        $traverser  = $this->parserService->createTraverser();
        $visitor    = new FunctionAndClassVisitor();
        $traverser->addVisitor($visitor);

        $items      = [];
        $phpFiles   = [];

        // 3) Collect all PHP files from the folders
        foreach ($folderPaths as $folderPath) {
            $realPath = $this->normalizePath($folderPath);
            if (!File::isDirectory($realPath)) {
                $this->warn("Folder not found: {$realPath}");
                continue;
            }
            $folderPhpFiles = $this->getPhpFiles($realPath);
            $phpFiles = array_merge($phpFiles, $folderPhpFiles);
        }

        // Add individual files to the list
        foreach ($filePaths as $filePath) {
            $realPath = $this->normalizePath($filePath);
            if (!File::exists($realPath)) {
                $this->warn("File not found: {$realPath}");
                continue;
            }
            $phpFiles[] = $realPath;
        }

        // Remove duplicates
        $phpFiles = array_unique($phpFiles);

        // 4) Parse each PHP file
        foreach ($phpFiles as $phpFile) {
            $this->parseOneFile($phpFile, $parser, $traverser, $visitor);
        }

        $items = $visitor->getItems();

        // 4) Apply filter if given
        if ($filter) {
            $items = array_filter($items, function($item) use ($filter) {
                return stripos($item['name'], $filter) !== false;
            });
        }

        // 6) Store parsed items in the database
        foreach ($items as $item) {
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
                    'details' => $item['details'] ?: [],
                ]
            );
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
     * Parse a single file with the provided parser/traverser/visitor.
     */
    private function parseOneFile(string $filePath, $parser, $traverser, $visitor)
    {
        try {
            $code = File::get($filePath);
            $ast  = $parser->parse($code);
            if ($ast === null) {
                throw new \Exception("Unable to parse AST for {$filePath}.");
            }
            // Set the "current file" context
            $visitor->setCurrentFile($filePath);
            $traverser->traverse($ast);
        } catch (Error $e) {
            $this->error("Parse error in {$filePath}: {$e->getMessage()}");
        } catch (\Exception $e) {
            $this->error("Error processing {$filePath}: {$e->getMessage()}");
        }
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
                    $annotations = implode("\n", $item['annotations']);
                    $attributes  = implode("\n", $item['attributes']);
                    $location    = $item['file'] . ':' . $item['line'];
                    return [$item['type'], $item['name'], $details, $annotations, $attributes, $location];
                } else { // 'Class'
                    $methods = collect($item['details']['methods'])->map(function($m) {
                        $params = collect($m['params'])->map(fn($p) => "{$p['type']} {$p['name']}")->implode(', ');
                        $desc   = $m['description'] ? ' - ' . $m['description'] : '';
                        return "{$m['name']}($params)$desc";
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
}
