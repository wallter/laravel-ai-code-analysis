<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-sm p-4 transition-colors ' . $class]) }}>
    {{ $slot }}
</div>
