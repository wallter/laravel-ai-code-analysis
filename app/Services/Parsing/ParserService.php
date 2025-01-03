<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use App\Models\CodeAnalysis;
use PhpParser\NodeTraverser;

class ParserService
{
    public function __construct()
    {
    }

    /**
     * Collect all PHP files from configured folders and individual files.
     *
     * @return array An array of absolute paths to PHP files.
     */
    public function collectPhpFiles(): array
    {
        $filePaths   = config('parsing.files', []);
        $folderPaths = config('parsing.folders', []);
        $phpFiles = [];

        // Collect PHP files from folders
        foreach ($folderPaths as $folderPath) {
            $realPath = $this->normalizePath($folderPath);
            if (!is_dir($realPath)) {
                continue;
            }
            $folderPhpFiles = $this->getPhpFiles($realPath);
            $phpFiles = array_merge($phpFiles, $folderPhpFiles);
        }

        // Collect individual PHP files
        foreach ($filePaths as $filePath) {
            $realPath = $this->normalizePath($filePath);
            if (!file_exists($realPath)) {
                continue;
            }
            $phpFiles[] = $realPath;
        }

        // Remove duplicate file paths
        return array_unique($phpFiles);
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
     * @return \PhpParser\Parser
     */
    public function createParser()
    {
        return (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Create a new NodeTraverser instance.
     *
     * @return \PhpParser\NodeTraverser
     */
    public function createTraverser()
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new \PhpParser\NodeVisitor\ParentConnectingVisitor());
        return $traverser;
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
        // Check if the file has already been parsed and stored
        $existingAnalysis = CodeAnalysis::where('file_path', $this->normalizePath($filePath))->first();
        if ($existingAnalysis) {
            return json_decode($existingAnalysis->ast, true);
        }

        $parser = $this->createParser();
        $traverser = $this->createTraverser();
        $visitor = new \App\Services\Parsing\ClassVisitor();

        $traverser->addVisitor($visitor);

        $code = file_get_contents($filePath);
        $ast = $parser->parse($code);

        if ($ast === null) {
            throw new \Exception("Failed to parse AST for file: {$filePath}");
        }

        $traverser->traverse($ast);

        return $ast;
    }
}
