<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use Illuminate\Support\Collection;

/**
 * Single-pass visitor collecting classes (incl. traits, interfaces), and functions.
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

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node)
    {
        // Track namespace
        if ($node instanceof Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : null;
        }

        // Collect classes, traits, and interfaces
        if ($node instanceof ClassLike && $node->name !== null) {
            $type = $this->resolveClassLikeType($node);
            $docInfo = $this->extractDocInfo($node);
            $methods = $this->collectMethods($node);

            $this->items->push([
                'type'       => $type, // "Class", "Trait", or "Interface"
                'name'       => (string) $node->name,
                'namespace'  => $this->currentNamespace,
                'annotations'=> $docInfo['annotations'],
                'description'=> $docInfo['shortDescription'],
                'details'    => ['methods' => $methods],
                'file'       => $this->currentFile,
                'line'       => $node->getStartLine(),
            ]);
        }

        // Collect free-floating functions
        if ($node instanceof Function_) {
            $docInfo = $this->extractDocInfo($node);
            $params  = $this->collectFunctionParams($node);

            $this->items->push([
                'type'       => 'Function',
                'name'       => $node->name->name,
                'annotations'=> $docInfo['annotations'],
                'details'    => [
                    'params'      => $params,
                    'description' => $docInfo['shortDescription'],
                ],
                'file'       => $this->currentFile,
                'line'       => $node->getStartLine(),
            ]);
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = null;
        }
    }

    public function getItems(): array
    {
        return $this->items->all();
    }

    // -----------------------------------------------------
    // PRIVATE/PROTECTED METHODS
    // -----------------------------------------------------

    protected function resolveClassLikeType(ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'Interface';
        } elseif ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'Trait';
        } elseif ($node instanceof \PhpParser\Node\Stmt\Class_) {
            // Could also distinguish abstract/final if needed
            return 'Class';
        }
        
        return 'Class';
    }

    protected function collectMethods(ClassLike $node): array
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $mDoc   = $this->extractDocInfo($method);
            $params = $this->collectMethodParams($method);

            $methods[] = [
                'name'        => $method->name->name,
                'description' => $mDoc['shortDescription'],
                'annotations' => $mDoc['annotations'],
                'params'      => $params,
                'line'        => $method->getStartLine(),
            ];
        }
        return $methods;
    }

    protected function collectMethodParams(Node\Stmt\ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $p) {
            $params[] = [
                'name' => '$' . $p->var->name,
                'type' => $p->type ? $this->typeToString($p->type) : 'mixed',
            ];
        }
        return $params;
    }

    protected function collectFunctionParams(Function_ $function): array
    {
        $params = [];
        foreach ($function->params as $p) {
            $params[] = [
                'name' => '$' . $p->var->name,
                'type' => $p->type ? $this->typeToString($p->type) : 'mixed',
            ];
        }
        return $params;
    }

    protected function typeToString($typeNode): string
    {
        if ($typeNode instanceof Node\Identifier) {
            return $typeNode->name;
        }
        if ($typeNode instanceof Node\NullableType) {
            return '?' . $this->typeToString($typeNode->type);
        }
        if ($typeNode instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'typeToString'], $typeNode->types));
        }
        if ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        }
        return 'mixed';
    }

    protected function extractDocInfo(Node $node): array
    {
        $docComment = $node->getDocComment();
        if (!$docComment) {
            return [
                'shortDescription' => '',
                'annotations'      => [],
            ];
        }

        // Minimal docblock parse
        return DocblockParser::parseDocblock($docComment->getText());
    }
}