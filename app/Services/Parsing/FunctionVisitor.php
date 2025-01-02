<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FunctionVisitor extends NodeVisitorAbstract
{
    private array $functions = [];
    private string $currentFile = '';

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->functions[] = $this->collectFunctionData($node);
        }
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        $params = [];
        foreach ($node->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[] = ['name' => $paramName, 'type' => $paramType];
        }

        $docComment = $node->getDocComment();
        $description = '';
        $annotations = [];
        $restlerTags = [];

        if ($docComment) {
            $docText = $docComment->getText();
            $description = DocblockParser::extractShortDescription($docText);
            $annotations = DocblockParser::extractAnnotations($docText);
            $restlerTags = $annotations;
        }

        $attributes = DocblockParser::collectAttributes($node->attrGroups);

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
