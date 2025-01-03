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
 * UnifiedAstVisitor
 *  - Collects classes (and methods) + free-floating functions
 *  - Extracts docblock short descriptions & annotations (e.g. @url).
 *  - Minimizes duplication by doing it all in one pass.
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

            $item = [
                'type'      => 'Class',
                'name'      => $className,
                'namespace' => $this->currentNamespace,
                'annotations' => $docInfo['annotations'],
                'description' => $docInfo['shortDescription'],
                'details'   => [
                    'methods' => $methods,
                ],
                'file'      => $this->currentFile,
                'line'      => $node->getStartLine(),
            ];
            $this->items->push($item);
        }

        // Collect free-floating functions (not in a class)
        if ($node instanceof Function_) {
            $docInfo = $this->extractDocInfo($node);
            $item = [
                'type'    => 'Function',
                'name'    => $node->name->name,
                'annotations' => $docInfo['annotations'],
                'details' => [
                    'params'       => $this->collectFunctionParams($node),
                    'description'  => $docInfo['shortDescription'],
                ],
                'file'    => $this->currentFile,
                'line'    => $node->getStartLine(),
            ];
            $this->items->push($item);
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

    /**
     * Extract docblock info (short description, annotations) for any Node with a doc comment.
     */
    protected function extractDocInfo(Node $node): array
    {
        $docComment = $node->getDocComment();
        if (!$docComment) {
            return [
                'shortDescription' => '',
                'annotations'      => [],
            ];
        }
        return DocblockParser::parseDocblock($docComment->getText());
    }

    /**
     * Collect method info from a ClassLike node.
     */
    protected function collectMethods(ClassLike $node): array
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $mDoc  = $this->extractDocInfo($method);
            $methods[] = [
                'name'        => $method->name->name,
                'description' => $mDoc['shortDescription'],
                'annotations' => $mDoc['annotations'],
                'params'      => $this->collectMethodParams($method),
                'line'        => $method->getStartLine(),
            ];
        }
        return $methods;
    }

    protected function collectMethodParams(Node\Stmt\ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $p) {
            $pName = '$' . $p->var->name;
            $pType = $p->type ? $this->typeToString($p->type) : 'mixed';
            $params[] = [
                'name' => $pName,
                'type' => $pType,
            ];
        }
        return $params;
    }

    protected function collectFunctionParams(Function_ $function): array
    {
        $params = [];
        foreach ($function->params as $p) {
            $pName = '$' . $p->var->name;
            $pType = $p->type ? $this->typeToString($p->type) : 'mixed';
            $params[] = [
                'name' => $pName,
                'type' => $pType,
            ];
        }
        return $params;
    }

    /**
     * Convert type nodes (nullable, union, or simple) to strings.
     */
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
}