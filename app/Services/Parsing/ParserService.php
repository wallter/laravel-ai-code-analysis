<?php

namespace App\Services\Parsing;

use App\Models\CodeAnalysis;
use App\Services\ParsedItemService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Throwable;

/**
 * Provides file collection and AST parsing with unified visitor.
 */
class ParserService
{
    /**
     * Initialize the ParserService with necessary dependencies.
     *
     * @param  ParsedItemService  $parsedItemService  The service handling ParsedItem creation.
     */
    public function __construct(protected ParsedItemService $parsedItemService) {}

    /**
     * Collect .php files from config('parsing.files') and config('parsing.folders').
     *
     * @return Collection<string> A collection of PHP file paths.
     */
    public function collectPhpFiles(string $directory = 'app'): Collection
    {
        Log::info('ParserService: Collecting .php files from configuration.');

        $files = config('parsing.files', []);
        $folders = config('parsing.folders', []);

        $fileList = collect($files)
            ->map(fn ($f) => realpath($f))
            ->filter();

        $folderList = collect($folders)
            ->flatMap(fn ($dir) => $this->getPhpFiles($dir));

        $merged = $fileList->merge($folderList)->unique()->values();

        Log::info("ParserService: Total .php files collected => {$merged->count()}");

        return $merged;
    }

    /**
     * Parse a single PHP file with UnifiedAstVisitor, and return the raw AST array.
     * If $useCache is true, checks code_analyses table first.
     *
     * @param  string  $filePath  The path to the PHP file.
     * @param  bool  $useCache  Whether to use cached AST from the database.
     * @return array The parsed AST.
     */
    public function parseFile(string $filePath, bool $useCache = false): array
    {
        $basePath = Config::get('filesystems.base_path');
        $absolutePath = realpath($basePath.DIRECTORY_SEPARATOR.$filePath) ?: $filePath;
        Log::debug("ParserService.parseFile => [{$absolutePath}], useCache={$useCache}");

        if ($useCache) {
            $cached = CodeAnalysis::where('file_path', $filePath)->first();
            if ($cached && ! empty($cached->ast)) {
                Log::info("ParserService: Found cached AST for [{$filePath}].");

                return $cached->ast;
            }
        }

        // Read code
        try {
            $code = File::get($absolutePath);
        } catch (Throwable $throwable) {
            Log::error("ParserService: Failed to read [{$absolutePath}]: {$throwable->getMessage()}");

            return [];
        }

        // Create parser & parse
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = [];
        try {
            $ast = $parser->parse($code);
            if (! $ast) {
                Log::warning("ParserService: AST is null for [{$absolutePath}].");

                return [];
            }
        } catch (Throwable $throwable) {
            Log::error("ParserService: Parse error [{$absolutePath}]: {$throwable->getMessage()}");

            return [];
        }

        // Initialize UnifiedAstVisitor
        $visitor = new UnifiedAstVisitor;
        $visitor->setCurrentFile($absolutePath);

        // Traverse AST with UnifiedAstVisitor
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        // Extract and store parsed items
        $this->extractAndStoreParsedItems($visitor->getParsedItems(), $absolutePath);

        // Optionally store AST in DB
        if ($useCache) {
            try {
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $absolutePath],
                    ['ast' => $ast]
                );
                Log::info("ParserService: Cached AST in DB for [{$absolutePath}].");
            } catch (Throwable $throwable) {
                Log::error("ParserService: Failed caching AST [{$absolutePath}]: {$throwable->getMessage()}");
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
            Log::warning("ParserService.getPhpFiles: Folder not found or invalid => [{$directory}].");

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

        Log::info("ParserService.getPhpFiles => [{$realDir}] => found [".count($phpFiles).'] .php files.');

        return collect($phpFiles);
    }

    /**
     * Extract classes, traits, interfaces, and functions from parsed items and store them.
     *
     * @param  array<int, array<string, mixed>>  $parsedItems  The parsed items from AST.
     * @param  string  $filePath  The path to the PHP file.
     */
    protected function extractAndStoreParsedItems(array $parsedItems, string $filePath): void
    {
        if (empty($parsedItems)) {
            return;
        }

        foreach ($parsedItems as $item) {
            $this->parsedItemService->createParsedItem([
                'type' => $item['type'],
                'name' => $item['name'],
                'fully_qualified_name' => $item['fully_qualified_name'],
                'file_path' => $filePath,
                'line_number' => $item['line_number'],
                // Add more fields if necessary
            ]);
        }

        Log::info('ParserService: Stored '.count($parsedItems)." parsed items for [{$filePath}].");
    }
}
