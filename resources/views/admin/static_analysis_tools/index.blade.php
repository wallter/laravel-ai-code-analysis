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
            <h1 class="text-2xl font-bold dark:text-white">Static Analysis Tools</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Manage your static analysis tools below.
            </p>
        </div>
        <x-button href="{{ route('admin.static-analysis-tools.create') }}" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
            + Create New Tool
        </x-button>
    </div>

    <!-- Static Analysis Tools Table -->
    @if($tools->isEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-4 transition-colors">
            <p class="text-gray-600 dark:text-gray-300">
                No static analysis tools found. Click the "Create New Tool" button to add one.
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded shadow-sm">
            <table class="min-w-full table-auto bg-white dark:bg-gray-800 transition-colors text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Name</th>
                        <th class="px-4 py-2 text-left font-semibold">Enabled</th>
                        <th class="px-4 py-2 text-left font-semibold">Command</th>
                        <th class="px-4 py-2 text-left font-semibold">Options</th>
                        <th class="px-4 py-2 text-left font-semibold">Output Format</th>
                        <th class="px-4 py-2 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tools as $tool)
                        <tr 
                            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('admin.static-analysis-tools.show', $tool->id) }}'"
                        >
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $tool->name }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $tool->enabled ? 'Yes' : 'No' }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $tool->command }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ is_array($tool->options) ? json_encode($tool->options) : 'N/A' }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                {{ $tool->output_format }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <x-button href="{{ route('admin.static-analysis-tools.show', $tool->id) }}" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
                                    View
                                </x-button>
                                <x-button href="{{ route('admin.static-analysis-tools.edit', $tool->id) }}" class="bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
                                    Edit
                                </x-button>
                                <button 
                                    @click.prevent="$dispatch('trigger-modal', { id: 'delete-modal-{{ $tool->id }}' })" 
                                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 focus:ring-red-500"
                                >
                                    Delete
                                </button>

                                <!-- Delete Confirmation Modal -->
                                <x-modal id="delete-modal-{{ $tool->id }}" title="Confirm Deletion">
                                    <p class="text-gray-700 dark:text-gray-300">
                                        Are you sure you want to delete the Static Analysis Tool <strong>{{ $tool->name }}</strong>? This action cannot be undone.
                                    </p>
                                    
                                    <!-- Delete Form Passed as Slot -->
                                    <x-slot name="deleteForm">
                                        <form action="{{ route('admin.static-analysis-tools.destroy', $tool->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
                                                Delete Static Analysis Tool
                                            </x-button>
                                        </form>
                                    </x-slot>
                                </x-modal>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
