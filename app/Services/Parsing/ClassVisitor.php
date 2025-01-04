<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassLike;

class ClassVisitor extends NodeVisitorAbstract
{
    private array $classes = [];
    private string $currentFile = '';
    private string $currentClassName = '';
    private string $currentNamespace = '';

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $this->currentClassName = $node->name->name;
            $this->currentNamespace = $this->getNamespace($node);
            $this->classes[] = $this->collectClassData($node);
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $this->currentClassName = '';
            $this->currentNamespace = '';
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    private function collectClassData(ClassLike $node): array
    {
        // If needed, parse doc comments
        $description = '';
        $annotations = [];
        $attributes  = [];
        $docComment  = $node->getDocComment();

        // Example minimal approach:
        if ($docComment) {
            $docText = $docComment->getText();
            // parse out short description or annotations, if you want
        }

        // If you want to parse attributes (PHP 8+):
        $attributes = []; // or implement logic as in other visitors

        // Gather methods
        $methods = collect($node->getMethods())
            ->map(fn($method) => $this->collectMethodData($method))
            ->all();

        $className = $node->name->name;
        $namespace = $this->getNamespace($node);
        $fullyQualifiedName = $namespace ? "{$namespace}\\{$className}" : $className;

        return [
            'type'               => 'Class',
            'name'               => $className,
            'namespace'          => $namespace,
            'fullyQualifiedName' => $fullyQualifiedName,
            'details'            => [
                'methods'     => $methods,
                'description' => $description,
            ],
            'annotations'        => $annotations,
            'attributes'         => $attributes,
            'file'               => $this->currentFile,
            'line'               => $node->getStartLine(),
        ];
    }

    private function collectMethodData(Node\Stmt\ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[] = ['name' => $paramName, 'type' => $paramType];
        }

        // If doc extraction is needed:
        $description = '';
        $annotations = [];
        // etc.

        return [
            'name'        => $method->name->name,
            'params'      => $params,
            'description' => $description,
            'annotations' => $annotations,
            'line'        => $method->getStartLine(),
        ];
    }

    private function getNamespace(Node $node): string
    {
        $namespace = '';
        $current = $node;
        while ($current->getAttribute('parent')) {
            $current = $current->getAttribute('parent');
            if ($current instanceof Node\Stmt\Namespace_) {
                $namespace = $current->name ? $current->name->toString() : '';
                break;
            }
        }
        return $namespace;
    }

    private function typeToString($typeNode): string
    {
        if ($typeNode instanceof Node\Identifier) {
            return $typeNode->name;
        } elseif ($typeNode instanceof Node\NullableType) {
            return '?' . $this->typeToString($typeNode->type);
        } elseif ($typeNode instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'typeToString'], $typeNode->types));
        } elseif ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        } else {
            return 'mixed';
        }
    }
}