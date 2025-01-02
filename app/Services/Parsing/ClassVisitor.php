<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ClassVisitor extends NodeVisitorAbstract
{
    private array $classes = [];
    private string $currentFile = '';
    private string $currentClassName = '';
    private string $currentNamespace = '';

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->name !== null) {
            $this->currentClassName = $node->name->name;
            $this->currentNamespace = $this->getNamespace($node);
            $this->classes[] = $this->collectClassData($node);
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->name !== null) {
            $this->currentClassName = '';
            $this->currentNamespace = '';
        }
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    private function collectClassData(Node\Stmt\ClassLike $node): array
    {
        $description = '';
        $annotations = [];
        $restlerTags = [];
        $docComment = $node->getDocComment();
        if ($docComment) {
            $docText = $docComment->getText();
            $description = DocblockParser::extractShortDescription($docText);
            $annotations = DocblockParser::extractAnnotations($docText);
            $restlerTags = $annotations;
        }

        $attributes = DocblockParser::collectAttributes($node->attrGroups);

        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[] = $this->collectMethodData($method);
        }

        $className = $node->name->name;
        $namespace = $this->getNamespace($node);
        $fullyQualifiedName = $namespace ? "{$namespace}\\{$className}" : $className;

        return [
            'type'                => 'Class',
            'name'                => $className,
            'namespace'           => $namespace,
            'fullyQualifiedName'  => $fullyQualifiedName,
            'details'             => [
                'methods'      => $methods,
                'description'  => $description,
                'restler_tags' => $restlerTags
            ],
            'annotations'         => $annotations,
            'attributes'          => $attributes,
            'restler_tags'        => $restlerTags,
            'file'                => $this->currentFile,
            'line'                => $node->getStartLine(),
        ];
    }

    private function collectMethodData(Node\Stmt\ClassMethod $method): array
    {
        $params = [];
        foreach ($method->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[] = ['name' => $paramName, 'type' => $paramType];
        }

        $docComment = $method->getDocComment();
        $description = '';
        $annotations = [];
        $restlerTags = [];

        if ($docComment) {
            $docText = $docComment->getText();
            $description = DocblockParser::extractShortDescription($docText);
            $annotations = DocblockParser::extractAnnotations($docText);
            $restlerTags = $annotations;
        }

        $attributes = DocblockParser::collectAttributes($method->attrGroups);

        return [
            'name'              => $method->name->name,
            'params'            => $params,
            'description'       => $description,
            'annotations'       => $annotations,
            'attributes'        => $attributes,
            'class'             => $this->currentClassName,
            'namespace'         => $this->currentNamespace,
            'visibility'        => implode(' ', \PhpParser\Node\Stmt\Class_::getModifierNames($method->flags)),
            'isStatic'          => $method->isStatic(),
            'line'              => $method->getStartLine(),
        ];
    }

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
