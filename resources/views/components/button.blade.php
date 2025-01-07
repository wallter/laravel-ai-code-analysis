<button {{ $attributes->merge(['class' => 'px-4 py-2 rounded focus:outline-none focus:ring-2 transition-colors']) }}>
    {{ $slot }}
</button>
