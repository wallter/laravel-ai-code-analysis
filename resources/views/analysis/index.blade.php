@extends('layouts.app')

@section('content')
<div class="mb-8 space-y-4">
  <!-- Page Title & Form -->
  <div>
    <h1 class="text-2xl font-bold mb-2">Analyses</h1>
    <p class="text-sm text-gray-600 mb-4">
      Enter a file or folder path below to queue an AI analysis.
    </p>

    <form action="{{ route('analysis.analyze') }}" method="POST" class="flex items-center space-x-2">
      @csrf
      <input
        type="text"
        name="filePath"
        placeholder="/path/to/your/code"
        class="border border-gray-300 px-3 py-2 rounded w-1/2 focus:outline-none focus:border-blue-500"
      />
      <button
        type="submit"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
      >
        Analyze
      </button>
    </form>
  </div>

  @if($analyses->isEmpty())
    <!-- Empty State -->
    <div class="bg-white border border-gray-200 rounded shadow-sm p-4">
      <p class="text-gray-600">No analysis records found. Please submit a file or folder path above.</p>
    </div>
  @else
    <!-- Table of Analyses -->
    <div class="overflow-x-auto">
      <table class="w-full table-auto bg-white shadow rounded">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold">File Path</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Current Pass</th>
            <th class="px-4 py-2 text-left text-sm font-semibold">Completed Passes</th>
            <th class="px-4 py-2 text-sm font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          @foreach ($analyses as $analysis)
            <tr class="hover:bg-gray-50 transition">
              <td class="px-4 py-2">
                <span class="font-medium text-gray-800">
                  {{ $analysis->file_path }}
                </span>
              </td>
              <td class="px-4 py-2">
                {{ $analysis->current_pass }}
              </td>
              <td class="px-4 py-2">
                @if(!empty($analysis->completed_passes))
                  {{ implode(', ', (array)$analysis->completed_passes) }}
                @else
                  <span class="text-gray-400">None</span>
                @endif
              </td>
              <td class="px-4 py-2 text-center">
                <a
                  href="{{ route('analysis.show', $analysis->id) }}"
                  class="inline-block bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 transition-colors"
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