@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold dark:text-white">Create New AI Model</h1>
        <x-button href="{{ route('admin.ai-models.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            &larr; Back to List
        </x-button>
    </div>

    @if ($errors->any())
        <x-alert class="bg-red-100 border-red-400 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-5 transition-colors">
        <form action="{{ route('admin.ai-models.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Enter model name"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Enter model description (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                <input
                    type="number"
                    id="max_tokens"
                    name="max_tokens"
                    value="{{ old('max_tokens') }}"
                    placeholder="Enter maximum tokens"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <div>
                <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Temperature</label>
                <input
                    type="number"
                    step="0.01"
                    id="temperature"
                    name="temperature"
                    value="{{ old('temperature') }}"
                    placeholder="Enter temperature (0-1)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

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

            <div>
                <label for="token_limit_parameter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token Limit Parameter</label>
                <input
                    type="text"
                    id="token_limit_parameter"
                    name="token_limit_parameter"
                    value="{{ old('token_limit_parameter') }}"
                    placeholder="Enter token limit parameter"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <div class="flex justify-end">
                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Create AI Model
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
