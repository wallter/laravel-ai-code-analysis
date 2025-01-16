@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Success Alert -->
    <x-alert class="bg-green-100 border-green-400 text-green-700">
        Static Analysis Tool "<strong>{{ session('tool_name') }}</strong>" has been created successfully.
    </x-alert>

    <!-- Action Buttons -->
    <div class="mt-6 flex space-x-4">
        <x-button href="{{ route('admin.static-analysis-tools.index') }}" class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
            Back to Tools List
        </x-button>
        <x-button href="{{ route('admin.static-analysis-tools.create') }}" class="bg-green-600 text-white hover:bg-green-700 focus:ring-green-500">
            Create Another Tool
        </x-button>
    </div>
</div>
@endsection
