<?php
declare(strict_types=1);

namespace App\Services\Parsing;

use PhpParser\Node;

class DocblockParser
{
    public static function extractShortDescription(string $docblock): string
    {
        $lines   = preg_split('/\R/', $docblock);
        $cleaned = array_map(function($line) {
            $line = preg_replace('/^\s*\/\*\*?/', '', $line);
            $line = preg_replace('/\*\/\s*$/', '', $line);
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            return $line;
        }, $lines);

        $description = collect($cleaned)
            ->takeUntil(function ($line) {
                return trim($line) === '';
            })
            ->implode(' ');

        return trim($description);
    }

    public static function extractAnnotations(string $docblock): array
    {
        $annotations = collect($lines)
            ->mapWithKeys(function ($line) {
                $line = trim($line, " \t\n\r\0\x0B*");
                if (preg_match('/@(\w+)\s*(.*)/', $line, $matches)) {
                    $tag = $matches[1];
                    $value = $matches[2];
                    return [$tag => self::parseAnnotationValue($value)];
                }
                return [];
            })
            ->filter()
            ->all();

        return $annotations;
    }

    public static function parseAnnotationValue(string $value): mixed
    {
        $result = [];
        $pattern = '/\{(@\w+)\s+([^}]+)\}/';
        
        if (preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $nestedTag = ltrim($match[1], '@');
                $nestedValue = trim($match[2]);
                $result[$nestedTag] = $nestedValue;
            }
            $value = preg_replace($pattern, '', $value);
            $value = trim($value);
        }

        if (!empty($value)) {
            $result['value'] = $value;
        }

        if (count($result) === 1 && isset($result['value'])) {
            return $result['value'];
        }

        return $result;
    }

    public static function collectAttributes(array $attrGroups): array
    {
        $attributes = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                $args     = [];
                foreach ($attr->args as $arg) {
                    $args[] = self::argToString($arg->value);
                }
                $attributes[] = $attrName . '(' . implode(', ', $args) . ')';
            }
        }
        return $attributes;
    }

    private static function argToString(Node $node): string
    {
        if ($node instanceof Node\Scalar\String_) {
            return '"' . $node->value . '"';
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return (string) $node->value;
        } elseif ($node instanceof Node\Expr\Array_) {
            return self::parseArray($node);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $node->name->toString();
        }
        return '...';
    }

    private static function parseArray(Node\Expr\Array_ $array): string
    {
        $elements = [];
        foreach ($array->items as $item) {
            $key   = $item->key ? self::argToString($item->key) . ' => ' : '';
            $value = self::argToString($item->value);
            $elements[] = $key . $value;
        }
        return '[' . implode(', ', $elements) . ']';
    }
}
