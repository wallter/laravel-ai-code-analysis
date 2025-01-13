@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-6 space-y-4">
    <!-- Header -->
    <h2 class="text-2xl font-bold text-center dark:text-gray-100">Confirm Deletion</h2>
    <p class="text-gray-700 dark:text-gray-300 text-center">
        Are you sure you want to delete the AI model 
        <strong>{{ $aiModel->model_name }}</strong>? This action cannot be undone.
    </p>

    <!-- Action Buttons -->
    <div class="flex justify-center space-x-4">
        <!-- Cancel Button -->
        <x-button href="{{ route('admin.ai-models.show', $aiModel->id) }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            Cancel
        </x-button>

        <!-- Confirm Delete Form -->
        <form action="{{ route('admin.ai-models.destroy', $aiModel->id) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
                Delete AI Model
            </x-button>
        </form>
    </div>
</div>
@endsection
