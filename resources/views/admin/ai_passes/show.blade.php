@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold dark:text-white">AI Pass Details</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">View the details of the AI Pass below.</p>
        </div>
        <div class="flex space-x-2">
            <x-button href="{{ route('admin.ai-passes.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                &larr; Back to List
            </x-button>
            <x-button href="{{ route('admin.ai-passes.edit', $aiPass->id) }}" class="bg-blue-600 text-white hover:bg-blue-700">
                Edit AI Pass
            </x-button>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('success') }}
        </x-alert>
    @endif

    <!-- AI Pass Details -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-6 transition-colors">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold dark:text-white">General Information</h2>
                <div class="mt-2">
                    <p><strong>Name:</strong> {{ $aiPass->name }}</p>
                    <p><strong>Description:</strong> {{ $aiPass->description ?? 'N/A' }}</p>
                    <p><strong>Operation Identifier:</strong> {{ $aiPass->operation_identifier }}</p>
                    <p><strong>Type:</strong> {{ ucfirst($aiPass->type) }}</p>
                </div>
            </div>
            <div>
                <h2 class="text-lg font-semibold dark:text-white">Configuration</h2>
                <div class="mt-2">
                    <p><strong>AI Configuration ID:</strong> {{ $aiPass->ai_configuration_id }}</p>
                    <p><strong>Model:</strong> {{ $aiPass->model ? $aiPass->model->model_name : 'N/A' }}</p>
                    <p><strong>Max Tokens:</strong> {{ $aiPass->max_tokens ?? 'N/A' }}</p>
                    <p><strong>Temperature:</strong> {{ $aiPass->temperature ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold dark:text-white">System Message</h2>
                <div class="mt-2">
                    <p>{{ $aiPass->supports_system_message ? $aiPass->system_message : 'N/A' }}</p>
                </div>
            </div>
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold dark:text-white">Prompt Sections</h2>
                <div class="mt-2">
                    @if($aiPass->prompt_sections)
                        <pre class="bg-gray-100 dark:bg-gray-700 p-4 rounded text-sm overflow-auto">{{ json_encode($aiPass->prompt_sections, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <p>N/A</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
