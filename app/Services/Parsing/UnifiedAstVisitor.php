<?php

namespace App\Services\Parsing;

use App\Enums\ParsedItemType;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Unified AST Visitor that handles multiple node types.
 */
class UnifiedAstVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $parsedItems = [];

    /**
     * The current file being parsed.
     *
     * @var string|null
     */
    protected ?string $currentFile = null;

    /**
     * Set the current file being parsed.
     *
     * @param string $filePath
     * @return void
     */
    public function setCurrentFile(string $filePath): void
    {
        $this->currentFile = $filePath;
    }

    /**
     * Enter node callback.
     *
     * @param Node $node
     * @return null|int|Node|Node[]|void
     */
    public function enterNode(Node $node)
    {
        // Handle Classes, Traits, Interfaces
        if (
            ($node instanceof Node\Stmt\Class_ ||
             $node instanceof Node\Stmt\Trait_ ||
             $node instanceof Node\Stmt\Interface_) &&
            $node->name !== null
        ) {
            $type = match (true) {
                $node instanceof Node\Stmt\Class_ => ParsedItemType::CLASS_TYPE,
                $node instanceof Node\Stmt\Trait_ => ParsedItemType::TRAIT_TYPE,
                $node instanceof Node\Stmt\Interface_ => ParsedItemType::INTERFACE_TYPE,
                default => ParsedItemType::UNKNOWN,
            };

            $fullyQualifiedName = $this->getFullyQualifiedName($node);

            $this->parsedItems[] = [
                'type'                  => $type->value,
                'name'                  => $node->name->toString(),
                'fully_qualified_name'  => $fullyQualifiedName,
                'file_path'             => $this->currentFile,
                'line_number'           => $node->getStartLine() ?? 0,
                // Additional fields can be added here
            ];
        }

        // Handle Functions
        if ($node instanceof Node\Stmt\Function_ && $node->name !== null) {
            $fullyQualifiedName = $this->getFullyQualifiedName($node);

            $this->parsedItems[] = [
                'type'                  => ParsedItemType::FUNCTION_TYPE->value,
                'name'                  => $node->name->toString(),
                'fully_qualified_name'  => $fullyQualifiedName,
                'file_path'             => $this->currentFile,
                'line_number'           => $node->getStartLine() ?? 0,
                // Additional fields can be added here
            ];
        }
    }

    /**
     * Get the fully qualified name of a node.
     *
     * @param Node $node
     * @return string|null
     */
    protected function getFullyQualifiedName(Node $node): ?string
    {
        if (property_exists($node, 'namespacedName') && $node->namespacedName) {
            return $node->namespacedName->toString();
        }

        return null;
    }

    /**
     * Retrieve parsed items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getParsedItems(): array
    {
        return $this->parsedItems;
    }
}