@extends('layouts.app')

@section('content')

<div class="mb-6">
  <!-- Header / Page Title -->
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold leading-tight">Analysis Details</h2>
      <p class="text-sm text-gray-500 mt-1">
        Viewing AI results for: <span class="font-semibold">{{ $analysis->file_path }}</span>
      </p>
    </div>
    <a
      href="{{ route('analysis.index') }}"
      class="inline-block bg-gray-100 border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-200 transition"
    >
      &larr; Back to List
    </a>
  </div>
</div>

<!-- Analysis summary card -->
<div class="bg-white border border-gray-200 p-5 rounded shadow-sm mb-8">
  <h3 class="text-lg font-semibold mb-2">
    Code Analysis Summary
  </h3>
  <div class="text-sm text-gray-700 space-y-1">
    <p>
      <strong>Current Pass:</strong>
      {{ $analysis->current_pass }}
    </p>
    <p>
      <strong>Completed Passes:</strong>
      @if(!empty($analysis->completed_passes))
        {{ implode(', ', (array) $analysis->completed_passes) }}
      @else
        <span class="text-gray-400">None</span>
      @endif
    </p>
    @if(isset($totalCost))
      <p class="mt-2">
        <strong>Total Estimated Cost:</strong>
        <span class="inline-block px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded ml-1">
          ${{ number_format($totalCost, 4) }}
        </span>
      </p>
    @endif
  </div>
</div>

@if($analysis->aiResults->isEmpty())
  <p class="text-gray-600 mb-8">
    No AI results yet. The passes may still be in the queue or incomplete.
  </p>
@else

  <!-- Alpine container for the "Expand All / Collapse All" functionality -->
  <div x-data="{ expandAll: false }" class="space-y-4">

    <!-- Expand/Collapse All Button -->
    <div class="flex justify-end mb-2">
      <button
        type="button"
        class="bg-blue-600 text-white text-sm px-3 py-2 rounded hover:bg-blue-700 transition"
        @click="expandAll = !expandAll"
      >
        <span x-show="!expandAll">Expand All</span>
        <span x-show="expandAll">Collapse All</span>
      </button>
    </div>

    <div class="space-y-6">
      @foreach($analysis->aiResults as $result)
        @php
          $passCost = $result->metadata['cost_estimate_usd'] ?? 0;
          $highlight = $passCost > 0.01;  // Adjust threshold if desired
          $rawMarkdown = $result->response_text ?? '';
        @endphp

        <!-- Each pass item -->
        <div
          x-data="{ localOpen: false, viewRaw: false }"
          class="bg-white border border-gray-200 rounded shadow-sm"
        >
          <!-- Pass Header -->
          <div
            class="flex items-center justify-between px-4 py-3 cursor-pointer select-none hover:bg-gray-50 transition"
            @click="localOpen = !localOpen"
          >
            <!-- Title & Timestamp -->
            <div>
              <h4 class="text-base font-semibold">
                Pass: <span class="text-indigo-600">{{ $result->pass_name }}</span>
              </h4>
              <span class="text-xs text-gray-500">
                Created: {{ $result->created_at->format('Y-m-d H:i') }}
              </span>
            </div>

            <!-- Pass cost + Chevron icon -->
            <div class="flex items-center space-x-2">
              @if($passCost > 0)
                <span
                  class="text-xs px-2 py-0.5 rounded
                    {{ $highlight ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}"
                >
                  ${{ number_format($passCost, 4) }}
                </span>
              @endif
              <svg
                class="w-5 h-5 text-gray-600 transform transition-transform duration-200"
                :class="{ 'rotate-180': expandAll || localOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
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

          <!-- Collapsible content: open if either expandAll or localOpen is true -->
          <div
            class="px-4 py-3 border-t border-gray-100"
            x-show="expandAll || localOpen"
            x-transition
          >
            <!-- Toggle: "Rendered" or "Raw" view -->
            <div class="text-right mb-2">
              <button
                class="text-sm px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 transition"
                @click.stop="viewRaw = !viewRaw"
              >
                <span x-show="!viewRaw">Show Raw</span>
                <span x-show="viewRaw">Show Rendered</span>
              </button>
            </div>

            <!-- Distinct container for Markdown vs Raw -->
            <div x-show="!viewRaw" x-transition>
              <div class="bg-gray-50 p-4 rounded shadow mt-2">
                <div class="prose prose-indigo">
                    {!! \Illuminate\Support\Str::markdown($rawMarkdown) !!}
                </div>
              </div>
            </div>

            <div x-show="viewRaw" x-transition>
              <div class="bg-gray-50 p-4 rounded shadow mt-2">
                <pre class="text-xs leading-relaxed whitespace-pre-wrap">
                  <code class="language-md">
{{ $rawMarkdown }}
                  </code>
                </pre>
              </div>
            </div>

            <!-- Token usage if available -->
            @if(!empty($result->metadata['usage']))
              <div class="text-sm text-gray-700 mt-4 border-t border-gray-200 pt-2">
                <p class="mb-1 font-semibold">OpenAI Token Usage:</p>
                <ul class="list-disc list-inside space-y-1">
                  <li>Prompt: {{ $result->metadata['usage']['prompt_tokens'] ?? 0 }}</li>
                  <li>Completion: {{ $result->metadata['usage']['completion_tokens'] ?? 0 }}</li>
                  <li>Total: {{ $result->metadata['usage']['total_tokens'] ?? 0 }}</li>
                </ul>
              </div>
            @endif

          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif
@endsection
