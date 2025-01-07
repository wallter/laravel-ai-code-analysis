<!DOCTYPE html>
<html
    lang="en"
    x-data="{
        isDark: true,
        sidebarOpen: false,
        initTheme() {
            const stored = localStorage.getItem('theme');
            if (!stored) {
                this.isDark = true;
                localStorage.setItem('theme', 'dark');
            } else {
                this.isDark = (stored === 'dark');
            }
        },
        toggleTheme() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        },
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        }
    }"
    x-init="initTheme()"
    :class="{ 'dark': isDark }"
    class="h-full w-full transition-colors antialiased"
>
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js" defer></script>

    <!-- Some custom classes for green theme -->
    <style>
        .bg-forest-900 { background-color: #234d36; }
        .bg-forest-800 { background-color: #1c4030; }
        .text-forest-lt { color: #e0f2e9; }
        .border-forest  { border-color: #234d36; }
        /* If you want transitions for the sidebar */
        .sidebar-transition {
            transition: width 0.3s, margin 0.3s;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors">

<!-- Container for entire app layout -->
<div class="flex min-h-screen">

    <!-- Sidebar, shrinks rather than overlays. We use 'w-64' for open, 'w-16' for collapsed -->
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
                    <!-- If open => show X, if closed => show bars -->
                    <svg x-show="sidebarOpen" xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 transition-all duration-300"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M6 18L18 6M6 6l12 12"/>
                    </svg>
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

    <!-- Mobile hamburger (always visible on small screens) -->
    <div class="bg-forest-900 text-forest-lt p-2 flex sm:hidden items-center justify-between w-full">
      <div class="font-bold">
        {{ config('app.name') }}
      </div>
      <button 
        @click="sidebarOpen = !sidebarOpen"
        class="p-1 focus:outline-none hover:bg-forest-800 rounded"
      >
        <!-- Bars / X toggles similarly -->
        <svg x-show="!sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <svg x-show="sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                 d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Main Content area -->
    <div class="flex-1 flex flex-col">
      <!-- Breadcrumb or top bar for secondary navigation -->
      <div class="bg-gray-100 dark:bg-gray-700 text-sm px-4 py-2 flex items-center space-x-2
                  border-b border-gray-200 dark:border-gray-600">
        <a href="#" class="hover:underline">Home</a>
        <span>/</span>
        <a href="{{ route('analysis.index') }}" class="hover:underline">Analyses</a>
        <span>/</span>
        <span class="font-bold">Show</span>
      </div>

      <!-- Main scrollable content -->
      <main class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900
                   text-gray-800 dark:text-gray-100 transition-colors">
        @yield('content')
      </main>
    </div>

</div>
</body>
</html>