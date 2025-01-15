@extends('layouts.admin')

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
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 bg-white border-b border-gray-200 text-gray-800 text-left text-sm uppercase font-normal">
                                    Pass Name
                                </th>
                                <th class="px-5 py-3 bg-white border-b border-gray-200 text-gray-800 text-left text-sm uppercase font-normal">
                                    Order
                                </th>
                                <th class="px-5 py-3 bg-white border-b border-gray-200"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($passOrders as $passOrder)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        {{ $passOrder->aiPass->name }}
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        {{ $passOrder->order }}
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-right">
                                        <div class="flex items-center space-x-4">
                                            <a href="{{ route('admin.pass-orders.edit', $passOrder->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.pass-orders.destroy', $passOrder->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this pass order?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-5 bg-white text-sm text-center text-gray-500">
                                        No pass orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                        <span class="text-xs xs:text-sm text-gray-900">
                            Showing {{ $passOrders->count() }} Pass Orders
                        </span>
                        <div class="inline-flex mt-2 xs:mt-0">
                            {{ $passOrders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
