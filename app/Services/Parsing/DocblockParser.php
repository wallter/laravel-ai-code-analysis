<?php

declare(strict_types=1);

namespace App\Services\Parsing;

/**
 * DocblockParser
 *
 * Extracts:
 *  - shortDescription: lines before first blank line
 *  - annotations: grouped by tag name (e.g., param, throws, return, url, etc.)
 */
class DocblockParser
{
    /**
     * Parse a docblock string and extract the short description and annotations.
     *
     * @param  string  $docblock  The docblock text to parse.
     * @return array{
     *     shortDescription: string,
     *     annotations: array<string, mixed>
     * } The parsed docblock information.
     */
    public static function parseDocblock(string $docblock): array
    {
        // 1. Split lines, remove extra comment symbols
        $lines = preg_split('/\r?\n/', trim($docblock));
        $cleaned = array_map(static function ($line) {
            // Remove /** or /*
            $line = preg_replace('/^\s*\/\*\*?/', '', $line);
            // Remove */
            $line = preg_replace('/\*\/\s*$/', '', $line);
            // Remove leading *
            $line = preg_replace('/^\s*\*\s?/', '', $line);

            return trim($line);
        }, $lines);

        // Separate short description lines, then annotation lines
        $shortDescLines = [];
        $annotationLines = [];
        $reachedBlankLine = false;

        foreach ($cleaned as $line) {
            if ($line === '') {
                // Once we hit a blank line, everything that follows is annotation or extended description
                $reachedBlankLine = true;

                continue;
            }
            if (! $reachedBlankLine && ! str_starts_with($line, '@')) {
                // Still in short description
                $shortDescLines[] = $line;
            } else {
                // In annotation section
                $annotationLines[] = $line;
            }
        }

        $shortDescription = implode(' ', $shortDescLines);

        // 2. Parse annotations (including multi-line)
        $annotations = [];
        $currentTag = null;
        $currentBuffer = '';

        // Helper function to store buffered content under a tag
        $flushBuffer = function () use (&$annotations, &$currentTag, &$currentBuffer) {
            if ($currentTag && $currentBuffer !== '') {
                $annotations[$currentTag][] = trim($currentBuffer);
            }
            $currentBuffer = '';
        };

        foreach ($annotationLines as $line) {
            if (preg_match('/^@(\w+)\s+(.*)$/', $line, $matches)) {
                // Found a new tag
                //  - first flush old buffer if it exists
                $flushBuffer();

                $tag = $matches[1];
                $value = $matches[2];

                // Check known patterns first
                if ($tag === 'param') {
                    // @param <type> $<var> <desc...>
                    if (preg_match('/^([^\s]+)\s+\$([A-Za-z0-9_]+)\s*(.*)$/', $value, $m)) {
                        $type = $m[1];
                        $var = $m[2];
                        $desc = $m[3] ?? '';
                        $annotations['param'][] = [
                            'type' => $type,
                            'var' => $var,
                            'desc' => $desc,
                        ];
                        // reset currentTag because param is fully handled on one line
                        $currentTag = null;
                        $currentBuffer = '';

                        continue;
                    }
                } elseif ($tag === 'return') {
                    // @return <type> <desc...>
                    if (preg_match('/^([^\s]+)\s*(.*)$/', $value, $m)) {
                        $type = $m[1];
                        $desc = $m[2] ?? '';
                        $annotations['return'][] = $desc === ''
                            ? $type
                            : ($type.' '.$desc);
                        $currentTag = null;
                        $currentBuffer = '';

                        continue;
                    }
                } elseif ($tag === 'throws') {
                    // Check numeric code
                    if (preg_match('/^(\d+)\s+(.*)$/', $value, $m)) {
                        $annotations['throws'][] = [
                            'code' => $m[1],
                            'desc' => $m[2],
                        ];
                        $currentTag = null;
                        $currentBuffer = '';

                        continue;
                    }
                    // Alternatively, check for a class-based exception
                    if (preg_match('/^([A-Za-z0-9_\\\\]+)\s+(.*)$/', $value, $m)) {
                        $annotations['throws'][] = [
                            'type' => $m[1],
                            'desc' => $m[2],
                        ];
                        $currentTag = null;
                        $currentBuffer = '';

                        continue;
                    }
                }

                // Otherwise treat as a general tag
                $currentTag = $tag;
                $currentBuffer = $value;
            } else {
                // Not a new tag, so continuation of the existing tag
                if ($currentTag) {
                    $currentBuffer .= ' '.$line;
                }
            }
        }
        // Flush any leftover buffer
        $flushBuffer();

        return [
            'shortDescription' => $shortDescription,
            'annotations' => $annotations,
        ];
    }
}
