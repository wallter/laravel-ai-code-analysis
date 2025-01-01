<?php

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
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
        return new NodeTraverser();
    }
}
