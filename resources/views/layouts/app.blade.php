<head>
  <meta charset="UTF-8">
  <title>Laravel AI Code Analysis</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js for collapsible cards -->
  <script src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
  
  <!-- If you want some global styling (e.g. for code blocks or markdown) -->
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
<body class="bg-gray-50 text-gray-800">
  <nav class="bg-white shadow mb-4">
    <!-- ... nav content ... -->
  </nav>

  <div class="container mx-auto px-4">
    @yield('content')
  </div>
</body>
</html>