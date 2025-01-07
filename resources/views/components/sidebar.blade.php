<aside 
    class="sidebar-transition bg-forest-900 text-forest-lt flex flex-col justify-between
           h-full sticky top-0 hidden sm:flex min-h-screen"
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
            <a href="{{ route('analysis.index') }}"
               class="block py-2 px-2 hover:bg-forest-800 rounded mb-1 transition-colors"
               :class="sidebarOpen ? 'text-sm' : 'text-xs text-center'"
            >
              <span x-show="sidebarOpen" x-transition>Analyses</span>
              <span x-show="!sidebarOpen" class="block" x-transition>A</span>
            </a>
            <hr class="bg-gray-200 h-px border-0 opacity-50 my-2">
            <a href="https://www.linkedin.com/in/tylerrwall/" target="_blank"
               class="block py-2 px-2 hover:bg-forest-800 rounded mb-1 transition-colors"
               :class="sidebarOpen ? 'text-sm' : 'text-xs text-center'"
            >
              <span x-show="sidebarOpen" x-transition>Tyler Wall | LinkedIn</span>
              <span x-show="!sidebarOpen" class="block" x-transition>L</span>
            </a>
            <a href="https://github.com/wallter" target="_blank"
               class="block py-2 px-2 hover:bg-forest-800 rounded transition-colors"
               :class="sidebarOpen ? 'text-sm' : 'text-xs text-center'"
            >
              <span x-show="sidebarOpen" x-transition>Tyler Wall | Github</span>
              <span x-show="!sidebarOpen" class="block" x-transition>G</span>
            </a>
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
