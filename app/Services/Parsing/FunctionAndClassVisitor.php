<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Modifiers;
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
        parent::__construct();
        $this->items = collect();
        $this->warnings = collect();
    }
    
    private $maxDepth = 2;
    private $astSizeLimit = 100000;

    private $currentFile = '';
    private $currentClassName = '';
    private $currentNamespace = '';

    /**
     * Sets the current file being processed.
     */
    public function setCurrentFile(string $file)
    {
        $this->currentFile = $file;
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

    public function getItems(): array
    {
        return $this->items->all();
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Helper to interpret method flags for visibility/static if `Class_::getModifierNames` is not available.
     */
    private function resolveModifierNames(int $flags): array
    {
        $names = [];

        // These constants exist in \PhpParser\Modifier for visibility:
        //   PUBLIC = 1
        //   PROTECTED = 2
        //   PRIVATE = 4
        //   STATIC = 8
        //   ABSTRACT = 16
        //   FINAL = 32

        // Visibility
        if ($flags & Modifiers::PUBLIC) {
            $names[] = 'public';
        } elseif ($flags & Modifiers::PROTECTED) {
            $names[] = 'protected';
        } elseif ($flags & Modifiers::PRIVATE) {
            $names[] = 'private';
        }

        // Static?
        if ($flags & Modifiers::STATIC) {
            $names[] = 'static';
        }

        // Abstract?
        if ($flags & Modifiers::ABSTRACT) {
            $names[] = 'abstract';
        }

        // Final?
        if ($flags & Modifiers::FINAL) {
            $names[] = 'final';
        }

        return $names;
    }

    /**
     * Called on each node to detect free-floating functions and classes.
     */
    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->items->push($this->collectFunctionData($node));
        } elseif ($node instanceof ClassLike && $node->name !== null) {
            $this->currentClassName = $node->name->name;
            $this->currentNamespace = $this->getNamespace($node);
            $this->items[] = $this->collectClassData($node);
        }
    }

    /**
     * Resets current class/namespace after leaving a class node.
     */
    public function leaveNode(Node $node): void
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $this->currentClassName = '';
            $this->currentNamespace = '';
        }
    }

    /**
     * Returns all discovered classes.
     */
    public function getClasses(): array
    {
        return $this->items->filter(function ($item) {
            return $item['type'] === 'Class';
        });
    }

    /**
     * Collects data for a standalone function.
     */
    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        $params = [];
        foreach ($node->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[]  = ['name' => $paramName, 'type' => $paramType];
        }

        $docComment  = $node->getDocComment();
        $description = '';
        $annotations = [];
        $restlerTags = [];

        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
        }

        $attributes = $this->collectAttributes($node->attrGroups);

        // Serialize the AST
        $astSerialized = $this->serializeAst($node);
        $astSize = strlen($astSerialized);

        // Check if AST exceeds the size limit
        if ($astSize > $this->astSizeLimit) {
            $this->warnings->push("AST size for function '{$node->name->name}' exceeds limit ({$astSize} bytes).");
            $astSerialized = null;
        }

        return [
            'type' => 'Function',
            'name' => $node->name->name,
            'details' => [
                'params'       => $params,
                'description'  => $description,
            ],
            'annotations' => $annotations,
            'attributes'  => $attributes,
            'file'        => $this->currentFile,
            'line'        => $node->getStartLine(),
            'ast'         => $astSerialized,
        ];
    }

    /**
     * Collects data for a class node.
     */
    private function collectClassData(ClassLike $node): array
    {
        $description = '';
        $annotations = [];
        $restlerTags = [];
        $docComment  = $node->getDocComment();
        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
            $restlerTags = $annotations;
        }

        $attributes = $this->collectAttributes($node->attrGroups);

        // Gather methods
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[] = $this->collectMethodData($method);
        }

        $className = $node->name->name;
        $namespace = $this->getNamespace($node);
        $fullyQualifiedName = $namespace ? "{$namespace}\\{$className}" : $className;

        return [
            'type'               => 'Class',
            'name'               => $className,
            'namespace'          => $namespace,
            'fullyQualifiedName' => $fullyQualifiedName,
            'details' => [
                'methods'      => $methods,
                'description'  => $description,
                'restler_tags' => $restlerTags,
            ],
            'annotations'  => $annotations,
            'attributes'   => $attributes,
            'file'         => $this->currentFile,
            'line'         => $node->getStartLine(),
        ];
    }

    /**
     * Collect data for one class method.
     */
    private function collectMethodData(ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[]  = ['name' => $paramName, 'type' => $paramType];
        }

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
            $this->warnings[] = "AST size for method '{$method->name->name}' exceeds limit ({$astSize} bytes).";
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
        $cleaned = array_map(function($line) {
            $line = preg_replace('/^\s*\/\*\*?/', '', $line);
            $line = preg_replace('/\*\/\s*$/', '', $line);
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            return $line;
        }, $lines);

        $description = '';
        foreach ($cleaned as $line) {
            if (trim($line) === '') {
                break;
            }
            $description .= $line . ' ';
        }

        return trim($description);
    }

    /**
     * Extract lines with @annotation in docblock.
     */
    private function extractAnnotations(string $docblock): array
    {
        $annotations = [];
        $lines = preg_split('/\R/', $docblock);

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*");
            if (preg_match('/@(\w+)\s*(.*)/', $line, $matches)) {
                $tag = $matches[1];
                $value = $matches[2];
                $annotations[$tag] = $this->parseAnnotationValue($value);
            }
        }

        return $annotations;
    }

    /**
     * Parse an annotation line that may have nested braces like {@requires guest}.
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
     * Summarize a method's statement types.
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
     * Collect attributes (PHP 8+).
     */
    private function collectAttributes(array $attrGroups): array
    {
        $attributes = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                $args = [];
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
     * Resolve the namespace of a class node.
     */
    private function getNamespace(Node $node): string
    {
        $namespace = '';
        $current = $node;
        while ($current->getAttribute('parent')) {
            $current = $current->getAttribute('parent');
            if ($current instanceof Namespace_) {
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
        } elseif ($typeNode instanceof Node\IntersectionType) {
            return implode('&', array_map([$this, 'typeToString'], $typeNode->types));
        } elseif ($typeNode instanceof Node\Name) {
            return $typeNode->toString();
        } else {
            return 'mixed';
        }
    }

    /**
     * Get the name of the caller in a method call.
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
     * Retrieve the collected BusinessRule calls.
     */
    public function getBusinessRuleCalls(): array
    {
        return $this->businessRuleCalls;
    }
}
