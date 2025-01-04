<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\NodeVisitor;
use PhpParser\NodeFinder;
use App\Models\CodeAnalysis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node;
use Illuminate\Support\Facades\Context;

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
        Log::info("Collecting PHP files from configuration.");
        $filesConfig   = config('parsing.files', []);
        $foldersConfig = config('parsing.folders', []);
        
        $filePaths   = collect($filesConfig);
        $folderPaths = collect($foldersConfig);


        // Collect from folders
        $phpFiles = $folderPaths
            ->map(function ($folderPath) {
                $realPath = $this->normalizePath($folderPath);
                if (!is_dir($realPath)) {
                    Log::warning("Folder does not exist or is not a directory.", ['folder' => $realPath]);
                    return collect([]);
                }
                $phpFiles = $this->getPhpFiles($realPath);
                return collect($phpFiles);
            })
            ->flatten();

        // Collect individual files
        $individualFiles = $filePaths->map(function ($filePath) {
            $realPath = $this->normalizePath($filePath);
            if (!file_exists($realPath)) {
                Log::warning("File does not exist.", ['file' => $realPath]);
                return null;
            }
            // Ensure the file has a .php extension
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            if ($extension !== 'php') {
                Log::warning("File does not have a .php extension.", ['file' => $realPath, 'extension' => $extension]);
                return null;
            }
            return $realPath;
        })->filter();

        $mergedFiles = $phpFiles->merge($individualFiles)->unique()->values();
        Log::info("Collected PHP files.", ['count' => $mergedFiles->count()]);
        return $mergedFiles;
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
    public function parseFile(string $filePath, array $visitors = [], bool $useCache = true): array
    {
        Log::info("Starting to parse file.", ['filePath' => $filePath]);
        $filePath = $this->normalizePath($filePath);

        // Attempt to retrieve cached AST, if enabled
        if ($useCache) {
            $existingAnalysis = CodeAnalysis::where('file_path', $filePath)->first();
            if ($existingAnalysis) {
                Log::info("Found cached AST for file.", ['filePath' => $filePath]);
                return json_decode($existingAnalysis->ast, true) ?? [];
            }
        }

        // Set context for file parsing
        Context::add('file_path', $filePath);

        // Read source code
        try {
            $code = File::get($filePath);
        } catch (\Exception $e) {
            Log::error("Failed to read file.", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            Context::forget('file_path');
            throw $e;
        }

        $parser = $this->createParser();
        try {
            $ast = $parser->parse($code);
        } catch (\PhpParser\Error $e) {
            Log::error("Failed to parse AST for file.", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            Context::forget('file_path');
            throw $e;
        }

        if ($ast === null) {
            Log::error("AST parsing returned null.", ['filePath' => $filePath]);
            Context::forget('file_path');
            throw new \Exception("Failed to parse AST for file: {$filePath}");
        }

        // If you have visitors, traverse the AST with them
        if (!empty($visitors)) {
            $traverser = $this->createTraverser($visitors);
            $traverser->traverse($ast);
        }

        // Optionally store the AST in DB
        if ($useCache) {
            try {
                CodeAnalysis::updateOrCreate(
                    [ 'file_path' => $filePath ],
                    [ 'ast' => json_encode($ast) ]
                );
                Log::info("Stored AST in cache.", ['filePath' => $filePath]);
            } catch (\Exception $e) {
                Log::error("Failed to store AST in cache.", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            }
        }

        // Remove context after parsing
        Context::forget('file_path');

        return $ast;
    }

    /**
     * Recursively retrieve all .php files from a directory.
     */
    public function getPhpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            Log::warning("Directory does not exist.", ['directory' => $directory]);
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $realPath = $file->getRealPath();
                $phpFiles[] = $realPath;
            }
        }
        Log::info("Retrieved PHP files from directory.", ['directory' => $directory, 'count' => count($phpFiles)]);
        return $phpFiles;
    }

    /**
     * Normalize a path to absolute form if possible.
     */
    public function normalizePath(string $path): string
    {
        $normalizedPath = realpath($path) ?: $path;
        return $normalizedPath;
    }

    /**
     * Extract individual functions from a PHP file.
     *
     * @param string $filePath
     * @return array An array of functions with 'name' and 'ast' keys.
     */
    public function getFunctionsFromFile(string $filePath): array
    {
        Log::info("Extracting functions from file.", ['filePath' => $filePath]);
        try {
            $ast = $this->parseFile($filePath);
        } catch (\Exception $e) {
            Log::error("Failed to parse file for function extraction.", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            return [];
        }

        $nodeFinder = new NodeFinder();
        /** @var Function_[] $functionNodes */
        $functionNodes = $nodeFinder->findInstanceOf($ast, Function_::class);

        $functions = [];

        foreach ($functionNodes as $func) {
            $functions[] = [
                'name' => $func->name->toString(),
                'ast'  => $func,
            ];
        }

        Log::info("Extracted functions from file.", ['filePath' => $filePath, 'function_count' => count($functions)]);
        return $functions;
    }
}
