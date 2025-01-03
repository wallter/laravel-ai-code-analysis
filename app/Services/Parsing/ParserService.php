<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\NodeVisitor;
use App\Models\CodeAnalysis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

/**
 * Provides helper methods to collect, parse, and optionally store AST data.
 */
class ParserService
{
    /**
     * Create a new PHP parser instance using the newest supported version.
     */
    public function createParser(): Parser
    {
        return (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Create a new NodeTraverser instance, optionally adding parent/connecting visitors if desired.
     */
    public function createTraverser(array $visitors = []): NodeTraverser
    {
        $traverser = new NodeTraverser();
        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        return $traverser;
    }

    /**
     * Collect all PHP files from configured folders and individual files.
     */
    public function collectPhpFiles(): Collection
    {
        $filesConfig   = config('parsing.files', []);
        $foldersConfig = config('parsing.folders', []);
        
        $filePaths   = collect($filesConfig);
        $folderPaths = collect($foldersConfig);

        // Collect from folders
        $phpFiles = $folderPaths
            ->map(function ($folderPath) {
                $realPath = $this->normalizePath($folderPath);
                if (!is_dir($realPath)) {
                    return collect([]);
                }
                return collect($this->getPhpFiles($realPath));
            })
            ->flatten();

        // Collect individual files
        $individualFiles = $filePaths->map(function ($filePath) {
            $realPath = $this->normalizePath($filePath);
            if (!file_exists($realPath)) {
                return null;
            }
            return $realPath;
        })->filter();

        return $phpFiles->merge($individualFiles)->unique()->values();
    }

    /**
     * Parse a single PHP file into an AST, using the parser + any visitors desired.
     *
     * @param string          $filePath
     * @param NodeVisitor[]   $visitors  Additional visitors you want to run on this parse.
     * @param bool            $useCache  If true, attempt to load and store AST in CodeAnalysis model.
     * @return array          The raw AST array returned by PhpParser (not the visitors' data).
     *
     * @throws \PhpParser\Error|\Exception
     */
    public function parseFile(string $filePath, array $visitors = [], bool $useCache = false): array
    {
        $filePath = $this->normalizePath($filePath);

        // Attempt to retrieve cached AST, if enabled
        if ($useCache) {
            $existingAnalysis = CodeAnalysis::where('file_path', $filePath)->first();
            if ($existingAnalysis) {
                return json_decode($existingAnalysis->ast, true) ?? [];
            }
        }

        // Read source code
        $code = File::get($filePath);
        $parser = $this->createParser();
        $ast = $parser->parse($code);

        if ($ast === null) {
            throw new \Exception("Failed to parse AST for file: {$filePath}");
        }

        // If you have visitors, traverse the AST with them
        if (!empty($visitors)) {
            $traverser = $this->createTraverser($visitors);
            // The final result from traverse() is often the transformed AST, but we discard in this example.
            $traverser->traverse($ast);
        }

        // Optionally store the AST in DB
        if ($useCache) {
            CodeAnalysis::updateOrCreate(
                [ 'file_path' => $filePath ],
                [ 'ast' => json_encode($ast) ]
            );
        }

        return $ast;
    }

    /**
     * Recursively retrieve all .php files from a directory.
     */
    public function getPhpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
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
     * Normalize a path to absolute form if possible.
     */
    public function normalizePath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}