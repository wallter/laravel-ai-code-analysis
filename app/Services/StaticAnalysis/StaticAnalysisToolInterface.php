<?php

namespace App\Services\StaticAnalysis;

use App\Models\CodeAnalysis;
use App\Models\StaticAnalysis;

interface StaticAnalysisToolInterface
{
    /**
     * Run static analysis on the given CodeAnalysis entry.
     *
     * @param CodeAnalysis $codeAnalysis
     * @return StaticAnalysis|null
     */
    public function runAnalysis(CodeAnalysis $codeAnalysis): ?StaticAnalysis;
}
