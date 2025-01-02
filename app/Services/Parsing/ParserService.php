<?php

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use App\Services\Parsing\FunctionVisitor;
use App\Services\Parsing\ClassVisitor;
use PhpParser\NodeTraverser;

class ParserService
{
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
}
