<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;

/**
 * Collects both free-floating functions and classes with methods/attributes.
 */
class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    private Collection $items;
    private Collection $warnings;

    public function __construct()
    {
        $this->items = collect();
        $this->warnings = collect();
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
     * Collects data for a standalone function.
     *
     * @param Node\Stmt\Function_ $node
     * @return array
     */
    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        $params = collect($node->params)
            ->map(function ($param) {
                return [
                    'name' => '$' . $param->var->name,
                    'type' => $this->typeToString($param->type) ?: 'mixed',
                ];
            })
            ->all();

        $docComment  = $node->getDocComment();
        $description = '';
        $annotations = [];
        $restlerTags = [];

        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
            $restlerTags = $annotations;
        }

        $attributes = $this->collectAttributes($node->attrGroups);

        return [
            'type'       => 'Function',
            'name'       => $node->name->name,
            'details'    => [
                'params'       => $params,
                'description'  => $description,
                'restler_tags' => $restlerTags
            ],
            'annotations' => $annotations,
            'attributes'  => $attributes,
            'file'        => $this->currentFile,
            'line'        => $node->getStartLine(),
        ];
    }

    /**
     * Collects data for one class method.
     *
     * @param Node\Stmt\ClassMethod $method
     * @return array
     */
    private function collectMethodData(Node\Stmt\ClassMethod $method): array
    {
        $params = collect($method->params)
            ->map(function ($param) {
                return [
                    'name' => '$' . $param->var->name,
                    'type' => $this->typeToString($param->type) ?: 'mixed',
                ];
            })
            ->all();

        $docComment = $method->getDocComment();
        $description = '';
        $annotations = [];
        $restlerTags = [];

        if ($docComment) {
            $docText      = $docComment->getText();
            $description  = $this->extractShortDescription($docText);
            $annotations  = $this->extractAnnotations($docText);
            $restlerTags  = $annotations;
        }

        $attributes = $this->collectAttributes($method->attrGroups);

        // Serialize the AST
        $astSerialized = $this->serializeAst($method);
        $astSize = strlen($astSerialized);

        if ($astSize > $this->astSizeLimit) {
            $this->warnings->push("AST size for method '{$method->name->name}' exceeds limit ({$astSize} bytes).");
            $astSerialized = null;
        }

        // Summarize method body
        $operationSummary = $this->summarizeMethodBody($method);

        // Collect called methods
        $calledMethods = $this->collectCalledMethods($method);

        $visibilityFlags = $this->resolveModifierNames($method->flags);
        $visibility = implode(' ', $visibilityFlags);

        return [
            'name'              => $method->name->name,
            'params'            => $params,
            'description'       => $description,
            'annotations'       => $annotations,
            'attributes'        => $attributes,
            'class'             => $this->currentClassName,
            'namespace'         => $this->currentNamespace,
            'visibility'        => $visibility,
            'isStatic'          => $method->isStatic(),
            'line'              => $method->getStartLine(),
            'operation_summary' => $operationSummary,
            'called_methods'    => $calledMethods,
            'ast'               => $astSerialized,
        ];
    }

    /**
     * Convert docblock lines to short description.
     */
    private function extractShortDescription(string $docblock): string
    {
        $lines   = preg_split('/\R/', $docblock);
        $cleaned = collect($lines)
            ->map(function($line) {
                $line = preg_replace('/^\s*\/\*\*?/', '', $line);
                $line = preg_replace('/\*\/\s*$/', '', $line);
                $line = preg_replace('/^\s*\*\s?/', '', $line);
                return $line;
            });

        $description = $cleaned
            ->takeUntil(function ($line) {
                return trim($line) === '';
            })
            ->implode(' ');

        return trim($description);
    }

    /**
     * Extract lines with @annotation in docblock.
     *
     * @param string $docblock
     * @return array
     */
    private function extractAnnotations(string $docblock): array
    {
        return collect(explode("\n", $docblock))
            ->mapWithKeys(function ($line) {
                $line = trim($line, " \t\n\r\0\x0B*");
                if (preg_match('/@(\w+)\s*(.*)/', $line, $matches)) {
                    $tag = $matches[1];
                    $value = $matches[2];
                    return [$tag => $this->parseAnnotationValue($value)];
                }
                return [];
            })
            ->filter()
            ->all();
    }

    /**
     * Parse an annotation line that may have nested braces like {@requires guest}.
     *
     * @param string $value
     * @return mixed
     */
    private function parseAnnotationValue(string $value): mixed
    {
        $result = [];
        $pattern = '/\{(@\w+)\s+([^}]+)\}/';

        if (preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $nestedTag = ltrim($match[1], '@');
                $nestedValue = trim($match[2]);
                $result[$nestedTag] = $nestedValue;
            }
            $value = preg_replace($pattern, '', $value);
            $value = trim($value);
        }

        if (!empty($value)) {
            $result['value'] = $value;
        }

        if (count($result) === 1 && isset($result['value'])) {
            return $result['value'];
        }

        return $result;
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
     * Serialize the AST node to JSON.
     *
     * @param Node $node
     * @return string|null
     */
    private function serializeAst(Node $node): ?string
    {
        return json_encode($node);
    }

    /**
     * Summarize a method's statement types.
     *
     * @param Node\Stmt\ClassMethod $method
     * @return string
     */
    private function summarizeMethodBody(Node\Stmt\ClassMethod $method): string
    {
        $statementTypes = [];
        if ($method->stmts) {
            foreach ($method->stmts as $stmt) {
                $type = $stmt->getType();
                if (!isset($statementTypes[$type])) {
                    $statementTypes[$type] = 0;
                }
                $statementTypes[$type]++;
            }
        }

        $summaryParts = [];
        foreach ($statementTypes as $type => $count) {
            $summaryParts[] = "{$count} {$type}(s)";
        }

        return 'Contains ' . implode(', ', $summaryParts) . '.';
    }

    /**
     * Recursively gather method calls within a method's body up to maxDepth.
     *
     * @param Node $node
     * @param int $currentDepth
     * @return array
     */
    private function collectCalledMethods(Node $node, int $currentDepth = 0): array
    {
        if ($currentDepth >= $this->maxDepth) {
            return [];
        }

        $calledMethods = [];

        if (isset($node->stmts) && is_array($node->stmts)) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Expression) {
                    $expr = $stmt->expr;
                    if ($expr instanceof Node\Expr\MethodCall) {
                        $caller = $this->getCallerName($expr->var);
                        $methodName = $expr->name instanceof Node\Identifier
                            ? $expr->name->name
                            : '';
                        if ($methodName) {
                            $calledMethods[] = $caller . '->' . $methodName;
                        }
                    }
                }
                // Recursively explore nested statements
                $calledMethods = array_merge(
                    $calledMethods,
                    $this->collectCalledMethods($stmt, $currentDepth + 1)
                );
            }
        }

        return $calledMethods;
    }

    /**
     * Get the name of the caller in a method call.
     *
     * @param Node $var
     * @return string
     */
    private function getCallerName($var): string
    {
        if ($var instanceof Node\Expr\Variable) {
            return '$' . $var->name;
        } elseif ($var instanceof Node\Expr\PropertyFetch) {
            return $this->getCallerName($var->var) . '->' . $var->name->name;
        } elseif ($var instanceof Node\Expr\StaticPropertyFetch) {
            return $var->class->toString() . '::$' . $var->name->name;
        } elseif ($var instanceof Node\Expr\StaticCall) {
            return $var->class->toString();
        } elseif ($var instanceof Node\Expr\ArrayDimFetch) {
            return $this->getCallerName($var->var) . '[' . $this->getCallerName($var->dim) . ']';
        } else {
            return '';
        }
    }

    /**
     * Resolve the namespace of a class node.
     *
     * @param Node $node
     * @return string
     */
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

    /**
     * Interpret method flags for visibility/static.
     *
     * @param int $flags
     * @return array
     */
    private function resolveModifierNames(int $flags): array
    {
        $names = [];

        // Visibility
        if ($flags & Node\Stmt\Class_::MODIFIER_PUBLIC) {
            $names[] = 'public';
        } elseif ($flags & Node\Stmt\Class_::MODIFIER_PROTECTED) {
            $names[] = 'protected';
        } elseif ($flags & Node\Stmt\Class_::MODIFIER_PRIVATE) {
            $names[] = 'private';
        }

        // Static?
        if ($flags & Node\Stmt\Class_::MODIFIER_STATIC) {
            $names[] = 'static';
        }

        return $names;
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
