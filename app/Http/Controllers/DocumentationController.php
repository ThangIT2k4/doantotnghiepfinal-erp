<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    protected $docsPath;
    protected $basePath;

    public function __construct()
    {
        $this->basePath = base_path('docs/user-guides');
    }

    /**
     * Display documentation index page
     */
    public function index()
    {
        $sections = $this->getDocumentationStructure();
        $readmePath = $this->basePath . '/README.md';
        
        if (File::exists($readmePath)) {
            $content = File::get($readmePath);
        } else {
            $content = "# Tài liệu hướng dẫn\n\nChào mừng đến với tài liệu hướng dẫn sử dụng hệ thống.";
        }
        
        return view('documentation.index', [
            'sections' => $sections,
            'content' => $content,
            'currentPath' => '',
            'title' => 'Tài liệu hướng dẫn'
        ]);
    }

    /**
     * Display a specific documentation page
     */
    public function show($section = null, $file = null)
    {
        $sections = $this->getDocumentationStructure();
        
        // If no section, show index
        if (!$section) {
            return $this->index();
        }

        // Build file path
        $filePath = $this->basePath . '/' . $section;
        
        if ($file) {
            $filePath .= '/' . $file . '.md';
        } else {
            // If section but no file, try to find README or first file
            $readmePath = $filePath . '/README.md';
            if (File::exists($readmePath)) {
                $filePath = $readmePath;
            } else {
                // Get first file in section
                if (File::isDirectory($filePath)) {
                    $files = File::files($filePath);
                    $mdFiles = array_filter($files, function($file) {
                        return $file->getExtension() === 'md' && $file->getFilename() !== 'README.md';
                    });
                    
                    if (count($mdFiles) > 0) {
                        // Sort files by name
                        usort($mdFiles, function($a, $b) {
                            return strcmp($a->getFilename(), $b->getFilename());
                        });
                        $filePath = $mdFiles[0]->getPathname();
                    } else {
                        abort(404, 'Documentation not found');
                    }
                } else {
                    abort(404, 'Documentation not found');
                }
            }
        }

        // Check if file exists
        if (!File::exists($filePath)) {
            abort(404, 'Documentation not found');
        }

        $content = File::get($filePath);
        $title = $this->extractTitle($content);

        // Build current path for active link
        $currentPath = $section;
        if ($file) {
            $currentPath .= '/' . $file;
        } else {
            // If showing README.md, set currentPath to section only
            if (Str::endsWith($filePath, '/README.md')) {
                $currentPath = $section;
            }
        }

        return view('documentation.show', [
            'sections' => $sections,
            'content' => $content,
            'currentPath' => $currentPath,
            'title' => $title,
            'section' => $section,
            'file' => $file
        ]);
    }

    /**
     * Get documentation structure
     */
    protected function getDocumentationStructure()
    {
        $structure = [];

        // Staff section
        $staffPath = $this->basePath . '/staff';
        if (File::isDirectory($staffPath)) {
            $staffFiles = File::files($staffPath);
            $structure['staff'] = [
                'name' => 'Staff',
                'label' => 'Hướng dẫn Staff',
                'icon' => 'fas fa-user-tie',
                'files' => $this->formatFiles($staffFiles, 'staff')
            ];
        }

        // Tenant section
        $tenantPath = $this->basePath . '/tenant';
        if (File::isDirectory($tenantPath)) {
            $tenantFiles = File::files($tenantPath);
            $structure['tenant'] = [
                'name' => 'Tenant',
                'label' => 'Hướng dẫn Tenant',
                'icon' => 'fas fa-user',
                'files' => $this->formatFiles($tenantFiles, 'tenant')
            ];
        }

        // Workflows section
        $workflowsPath = $this->basePath . '/workflows';
        if (File::isDirectory($workflowsPath)) {
            $workflowsFiles = File::files($workflowsPath);
            $structure['workflows'] = [
                'name' => 'Workflows',
                'label' => 'Quy trình nghiệp vụ',
                'icon' => 'fas fa-project-diagram',
                'files' => $this->formatFiles($workflowsFiles, 'workflows')
            ];
        }

        return $structure;
    }

    /**
     * Format files for navigation
     */
    protected function formatFiles($files, $section)
    {
        $formatted = [];
        $readmeFile = null;
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                if ($file->getFilename() === 'README.md') {
                    $readmeFile = $file;
                } else {
                    $fileName = $file->getFilenameWithoutExtension();
                    $content = File::get($file->getPathname());
                    $title = $this->extractTitle($content);
                    
                    $formatted[] = [
                        'name' => $fileName,
                        'title' => $title,
                        'path' => $section . '/' . $fileName
                    ];
                }
            }
        }

        // Sort by file name (number prefix)
        usort($formatted, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        // Add README.md at the beginning if exists
        if ($readmeFile) {
            $content = File::get($readmeFile->getPathname());
            $title = $this->extractTitle($content);
            
            array_unshift($formatted, [
                'name' => 'README',
                'title' => $title,
                'path' => $section
            ]);
        }

        return $formatted;
    }

    /**
     * Get markdown content
     */
    protected function getMarkdownContent($path)
    {
        if (Str::startsWith($path, $this->basePath)) {
            $filePath = $path;
        } else {
            $filePath = $this->basePath . '/' . $path;
        }

        if (!File::exists($filePath)) {
            // Try to get README.md from base path
            $readmePath = $this->basePath . '/README.md';
            if (File::exists($readmePath)) {
                return File::get($readmePath);
            }
            return '# Không tìm thấy tài liệu';
        }

        return File::get($filePath);
    }

    /**
     * Extract title from markdown content
     */
    protected function extractTitle($content)
    {
        if (empty($content)) {
            return 'Tài liệu';
        }
        
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Use regex to find first H1 (exactly one #, not ## or ###)
        // Pattern: ^#\s+ means start of line, one #, one or more spaces, then capture the rest
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            
            // Remove any markdown syntax that might be in the title (shouldn't happen but just in case)
            $title = preg_replace('/^#+\s*/', '', $title);
            
            // Remove any trailing markdown syntax
            $title = preg_replace('/\s*#+\s*$/', '', $title);
            
            return trim($title);
        }
        
        // Fallback: try H2 if no H1 found (should not happen for proper docs)
        if (preg_match('/^##\s+(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            
            // Remove any markdown syntax that might be in the title
            $title = preg_replace('/^#+\s*/', '', $title);
            
            // Remove any trailing markdown syntax
            $title = preg_replace('/\s*#+\s*$/', '', $title);
            
            return trim($title);
        }

        return 'Tài liệu';
    }

    /**
     * Search documentation
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $results = [];

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $sections = ['staff', 'tenant', 'workflows'];
        
        foreach ($sections as $section) {
            $sectionPath = $this->basePath . '/' . $section;
            if (!File::isDirectory($sectionPath)) {
                continue;
            }

            $files = File::files($sectionPath);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'md') {
                    continue;
                }

                $content = File::get($file->getPathname());
                $fileName = $file->getFilenameWithoutExtension();
                
                // Simple search in content
                if (stripos($content, $query) !== false || stripos($fileName, $query) !== false) {
                    $title = $this->extractTitle($content);
                    $excerpt = $this->extractExcerpt($content, $query);
                    
                    $results[] = [
                        'title' => $title,
                        'section' => $section,
                        'file' => $fileName,
                        'path' => $section . '/' . $fileName,
                        'excerpt' => $excerpt
                    ];
                }
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Extract excerpt with search term highlighted
     */
    protected function extractExcerpt($content, $query, $length = 150)
    {
        $pos = stripos($content, $query);
        if ($pos === false) {
            return Str::limit(strip_tags($content), $length);
        }

        $start = max(0, $pos - 50);
        $excerpt = substr($content, $start, $length);
        
        // Highlight query
        $excerpt = preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark>$1</mark>', $excerpt);
        
        return '...' . trim($excerpt) . '...';
    }
}

