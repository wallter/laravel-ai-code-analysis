<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;
use PhpParser\Modifiers;

/**
 * Collects both free-floating functions and classes with methods/attributes.
 */
class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    private Collection $items;
    private Collection $warnings;
    private string $currentFile;
    private int $astSizeLimit = 1000; // Adjust the limit as needed
    private string $currentClassName;
    private string $currentNamespace;
    private int $maxDepth = 5; // Adjust the max depth as needed

    public function __construct()
    {
        $this->items = collect();
        $this->warnings = collect();
    } // Properties with default values are already initialized above

    /**
     * Returns all discovered items (functions, classes, etc.).
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items->all();
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClassName = $node->name->name;
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : '';
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClassName = '';
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = '';
        }
    }

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function setCurrentClassName(string $className): void
    {
        $this->currentClassName = $className;
    }

    public function setCurrentNamespace(string $namespace): void
    {
        $this->currentNamespace = $namespace;
    }

    public function setMaxDepth(int $depth): void
    {
        $this->maxDepth = $depth;
    }

    public function setAstSizeLimit(int $limit): void
    {
        $this->astSizeLimit = $limit;
    }

    /**
     * Returns all collected warnings.
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings->all();
    }
    
    /**
     * Convert an attribute argument node to string.
     *
     * @param Node $node
     * @return string
     */
    private function argToString(Node $node): string
    {
        if ($node instanceof Node\Scalar\String_) {
            return '"' . $node->value . '"';
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return (string) $node->value;
        } elseif ($node instanceof Node\Expr\Array_) {
            return $this->parseArray($node);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $node->name->toString();
        }
        return '...';
    }

    /**
     * Parse an array node into a string representation.
     *
     * @param Node\Expr\Array_ $array
     * @return string
     */
    private function parseArray(Node\Expr\Array_ $array): string
    {
        $elements = [];
        foreach ($array->items as $item) {
            $key = $item->key ? $this->argToString($item->key) . ' => ' : '';
            $value = $this->argToString($item->value);
            $elements[] = $key . $value;
        }
            return '[' . implode(', ', $elements) . ']';
    }

    /**
     * Convert type node to string.
     *
     * @param mixed $typeNode
     * @return string
     */
    private function typeToString($typeNode): string
    {
        if ($typeNode instanceof Node\Identifier) {
            return $typeNode->name;
        } elseif ($typeNode instanceof Node\NullableType) {
            return '?' . $this->typeToString($typeNode->type);
        } elseif ($typeNode instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'typeToString'], $typeNode->types));
        } elseif ($typeNode instanceof Node\IntersectionType) {
            return implode('&', array_map([$this, 'typeToString'], $typeNode->types));
        } elseif ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        } else {
            return 'mixed';
        }
    }
}
