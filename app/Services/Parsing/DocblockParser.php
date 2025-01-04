<?php
declare(strict_types=1);

namespace App\Services\Parsing;

class DocblockParser
{
    /**
     * parseDocblock parses a PHP docblock string and returns a structured array:
     * [
     *   'shortDescription' => (string),
     *   'annotations' => (array) e.g.
     *      [
     *        'param' => [
     *          [
     *            'type' => 'string',
     *            'var'  => 'type',
     *            'desc' => '{@from body} {@choice apple,base64...}'
     *          ],
     *          ...
     *        ],
     *        'throws' => [
     *          [
     *            'code' => '400',
     *            'desc' => 'Username or password are invalid.'
     *          ]
     *        ],
     *        'url' => ['POST'],
     *        'access' => ['protected'],
     *        'class' => ['AccessControl {@requires guest}'],
     *        'return' => ['mixed'],
     *        'status' => ['201'],
     *        ...
     *      ]
     * ]
     *
     * Handles special tags:
     *   - @param <type> $<var> <description...>
     *   - @return <type> <description...>
     *   - @throws <code> <desc...> (some devs use @throws <exceptionClass> <desc>, adapt as needed)
     *
     * All other tags are stored under their name, appending each occurrence to an array of values.
     */
    public static function parseDocblock(string $docblock): array
    {
        // 1) Split lines, remove leading /** or /*, trailing */, and leading asterisks
        $lines = preg_split('/\r?\n/', trim($docblock));
        $cleaned = array_map(static function ($line) {
            $line = preg_replace('/^\s*\/\*\*?/', '', $line); // remove /** or /*
            $line = preg_replace('/\*\/\s*$/', '', $line);    // remove */
            $line = preg_replace('/^\s*\*\s?/', '', $line);   // remove leading asterisk
            return trim($line);
        }, $lines);

        // 2) Extract short description up to first blank line or annotation
        $shortDescLines = [];
        $annotations = [];

        // We'll gather lines until we hit either a blank line or an annotation
        foreach ($cleaned as $line) {
            if ($line === '' || str_starts_with($line, '@')) {
                break;
            }
            $shortDescLines[] = $line;
        }
        $shortDescription = implode(' ', $shortDescLines);

        // 3) Parse out annotations from all lines
        foreach ($cleaned as $line) {
            // @param pattern: `@param <type> $<var> <desc>`
            if (preg_match('/^@param\s+([^\s]+)\s+\$([A-Za-z0-9_]+)\s*(.*)?$/', $line, $m)) {
                $type = $m[1];
                $var  = $m[2];
                $desc = isset($m[3]) ? trim($m[3]) : '';
                $annotations['param'][] = [
                    'type' => $type,
                    'var'  => $var,
                    'desc' => $desc,
                ];
                continue;
            }

            // @return pattern: `@return <type> <desc>`
            if (preg_match('/^@return\s+([^\s]+)\s*(.*)?$/', $line, $m)) {
                $type = $m[1];
                $desc = isset($m[2]) ? trim($m[2]) : '';
                // store as object or just keep it simple
                $annotations['return'][] = $desc === ''
                    ? $type
                    : ($type . ' ' . $desc);
                continue;
            }

            // @throws pattern (two main forms):
            // e.g. `@throws SomeException On invalid input.`
            // or   `@throws 400 Username or password are invalid.`
            // We'll demonstrate a numeric code approach:
            if (preg_match('/^@throws\s+(\d+)\s+(.*)$/', $line, $m)) {
                $annotations['throws'][] = [
                    'code' => $m[1],
                    'desc' => $m[2],
                ];
                continue;
            }
            // For a more standard usage: `@throws ExceptionClass Some desc`
            // else if (preg_match('/^@throws\s+([A-Za-z0-9\\\\_]+)\s+(.*)$/', $line, $m)) {
            //     $annotations['throws'][] = [
            //         'type' => $m[1],
            //         'desc' => $m[2],
            //     ];
            //     continue;
            // }

            // For all other tags, e.g. @url, @status, @access, @class, ...
            if (preg_match('/^@(\w+)\s+(.*)$/', $line, $m)) {
                $tag = $m[1];
                $val = trim($m[2]);
                $annotations[$tag][] = $val;
            }
        }

        return [
            'shortDescription' => $shortDescription,
            'annotations'      => $annotations,
        ];
    }
}