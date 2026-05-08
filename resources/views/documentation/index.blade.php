@extends('documentation.layout')

@section('content')
    <div id="markdown-content"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const content = @json($content);
            const markdownContent = document.getElementById('markdown-content');
            
            if (typeof marked !== 'undefined') {
                markdownContent.innerHTML = marked.parse(content);
                
                // Highlight code blocks
                if (typeof hljs !== 'undefined') {
                    markdownContent.querySelectorAll('pre code').forEach((block) => {
                        hljs.highlightElement(block);
                    });
                }
                /*
                // Convert relative markdown links to documentation routes
                markdownContent.querySelectorAll('a[href]').forEach(function(link) {
                    const href = link.getAttribute('href');

                    // Skip external links, anchors, and absolute URLs
                    if (href.startsWith('http://') || href.startsWith('https://') ||
                        href.startsWith('#') || href.startsWith('/docs/') ||
                        href.startsWith('mailto:') || href.startsWith('tel:')) {
                        return;
                    }

                    // Handle relative links like ./file.md or ../file.md
                    if (href.startsWith('./') || href.startsWith('../') || href.endsWith('.md')) {
                        // Remove ./ or ../
                        let filePath = href.replace(/^\.\//, '').replace(/^\.\.\//, '');

                        // Remove .md extension
                        filePath = filePath.replace(/\.md$/, '');

                        // Remove leading slash if present
                        filePath = filePath.replace(/^\//, '');

                        // Extract section from path (e.g., staff/01-authentication -> staff, 01-authentication)
                        const pathParts = filePath.split('/');
                        let section = '';
                        let file = '';

                        // Common sections
                        const sections = ['staff', 'tenant', 'superadmin', 'workflows', 'common'];

                        if (pathParts.length > 1) {
                            // Path contains section (e.g., staff/01-authentication)
                            section = pathParts[0];
                            file = pathParts.slice(1).join('/');
                        } else if (sections.includes(pathParts[0])) {
                            // Just section name (e.g., staff)
                            section = pathParts[0];
                            file = '';
                        } else {
                            // Try to detect section from current URL
                            const currentPath = window.location.pathname;
                            const match = currentPath.match(/\/docs\/([^\/]+)/);
                            if (match) {
                                section = match[1];
                                file = filePath;
                            } else {
                                // Default to staff if can't detect
                                section = 'staff';
                                file = filePath;
                            }
                        }

                        // Build documentation route
                        if (file === 'README' || file === '') {
                            // Link to section index
                            link.href = '/docs/' + section;
                        } else {
                            // Link to specific file
                            link.href = '/docs/' + section + '/' + file;
                        }
                    }
                });
                */
            } else {
                markdownContent.innerHTML = '<pre>' + content + '</pre>';
            }
        });
    </script>
@endsection

