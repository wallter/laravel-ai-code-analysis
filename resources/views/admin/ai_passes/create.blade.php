@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">Create New AI Pass</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Fill in the details below to create a new AI Pass.
            </p>
        </div>
        <x-button href="{{ route('admin.ai-passes.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            &larr; Back to List
        </x-button>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <x-alert class="bg-red-100 border-red-400 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <!-- Form Section -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-5 transition-colors">
        <form action="{{ route('admin.ai-passes.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- AI Configuration ID (Hidden Field) -->
            <input type="hidden" name="ai_configuration_id" value="{{ auth()->user()->aiConfiguration->id }}">
            
            <!-- Pass Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pass Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Enter pass name"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Enter pass description (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('description') }}</textarea>
            </div>

            <!-- Operation Identifier -->
            <div>
                <label for="operation_identifier" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Operation Identifier</label>
                <input
                    type="text"
                    id="operation_identifier"
                    name="operation_identifier"
                    value="{{ old('operation_identifier') }}"
                    placeholder="Enter operation identifier"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <!-- Model Selection -->
            <div>
                <label for="model_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">AI Model</label>
                <select
                    id="model_id"
                    name="model_id"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >
                    <option value="">-- Select AI Model (Optional) --</option>
                    @foreach($aiModels as $model)
                        <option value="{{ $model->id }}" {{ old('model_id') == $model->id ? 'selected' : '' }}>
                            {{ $model->model_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Max Tokens -->
            <div>
                <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                <input
                    type="number"
                    id="max_tokens"
                    name="max_tokens"
                    value="{{ old('max_tokens') }}"
                    placeholder="Enter maximum tokens (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    min="1"
                />
            </div>

            <!-- Temperature -->
            <div>
                <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Temperature</label>
                <input
                    type="number"
                    step="0.01"
                    id="temperature"
                    name="temperature"
                    value="{{ old('temperature') }}"
                    placeholder="Enter temperature (0-1) (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    min="0" max="1"
                />
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                <select
                    id="type"
                    name="type"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                >
                    <option value="">-- Select Type --</option>
                    <option value="single" {{ old('type') == 'single' ? 'selected' : '' }}>Single</option>
                    <option value="both" {{ old('type') == 'both' ? 'selected' : '' }}>Both</option>
                </select>
            </div>

            <!-- Supports System Message -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="supports_system_message"
                    name="supports_system_message"
                    value="1"
                    class="h-4 w-4 text-blue-600 dark:text-green-500 border-gray-300 dark:border-gray-700 rounded"
                    {{ old('supports_system_message') ? 'checked' : '' }}
                />
                <label for="supports_system_message" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Supports System Message
                </label>
            </div>

            <!-- System Message -->
            <div>
                <label for="system_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">System Message</label>
                <textarea
                    id="system_message"
                    name="system_message"
                    rows="3"
                    placeholder="Enter system message (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('system_message') }}</textarea>
            </div>

            <!-- Prompt Sections -->
            <div>
                <label for="prompt_sections" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt Sections</label>
                <textarea
                    id="prompt_sections"
                    name="prompt_sections"
                    rows="4"
                    placeholder="Enter prompt sections as JSON (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('prompt_sections') }}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Example:
<pre class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-xs">
[
    {
        "section": "Introduction",
        "content": "Provide a brief overview of the project."
    },
    {
        "section": "Requirements",
        "content": "List the project requirements."
    }
]
</pre>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end">
                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Create AI Pass
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
