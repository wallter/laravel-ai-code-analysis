<?php

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects both free-floating functions and classes with methods/attributes.
 */
class FunctionAndClassVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var string
     */
    private $currentFile = '';

    /**
     * Sets the current file being processed.
     */
    public function setCurrentFile(string $file)
    {
        $this->currentFile = $file;
    }

    /**
     * Called on each node to detect functions and classes.
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->items[] = $this->collectFunctionData($node);
        }

        if ($node instanceof Class_) {
            $this->items[] = $this->collectClassData($node);
        }
    }

    /**
     * Returns all discovered items (functions, classes, etc.).
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Collects data for a standalone function.
     *
     * @param Node\Stmt\Function_ $node
     * @return array
     */
    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        $params      = [];
        foreach ($node->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $param->type->toString() : 'mixed';
            $params[]  = ['name' => $paramName, 'type' => $paramType];
        }

        $docComment  = $node->getDocComment();
        $description = '';
        $annotations = [];

        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
        }

        $attributes = $this->collectAttributes($node->attrGroups);

        return [
            'type'       => 'Function',
            'name'       => $node->name->name,
            'details'    => [
                'params'      => $params,
                'description' => $description
            ],
            'annotations' => $annotations,
            'attributes'  => $attributes,
            'file'        => $this->currentFile,
            'line'        => $node->getStartLine(),
        ];
    }

    /**
     * Collects data for a class node, including methods and attributes.
     *
     * @param Class_ $node
     * @return array
     */
    private function collectClassData(Class_ $node): array
    {
        $description = '';
        $annotations = [];
        $docComment  = $node->getDocComment();
        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
        }

        $attributes = $this->collectAttributes($node->attrGroups);

        // Gather methods
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[] = $this->collectMethodData($method);
        }

        return [
            'type'       => 'Class',
            'name'       => $node->name->name,
            'details'    => [
                'methods'     => $methods,
                'description' => $description
            ],
            'annotations' => $annotations,
            'attributes'  => $attributes,
            'file'        => $this->currentFile,
            'line'        => $node->getStartLine(),
        ];
    }

    /**
     * Extracts attributes (e.g. PHP 8+) from the given attribute groups.
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
                    // Some basic logic for attribute arguments
                    $args[] = $this->argToString($arg->value);
                }
                $attributes[] = $attrName . '(' . implode(', ', $args) . ')';
            }
        }
        return $attributes;
    }

    /**
     * Collects data for a class method node.
     *
     * @param Node\Stmt\ClassMethod $method
     * @return array
     */
    private function collectMethodData(Node\Stmt\ClassMethod $method): array
    {
        $methodName       = $method->name->name;
        $methodParams     = [];
        foreach ($method->params as $param) {
            $paramName  = '$' . $param->var->name;
            $paramType  = $param->type ? $param->type->toString() : 'mixed';
            $methodParams[] = ['name' => $paramName, 'type' => $paramType];
        }

        $methodDescription = '';
        $methodAnnotations = [];
        $methodDocComment  = $method->getDocComment();
        if ($methodDocComment) {
            $docText          = $methodDocComment->getText();
            $methodDescription = $this->extractShortDescription($docText);
            $methodAnnotations = $this->extractAnnotations($docText);
        }

        $methodAttributes = $this->collectAttributes($method->attrGroups);

        return [
            'name'        => $methodName,
            'params'      => $methodParams,
            'description' => $methodDescription,
            'annotations' => $methodAnnotations,
            'attributes'  => $methodAttributes,
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
     * Find lines with @annotation in docblock.
     */
    private function extractAnnotations(string $docblock): array
    {
        $annotations = [];
        $lines       = preg_split('/\R/', $docblock);
        foreach ($lines as $line) {
            if (preg_match('/@(\w+)\s*(.*)/', $line, $matches)) {
                $annotations[] = $matches[0];
            }
        }
        return $annotations;
    }

    /**
     * Convert attribute argument node to a string representation.
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
     * Turn a PhpParser array node into a string.
     */
    private function parseArray(Node\Expr\Array_ $array): string
    {
        $elements = [];
        foreach ($array->items as $item) {
            $key   = $item->key ? $this->argToString($item->key) . ' => ' : '';
            $value = $this->argToString($item->value);
            $elements[] = $key . $value;
        }
        return '[' . implode(', ', $elements) . ']';
    }
}
