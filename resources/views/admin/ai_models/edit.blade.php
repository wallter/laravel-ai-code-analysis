@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">Edit AI Model</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Update the details of the AI model below.
            </p>
        </div>
        <x-button href="{{ route('admin.ai-models.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
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
        <form action="{{ route('admin.ai-models.update', $aiModel->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Model Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $aiModel->model_name) }}"
                    placeholder="Enter model name"
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
                    placeholder="Enter model description (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('description', $aiModel->description) }}</textarea>
            </div>

            <!-- Max Tokens -->
            <div>
                <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                <input
                    type="number"
                    id="max_tokens"
                    name="max_tokens"
                    value="{{ old('max_tokens', $aiModel->max_tokens) }}"
                    placeholder="Enter maximum tokens"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
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
                    value="{{ old('temperature', $aiModel->temperature) }}"
                    placeholder="Enter temperature (0-1)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <!-- Supports System Message -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="supports_system_message"
                    name="supports_system_message"
                    value="1"
                    class="h-4 w-4 text-blue-600 dark:text-green-500 border-gray-300 dark:border-gray-700 rounded"
                    {{ old('supports_system_message', $aiModel->supports_system_message) ? 'checked' : '' }}
                />
                <label for="supports_system_message" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Supports System Message
                </label>
            </div>

            <!-- Token Limit Parameter -->
            <div>
                <label for="token_limit_parameter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token Limit Parameter</label>
                <input
                    type="text"
                    id="token_limit_parameter"
                    name="token_limit_parameter"
                    value="{{ old('token_limit_parameter', $aiModel->token_limit_parameter) }}"
                    placeholder="Enter token limit parameter"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2">
                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Update AI Model
                </x-button>

                <form action="{{ route('admin.ai-models.destroy', $aiModel->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this AI model?');">
                        Delete AI Model
                    </x-button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">Edit AI Model</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Update the details of the AI model below.
            </p>
        </div>
        <x-button href="{{ route('admin.ai-models.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
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
        <form action="{{ route('admin.ai-models.update', $aiModel->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Model Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $aiModel->model_name) }}"
                    placeholder="Enter model name"
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
                    placeholder="Enter model description (optional)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                >{{ old('description', $aiModel->description) }}</textarea>
            </div>

            <!-- Max Tokens -->
            <div>
                <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                <input
                    type="number"
                    id="max_tokens"
                    name="max_tokens"
                    value="{{ old('max_tokens', $aiModel->max_tokens) }}"
                    placeholder="Enter maximum tokens"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
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
                    value="{{ old('temperature', $aiModel->temperature) }}"
                    placeholder="Enter temperature (0-1)"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <!-- Supports System Message -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="supports_system_message"
                    name="supports_system_message"
                    value="1"
                    class="h-4 w-4 text-blue-600 dark:text-green-500 border-gray-300 dark:border-gray-700 rounded"
                    {{ old('supports_system_message', $aiModel->supports_system_message) ? 'checked' : '' }}
                />
                <label for="supports_system_message" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Supports System Message
                </label>
            </div>

            <!-- Token Limit Parameter -->
            <div>
                <label for="token_limit_parameter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token Limit Parameter</label>
                <input
                    type="text"
                    id="token_limit_parameter"
                    name="token_limit_parameter"
                    value="{{ old('token_limit_parameter', $aiModel->token_limit_parameter) }}"
                    placeholder="Enter token limit parameter"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                />
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2">
                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Update AI Model
                </x-button>

                <form action="{{ route('admin.ai-models.destroy', $aiModel->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this AI model?');">
                        Delete AI Model
                    </x-button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
