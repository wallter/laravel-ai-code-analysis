<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Illuminate\Support\Collection;

/**
 * Gathers classes (with methods) and free-floating functions in a single pass.
 */
class UnifiedAstVisitor extends NodeVisitorAbstract
{
    protected Collection $items;
    protected ?string $currentFile      = null;
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

        // Collect classes/traits/interfaces
        if ($node instanceof ClassLike && $node->name !== null) {
            $className  = $node->name->toString();
            $docInfo    = $this->extractDocInfo($node);
            $methods    = $this->collectMethods($node);

            $this->items->push([
                'type'       => 'Class',
                'name'       => $className,
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
        return $this->items->values()->all();
    }

    // -----------------------------------------------------
    // PRIVATE/PROTECTED HELPER METHODS
    // -----------------------------------------------------

    protected function extractDocInfo(Node $node): array
    {
        $docComment = $node->getDocComment();
        if (!$docComment) {
            return ['shortDescription' => '', 'annotations' => []];
        }
        // Example: parse docblock if desired
        return DocblockParser::parseDocblock($docComment->getText());
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
                'name' => '$'.$p->var->name,
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
                'name' => '$'.$p->var->name,
                'type' => $p->type ? $this->typeToString($p->type) : 'mixed',
            ];
        }
        return $params;
    }

    protected function typeToString($typeNode): string
    {
        if ($typeNode instanceof Node\Identifier) {
            return $typeNode->name;
        } elseif ($typeNode instanceof Node\NullableType) {
            return '?' . $this->typeToString($typeNode->type);
        } elseif ($typeNode instanceof Node\UnionType) {
            return implode('|', array_map([$this, 'typeToString'], $typeNode->types));
        } elseif ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        } 
        return 'mixed';
    }
}