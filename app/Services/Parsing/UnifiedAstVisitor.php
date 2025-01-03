<?php

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class UnifiedAstVisitor extends NodeVisitorAbstract
{
    protected $currentFile;
    protected $items = [];

    /**
     * Set the current file being analyzed.
     *
     * @param string $filePath
     */
    public function setCurrentFile(string $filePath)
    {
        $this->currentFile = $filePath;
    }

    /**
     * Called when entering a node during traversal.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Collect class information
        if ($node instanceof Node\Stmt\Class_) {
            $className = $node->name->toString();
            $namespace = $node->namespacedName->toString();

            $this->items[] = [
                'type' => 'Class',
                'name' => $className,
                'namespace' => $namespace,
                'annotations' => $this->getAnnotations($node),
                'description' => $this->getDescription($node),
                'methods' => $this->collectMethods($node),
            ];
        }

        // Collect function information
        if ($node instanceof Node\Stmt\Function_) {
            $functionName = $node->name->toString();
            $namespace = $node->namespacedName->toString();

            $this->items[] = [
                'type' => 'Function',
                'name' => $functionName,
                'namespace' => $namespace,
                'annotations' => $this->getAnnotations($node),
                'description' => $this->getDescription($node),
            ];
        }
    }

    /**
     * Extract annotations from a node's doc comment.
     *
     * @param Node $node
     * @return array
     */
    protected function getAnnotations(Node $node): array
    {
        $docComment = $node->getDocComment();
        if ($docComment) {
            // Parse annotations using regex
            preg_match_all('/@(\w+)(\s+([\w\W]+))?/', $docComment->getText(), $matches, PREG_SET_ORDER);
            $annotations = [];
            foreach ($matches as $match) {
                $annotations[$match[1]] = isset($match[3]) ? trim($match[3]) : true;
            }
            return $annotations;
        }
        return [];
    }

    /**
     * Extract the description from a node's doc comment.
     *
     * @param Node $node
     * @return string
     */
    protected function getDescription(Node $node): string
    {
        $docComment = $node->getDocComment();
        if ($docComment) {
            // Extract the first non-annotation line as description
            $lines = explode("\n", $docComment->getText());
            foreach ($lines as $line) {
                $line = trim($line, "/* \t\n\r\0\x0B");
                if ($line && strpos($line, '@') !== 0) {
                    return $line;
                }
            }
        }
        return '';
    }

    /**
     * Collect method information from a class node.
     *
     * @param Node\Stmt\Class_ $classNode
     * @return array
     */
    protected function collectMethods(Node\Stmt\Class_ $classNode): array
    {
        $methods = [];
        foreach ($classNode->getMethods() as $method) {
            $methods[] = [
                'name' => $method->name->toString(),
                'annotations' => $this->getAnnotations($method),
                'description' => $this->getDescription($method),
            ];
        }
        return $methods;
    }

    /**
     * Retrieve all collected items.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
