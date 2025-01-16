@php
$navItems = [
    // Group 1
    [
        'label' => 'Analyses',
        'short_label' => 'A',
        'route' => route('analysis.index'),
        'disabled' => false, // If we want opacity-50
    ],
    // Separator
    [ 'separator' => true ],
    // Group 2
    [
        'label' => 'Admin',
        'short_label' => 'A',
        'route' => route('admin.ai-models.index'),
        'disabled' => true,
    ],
    [
        'label' => 'AI Models',
        'short_label' => 'A',
        'route' => route('admin.ai-models.index'),
        'disabled' => false,
    ],
    [
        'label' => 'AI Configurations',
        'short_label' => 'A',
        'route' => route('admin.ai-configurations.index'),
        'disabled' => false,
    ],
    [
        'label' => 'Static Analysis',
        'short_label' => 'A',
        'route' => route('admin.static-analysis-tools.index'),
        'disabled' => false,
    ],
    [
        'label' => 'AI Pass Orders',
        'short_label' => 'A',
        'route' => route('admin.pass-orders.index'),
        'disabled' => false,
    ],
    [
        'label' => 'AI Passes',
        'short_label' => 'A',
        'route' => route('admin.ai-passes.index'),
        'disabled' => false,
    ],
    // Separator
    [ 'separator' => true ],
    // Group 3 (External Links)
    [
        'label' => 'Tyler Wall',
        'short_label' => 'T',
        'route' => 'https://www.linkedin.com/in/tylerrwall/',
        'external' => true,
        'disabled' => true,
    ],
    [
        'label' => 'LinkedIn',
        'short_label' => 'L',
        'route' => 'https://www.linkedin.com/in/tylerrwall/',
        'external' => true,
        'disabled' => false,
    ],
    [
        'label' => 'Github',
        'short_label' => 'G',
        'route' => 'https://github.com/wallter',
        'external' => true,
        'disabled' => false,
    ],
];
@endphp

<aside 
    class="sidebar-transition bg-forest-900 text-forest-lt flex flex-col justify-between h-full sticky top-0 hidden sm:flex min-h-screen w-16"
    :class="sidebarOpen ? 'w-64' : 'w-16'"
>
    <!-- Top portion -->
    <div>
        <!-- Logo/App name section -->
        <div class="px-4 py-4 border-b border-forest flex items-center"
             :class="sidebarOpen ? 'justify-between' : 'justify-center'">
            <h2 x-show="sidebarOpen" class="font-bold text-lg" x-transition>
                {{ config('app.name') }}
            </h2>
            <!-- Animated Hamburger: changes lines -> X -->
            <button
                class="p-2 focus:outline-none hover:bg-forest-800 rounded sm:block hidden"
                @click.stop="toggleSidebar"
            >
                <!-- X Icon -->
                <svg x-show="sidebarOpen" xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 transition-all duration-300"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <!-- Hamburger Icon -->
                <svg x-show="!sidebarOpen" xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 transition-all duration-300"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <!-- Menu items -->
        <nav class="px-2 py-4">
          @foreach($navItems as $item)
              @if(isset($item['separator']) && $item['separator'] === true)
                  <hr class="bg-gray-200 h-px border-0 opacity-50 my-2">
              @else
                  <a 
                      href="{{ $item['route'] }}"
                      class="block py-2 px-2 hover:bg-forest-800 rounded mb-1 transition-colors {{ $item['disabled'] ? 'opacity-50' : '' }}"
                      :class="sidebarOpen ? 'text-sm' : 'text-xs text-center'"
                      @if(!empty($item['external'])) target="_blank" @endif
                  >
                      <span x-show="sidebarOpen" x-transition>{{ $item['label'] }}</span>
                      <span x-show="!sidebarOpen" class="block" x-transition>{{ $item['short_label'] }}</span>
                  </a>
              @endif
          @endforeach
      </nav>
    </div>

    <!-- Bottom portion: theme toggle, etc. -->
    <div class="px-2 py-4 border-t border-forest flex items-center justify-center">
        <button
          class="border border-forest px-3 py-1 rounded text-forest-lt
                 hover:bg-forest-800 transition-colors focus:outline-none focus:ring-2
                 focus:ring-offset-2 focus:ring-forest"
          @click="toggleTheme"
        >
          <span x-text="isDark ? 'Light' : 'Dark'"></span>
        </button>
    </div>
</aside>
