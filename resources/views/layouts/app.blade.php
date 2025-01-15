<!DOCTYPE html>
<html
    lang="en"
    x-data="{
        isDark: true,
        sidebarOpen: true,
        initTheme() {
            const stored = localStorage.getItem('theme');
            if (stored === 'light') {
                this.isDark = false;
            } else {
                this.isDark = true;
                localStorage.setItem('theme', 'dark');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js" defer></script>

    <!-- Dark mode first custom styles -->
    <style>
        .bg-forest-900 { background-color: #234d36; }
        .bg-forest-800 { background-color: #1c4030; }
        .text-forest-lt { color: #e0f2e9; }
        .border-forest { border-color: #234d36; }
        .sidebar-transition { transition: width 0.3s, margin 0.3s; }

        /* Default theme colors */
        body {
            background-color: #1a202c; /* Dark mode background */
            color: #f7fafc; /* Dark mode text */
            transition: background-color 0.3s, color 0.3s;
        }
        body.light {
            background-color: #f7fafc; /* Light mode background */
            color: #2d3748; /* Light mode text */
        }
    </style>
</head>
<body :class="{ 'light': !isDark }" class="min-h-screen transition-colors">

<!-- Container for entire app layout -->
<div class="flex min-h-screen">

    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Mobile hamburger (always visible on small screens) -->
    <div class="bg-forest-900 text-forest-lt p-2 flex sm:hidden items-center justify-between w-full">
      <div class="font-bold">
        {{ config('app.name') }}
      </div>
      <button 
        @click="toggleSidebar()"
        class="p-1 focus:outline-none hover:bg-forest-800 rounded"
      >
        <svg x-show="!sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <svg x-show="sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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