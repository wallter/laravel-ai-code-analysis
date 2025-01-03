<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use App\Models\CodeAnalysis;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\Parser;
use PhpParser\NodeVisitor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class ParserService
{
    /**
     * @var Parser
     */
    protected Parser $parser;

    /**
     * @var NodeTraverser
     */
    protected NodeTraverser $traverser;

    /**
     * @var NodeVisitor
     */
    protected NodeVisitor $visitor;

    public function __construct()
    {
        $this->parser = $this->createParser();
        $this->traverser = $this->createTraverser();
        $this->visitor = new FunctionAndClassVisitor();
        $this->traverser->addVisitor($this->visitor);
    }

    /**
     * Collect all PHP files from configured folders and individual files.
     *
     * @return Collection An Illuminate\Support\Collection of absolute paths to PHP files.
     */
    public function collectPhpFiles(): Collection
    {
        $filePaths = collect(config('parsing.files', []));
        $folderPaths = collect(config('parsing.folders', []));

        $phpFiles = $folderPaths->map(function ($folderPath) {
            $realPath = $this->normalizePath($folderPath);
            if (!is_dir($realPath)) {
                return collect([]);
            }
            return collect($this->getPhpFiles($realPath));
        })->flatten();

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
     * Retrieve all PHP files recursively from a given directory.
     *
     * @param string $directory
     * @return array
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
     * Normalize a file path.
     *
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path): string
    {
        return realpath($path) ?: $path;
    }

    /**
     * Determine if a given path is absolute.
     *
     * @param string $path
     * @return bool
     */
    public function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(\/|[A-Za-z]:[\/\\\\])/', $path) === 1;
    }

    /**
     * Create a new PHP parser instance using the newest supported version.
     *
     * @return Parser
     */
    public function createParser(): Parser
    {
        return (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Create a new NodeTraverser instance.
     *
     * @return NodeTraverser
     */
    public function createTraverser(): NodeTraverser
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        return $traverser;
    }

    /**
     * Get the configured visitor.
     *
     * @return NodeVisitor
     */
    public function getVisitor(): NodeVisitor
    {
        return $this->visitor;
    }

    /**
     * Parse a single PHP file and return its AST.
     *
     * @param string $filePath
     * @return array
     *
     * @throws \PhpParser\Error
     */
    public function parseFile(string $filePath): array
    {
        if ($existingAnalysis) {
            return collect(json_decode($existingAnalysis->ast, true));
        }

        $code = File::get($filePath);
        $ast = $this->parser->parse($code);

        if ($ast === null) {
            throw new \Exception("Failed to parse AST for file: {$filePath}");
        }

        collect($ast)->each(function ($node) {
            $this->traverser->traverse([$node]);
        });

        return collect($ast);
    }

    /**
     * Setup parser and traverser with visitor.
     *
     * @return void
     */
    public function setupParserTraversal(): void
    {
        $this->traverser = $this->createTraverser();
        $this->visitor = new FunctionAndClassVisitor();
        $this->traverser->addVisitor($this->visitor);
    }
}
