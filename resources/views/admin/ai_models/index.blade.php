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
            <h1 class="text-2xl font-bold dark:text-white">AI Models</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Manage your AI models below.
            </p>
        </div>
        <x-button href="{{ route('admin.ai-models.create') }}" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
            + Create New AI Model
        </x-button>
    </div>

    <!-- AI Models Table -->
    @if($aiModels->isEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-4 transition-colors">
            <p class="text-gray-600 dark:text-gray-300">
                No AI models found. Click the "Create New AI Model" button to add one.
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded shadow-sm">
            <table class="min-w-full table-auto bg-white dark:bg-gray-800 transition-colors text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Model Name</th>
                        <th class="px-4 py-2 text-left font-semibold">Description</th>
                        <th class="px-4 py-2 text-left font-semibold">Created At</th>
                        <th class="px-4 py-2 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($aiModels as $model)
                        <tr 
                            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('admin.ai-models.show', $model->id) }}'"
                        >
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $model->model_name }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ Str::limit($model->description, 50) }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $model->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <x-button href="{{ route('admin.ai-models.show', $model->id) }}" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
                                    View
                                </x-button>
                                <x-button href="{{ route('admin.ai-models.edit', $model->id) }}" class="bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
                                    Edit
                                </x-button>
                                <form action="{{ route('admin.ai-models.destroy', $model->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this AI model?');">
                                        Delete
                                    </x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
