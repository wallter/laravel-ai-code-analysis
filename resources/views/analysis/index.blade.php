@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
    @if (session('status'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('status') }}
        </x-alert>
    @endif

    @if ($errors->any())
        <x-alert class="bg-red-100 border-red-400 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
    @if (session('status'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('status') }}
        </x-alert>
    @endif

    @if ($errors->any())
        <x-alert class="bg-red-100 border-red-400 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
    @if (session('status'))
        <x-alert class="bg-green-100 border-green-400 text-green-700">
            {{ session('status') }}
        </x-alert>
    @endif

    @if ($errors->any())
        <x-alert class="bg-red-100 border-red-400 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
  <div>
    <h1 class="text-2xl font-bold mb-2 dark:text-white">Analyses</h1>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold mb-2 dark:text-white">Analyses</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Enter a file or folder path below to queue an AI analysis:
            </p>
        </div>
        <div>
            <x-button href="{{ route('analysis.create') }}" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
                + Create New Analysis
            </x-button>
        </div>
    </div>
      Enter a file or folder path below to queue an AI analysis:
    </p>

    <form
      action="{{ route('analysis.analyze') }}"
      method="POST"
      class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2"
    >
      @csrf
      <input
        type="text"
        name="filePath"
        placeholder="/path/to/your/code"
        class="border border-gray-300 dark:border-gray-700 px-3 py-2 rounded w-full sm:w-1/2
               focus:outline-none focus:border-blue-500 bg-white dark:bg-gray-800
               dark:text-gray-100 transition-colors"
      />
      <x-button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
          Analyze
      </x-button>
    </form>
  </div>

  @if($analyses->isEmpty())
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                rounded shadow-sm p-4 transition-colors">
      <p class="text-gray-600 dark:text-gray-300">
        No analysis records found. Please submit a file or folder path above.
      </p>
    </div>
  @else
    <div class="overflow-x-auto rounded shadow-sm">
      <table class="min-w-full table-auto bg-white dark:bg-gray-800 transition-colors text-sm">
        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-4 py-2 text-left font-semibold">File Path</th>
            <th class="px-4 py-2 text-left font-semibold">Current Pass</th>
            <th class="px-4 py-2 text-left font-semibold">Completed Passes</th>
            <th class="px-4 py-2 font-semibold text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @foreach ($analyses as $analysis)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100 w-1/3 break-all">
                {{ $analysis->relative_file_path }}
              </td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100 w-1/4">
                {{ $analysis->current_pass }}
              </td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100 w-1/4">
                @if(!empty($analysis->completed_passes))
                  {{ implode(', ', (array)$analysis->completed_passes) }}
                @else
                  <span class="text-gray-400 dark:text-gray-500">None</span>
                @endif
              </td>
              <td class="px-4 py-2 text-center w-1/6">
                <x-button href="{{ route('analysis.edit', $analysis->id) }}" class="bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
                    Edit
                </x-button>
                
                <form action="{{ route('analysis.destroy', $analysis->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this analysis?');">
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
