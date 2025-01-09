@extends('layouts.app')

@section('content')
@php
    dump($analysis->toArray());
    // Sort AI results, prioritizing the consolidation pass
    $sortedAiResults = $analysis->aiResults->sortByDesc(function($result) {
        return $result->pass_name === App\Enums\OperationIdentifier::CONSOLIDATION_PASS->value;
    });

    // Total estimated cost from AI analyses
    $totalAICost = $totalAICost ?? 0;

    // Total errors from static analyses
    $totalStaticErrors = $totalStaticErrors ?? 0;

    // Sort static analyses by creation date descending
    $sortedStaticAnalyses = $analysis->staticAnalyses->sortByDesc('created_at');
@endphp

<div class="mb-6 space-y-4">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold leading-tight dark:text-white">Analysis Details</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Viewing analysis for:
                <span class="font-semibold">{{ $analysis->file_path }}</span>
            </p>
        </div>
        <div class="mt-2 sm:mt-0">
            <x-button href="{{ route('analysis.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                &larr; Back to List
            </x-button>
        </div>
    </div>

    <!-- Code Analysis Summary -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 rounded shadow-sm transition-colors">
        <h3 class="text-lg font-semibold mb-2 dark:text-gray-100">Code Analysis Summary</h3>
        <div class="text-sm text-gray-700 dark:text-gray-200 space-y-1">
            <p>
                <strong>Current Pass:</strong>
                {{ $analysis->current_pass }}
            </p>
            <p>
                <strong>Completed Passes:</strong>
                @if(!empty($analysis->completed_passes))
                    {{ implode(', ', (array)$analysis->completed_passes) }}
                @else
                    <span class="text-gray-400 dark:text-gray-500">None</span>
                @endif
            </p>
            @if($totalAICost > 0)
                <p class="mt-2">
                    <strong>Total Estimated AI Cost:</strong>
                    <span class="inline-block px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded ml-1">
                        ${{ number_format($totalAICost, 4) }}
                    </span>
                </p>
            @endif
            @if($totalStaticErrors > 0)
                <p class="mt-2">
                    <strong>Total Static Analysis Errors:</strong>
                    <span class="inline-block px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded ml-1">
                        {{ $totalStaticErrors }}
                    </span>
                </p>
            @endif
        </div>
    </div>
</div>

<!-- Display Message if No Results -->
@if($sortedAiResults->isEmpty() && $sortedStaticAnalyses->isEmpty())
    <p class="text-gray-600 dark:text-gray-300">
        No AI or static analysis results found. The processes may still be in the queue or incomplete.
    </p>
