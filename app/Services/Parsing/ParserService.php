<?php

namespace App\Services\Parsing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use App\Models\CodeAnalysis;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Exception;

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

        $fileList = collect($files)->map(fn($f) => realpath($f))->filter();
        $folderList = collect($folders)->flatMap(fn($dir) => $this->getPhpFiles($dir));

        $merged = $fileList->merge($folderList)->unique()->values();
        Log::info("ParserService: total .php files => {$merged->count()}");
        return $merged;
    }

    /**
     * Parse a single PHP file with optional visitors, and return the raw AST array.
     * If $useCache is true, we check code_analyses table first.
     *
     * @param string $filePath The path to the PHP file to parse.
     * @param array $visitors Optional array of NodeVisitor instances.
     * @param bool $useCache Whether to use cached AST from the database.
     * @return array The parsed AST.
     */
    public function parseFile(string $filePath, array $visitors = [], bool $useCache = false): array
    {
        $realPath = realpath($filePath) ?: $filePath;
        Log::debug("ParserService.parseFile => [{$realPath}], useCache={$useCache}");

        if ($useCache) {
            $cached = CodeAnalysis::where('file_path', $realPath)->first();
            if ($cached && !empty($cached->ast)) {
                Log::info("ParserService: Found cached AST for [{$realPath}].");
                return $cached->ast;
            }
        }

        // Read code
        try {
            $code = File::get($realPath);
        } catch (\Throwable $ex) {
            Log::error("ParserService: failed to read [{$realPath}]: " . $ex->getMessage());
            return [];
        }

        // Create parser & parse
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = [];
        try {
            $ast = $parser->parse($code);
            if (!$ast) {
                Log::warning("ParserService: AST is null for [{$realPath}].");
                return [];
            }
        } catch (\Throwable $e) {
            Log::error("ParserService: parse error [{$realPath}]: " . $e->getMessage());
            return [];
        }

        // Optionally traverse with visitors
        if (!empty($visitors)) {
            $traverser = new NodeTraverser();
            foreach ($visitors as $v) {
                if (method_exists($v, 'setCurrentFile')) {
                    $v->setCurrentFile($realPath);
                }
                $traverser->addVisitor($v);
            }
            $traverser->traverse($ast);
        }

        // Optionally store AST in DB
        if ($useCache) {
            try {
                CodeAnalysis::updateOrCreate(
                    ['file_path' => $realPath],
                    ['ast' => $ast]
                );
                Log::info("ParserService: Cached AST in DB for [{$realPath}].");
            } catch (\Throwable $e) {
                Log::error("ParserService: failed caching AST [{$realPath}]: " . $e->getMessage());
            }
        }

        return $ast;
    }

    /**
     * Recursively get .php files from a directory.
     *
     * @param string $directory The directory path to search.
     * @return Collection<string> A collection of PHP file paths.
     */
    protected function getPhpFiles(string $directory): Collection
    {
        $realDir = realpath($directory);
        if (!$realDir || !is_dir($realDir)) {
            Log::warning("ParserService.getPhpFiles: folder not found or invalid => [{$directory}].");
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

        Log::info("ParserService.getPhpFiles => [{$realDir}] => found [".count($phpFiles)."] .php files.");
        return collect($phpFiles);
    }
}
