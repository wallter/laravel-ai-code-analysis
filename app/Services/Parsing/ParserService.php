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
    private Collection $items;
    private Collection $warnings;
    private SplObjectStorage $processedNodes;

    public function __construct()
    {
        $this->items = collect();
        $this->warnings = collect();
        $this->processedNodes = new SplObjectStorage();
    }

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
        Log::info('Starting collection of PHP files.');

        $filesConfig   = config('parsing.files', []);
        $foldersConfig = config('parsing.folders', []);
        
        $filePaths   = collect($filesConfig);
        $folderPaths = collect($foldersConfig);

        // Collect from folders
        $phpFiles = $folderPaths
            ->map(function ($folderPath) {
                $realPath = $this->normalizePath($folderPath);
                if (!is_dir($realPath)) {
                    Log::warning('Invalid directory path.', ['path' => $realPath]);
                    return collect([]);
                }
                return collect($this->getPhpFiles($realPath));
            })
            ->flatten();

        // Collect individual files
        $individualFiles = $filePaths->map(function ($filePath) {
            $realPath = $this->normalizePath($filePath);
            if (!file_exists($realPath)) {
                Log::warning('File does not exist.', ['file' => $realPath]);
                return null;
            }
            // Ensure the file has a .php extension
            return (strtolower(pathinfo($realPath, PATHINFO_EXTENSION)) === 'php') ? $realPath : null;
        })->filter();

        $collectedFiles = $phpFiles->merge($individualFiles)->unique()->values();

        $count = $collectedFiles->count();
        Log::info('Collected PHP files.', ['count' => $count, 'files' => $collectedFiles]);

        return $collectedFiles;
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

        Log::info('Starting to parse file.', ['file' => $filePath]);

        // Attempt to retrieve cached AST, if enabled
        if ($useCache) {
            $existingAnalysis = CodeAnalysis::where('file_path', $filePath)->first();
            if ($existingAnalysis) {
                Log::info('Using cached AST.', ['file' => $filePath]);
                return json_decode($existingAnalysis->ast, true) ?? [];
            }
        }

        // Set context for file parsing
        Context::add('file_path', $filePath);

        try {
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
                Log::info('Stored AST in database.', ['file' => $filePath]);
            }

        } catch (\Exception $e) {
            Log::error('Error parsing file.', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Remove context after parsing
        Context::forget('file_path');

        Log::info('Successfully parsed file.', ['file' => $filePath]);

        return $ast;
    }

    /**
     * Recursively retrieve all .php files from a directory.
     */
    public function getPhpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            Log::warning('Directory does not exist.', ['directory' => $directory]);
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $phpFiles[] = $file->getRealPath();
                Log::debug('Found PHP file.', ['file' => $file->getRealPath()]);
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

    /**
     * Extract individual functions from a PHP file.
     *
     * @param string $filePath
     * @return array An array of functions with 'name' and 'ast' keys.
     */
    public function getFunctionsFromFile(string $filePath): array
    {
        try {
            $ast = $this->parseFile($filePath);

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

            $count = count($functions);
            Log::info('Extracted functions from file.', ['file' => $filePath, 'function_count' => $count]);

            return $functions;
        } catch (\Exception $e) {
            Log::error("Failed to extract functions from file.", [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
