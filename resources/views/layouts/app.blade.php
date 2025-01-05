<!DOCTYPE html>
<html
    lang="en"
    x-data="themeToggle"
    x-init="init()"
    :class="{ 'dark': isDark }"
    class="h-full w-full transition-colors"
>
<head>
    <meta charset="UTF-8">
    <title>Laravel AI Code Analysis</title>

    <!-- Tailwind + your build pipeline -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js (defer load) -->
    <script src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js" defer></script>

    <!-- Early script to set dark mode if stored in localStorage -->
    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
    <!-- Inline script for theme toggle logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('themeToggle', () => ({
                isDark: true, // default => dark if no localStorage

                init() {
                    const storedTheme = localStorage.getItem('theme');
                    if (!storedTheme) {
                        this.isDark = true;
                        localStorage.setItem('theme', 'dark');
                        console.log('No theme => default to dark');
                    } else {
                        this.isDark = (storedTheme === 'dark');
                        console.log(`Loaded theme: ${storedTheme} -> isDark=${this.isDark}`);
                    }
                },

                toggleTheme() {
                    console.log(`Before toggling => isDark=${this.isDark}`);
                    this.isDark = !this.isDark;
                    console.log(`After toggling => isDark=${this.isDark}`);
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                }
            }));
        });
    </script>

    <!-- Optional custom styling for code blocks, etc. -->
    <style>
        .prose :where(hr):not(:where([class~="not-prose"],[class~="not-prose"] *)) {
            margin-top: 2rem;
            margin-bottom: 2rem;
         }

         .prose :where(pre) {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #111; /* Dark background to enhance glow visibility */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .glow-container {
            padding: 2em;
            border-radius: 1em;
            background: #222; 
            color: white;
            text-align: center;
            box-shadow: 
                0 0 20px rgba(255, 255, 255, 0.2),    /* Outer light glow */
                inset 0 0 20px rgba(255, 255, 255, 0.1); /* Subtle inner glow */

            animation: glowPulse 3s ease-in-out infinite alternate;
        }

        @keyframes glowPulse {
            0% {
                box-shadow: 
                    0 0 20px rgba(255, 255, 255, 0.2),
                    inset 0 0 20px rgba(255, 255, 255, 0.1);
            }
            100% {
                box-shadow: 
                    0 0 60px rgba(255, 255, 255, 0.7),
                    inset 0 0 30px rgba(255, 255, 255, 0.2);
            }
        }
    </style>
    
</head>

<!-- 
  :class="{ 'dark': isDark }" on <html> ensures the .dark class 
  is applied top-level for Tailwind’s dark mode. 
  We also do class="h-full w-full transition-colors" 
  so the entire page can animate color changes. 
-->
<body
    class="min-h-screen bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors"
>
    <nav class="bg-white dark:bg-gray-800 shadow mb-4 transition-colors">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <!-- Site Title -->
            <div class="text-xl font-semibold dark:text-gray-100">
                Laravel AI Code Analysis
                <small class="text-sm text-gray-400 dark:text-gray-400">
                    by Tyler Wall
                </small>
            </div>

            <!-- Theme Toggle (Outline Button) -->
            <div class="flex items-center space-x-4">
                <button
                    type="button"
                    @click="toggleTheme"
                    class="border border-gray-500 dark:border-gray-300 text-gray-600 dark:text-gray-300
                           px-4 py-2 rounded hover:bg-gray-500 hover:text-white
                           dark:hover:bg-gray-300 dark:hover:text-black transition-colors"
                >
                    <!-- The label text updates based on isDark -->
                    <span x-text="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main container -->
    <div class="container mx-auto px-4 bg-white dark:bg-gray-800 transition-colors py-6 rounded shadow">
        @yield('content')
    </div>
</body>
</html>