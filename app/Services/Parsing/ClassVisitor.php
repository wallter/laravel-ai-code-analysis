<?php

namespace App\Services\Parsing;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

class ClassVisitor extends NodeVisitorAbstract
{
    protected string $currentFile = '';
    protected array $classes = [];

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            // Collect class data...
            $classData = $this->collectClassData($node);
            $this->classes[] = $classData;
        }

        if ($node instanceof ClassMethod) {
            // Collect method data...
            $methodData = $this->collectMethodData($node);
            // Associate with current class...
            if (!empty($this->classes)) {
                end($this->classes);
                $this->classes[key($this->classes)]['methods'][] = $methodData;
            }
        }
    }

    private function collectClassData(ClassLike $node): array
    {
        // Existing implementation...
    }

    private function collectMethodData(ClassMethod $method): array
    {
        // Existing implementation...
    }

    private function getNamespace(Node $node): string
    {
        // Existing implementation...
    }

    private function typeToString($typeNode): string
    {
        // Existing implementation...
    }
}
