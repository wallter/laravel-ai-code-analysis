<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use SplObjectStorage;

/**
 * Collects both free-floating functions and classes with methods/attributes.
 */
class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    private Collection $items;
    private Collection $warnings;
    private string $currentFile;
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
     * Recursively converts a PhpParser Node into a JSON string.
     *
     * @param Node $node
     * @param int $currentDepth
     * @return string
     */
    private function astToJson(Node $node, int $currentDepth = 0): string
    {
        // Check for maximum depth to prevent deep recursion
        if ($currentDepth > $this->maxDepth) {
            return json_encode([
                'nodeType' => $node->getType(),
                'attributes' => $node->getAttributes(),
                'note' => 'Max depth reached, recursion stopped.',
            ]) ?: '{}';
        }

        // Detect and prevent processing the same node multiple times
        if ($this->processedNodes->contains($node)) {
            return json_encode([
                'nodeType' => $node->getType(),
                'attributes' => $node->getAttributes(),
                'note' => 'Recursion detected, node already processed.',
            ]) ?: '{}';
        }

        // Mark the current node as processed
        $this->processedNodes->attach($node);

        $result = [
            'nodeType' => $node->getType(),
            'attributes' => $node->getAttributes(),
        ];

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if ($subNode instanceof Node) {
                $subNodeJson = $this->astToJson($subNode, $currentDepth + 1);
                if ($subNodeJson === false) {
                    // Handle json_encode failure
                    $result[$subNodeName] = [
                        'nodeType' => $subNode->getType(),
                        'attributes' => $subNode->getAttributes(),
                        'note' => 'Failed to encode AST node to JSON.',
                    ];
                } else {
                    $result[$subNodeName] = json_decode($subNodeJson, true);
                }
            } elseif (is_array($subNode)) {
                $result[$subNodeName] = array_map(function ($item) use ($currentDepth) {
                    if ($item instanceof Node) {
                        $itemJson = $this->astToJson($item, $currentDepth + 1);
                        return $itemJson !== false ? json_decode($itemJson, true) : [
                            'nodeType' => $item->getType(),
                            'attributes' => $item->getAttributes(),
                            'note' => 'Failed to encode AST node to JSON.',
                        ];
                    }
                    return $item;
                }, $subNode);
            } else {
                $result[$subNodeName] = $subNode;
            }
        }

        $json = json_encode($result);
        if ($json === false) {
            // Fallback in case json_encode fails
            $fallback = json_encode([
                'nodeType' => $node->getType(),
                'attributes' => $node->getAttributes(),
                'note' => 'Failed to encode AST node to JSON.',
            ]);
            if ($fallback === false) {
                // As a last resort, return an empty JSON object
                return '{}';
            }
            return $fallback;
        }

        return $json;
    }

    /**
     * Sets the current file being parsed.
     *
     * @param string $file
     * @return void
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $classData = $this->collectClassData($node);
            $this->items->push($classData);
            $this->currentClassName = $node->name->name;
        }

        if ($node instanceof Function_) {
            $functionData = $this->collectFunctionData($node);
            $this->items->push($functionData);
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : '';
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->currentClassName = '';
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = '';
        }
    }

    /**
     * Returns all discovered items (functions, classes, etc.).
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items->all();
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
     * Collect data for a standalone function.
     *
     * @param Function_ $node
     * @return array
     */
    private function collectFunctionData(Function_ $node): array
    {
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
                'description' => $this->extractDescription($node->getDocComment()),
            ],
            'annotations' => $this->extractAnnotations($node->getDocComment()),
            'attributes' => $this->collectAttributes($node->attrGroups),
            'file' => $this->currentFile,
            'line' => $node->getStartLine(),
            'fully_qualified_name' => $this->currentClassName 
                                        ? "{$this->currentClassName}::{$node->name->name}" 
                                        : "::{$node->name->name}",
            'ast' => $this->astToJson($node),
        ];
    }

    /**
     * Collect data for a class, including its methods.
     *
     * @param Class_ $node
     * @return array
     */
    private function collectClassData(Class_ $node): array
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[] = [
                'name' => $method->name->name,
                'params' => array_map(function ($param) {
                    return [
                        'name' => '$' . $param->var->name,
                        'type' => $this->typeToString($param->type) ?: 'mixed',
                    ];
                }, $method->params),
                'description' => $this->extractDescription($method->getDocComment()),
                'annotations' => $this->extractAnnotations($method->getDocComment()),
                'attributes' => $this->collectAttributes($method->attrGroups),
                'class' => $node->name->name,
                'namespace' => $this->currentNamespace,
                'visibility' => $this->resolveVisibility($method),
                'isStatic' => $method->isStatic(),
                'line' => $method->getStartLine(),
                'fully_qualified_name' => "{$node->name->name}::{$method->name->name}",
                'ast' => $this->astToJson($method),
            ];
        }

        return [
            'type' => 'Class',
            'name' => $node->name->name,
            'fully_qualified_name' => $this->currentNamespace 
                                        ? "{$this->currentNamespace}\\{$node->name->name}" 
                                        : $node->name->name,
            'details' => [
                'methods' => $methods,
                'description' => $this->extractDescription($node->getDocComment()),
            ],
            'annotations' => $this->extractAnnotations($node->getDocComment()),
            'attributes' => $this->collectAttributes($node->attrGroups),
            'file' => $this->currentFile,
            'line' => $node->getStartLine(),
            'ast' => $this->astToJson($node),
        ];
    }

    /**
     * Extract description from doc comment.
     *
     * @param \PhpParser\Comment\Doc|null $docComment
     * @return string
     */
    private function extractDescription(?\PhpParser\Comment\Doc $docComment): string
    {
        if (!$docComment) {
            return '';
        }

        $text = $docComment->getText();
        preg_match('/\/\*\*(.*?)\*\//s', $text, $matches);
        if (!isset($matches[1])) {
            return '';
        }

        $lines = explode("\n", $matches[1]);
        $descriptionLines = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*");
            if (strpos($line, '@') === 0) {
                break;
            }
            if ($line !== '') {
                $descriptionLines[] = $line;
            }
        }

        return implode(' ', $descriptionLines);
    }

    /**
     * Extract annotations from doc comment.
     *
     * @param \PhpParser\Comment\Doc|null $docComment
     * @return array
     */
    private function extractAnnotations(?\PhpParser\Comment\Doc $docComment): array
    {
        if (!$docComment) {
            return [];
        }

        $text = $docComment->getText();
        preg_match_all('/@(\w+)\s+(.*)/', $text, $matches, PREG_SET_ORDER);

        $annotations = [];
        foreach ($matches as $match) {
            $tag = $match[1];
            $value = $match[2];
            $annotations[$tag][] = $value;
        }

        return $annotations;
    }

    /**
     * Collect attributes (PHP 8+).
     *
     * @param array $attrGroups
     * @return array
     */
    private function collectAttributes(array $attrGroups): array
    {
        $attributes = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                $args     = [];
                foreach ($attr->args as $arg) {
                    $args[] = $this->argToString($arg->value);
                }
                $attributes[] = $attrName . '(' . implode(', ', $args) . ')';
            }
        }
        return $attributes;
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
     * Resolve visibility of a method.
     *
     * @param Node\Stmt\ClassMethod $method
     * @return string
     */
    private function resolveVisibility(Node\Stmt\ClassMethod $method): string
    {
        if ($method->isPublic()) {
            return 'public';
        }
        if ($method->isProtected()) {
            return 'protected';
        }
        if ($method->isPrivate()) {
            return 'private';
        }
        return 'public'; // Default visibility
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
        } elseif ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        } else {
            return 'mixed';
        }
    }
}
