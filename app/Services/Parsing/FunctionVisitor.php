<?php

namespace App\Services\Parsing;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;

class FunctionVisitor extends NodeVisitorAbstract
{
    protected string $currentFile = '';
    protected array $functions = [];

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Function_) {
            $functionData = $this->collectFunctionData($node);
            $this->functions[] = $functionData;
        }
    }

    private function collectFunctionData(Function_ $node): array
    {
        // Existing implementation...
    }

    private function astToArray(Node $node): array
    {
        // Existing implementation...
    }

    private function typeToString($typeNode): string
    {
        // Existing implementation...
    }
}
