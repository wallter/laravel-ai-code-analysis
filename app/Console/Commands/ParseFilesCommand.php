<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use App\Services\Parsing\FunctionAndClassVisitor;
use Illuminate\Support\Facades\File;

/**
 * Parses PHP files and outputs discovered classes & functions.
 */
class ParseFilesCommand extends Command
{
    protected $signature = 'parse:files {paths?*} {--filter=} {--output-file=}';
    protected $description = 'Parses configured or specified files/directories and lists discovered functions and classes';

    public function handle()
    {
        // 1) Collect paths from config or direct input
        $paths = config('parsing.files', []);
        $inputPaths = $this->argument('paths');

        if (!empty($inputPaths)) {
            $paths = $inputPaths;
        }

        $filter     = $this->option('filter');
        $outputFile = $this->option('output-file');

        // 2) Create parser
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser();
        $visitor = new FunctionAndClassVisitor();
        $traverser->addVisitor($visitor);

        $items = [];

        // 3) Iterate over all configured paths
        foreach ($paths as $path) {
            $realPath = $this->normalizePath($path);
            if (!File::exists($realPath)) {
                $this->warn("Path not found: {$realPath}");
                continue;
            }

            if (File::isDirectory($realPath)) {
                $phpFiles = $this->getPhpFiles($realPath);
                foreach ($phpFiles as $phpFile) {
                    $this->parseOneFile($phpFile, $parser, $traverser, $visitor);
                }
            } else {
                $this->parseOneFile($realPath, $parser, $traverser, $visitor);
            }
        }

        $items = $visitor->getItems();

        // 4) Apply filter if given
        if ($filter) {
            $items = array_filter($items, function($item) use ($filter) {
                return stripos($item['name'], $filter) !== false;
            });
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
        return File::allFiles($directory)
            ->filter(fn($file) => strtolower($file->getExtension()) === 'php')
            ->map(fn($file) => $file->getRealPath())
            ->toArray();
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
        if (File::isAbsolutePath($path)) {
            return $path;
        }
        return base_path($path);
    }
}
