@extends('documentation.layout')

@section('content')
    <div id="markdown-content"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const content = @json($content);
            const markdownContent = document.getElementById('markdown-content');
            const currentSection = @json($section ?? '');
            
            if (typeof marked !== 'undefined') {
                markdownContent.innerHTML = marked.parse(content);
                
                // Highlight code blocks
                if (typeof hljs !== 'undefined') {
                    markdownContent.querySelectorAll('pre code').forEach((block) => {
                        hljs.highlightElement(block);
                    });
                }
                
                // Convert relative markdown links to documentation routes
                // markdownContent.querySelectorAll('a[href]').forEach(function(link) {
                //     const href = link.getAttribute('href');
                    
                //     // Skip external links, anchors, and absolute URLs
                //     if (href.startsWith('http://') || href.startsWith('https://') || 
                //         href.startsWith('#') || href.startsWith('/docs/') ||
                //         href.startsWith('mailto:') || href.startsWith('tel:')) {
                //         return;
                //     }
                    
                //     // Handle relative links like ./file.md or ../file.md
                //     if (href.startsWith('./') || href.startsWith('../') || href.endsWith('.md')) {
                //         // Remove ./ or ../
                //         let filePath = href.replace(/^\.\//, '').replace(/^\.\.\//, '');
                        
                //         // Remove .md extension
                //         filePath = filePath.replace(/\.md$/, '');
                        
                //         // Remove leading slash if present
                //         filePath = filePath.replace(/^\//, '');
                        
                //         // Build documentation route
                //         if (filePath === 'README' || filePath === '') {
                //             // Link to section index
                //             link.href = '/docs/' + currentSection;
                //         } else {
                //             // Link to specific file
                //             link.href = '/docs/' + currentSection + '/' + filePath;
                //         }
                //     }
                // });
            } else {
                markdownContent.innerHTML = '<pre>' + content + '</pre>';
            }
        });
    </script>
@endsection

