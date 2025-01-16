@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-8">
        <div class="py-8">
            <div class="flex justify-between mb-4">
                <h2 class="text-2xl font-semibold leading-tight">Pass Orders</h2>
                <a href="{{ route('admin.pass-orders.create') }}" class="btn btn-primary">
                    Create Pass Order
                </a>
            </div>

            {{-- Display Success Alert --}}
            @if(session('success'))
                <x-alert class="alert-success">
                    {{ session('success') }}
                </x-alert>
            @endif

            <div class="-mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
                <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                    <table class="min-w-full table-auto bg-white dark:bg-gray-800 transition-colors text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold">Pass Name</th>
                                <th class="px-4 py-2 text-left font-semibold">Order</th>
                                <th class="px-4 py-2 font-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($passOrders as $passOrder)
                                <tr 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                >
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                        {{ $passOrder->aiPass?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                        {{ $passOrder->order }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <x-button href="{{ route('admin.pass-orders.edit', $passOrder->id) }}" class="bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500">
                                            Edit
                                        </x-button>
                                        <button 
                                            @click.prevent="$dispatch('trigger-modal', { id: 'delete-modal-{{ $passOrder->id }}' })" 
                                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 focus:ring-red-500"
                                        >
                                            Delete
                                        </button>

                                        <!-- Delete Confirmation Modal -->
                                        <x-modal id="delete-modal-{{ $passOrder->id }}" title="Confirm Deletion">
                                            <p class="text-gray-700 dark:text-gray-300">
                                                Are you sure you want to delete the Pass Order <strong>{{ $passOrder->aiPass?->name ?? 'N/A' }}</strong>? This action cannot be undone.
                                            </p>
                                            
                                            <!-- Delete Form Passed as Slot -->
                                            <x-slot name="deleteForm">
                                                <form action="{{ route('admin.pass-orders.destroy', $passOrder->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button type="submit" class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
                                                        Delete Pass Order
                                                    </x-button>
                                                </form>
                                            </x-slot>
                                        </x-modal>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-2 bg-white dark:bg-gray-800 text-sm text-center text-gray-500">
                                        No pass orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex items-center justify-between">
                        <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                            Total {{ count($passOrders) }} pass order{{ count($passOrders) !== 1 ? 's' : '' }} found.
                        </div>
                        <div>
                            {{-- Pagination links can be added here if needed --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
