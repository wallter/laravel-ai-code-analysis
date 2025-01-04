<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Illuminate\Support\Facades\Log;
use SplObjectStorage;

/**
 * Collects both free-floating functions and classes with methods/attributes.
 */
class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    private Collection $items;
    private Collection $warnings;
    private string $currentFile = '';
    private int $astSizeLimit = 1000; // Adjust the limit as needed
    private string $currentClassName = '';
    private string $currentNamespace = '';
    private int $maxDepth = 5; // Adjust the max depth as needed
    private SplObjectStorage $processedNodes;

    public function __construct()
    {
        $this->items = collect();
        $this->warnings = collect();
        $this->processedNodes = new SplObjectStorage();
    }

    /**
     * Return only the discovered classes (filtered from $this->items).
     */
    public function getClasses(): array
    {
        return $this->items
            ->filter(fn($item) => isset($item['type']) && $item['type'] === 'Class')
            ->values()
            ->all();
    }

    /**
     * Return only the discovered functions (filtered from $this->items).
     */
    public function getFunctions(): array
    {
        return $this->items
            ->filter(fn($item) => isset($item['type']) && $item['type'] === 'Function')
            ->values()
            ->all();
    }

    /**
     * Return *all* discovered items (classes & functions).
     */
    public function getItems(): array
    {
        return $this->items->all();
    }

    /**
     * Return any warnings collected.
     */
    public function getWarnings(): array
    {
        return $this->warnings->all();
    }

    /**
     * Sets the current file being parsed.
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node)
    {
        // Handle entering a Namespace
        if ($node instanceof Namespace_) {
            $namespace = $node->name ? $node->name->toString() : '';
            Log::debug("FunctionAndClassVisitor: Entering Namespace - " . $namespace);
            $this->currentNamespace = $namespace;
        }

        // Handle entering a Class
        if ($node instanceof Class_) {
            Log::debug("FunctionAndClassVisitor: Found Class node - " . $node->name->name);
            $classData = $this->collectClassData($node);
            $this->items->push($classData);
            Log::debug("FunctionAndClassVisitor: Pushed Class data. Total items now: " . $this->items->count());
            $this->currentClassName = $node->name->name;
            Log::debug("FunctionAndClassVisitor: Exiting Class node.");
        }

        // Handle entering a Function
        if ($node instanceof Function_) {
            Log::debug("FunctionAndClassVisitor: Found Function node - " . $node->name->name);
            $functionData = $this->collectFunctionData($node);
            $this->items->push($functionData);
            Log::debug("FunctionAndClassVisitor: Pushed Function data. Total items now: " . $this->items->count());
        }
    }

    public function leaveNode(Node $node)
    {
        // Handle leaving a Class
        if ($node instanceof Class_) {
            Log::debug("FunctionAndClassVisitor: Leaving Class node - " . $node->name->name);
            $this->currentClassName = '';
        }

        // Handle leaving a Namespace
        if ($node instanceof Namespace_) {
            Log::debug("FunctionAndClassVisitor: Leaving Namespace - " . $this->currentNamespace);
            $this->currentNamespace = '';
        }
    }

    // -------------------------------------------------
    // Below are the same private methods from your code
    // with minor changes if needed.
    // -------------------------------------------------

    /**
     * Convert the entire AST node into an array (with recursion limit).
     */
    private function astToArray(Node $node, int $currentDepth = 0): array
    {
        // guard max depth
        if ($currentDepth > $this->maxDepth) {
            return [
                'nodeType' => $node->getType(),
                'attributes' => $node->getAttributes(),
                'note' => 'Max depth reached, recursion stopped.',
            ];
        }
        if ($this->processedNodes->contains($node)) {
            return [
                'nodeType' => $node->getType(),
                'attributes' => $node->getAttributes(),
                'note' => 'Recursion detected, node already processed.',
            ];
        }
        $this->processedNodes->attach($node);

        $result = [
            'nodeType' => $node->getType(),
            'attributes' => $this->processAttributes($node->getAttributes(), $currentDepth),
        ];
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if ($subNode instanceof Node) {
                $result[$subNodeName] = $this->astToArray($subNode, $currentDepth + 1);
            } elseif (is_array($subNode)) {
                $result[$subNodeName] = array_map(
                    fn($item) => $item instanceof Node
                        ? $this->astToArray($item, $currentDepth + 1)
                        : (is_object($item)
                            ? $this->objectToArray($item, $currentDepth + 1)
                            : $item),
                    $subNode
                );
            } elseif (is_object($subNode)) {
                $result[$subNodeName] = $this->objectToArray($subNode, $currentDepth + 1);
            } else {
                $result[$subNodeName] = $subNode;
            }
        }

        return $result;
    }

    /**
     * Recursively converts an arbitrary object into an array.
     */
    private function objectToArray(object $obj, int $currentDepth = 0): array
    {
        if ($currentDepth > $this->maxDepth) {
            return ['note' => 'Max depth reached, recursion stopped.'];
        }
        if ($this->processedNodes->contains($obj)) {
            return ['note' => 'Recursion detected, object already processed.'];
        }
        $this->processedNodes->attach($obj);

        $result = [];
        foreach (get_object_vars($obj) as $property => $value) {
            if ($value instanceof Node) {
                $result[$property] = $this->astToArray($value, $currentDepth + 1);
            } elseif (is_array($value)) {
                $result[$property] = array_map(
                    fn($item) => $item instanceof Node
                        ? $this->astToArray($item, $currentDepth + 1)
                        : (is_object($item)
                            ? $this->objectToArray($item, $currentDepth + 1)
                            : $item),
                    $value
                );
            } elseif (is_object($value)) {
                $result[$property] = $this->objectToArray($value, $currentDepth + 1);
            } else {
                $result[$property] = $value;
            }
        }
        return $result;
    }

    private function processAttributes(array $attributes, int $currentDepth = 0): array
    {
        return array_map(function ($value) use ($currentDepth) {
            if ($value instanceof Node) {
                return $this->astToArray($value, $currentDepth + 1);
            } elseif (is_object($value)) {
                return $this->objectToArray($value, $currentDepth + 1);
            } elseif (is_array($value)) {
                return array_map(function ($item) use ($currentDepth) {
                    if ($item instanceof Node) {
                        return $this->astToArray($item, $currentDepth + 1);
                    } elseif (is_object($item)) {
                        return $this->objectToArray($item, $currentDepth + 1);
                    }
                    return $item;
                }, $value);
            }
            return $value;
        }, $attributes);
    }

    private function collectFunctionData(Function_ $node): array
    {
        Log::debug("FunctionAndClassVisitor: Collecting data for Function - " . $node->name->name);
        return [
            'type' => 'Function',
            'name' => $node->name->name,
            'details' => [
                'params' => array_map(function ($param) {
                    return [
                        'name' => '$' . $param->var->name,
                        'type' => $this->typeToString($param->type) ?: 'mixed',
                    ];
                }, $node->params),
                'description' => '', // optional doc extraction if needed
            ],
            'file' => $this->currentFile,
            'line' => $node->getStartLine(),
            'ast'  => $this->astToArray($node),
        ];
    }

    private function collectClassData(Class_ $node): array
    {
        Log::debug("FunctionAndClassVisitor: Collecting data for Class - " . $node->name->name);
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[] = [
                'name'        => $method->name->name,
                'params'      => array_map(function ($param) {
                    return [
                        'name' => '$' . $param->var->name,
                        'type' => $this->typeToString($param->type) ?: 'mixed',
                    ];
                }, $method->params),
                'description' => '',
                'class'       => $this->currentClassName,
                'namespace'   => $this->currentNamespace,
                'line'        => $method->getStartLine(),
            ];
        }

        return [
            'type' => 'Class',
            'name' => $node->name->name,
            'namespace' => $this->currentNamespace,
            'details' => [
                'methods' => $methods,
            ],
            'file' => $this->currentFile,
            'line' => $node->getStartLine(),
            'ast'  => $this->astToArray($node),
        ];
    }

    private function typeToString($typeNode): string
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
