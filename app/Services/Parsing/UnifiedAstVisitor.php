<?php

declare(strict_types=1);

namespace App\Services\Parsing;

use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

/**
 * Single-pass visitor collecting classes (traits, interfaces) and free-floating functions.
 * Captures docblocks, methods, parameters, etc.
 */
class UnifiedAstVisitor extends NodeVisitorAbstract
{
    protected Collection $items;

    protected ?string $currentFile = null;

    protected ?string $currentNamespace = null;

    public function __construct()
    {
        $this->items = collect();
    }

    /**
     * Set the current file being parsed; used for referencing in output.
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    /**
     * Called when entering a node in the AST.
     */
    public function enterNode(Node $node): ?Node
    {
        // Track namespace
        if ($node instanceof Namespace_) {
            $this->currentNamespace = $node->name
                ? $node->name->toString()
                : null;
        }

        // Collect Class, Trait, or Interface
        if ($node instanceof ClassLike && $node->name !== null) {
            $type = $this->resolveClassLikeType($node);
            $docInfo = $this->extractDocInfo($node);
            $methods = $this->collectMethods($node);

            $this->items->push([
                'type' => $type, // "Class", "Trait", or "Interface"
                'name' => $node->name->toString(),
                'namespace' => $this->currentNamespace,
                'annotations' => $docInfo['annotations'],
                'description' => $docInfo['shortDescription'],
                'details' => [
                    'methods' => $methods,
                ],
                'file' => $this->currentFile,
                'line_number' => $node->getStartLine(),
            ]);
        }

        // Collect free-floating functions
        if ($node instanceof Function_) {
            $docInfo = $this->extractDocInfo($node);
            $params = $this->collectFunctionParams($node);

            $this->items->push([
                'type' => 'Function',
                'name' => $node->name->toString(),
                'annotations' => $docInfo['annotations'],
                'details' => [
                    'params' => $params,
                    'description' => $docInfo['shortDescription'],
                ],
                'file' => $this->currentFile,
                'line_number' => $node->getStartLine(),
            ]);
        }

        return null;
    }

    /**
     * Called when leaving a node.
     */
    public function leaveNode(Node $node): ?Node
    {
        // Reset namespace after leaving a Namespace_ node
        if ($node instanceof Namespace_) {
            $this->currentNamespace = null;
        }

        return null;
    }

    /**
     * Returns an array of all collected items (classes, traits, interfaces, functions).
     */
    public function getItems(): array
    {
        return $this->items->all();
    }

    // -------------------------------------------------------------------
    // Below are private/protected helper methods for docblock, etc.
    // -------------------------------------------------------------------

    /**
     * Distinguish if a node is a Class, Trait, or Interface.
     */
    protected function resolveClassLikeType(ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'Interface';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'Trait';
        }

        return 'Class'; // default fallback
    }

    /**
     * Gather methods from a ClassLike node, extracting doc info & parameters.
     */
    protected function collectMethods(ClassLike $node): array
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $mDoc = $this->extractDocInfo($method);
            $params = $this->collectMethodParams($method);

            $methods[] = [
                'name' => $method->name->toString(),
                'description' => $mDoc['shortDescription'],
                'annotations' => $mDoc['annotations'],
                'params' => $params,
                'line' => $method->getStartLine(),
            ];
        }

        return $methods;
    }

    /**
     * Collect parameters from a class method.
     */
    protected function collectMethodParams(Node\Stmt\ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $p) {
            $params[] = [
                'name' => '$'.$p->var->name,
                'type' => $p->type ? $this->typeToString($p->type) : 'mixed',
            ];
        }

        return $params;
    }

    /**
     * Collect parameters from a free-floating function.
     */
    protected function collectFunctionParams(Function_ $function): array
    {
        $params = [];
        foreach ($function->params as $p) {
            $params[] = [
                'name' => '$'.$p->var->name,
                'type' => $p->type ? $this->typeToString($p->type) : 'mixed',
            ];
        }

        return $params;
    }

    /**
     * Convert a PhpParser type node into a string representation.
     */
    protected function typeToString($typeNode): string
    {
        if ($typeNode instanceof Node\Identifier) {
            return $typeNode->name;
        }

        if ($typeNode instanceof Node\NullableType) {
            return '?'.$this->typeToString($typeNode->type);
        }

        if ($typeNode instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'typeToString'], $typeNode->types));
        }

        if ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        }

        return 'mixed';
    }

    /**
     * Extract docblock info (short description + annotations) from a node.
     */
    protected function extractDocInfo(Node $node): array
    {
        $docComment = $node->getDocComment();
        if (! $docComment) {
            return [
                'shortDescription' => '',
                'annotations' => [],
            ];
        }

        // Minimal docblock parse
        return DocblockParser::parseDocblock($docComment->getText());
    }
}
