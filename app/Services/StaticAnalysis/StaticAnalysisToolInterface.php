<?php

namespace App\Services\StaticAnalysis;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;

/**
 * Interface for static analysis tools.
 */
interface StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given CodeAnalysis entry.
     *
     * @param CodeAnalysis $codeAnalysis
     * @param string $toolName
     * @return StaticAnalysis|null
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis, string $toolName): ?StaticAnalysis;
}
