<?php

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Class_;

class ClassVisitor extends NodeVisitorAbstract
{
    /**
     * @var array Stores discovered classes
     */
    private $classes = [];

    /**
     * @var string Current file under analysis
     */
    private $currentFile = '';

    /**
     * Sets the current file name/path.
     *
     * @param string $file
     */
    public function setCurrentFile(string $file)
    {
        $this->currentFile = $file;
    }

    /**
     * Invoked by PhpParser on each node; collects class data.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $className = $node->name ? $node->name->name : 'anonymous class';
            $namespace = $this->getNamespace($node);
            $fullyQualifiedName = $namespace ? "{$namespace}\\{$className}" : $className;

            // Gather constructor-based dependencies
            $dependencies = $this->getClassDependencies($node);

            // Gather methods (excluding constructor)
            $methods = [];
            foreach ($node->getMethods() as $method) {
                if ($method->name->name === '__construct') {
                    continue;
                }
                $methods[] = $this->collectMethodData($method);
            }

            $this->classes[] = [
                'name'                => $className,
                'namespace'           => $namespace,
                'fullyQualifiedName'  => $fullyQualifiedName,
                'methods'             => $methods,
                'dependencies'        => $dependencies,
                'file'                => $this->currentFile,
                'line'                => $node->getStartLine(),
            ];
        }
    }

    /**
     * Returns the collected classes.
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Extracts the namespace from the given node.
     *
     * @param Class_ $node
     * @return string
     */
    private function getNamespace(Class_ $node): string
    {
        $namespace = '';
        $current   = $node;
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
     * Retrieves class dependencies from the constructor (if any).
     *
     * @param Class_ $node
     * @return array
     */
    private function getClassDependencies(Class_ $node): array
    {
        $dependencies = [];
        foreach ($node->getMethods() as $method) {
            if ($method->name->name === '__construct') {
                foreach ($method->getParams() as $param) {
                    $type = $this->detectParamType($param);
                    $dependencies[] = [
                        'name' => '$' . $param->var->name,
                        'type' => $type,
                    ];
                }
                break; // Only one constructor
            }
        }
        return $dependencies;
    }

    /**
     * Extracts method data (description, annotations, parameters).
     *
     * @param Node\Stmt\ClassMethod $method
     * @return array
     */
    private function collectMethodData(Node\Stmt\ClassMethod $method): array
    {
        $methodName    = $method->name->name;
        $docComment    = $method->getDocComment();
        $description   = '';
        $annotations   = [];

        if ($docComment) {
            $docText     = $docComment->getText();
            $description = $this->extractShortDescription($docText);
            $annotations = $this->extractAnnotations($docText);
        }

        $parameters = [];
        foreach ($method->getParams() as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $param->type->toString() : 'mixed';
            $parameters[] = ['name' => $paramName, 'type' => $paramType];
        }

        return [
            'name'        => $methodName,
            'description' => $description,
            'annotations' => $annotations,
            'parameters'  => $parameters,
        ];
    }

    /**
     * Extracts short description from docblock (first lines until blank).
     *
     * @param string $docblock
     * @return string
     */
    private function extractShortDescription(string $docblock): string
    {
        $lines    = preg_split('/\R/', $docblock);
        $cleaned  = array_map(function($line) {
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
     * Extracts annotation lines (e.g., @param, @return) from docblock.
     *
     * @param string $docblock
     * @return array
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
     * Detects parameter type, including fully qualified name if present.
     *
     * @param Node\Param $param
     * @return string
     */
    private function detectParamType(Node\Param $param): string
    {
        if ($param->type && $param->type instanceof Node\Name\FullyQualified) {
            return '\\' . $param->type->toString();
        } elseif ($param->type && $param->type instanceof Node\Name) {
            return $param->type->toString();
        }
        return 'mixed';
    }
}
