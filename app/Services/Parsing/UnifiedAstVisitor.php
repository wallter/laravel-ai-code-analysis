<?php

namespace App\Services\Parsing;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\NodeTraverser;

class UnifiedAstVisitor extends NodeVisitorAbstract
{
    protected string $currentFile = '';
    protected array $items = [];

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            // Collect class-like data
            $classData = $this->collectClassData($node);
            $this->items[] = $classData;
        }

        if ($node instanceof Node\Stmt\Function_) {
            // Collect function data
            $functionData = $this->collectFunctionData($node);
            $this->items[] = $functionData;
        }
    }

    private function collectClassData(Node\Stmt\ClassLike $node): array
    {
        // Existing implementation...
    }

    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        // Existing implementation...
    }

    private function astToArray(Node $node, int $currentDepth = 0): array
    {
        // Existing implementation...
    }

    private function objectToArray(object $obj, int $currentDepth = 0): array
    {
        // Existing implementation...
    }

    private function processAttributes(array $attributes, int $currentDepth = 0): array
    {
        // Existing implementation...
    }

    private function typeToString($typeNode): string
    {
        // Existing implementation...
    }
}
