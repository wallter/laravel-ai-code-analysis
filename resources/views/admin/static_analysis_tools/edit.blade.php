@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <!-- Success Message -->
    @if (session('success'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('success') }}
        </x-alert>
    @endif

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

    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">Edit Static Analysis Tool</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Update the details of the static analysis tool below.
            </p>
        </div>
        <x-button href="{{ route('admin.static-analysis-tools.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            &larr; Back to List
        </x-button>
    </div>

    <!-- Edit Form Section -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-5 transition-colors">
        <form action="{{ route('admin.static-analysis-tools.update', $tool->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Tool Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tool Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $tool->name) }}"
                    placeholder="Enter tool name"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <!-- Command -->
            <div>
                <label for="command" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Command</label>
                <input
                    type="text"
                    id="command"
                    name="command"
                    value="{{ old('command', $tool->command) }}"
                    placeholder="Enter command to execute"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <!-- Options -->
            <div>
                <label for="options" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Options (JSON Format)</label>
                <textarea
                    id="options"
                    name="options"
                    rows="4"
                    placeholder='e.g., {"option1": "value1", "option2": "value2"}'
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('options', json_encode($tool->options, JSON_PRETTY_PRINT)) }}</textarea>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Enter options as a valid JSON object. Leave empty if not applicable.
                </p>
            </div>

            <!-- Output Format -->
            <div>
                <label for="output_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Output Format</label>
                <input
                    type="text"
                    id="output_format"
                    name="output_format"
                    value="{{ old('output_format', $tool->output_format) }}"
                    placeholder="e.g., JSON, XML"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <!-- Enabled -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="enabled"
                    name="enabled"
                    value="1"
                    class="h-4 w-4 text-blue-600 dark:text-green-500 border-gray-300 dark:border-gray-700 rounded"
                    {{ old('enabled', $tool->enabled) ? 'checked' : '' }}
                />
                <label for="enabled" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Enable this tool
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2">
                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Update Static Analysis Tool
                </x-button>

                <x-button href="{{ route('admin.static-analysis-tools.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                    Cancel
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
