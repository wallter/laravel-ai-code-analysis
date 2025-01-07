@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold dark:text-white">Edit Analysis</h1>
        <x-button href="{{ route('analysis.index') }}" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            &larr; Back to List
        </x-button>
    </div>

    @if ($errors->any())
        <x-alert>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li class="text-red-600 dark:text-red-400">{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-5 transition-colors">
        <form action="{{ route('analysis.update', $analysis) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="filePath" class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Path</label>
                <input
                    type="text"
                    id="filePath"
                    name="filePath"
                    value="{{ old('filePath', $analysis->file_path) }}"
                    placeholder="/path/to/your/code"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-700 px-3 py-2 rounded focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800 dark:text-gray-100 transition-colors"
                    required
                />
            </div>

            <div class="flex justify-end space-x-2">
                <x-button type="button" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this analysis?')) { document.getElementById('delete-form').submit(); }">
                    Delete
                </x-button>

                <x-button type="submit" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                    Update Analysis
                </x-button>
            </div>

            <!-- Delete Form -->
            <form id="delete-form" action="{{ route('analysis.destroy', $analysis) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </form>
    </div>
</div>
@endsection
