<?php

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use App\Services\Parsing\FunctionVisitor;
use App\Services\Parsing\ClassVisitor;
use PhpParser\NodeTraverser;

class ParserService
{
    /**
     * Create a new PHP parser instance using the newest supported version.
     *
     * @return \PhpParser\Parser
     */
    public function createParser()
    {
        return (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Create a new NodeTraverser instance.
     *
     * @return \PhpParser\NodeTraverser
     */
    public function createTraverser()
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new \PhpParser\NodeVisitor\ParentConnectingVisitor());
        return $traverser;
    }
}
