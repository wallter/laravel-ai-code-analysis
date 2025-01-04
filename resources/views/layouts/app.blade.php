<!DOCTYPE html>
<html lang="en" x-data="themeToggle" x-init="init()" :class="{ 'dark': isDark }">
<head>
  <meta charset="UTF-8">
  <title>Laravel AI Code Analysis</title>

  <!-- Example: Vite references to compile your Tailwind + JS -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Alpine.js for collapsible cards & theme toggling -->
  <script src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js" defer></script>

  <!-- Inline Alpine for theme data -->
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('themeToggle', () => ({
        isDark: true, // default to dark (if no stored preference)

        init() {
          // Check localStorage for theme preference
          const storedTheme = localStorage.getItem('theme');
          if (!storedTheme) {
            // no preference => default to dark
            this.isDark = true;
            localStorage.setItem('theme', 'dark');
          } else {
            // Use user preference
            this.isDark = (storedTheme === 'dark');
          }
        },

        toggleTheme() {
          this.isDark = !this.isDark;
          localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        }
      }));
    });
  </script>

  <!-- Optional custom styling for code blocks, etc. -->
  <style>
    .markdown-body pre, .markdown-body code {
      background-color: #f1f5f9;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
    }
    .markdown-body h1, .markdown-body h2, .markdown-body h3 {
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
  </style>
</head>

<!-- 
  We use :class="{ 'dark': isDark }" 
  to add the .dark class at root level 
  => tailwind's dark mode classes apply.
-->
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen transition-colors">
  <nav class="bg-white dark:bg-gray-800 shadow mb-4 transition-colors">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between">
      <!-- Site Title -->
      <div class="text-xl font-semibold dark:text-gray-100">
        Laravel AI Code Analysis
      </div>

      <!-- Theme Toggle (Switch) -->
      <div class="flex items-center space-x-4">
        <!-- Switch Container -->
        <label class="relative inline-flex items-center cursor-pointer">
          <!-- Hidden checkbox controlling dark/light -->
          <input 
            type="checkbox" 
            class="sr-only peer"
            x-model="isDark"
            @change="toggleTheme"
          />
          <!-- Track -->
          <div 
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none 
                   dark:bg-gray-700 rounded-full peer 
                   peer-checked:bg-blue-600 transition-colors 
                   relative"
          >
            <!-- Knob -->
            <div 
              class="absolute top-0.5 left-0.5 h-5 w-5 bg-white rounded-full 
                     border border-gray-300 dark:border-gray-600
                     peer-checked:translate-x-full 
                     peer-checked:border-white 
                     transition-transform"
            ></div>
          </div>
        </label>

        <!-- Text label that updates based on isDark -->
        <span class="text-sm dark:text-gray-100" x-text="isDark ? 'Dark Mode' : 'Light Mode'"></span>
      </div>
    </div>
  </nav>

  <!-- Main container -->
  <div class="container mx-auto px-4">
    @yield('content')
  </div>
</body>
</html>