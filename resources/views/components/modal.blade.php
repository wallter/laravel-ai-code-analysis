<div 
    x-data="{ open: false }" 
    @trigger-modal.window="if ($event.detail.id === '{{ $id }}') open = true"
    x-show="open"
    x-transition 
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title-{{ $id }}"
>
    <div 
        class="bg-white dark:bg-gray-800 rounded shadow-lg max-w-md w-full p-6"
        @click.away="open = false"
    >
        <h2 id="modal-title-{{ $id }}" class="text-xl font-semibold mb-4 dark:text-gray-100">{{ $title }}</h2>
        {{ $slot }}
        <div class="mt-6 flex justify-end space-x-2">
            <x-button @click="open = false" class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                Cancel
            </x-button>
            {{ $deleteForm }}
        </div>
    </div>
</div>
