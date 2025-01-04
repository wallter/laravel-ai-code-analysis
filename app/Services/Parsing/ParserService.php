<?php

namespace App\Services\Parsing;

use PhpParser\ParserFactory;
use PhpParser\Error;
use PhpParser\Node;

class ParserService
{
    protected $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * Parse a PHP file and return the AST.
     *
     * @param string $filePath
     * @return array|null
     */
    public function parseFile(string $filePath): ?array
    {
        try {
            $code = file_get_contents($filePath);
            $ast = $this->parser->parse($code);
            return $ast;
        } catch (Error $e) {
            // Handle parse error
            return null;
        }
    }
}
