<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use App\Models\CodeAnalysis;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

/**
 * Provides file collection and single-visitor parsing.
 */
class ParserService
{
    /**
     * Collect .php files from config:
     *   config('parsing.files') and config('parsing.folders')
     */
    public function collectPhpFiles(): Collection
    {
        Log::info("Collecting PHP files from configuration.");

        $files   = config('parsing.files', []);
        $folders = config('parsing.folders', []);

        $filePaths   = collect($files)->map(fn($f) => realpath($f))->filter();
        $folderFiles = collect($folders)->flatMap(fn($dir) => $this->getPhpFiles($dir));

        $merged = $filePaths->merge($folderFiles)->unique()->values();

        Log::info("Collected PHP files.", ['count' => $merged->count()]);
        return $merged;
    }

    /**
     * Parse a single file and return discovered items (classes, traits, functions).
     */
    public function parseFileForItems(string $filePath, bool $useCache = false): array
    {
        // Use the same parser method but attach UnifiedAstVisitor
        $visitor = new UnifiedAstVisitor();
        $this->parseFile($filePath, [$visitor], $useCache);
        return $visitor->getItems();
    }

    /**
     * Parse a PHP file with optional visitors.
     */
    public function parseFile(
        string $filePath,
        array $visitors = [],
        bool $useCache = false
    ): array {
        $filePath = realpath($filePath) ?: $filePath;

        // If caching is desired
        if ($useCache) {
            $cached = CodeAnalysis::where('file_path', $filePath)->first();
            if ($cached && !empty($cached->ast)) {
                Log::info("Found cached AST for file.", ['filePath' => $filePath]);
                return json_decode($cached->ast, true) ?? [];
            }
        }

        // Read code
        $code = File::get($filePath);

        // Parse
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        if (!$ast) {
            throw new \Exception("Failed to parse AST for file: {$filePath}");
        }

        // Traverse
        if (!empty($visitors)) {
            $traverser = new NodeTraverser();
            foreach ($visitors as $v) {
                // Let visitor know the current file
                if (method_exists($v, 'setCurrentFile')) {
                    $v->setCurrentFile($filePath);
                }
                $traverser->addVisitor($v);
            }
            $traverser->traverse($ast);
        }

        // Optionally store the AST
        if ($useCache) {
            CodeAnalysis::updateOrCreate(
                ['file_path' => $filePath],
                ['ast' => json_encode($ast)]
            );
        }

        return $ast;
    }

    /**
     * Recursively get .php files from a directory.
     */
    protected function getPhpFiles(string $directory): Collection
    {
        $realDir = realpath($directory);
        if (!$realDir || !is_dir($realDir)) {
            Log::warning("Folder does not exist.", ['folder' => $directory]);
            return collect();
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $phpFiles[] = $file->getRealPath();
            }
        }
        Log::info("Retrieved PHP files from directory.", [
            'directory' => $realDir,
            'count'     => count($phpFiles),
        ]);

        return collect($phpFiles);
    }
}