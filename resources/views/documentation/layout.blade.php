<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <title>{{ $title ?? 'Tài liệu hướng dẫn' }} - ZoroRMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Highlight.js for code syntax -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <!-- Marked.js for markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    
    <style>
        :root {
            --docs-primary: #2563eb;
            --docs-primary-dark: #1e40af;
            --docs-bg: #ffffff;
            --docs-sidebar-bg: #f8fafc;
            --docs-text: #1f2937;
            --docs-text-light: #6b7280;
            --docs-border: #e5e7eb;
            --docs-hover: #f1f5f9;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--docs-text);
            background: var(--docs-bg);
            line-height: 1.6;
        }

        /* Documentation Layout */
        .docs-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .docs-sidebar {
            width: 280px;
            background: var(--docs-sidebar-bg);
            border-right: 1px solid var(--docs-border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .docs-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .docs-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .docs-sidebar::-webkit-scrollbar-thumb {
            background: var(--docs-border);
            border-radius: 3px;
        }

        .docs-sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--docs-border);
            background: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .docs-sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--docs-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .docs-sidebar-header h1 i {
            font-size: 1.5rem;
        }

        .docs-sidebar-content {
            padding: 1rem 0;
        }

        .docs-section {
            margin-bottom: 1.5rem;
        }

        .docs-section-title {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: var(--docs-text-light);
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .docs-section-title i {
            font-size: 0.875rem;
        }

        .docs-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .docs-nav-item {
            margin: 0;
        }

        .docs-nav-link {
            display: block;
            padding: 0.625rem 1.5rem;
            color: var(--docs-text);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
            border-left: 3px solid transparent;
        }

        .docs-nav-link:hover {
            background: var(--docs-hover);
            color: var(--docs-primary);
            border-left-color: var(--docs-primary);
        }

        .docs-nav-link.active {
            background: var(--docs-hover);
            color: var(--docs-primary);
            font-weight: 600;
            border-left-color: var(--docs-primary);
        }

        /* Main Content */
        .docs-main {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        .docs-header {
            background: white;
            border-bottom: 1px solid var(--docs-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .docs-search {
            max-width: 500px;
            position: relative;
        }

        .docs-search input {
            width: 100%;
            padding: 0.625rem 2.5rem 0.625rem 1rem;
            border: 1px solid var(--docs-border);
            border-radius: 6px;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }

        .docs-search input:focus {
            outline: none;
            border-color: var(--docs-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .docs-search-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--docs-text-light);
        }

        .docs-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .docs-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--docs-text);
            line-height: 1.2;
        }

        .docs-content h2 {
            font-size: 1.875rem;
            font-weight: 600;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: var(--docs-text);
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--docs-border);
        }

        .docs-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: var(--docs-text);
        }

        .docs-content h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--docs-text);
        }

        .docs-content p {
            margin-bottom: 1rem;
            color: var(--docs-text);
        }

        .docs-content ul,
        .docs-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .docs-content li {
            margin-bottom: 0.5rem;
        }

        .docs-content code {
            background: var(--docs-sidebar-bg);
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.875em;
            font-family: 'Courier New', monospace;
            color: #e11d48;
        }

        .docs-content pre {
            background: var(--docs-sidebar-bg);
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            margin-bottom: 1.5rem;
            border: 1px solid var(--docs-border);
        }

        .docs-content pre code {
            background: none;
            padding: 0;
            color: inherit;
        }

        .docs-content blockquote {
            border-left: 4px solid var(--docs-primary);
            padding-left: 1rem;
            margin-left: 0;
            margin-bottom: 1rem;
            color: var(--docs-text-light);
            font-style: italic;
        }

        .docs-content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .docs-content table th,
        .docs-content table td {
            padding: 0.75rem;
            border: 1px solid var(--docs-border);
            text-align: left;
        }

        .docs-content table th {
            background: var(--docs-sidebar-bg);
            font-weight: 600;
        }

        .docs-content a {
            color: var(--docs-primary);
            text-decoration: none;
        }

        .docs-content a:hover {
            text-decoration: underline;
        }

        .docs-content img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 1.5rem 0;
        }

        /* Search Results */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--docs-border);
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 0.5rem;
            display: none;
        }

        .search-results.active {
            display: block;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--docs-border);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .search-result-item:hover {
            background: var(--docs-hover);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-title {
            font-weight: 600;
            color: var(--docs-text);
            margin-bottom: 0.25rem;
        }

        .search-result-excerpt {
            font-size: 0.875rem;
            color: var(--docs-text-light);
        }

        .search-result-excerpt mark {
            background: #fef3c7;
            padding: 0.125rem 0.25rem;
            border-radius: 2px;
        }

        /* Mobile Menu Toggle */
        .docs-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--docs-primary);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .docs-sidebar {
                transform: translateX(-100%);
            }

            .docs-sidebar.active {
                transform: translateX(0);
            }

            .docs-main {
                margin-left: 0;
            }

            .docs-menu-toggle {
                display: block;
            }

            .docs-content {
                padding: 2rem 1rem;
            }

            .docs-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <button class="docs-menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="docs-container">
        <!-- Sidebar -->
        <aside class="docs-sidebar" id="sidebar">
            <div class="docs-sidebar-header">
                <h1>
                    <i class="fas fa-book"></i>
                    Tài liệu
                </h1>
            </div>
            <div class="docs-sidebar-content">
                @foreach($sections as $sectionKey => $section)
                    <div class="docs-section">
                        <div class="docs-section-title">
                            <i class="{{ $section['icon'] }}"></i>
                            {{ $section['label'] }}
                        </div>
                        <ul class="docs-nav-list">
                            @foreach($section['files'] as $file)
                                <li class="docs-nav-item">
                                    <a href="{{ route('docs.show', ['section' => $sectionKey, 'file' => $file['name']]) }}" 
                                       class="docs-nav-link {{ $currentPath === $file['path'] ? 'active' : '' }}">
                                        {{ $file['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </aside>

        <!-- Main Content -->
        <main class="docs-main">
            <div class="docs-header">
                <div class="docs-search">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Tìm kiếm trong tài liệu..." 
                           autocomplete="off">
                    <i class="fas fa-search docs-search-icon"></i>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
            <div class="docs-content">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        // Menu toggle for mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Search functionality
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('docs.search') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        displaySearchResults(data.results);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        });

        function displaySearchResults(results) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-result-item">Không tìm thấy kết quả</div>';
                searchResults.classList.add('active');
                return;
            }

            let html = '';
            results.forEach(result => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='{{ url('docs') }}/${result.path}'">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-excerpt">${result.excerpt}</div>
                    </div>
                `;
            });

            searchResults.innerHTML = html;
            searchResults.classList.add('active');
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });

        // Markdown rendering
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                highlight: function(code, lang) {
                    if (lang && typeof hljs !== 'undefined') {
                        try {
                            return hljs.highlight(code, { language: lang }).value;
                        } catch (err) {}
                    }
                    return code;
                },
                breaks: true,
                gfm: true
            });
        }
    </script>
</body>
</html>

