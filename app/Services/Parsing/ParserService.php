<?php

namespace App\Services\Parsing;

use App\Models\CodeAnalysis;
use App\Models\ParsedItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * Provides file collection and single-visitor parsing.
 */
class ParserService
{
    /**
     * Collect .php files from config('parsing.files') + config('parsing.folders').
     *
     * @return Collection<string> A collection of PHP file paths.
     */
    public function collectPhpFiles(): Collection
    {
        Log::info("ParserService: collecting .php files via config('parsing.files','parsing.folders').");
        $files = config('parsing.files', []);
        $folders = config('parsing.folders', []);

        $fileList = collect($files)->map(fn ($f) => realpath($f))->filter();
        $folderList = collect($folders)->flatMap(fn ($dir) => $this->getPhpFiles($dir));

        $merged = $fileList->merge($folderList)->unique()->values();
        Log::info("ParserService: total .php files => {$merged->count()}");

        return $merged;
    }

    /**
     * Parse a single PHP file with optional visitors, and return the raw AST array.
     * If $useCache is true, we check code_analyses table first.
     *
     * @param  string  $filePath  The path to the PHP file to parse.
     * @param  array  $visitors  Optional array of NodeVisitor instances.
     * @param  bool  $useCache  Whether to use cached AST from the database.
     * @return array The parsed AST.
     */
    public function parseFile(string $filePath, array $visitors = [], bool $useCache = false): array
    {
        $realPath = realpath($filePath) ?: $filePath;
        Log::debug("ParserService.parseFile => [{$realPath}], useCache={$useCache}");

        if ($useCache) {
            $cached = CodeAnalysis::where('file_path', $realPath)->first();
            if ($cached && ! empty($cached->ast)) {
                Log::info("ParserService: Found cached AST for [{$realPath}].");

                return $cached->ast;
            }
        }

        // Read code
        try {
            $code = File::get($realPath);
        } catch (\Throwable $throwable) {
            Log::error("ParserService: failed to read [{$realPath}]: " . $throwable->getMessage());

            return [];
        }

        // Create parser & parse
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = [];
        try {
            $ast = $parser->parse($code);
            if (! $ast) {
                Log::warning("ParserService: AST is null for [{$realPath}].");

                return [];
            }
        } catch (\Throwable $throwable) {
            Log::error("ParserService: parse error [{$realPath}]: " . $throwable->getMessage());

            return [];
        }

        // Optionally traverse with visitors
        if (! empty($visitors)) {
            $traverser = new NodeTraverser;
            foreach ($visitors as $v) {
                if (method_exists($v, 'setCurrentFile')) {
                    $v->setCurrentFile($realPath);
                }

                $traverser->addVisitor($v);
            }

            $traverser->traverse($ast);
        }

        // Extract and store parsed items
        $this->extractAndStoreParsedItems($ast, $realPath);

        // Optionally store AST in DB
        if ($useCache) {
            try {
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $realPath],
                    ['ast' => $ast]
                );
                Log::info("ParserService: Cached AST in DB for [{$realPath}].");
            } catch (\Throwable $throwable) {
                Log::error("ParserService: failed caching AST [{$realPath}]: " . $throwable->getMessage());
            }
        }

        return $ast;
    }

    /**
     * Recursively get .php files from a directory.
     *
     * @param  string  $directory  The directory path to search.
     * @return Collection<string> A collection of PHP file paths.
     */
    protected function getPhpFiles(string $directory): Collection
    {
        $realDir = realpath($directory);
        if (! $realDir || ! is_dir($realDir)) {
            Log::warning("ParserService.getPhpFiles: folder not found or invalid => [{$directory}].");

            return collect();
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower((string) $file->getExtension()) === 'php') {
                $phpFiles[] = $file->getRealPath();
            }
        }

        Log::info("ParserService.getPhpFiles => [{$realDir}] => found [" . count($phpFiles) . '] .php files.');

        return collect($phpFiles);
    }

    /**
     * Extract classes and interfaces from AST and store them in parsed_items table.
     *
     * @param array $ast
     * @param string $filePath
     * @return void
     */
    protected function extractAndStoreParsedItems(array $ast, string $filePath): void
    {
        if (empty($ast)) {
            return;
        }

        $traverser = new NodeTraverser();
        $visitor = new class extends NodeVisitorAbstract {
            public array $parsedItems = [];

            public function enterNode(Node $node): void
            {
                if (
                    ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_) &&
                    $node->name !== null
                ) {
                    $this->parsedItems[] = [
                        'type' => $node instanceof Node\Stmt\Class_ ? 'Class' : 'Interface',
                        'name' => $node->name->toString(),
                    ];
                }
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        foreach ($visitor->parsedItems as $item) {
            ParsedItem::updateOrCreate(
                [
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'file_path' => $filePath,
                ],
                [
                    // Add any additional fields if necessary
                ]
            );
        }

        Log::info("ParserService: Stored " . count($visitor->parsedItems) . " parsed items for [{$filePath}].");
    }
}
