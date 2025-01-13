@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">AI Model Details</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                View and manage the details of the AI model below.
            </p>
        </div>
        <x-button href="{{ route('admin.ai-models.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            &larr; Back to List
        </x-button>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('success') }}
        </x-alert>
    @endif

    <!-- AI Model Details Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-5 transition-colors">
        <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Model Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Model Name:</span>
                <p class="text-gray-800 dark:text-gray-100">{{ $aiModel->model_name }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Description:</span>
                <p class="text-gray-800 dark:text-gray-100">{{ $aiModel->description ?? 'N/A' }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Max Tokens:</span>
                <p class="text-gray-800 dark:text-gray-100">{{ $aiModel->max_tokens ?? 'Unlimited' }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Temperature:</span>
                <p class="text-gray-800 dark:text-gray-100">{{ $aiModel->temperature ?? 'Default' }}</p>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Supports System Message:</span>
                <p class="text-gray-800 dark:text-gray-100">
                    {{ $aiModel->supports_system_message ? 'Yes' : 'No' }}
                </p>
            </div>
            <div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Token Limit Parameter:</span>
                <p class="text-gray-800 dark:text-gray-100">{{ $aiModel->token_limit_parameter ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- AI Model Actions -->
    <div class="flex space-x-2">
        <x-button href="{{ route('admin.ai-models.edit', $aiModel->id) }}" class="bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
            Edit AI Model
        </x-button>
        
        <form action="{{ route('admin.ai-models.destroy', $aiModel->id) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this AI model?');">
                Delete AI Model
            </x-button>
        </form>
    </div>
</div>
@endsection
