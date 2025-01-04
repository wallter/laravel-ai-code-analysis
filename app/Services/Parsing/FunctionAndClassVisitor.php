<?php

namespace App\Services\Parsing;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Class_;

class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    protected string $currentFile = '';
    protected array $classes = [];
    protected array $functions = [];

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getItems(): array
    {
        return [
            'classes' => $this->classes,
            'functions' => $this->functions,
        ];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $classData = $this->collectClassData($node);
            $this->classes[] = $classData;
        }

        if ($node instanceof Function_) {
            $functionData = $this->collectFunctionData($node);
            $this->functions[] = $functionData;
        }
    }

    private function collectClassData(Class_ $node): array
    {
        // Existing implementation...
    }

    private function collectFunctionData(Function_ $node): array
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
