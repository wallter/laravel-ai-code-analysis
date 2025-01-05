<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * FilesCommand
 *  - Provides shared options: --output-file, --limit-class, --limit-method
 *  - Many commands can extend this for consistent option handling
 */
abstract class FilesCommand extends Command
{
    protected $signature = 'code:base
        {--output-file=}
        {--limit-class=}
        {--limit-method=}';

    protected $description = 'Base command providing common arguments.';

    protected function getOutputFile(): ?string
    {
        $file = $this->option('output-file');
        if ($file && !str_ends_with(strtolower($file), '.json')) {
            $file .= '.json';
        }
        return $file ?: null;
    }

    protected function getClassLimit(): int
    {
        return (int) ($this->option('limit-class') ?: 0);
    }

    protected function getMethodLimit(): int
    {
        return (int) ($this->option('limit-method') ?: 0);
    }
}