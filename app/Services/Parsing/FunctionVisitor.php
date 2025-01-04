<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;

class FunctionVisitor extends NodeVisitorAbstract
{
    private Collection $functions;
    private string $currentFile = '';

    public function __construct()
    {
        $this->functions = collect();
    }

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->functions->push($this->collectFunctionData($node));
        }
    }

    public function getFunctions(): array
    {
        return $this->functions->all();
    }

    private function collectFunctionData(Node\Stmt\Function_ $node): array
    {
        $params = [];
        foreach ($node->params as $param) {
            $paramName = '$' . $param->var->name;
            $paramType = $param->type ? $this->typeToString($param->type) : 'mixed';
            $params[] = ['name' => $paramName, 'type' => $paramType];
        }

        // you can parse doccomments if you like
        $docComment = $node->getDocComment();
        $description = ''; // ...
        $annotations = []; // ...
        // $restlerTags = [];

        // no attributes for now, or do something similar to FunctionAndClassVisitor
        $attributes = [];

        return [
            'type'       => 'Function',
            'name'       => $node->name->name,
            'details'    => [
                'params'       => $params,
                'description'  => $description,
            ],
            'annotations' => $annotations,
            'attributes'  => $attributes,
            'file'        => $this->currentFile,
            'line'        => $node->getStartLine(),
            'ast'         => $this->astToArray($node),
        ];
    }

    private function astToArray(Node $node): array
    {
        $result = [
            'nodeType'   => $node->getType(),
            'attributes' => $node->getAttributes(),
        ];
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            if ($subNode instanceof Node) {
                $result[$subNodeName] = $this->astToArray($subNode);
            } elseif (is_array($subNode)) {
                $result[$subNodeName] = array_map(fn($item) => ($item instanceof Node)
                    ? $this->astToArray($item)
                    : $item, $subNode);
            } else {
                $result[$subNodeName] = $subNode;
            }
        }
        return $result;
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