@else
    <!-- AI Analysis Results Section -->
    @if(!$sortedAiResults->isEmpty())
        <div x-data="{ expandAllAI: false }" class="space-y-4 mb-8">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold dark:text-gray-100">AI Analysis Results</h3>
                <button
                    type="button"
                    class="bg-blue-600 text-white text-sm px-3 py-2 rounded hover:bg-blue-700 transition
                           focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @click="expandAllAI = !expandAllAI"
                >
                    <span x-show="!expandAllAI">Expand All</span>
                    <span x-show="expandAllAI">Collapse All</span>
                </button>
            </div>

            <div class="space-y-6">
                @foreach($sortedAiResults as $result)
                    @php
                        $passName = $result->pass_name;
                        $passCost = $result->metadata['cost_estimate_usd'] ?? 0;
                        $aiOutput = $result->ai_output ?? 'No output available.';
                        $metadataUsage = $result->metadata['usage'] ?? [];

                        // Determine badge color based on cost
                        $costBadgeClass = $passCost > 0.01 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';

                        // Determine if this is the consolidation pass
                        $isConsolidation = $passName === App\Enums\OperationIdentifier::CONSOLIDATION_PASS->value;

                        // Default open state
                        $defaultOpenAI = $isConsolidation ? 'true' : 'false';
                    @endphp

                    <div
                        x-data="{ localOpenAI: {{ $defaultOpenAI }}, viewRawAI: false }"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
                               rounded shadow-sm transition-colors"
                    >
                        <!-- Header -->
                        <div
                            class="flex items-center justify-between px-4 py-3 cursor-pointer select-none 
                                   hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            @click="localOpenAI = !localOpenAI"
                        >
                            <div>
                                <h4 class="text-base font-semibold dark:text-gray-100 flex items-center">
                                    Pass:
                                    <span
                                        class="text-indigo-600 dark:text-indigo-400 ml-1 relative"
                                        x-data="{ showTipAI: false }"
                                        @mouseenter="showTipAI = true"
                                        @mouseleave="showTipAI = false"
                                        @click.stop
                                    >
                                        {{ $passName }}
                                        <div
                                            class="absolute bg-gray-200 dark:bg-gray-600 text-black dark:text-gray-100
                                                   text-xs p-2 rounded shadow w-64 mt-1 z-10 whitespace-pre-wrap
                                                   transition-all duration-300 origin-top"
                                            x-show="showTipAI"
                                            x-transition
                                            style="display: none;"
                                        >
                                            Detailed information about the AI pass.
                                        </div>
                                    </span>
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Created: {{ $result->created_at->format('Y-m-d H:i') }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if($passCost > 0)
                                    <span class="text-xs px-2 py-0.5 rounded {{ $costBadgeClass }}">
                                        ${{ number_format($passCost, 4) }}
                                    </span>
                                @endif
                                <svg
                                    class="w-5 h-5 text-gray-600 dark:text-gray-300 transform transition-transform duration-200"
                                    :class="{ 'rotate-180': expandAllAI || localOpenAI }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round" 
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Content -->
                        <div
                            class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 relative"
                            x-show="expandAllAI || localOpenAI"
                            x-transition
                        >
                            <!-- Toggle View Button -->
                            <button
                                class="absolute top-3 right-3 text-xs px-2 py-1 border border-gray-400
                                       dark:border-gray-500 rounded bg-white dark:bg-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200
                                       transition-colors focus:outline-none focus:ring-1 focus:ring-gray-400"
                                @click.stop="viewRawAI = !viewRawAI"
                            >
                                <span x-show="!viewRawAI">Show Rendered</span>
                                <span x-show="viewRawAI">Show Raw</span>
                            </button>

                            <!-- Rendered AI Output -->
                            <div x-show="!viewRawAI" x-transition>
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded shadow mt-2 transition-colors">
                                    <div class="prose prose-indigo max-w-none dark:prose-invert">
                                        {!! \Illuminate\Support\Str::markdown($aiOutput) !!}
                                    </div>
                                </div>
                            </div>

                            <!-- Raw AI Output -->
                            <div x-show="viewRawAI" x-transition>
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded shadow mt-2 transition-colors">
                                    <pre class="text-xs leading-relaxed whitespace-pre-wrap text-gray-800 dark:text-gray-100">
<code class="language-md">
{{ $aiOutput }}
</code>
                                    </pre>
                                </div>
                            </div>

                            <!-- AI Token Usage -->
                            @if(!empty($metadataUsage))
                                <div class="text-sm text-gray-700 dark:text-gray-200 mt-4 border-t 
                                            border-gray-200 dark:border-gray-600 pt-2 transition-colors">
                                    <p class="mb-1 font-semibold">OpenAI Token Usage:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Prompt Tokens: {{ $metadataUsage['prompt_tokens'] ?? 0 }}</li>
                                        <li>Completion Tokens: {{ $metadataUsage['completion_tokens'] ?? 0 }}</li>
                                        <li>Total Tokens: {{ $metadataUsage['total_tokens'] ?? 0 }}</li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Static Analysis Runs Section -->
    @if(!$sortedStaticAnalyses->isEmpty())
        <div x-data="{ expandAllStatic: false }" class="space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold dark:text-gray-100">Static Analysis Runs</h3>
                <button
                    type="button"
                    class="bg-green-600 text-white text-sm px-3 py-2 rounded hover:bg-green-700 transition
                           focus:outline-none focus:ring-2 focus:ring-green-500"
                    @click="expandAllStatic = !expandAllStatic"
                >
                    <span x-show="!expandAllStatic">Expand All</span>
                    <span x-show="expandAllStatic">Collapse All</span>
                </button>
            </div>

            <div class="space-y-6">
                @foreach($sortedStaticAnalyses as $staticAnalysis)
                    @php
                        $toolName = $staticAnalysis->tool;
                        $results = $staticAnalysis->results;
                        $errors = $results['errors'] ?? [];
                        $fileErrors = $results['totals']['file_errors'] ?? 0;
                        $totalErrors = $results['totals']['errors'] ?? 0;

                        // Determine badge color based on errors
                        $errorBadgeClass = $totalErrors > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';

                        // Default open state for the latest static analysis
                        $defaultOpenStatic = $loop->first ? 'true' : 'false';
                    @endphp

                    <div
                        x-data="{ localOpenStatic: {{ $defaultOpenStatic }}, viewRawStatic: false }"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
                               rounded shadow-sm transition-colors"
                    >
                        <!-- Header -->
                        <div
                            class="flex items-center justify-between px-4 py-3 cursor-pointer select-none 
                                   hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            @click="localOpenStatic = !localOpenStatic"
                        >
                            <div>
                                <h4 class="text-base font-semibold dark:text-gray-100 flex items-center">
                                    Tool:
                                    <span
                                        class="text-indigo-600 dark:text-indigo-400 ml-1 relative"
                                        x-data="{ showTipStatic: false }"
                                        @mouseenter="showTipStatic = true"
                                        @mouseleave="showTipStatic = false"
                                        @click.stop
                                    >
                                        {{ $toolName }}
                                        <div
                                            class="absolute bg-gray-200 dark:bg-gray-600 text-black dark:text-gray-100
                                                   text-xs p-2 rounded shadow w-64 mt-1 z-10 whitespace-pre-wrap
                                                   transition-all duration-300 origin-top"
                                            x-show="showTipStatic"
                                            x-transition
                                            style="display: none;"
                                        >
                                            Detailed information about the static analysis tool.
                                        </div>
                                    </span>
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Created: {{ $staticAnalysis->created_at->format('Y-m-d H:i') }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if($totalErrors > 0)
                                    <span class="text-xs px-2 py-0.5 rounded {{ $errorBadgeClass }}">
                                        {{ $totalErrors }} Errors
                                    </span>
                                @endif
                                <svg
                                    class="w-5 h-5 text-gray-600 dark:text-gray-300 transform transition-transform duration-200"
                                    :class="{ 'rotate-180': expandAllStatic || localOpenStatic }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round" 
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Content -->
                        <div
                            class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 relative"
                            x-show="expandAllStatic || localOpenStatic"
                            x-transition
                        >
                            <!-- Toggle View Button -->
                            <button
                                class="absolute top-3 right-3 text-xs px-2 py-1 border border-gray-400
                                       dark:border-gray-500 rounded bg-white dark:bg-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200
                                       transition-colors focus:outline-none focus:ring-1 focus:ring-gray-400"
                                @click.stop="viewRawStatic = !viewRawStatic"
                            >
                                <span x-show="!viewRawStatic">Show Rendered</span>
                                <span x-show="viewRawStatic">Show Raw</span>
                            </button>

                            <!-- Rendered Static Analysis Results -->
                            <div x-show="!viewRawStatic" x-transition>
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded shadow mt-2 transition-colors">
                                    @if($fileErrors > 0)
                                        <div class="mb-2">
                                            <span class="font-semibold">File Errors:</span> {{ $fileErrors }}
                                        </div>
                                    @endif
                                    @if(!empty($errors))
                                        <div>
                                            <span class="font-semibold">Errors:</span>
                                            <ul class="list-disc list-inside">
                                                @foreach($errors as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <p class="text-green-700 dark:text-green-300">No errors found.</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Raw Static Analysis Results -->
                            <div x-show="viewRawStatic" x-transition>
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded shadow mt-2 transition-colors">
                                    <pre class="text-xs leading-relaxed whitespace-pre-wrap text-gray-800 dark:text-gray-100">
<code class="language-json">
{{ json_encode($results, JSON_PRETTY_PRINT) }}
</code>
                                    </pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif
@endsection
