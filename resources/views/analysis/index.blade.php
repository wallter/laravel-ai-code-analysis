@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
  <div>
    <h1 class="text-2xl font-bold mb-2 dark:text-white">Analyses</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
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
      <button
        type="submit"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors
               focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        Analyze
      </button>
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
                {{ $analysis->file_path }}
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
                <a
                  href="{{ route('analysis.show', $analysis->id) }}"
                  class="inline-block bg-indigo-600 text-white px-3 py-1 rounded
                         hover:bg-indigo-700 transition-colors
                         focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  View
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection