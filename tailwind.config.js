export default {
    darkMode: 'class', // <— important!
    content: [
      './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
      './storage/framework/views/*.php',
      './resources/**/*.blade.php',
      './resources/**/*.js',
      './resources/**/*.vue',
    ],
    theme: {
      extend: {
        // ...
      },
    },
    plugins: [
      require('@tailwindcss/typography'),
    ],
  }