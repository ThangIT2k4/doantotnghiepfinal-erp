<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Service: DocumentationService
 * 
 * MỤC ĐÍCH:
 * Load, tìm kiếm và trích xuất context từ tài liệu hệ thống (docs/user-guides, docs/AI-doc) để cung cấp cho AI Chat
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. loadAllDocuments(): Load tất cả file .md từ docs/user-guides và docs/AI-doc (đệ quy)
 * 2. searchRelevantDocuments(): Tìm kiếm tài liệu liên quan đến câu hỏi dựa trên từ khóa
 * 3. getContextForQuery(): Lấy context từ tài liệu liên quan, format với prompt cho AI
 * 4. clearCache(): Xóa cache documents để reload
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - File system: docs/user-guides/*.md (đệ quy), docs/AI-doc/*.md (đệ quy)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Không có (chỉ đọc file)
 * 
 * LƯU Ý:
 * - Ưu tiên load từ docs/user-guides trước, sau đó mới đến docs/AI-doc
 * - Cache documents trong memory để tránh đọc file nhiều lần
 * - Context tối đa 8000 ký tự để tránh vượt token limit
 * - Tài liệu được làm sạch UTF-8 để tránh lỗi encoding
 */
class DocumentationService
{
    protected $docPaths; // Mảng đường dẫn thư mục tài liệu → Dùng để load documents
    protected $documents = []; // Mảng cache documents → Tránh đọc file nhiều lần
    protected $cacheKey = 'documentation_documents_cache'; // Key cache → Không dùng trong code hiện tại

    public function __construct()
    {
        $this->docPaths = [
            base_path('docs/user-guides'), // Thư mục user-guides → Ưu tiên load trước
            base_path('docs/AI-doc'), // Thư mục AI-doc → Load sau
        ]; // Khởi tạo danh sách đường dẫn → Dùng để load documents
    }

    /**
     * Load tất cả tài liệu từ các thư mục (đệ quy)
     * 
     * MỤC ĐÍCH:
     * Load tất cả file .md từ docs/user-guides và docs/AI-doc, cache trong memory để tránh đọc file nhiều lần
     * 
     * INPUT:
     * - Không có tham số
     * 
     * OUTPUT:
     * - array: Mảng documents với structure: [
     *     filename, path, full_path, category, title, content, size
     *   ]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra cache trong memory (nếu đã load rồi thì trả về luôn)
     * 2. Reset documents array
     * 3. Duyệt từng docPath trong $this->docPaths
     * 4. Gọi loadDocumentsRecursive() để load đệ quy
     * 5. Trả về mảng documents
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - File system: docs/user-guides/*.md (đệ quy), docs/AI-doc/*.md (đệ quy)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Property $this->documents: Cache documents trong memory
     */
    public function loadAllDocuments(): array
    {
        if (!empty($this->documents)) { // Nếu đã load rồi (có cache)
            return $this->documents; // Trả về cache → Tránh đọc file lại
        }

        $this->documents = []; // Reset documents array → Chuẩn bị load mới

        foreach ($this->docPaths as $docPath) { // Duyệt từng thư mục tài liệu
            if (File::exists($docPath)) { // Nếu thư mục tồn tại
                $this->loadDocumentsRecursive($docPath); // Load đệ quy → Lấy tất cả file .md
            }
        }

        return $this->documents; // Trả về mảng documents → Dùng để search
    }

    /**
     * Xóa cache documents
     * 
     * MỤC ĐÍCH:
     * Xóa cache documents trong memory để reload tài liệu mới (dùng khi có lỗi encoding hoặc cập nhật tài liệu)
     * 
     * INPUT:
     * - Không có tham số
     * 
     * OUTPUT:
     * - void: Không trả về gì
     * 
     * LUỒNG XỬ LÝ:
     * 1. Reset $this->documents về mảng rỗng
     * 2. Lần load tiếp theo sẽ đọc lại từ file system
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có
     * 
     * DỮ LIỆU GHI VÀO:
     * - Property $this->documents: Reset về mảng rỗng
     */
    public function clearCache(): void
    {
        $this->documents = []; // Reset cache → Lần load tiếp theo sẽ đọc lại từ file
    }

    /**
     * Load tài liệu đệ quy từ thư mục và các thư mục con
     */
    protected function loadDocumentsRecursive(string $path, string $relativePath = ''): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        $items = File::allFiles($path);
        
        foreach ($items as $file) {
            if ($file->getExtension() === 'md') {
                $content = File::get($file->getPathname());
                
                // Làm sạch và chuẩn hóa UTF-8
                $content = $this->cleanUtf8($content);
                
                // Lấy đường dẫn tương đối để phân biệt nguồn
                $relativeFilePath = $relativePath ? $relativePath . '/' . $file->getFilename() : $file->getFilename();
                
                // Xác định category dựa trên đường dẫn
                $category = $this->extractCategory($file->getPathname());
                
                $this->documents[] = [
                    'filename' => $file->getFilename(),
                    'path' => $relativeFilePath,
                    'full_path' => $file->getPathname(),
                    'category' => $category,
                    'title' => $this->extractTitle($content),
                    'content' => $content,
                    'size' => strlen($content),
                ];
            }
        }
    }

    /**
     * Trích xuất category từ đường dẫn file
     */
    protected function extractCategory(string $filePath): string
    {
        $path = str_replace('\\', '/', $filePath);
        
        if (strpos($path, '/user-guides/staff/') !== false) {
            return 'staff';
        } elseif (strpos($path, '/user-guides/tenant/') !== false) {
            return 'tenant';
        } elseif (strpos($path, '/user-guides/superadmin/') !== false) {
            return 'superadmin';
        } elseif (strpos($path, '/user-guides/workflows/') !== false) {
            return 'workflows';
        } elseif (strpos($path, '/user-guides/common/') !== false) {
            return 'common';
        } elseif (strpos($path, '/AI-doc/') !== false) {
            return 'ai-doc';
        }
        
        return 'other';
    }

    /**
     * Tìm kiếm tài liệu liên quan đến câu hỏi
     * 
     * MỤC ĐÍCH:
     * Tìm các tài liệu liên quan nhất đến câu hỏi của user dựa trên từ khóa, tính điểm liên quan và sắp xếp
     * 
     * INPUT:
     * - query (string): Câu hỏi của user → Ví dụ: "Làm thế nào để tạo hợp đồng thuê?"
     * - maxResults (int): Số lượng tài liệu tối đa trả về → Mặc định 3
     * 
     * OUTPUT:
     * - array: Mảng documents liên quan nhất, đã sắp xếp theo điểm số giảm dần
     * 
     * LUỒNG XỬ LÝ:
     * 1. Load tất cả documents
     * 2. Trích xuất từ khóa từ câu hỏi (loại bỏ stop words)
     * 3. Tính điểm liên quan cho mỗi document (dựa trên số lần xuất hiện từ khóa)
     * 4. Sắp xếp theo điểm số giảm dần
     * 5. Lấy top N documents
     * 6. Trả về mảng documents
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Method loadAllDocuments(): Lấy tất cả documents
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     * 
     * LƯU Ý:
     * - Ưu tiên tài liệu từ user-guides hơn AI-doc (tăng 20% điểm)
     * - Bonus điểm nếu từ khóa có trong title, filename, hoặc path
     */
    public function searchRelevantDocuments(string $query, int $maxResults = 3): array
    {
        $documents = $this->loadAllDocuments(); // Load tất cả documents → Dùng để search
        
        if (empty($documents)) { // Nếu không có documents
            return []; // Trả về mảng rỗng → Không có tài liệu để search
        }

        $queryKeywords = $this->extractKeywords($query); // Trích xuất từ khóa từ câu hỏi → Loại bỏ stop words
        $scoredDocs = []; // Mảng chứa documents với điểm số → Dùng để sắp xếp

        foreach ($documents as $doc) { // Duyệt từng document
            $score = $this->calculateRelevanceScore($doc, $queryKeywords); // Tính điểm liên quan → Dựa trên từ khóa
            if ($score > 0) { // Nếu có điểm (có từ khóa trong document)
                $scoredDocs[] = [
                    'document' => $doc, // Document → Dùng để trả về
                    'score' => $score, // Điểm số → Dùng để sắp xếp
                ];
            }
        }

        usort($scoredDocs, function ($a, $b) {
            return $b['score'] <=> $a['score']; // Sắp xếp theo điểm số giảm dần → Document liên quan nhất ở đầu
        });

        $results = array_slice($scoredDocs, 0, $maxResults); // Lấy top N documents → Giới hạn số lượng
        
        return array_map(function ($item) {
            return $item['document']; // Chỉ trả về document → Bỏ điểm số
        }, $results); // Trả về mảng documents → Dùng để lấy context
    }

    /**
     * Lấy context từ tài liệu liên quan (giới hạn độ dài)
     * 
     * MỤC ĐÍCH:
     * Lấy context từ các tài liệu liên quan, format với prompt rõ ràng cho AI, giới hạn độ dài để tránh vượt token limit
     * 
     * INPUT:
     * - query (string): Câu hỏi của user → Dùng để tìm tài liệu liên quan
     * - maxLength (int): Độ dài tối đa của context → Mặc định 8000 ký tự
     * 
     * OUTPUT:
     * - string: Context đã được format với prompt, bao gồm quy tắc trả lời và nội dung tài liệu
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm tài liệu liên quan (searchRelevantDocuments)
     * 2. Tạo prompt với quy tắc trả lời (chỉ tiếng Việt, dịch thuật ngữ, ngắn gọn, ...)
     * 3. Duyệt từng tài liệu liên quan:
     *    - Nếu tài liệu quá dài (>3000 ký tự) → Chỉ lấy phần đầu và phần có từ khóa
     *    - Làm sạch UTF-8
     *    - Thêm vào context
     *    - Kiểm tra độ dài, cắt bớt nếu cần
     * 4. Thêm phần nhắc lại quy tắc ở cuối
     * 5. Trả về context
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Method searchRelevantDocuments(): Tìm tài liệu liên quan
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     * 
     * LƯU Ý:
     * - Context tối đa 8000 ký tự để tránh vượt token limit của Gemini API
     * - Prompt yêu cầu AI chỉ dùng tiếng Việt, dịch thuật ngữ, và chỉ dựa trên tài liệu
     * - Tài liệu dài sẽ được cắt bớt, chỉ giữ phần đầu và phần có từ khóa
     */
    public function getContextForQuery(string $query, int $maxLength = 8000): string
    {
        $relevantDocs = $this->searchRelevantDocuments($query, 3); // Tìm 3 tài liệu liên quan nhất → Dùng để lấy context
        
        if (empty($relevantDocs)) { // Nếu không tìm thấy tài liệu liên quan
            return ''; // Trả về rỗng → Không có context để gửi cho AI
        }

        $context = "=== TÀI LIỆU HỆ THỐNG ZoroRMS ===\n\n"; // Header context → Đánh dấu bắt đầu tài liệu
        $context .= "Bạn là trợ lý AI chuyên về hệ thống quản lý bất động sản ZoroRMS.\n\n"; // Giới thiệu AI → Định vị vai trò
        $context .= "QUY TẮC TRẢ LỜI (BẮT BUỘC):\n"; // Header quy tắc → Nhấn mạnh bắt buộc
        $context .= "1. CHỈ dùng tiếng Việt, KHÔNG dùng tiếng Anh trong câu trả lời\n"; // Quy tắc 1 → Đảm bảo response bằng tiếng Việt
        $context .= "2. Dịch TẤT CẢ thuật ngữ tiếng Anh sang tiếng Việt dễ hiểu (ví dụ: 'Lease' -> 'Hợp đồng thuê', 'Invoice' -> 'Hóa đơn', 'Payment' -> 'Thanh toán')\n"; // Quy tắc 2 → Dịch thuật ngữ
        $context .= "3. Trả lời NGẮN GỌN, súc tích để tiết kiệm token\n"; // Quy tắc 3 → Tiết kiệm token
        $context .= "4. Trình bày rõ ràng, có cấu trúc, KHÔNG dùng ký tự đặc biệt (###, ---, ===, **, __)\n"; // Quy tắc 4 → Format đơn giản
        $context .= "5. Sử dụng danh sách đơn giản (- hoặc số) thay vì markdown phức tạp\n"; // Quy tắc 5 → List đơn giản
        $context .= "6. VỀ THÔNG TIN HỆ THỐNG: Chỉ trả lời dựa trên tài liệu. Nếu không có trong tài liệu, nói rõ 'Thông tin này không có trong tài liệu hệ thống'\n"; // Quy tắc 6 → Chỉ dùng tài liệu
        $context .= "7. VỀ CÂU HỎI NGOÀI LỀ: Nếu người dùng hỏi về thông tin ngoài hệ thống (như giá bất động sản, thị trường, v.v.), bạn NÊN trả lời dựa trên kiến thức chung một cách hữu ích. Bắt đầu bằng: 'Thông tin này không có trong tài liệu hệ thống, nhưng dựa trên kiến thức chung...' và cung cấp thông tin hữu ích cho người dùng\n"; // Quy tắc 7 → Cho phép trả lời câu hỏi ngoài lề
        $context .= "8. VỀ THÔNG TIN LIÊN HỆ: Chỉ cung cấp thông tin liên hệ có trong tài liệu. KHÔNG được bịa đặt thông tin liên hệ\n\n"; // Quy tắc 8 → Không bịa đặt thông tin liên hệ
        
        $currentLength = strlen($context); // Lấy độ dài hiện tại → Dùng để kiểm tra giới hạn

        foreach ($relevantDocs as $doc) { // Duyệt từng tài liệu liên quan
            $categoryLabel = $this->getCategoryLabel($doc['category']); // Lấy label category → Dùng để hiển thị
            $docContext = "\n--- {$doc['title']} ({$categoryLabel}: {$doc['path']}) ---\n\n"; // Header tài liệu → Đánh dấu bắt đầu tài liệu
            
            $content = $doc['content']; // Lấy nội dung tài liệu → Dùng để thêm vào context
            if (strlen($content) > 3000) { // Nếu tài liệu quá dài (>3000 ký tự)
                $content = $this->extractRelevantSections($content, $this->extractKeywords($query)); // Chỉ lấy phần đầu và phần có từ khóa → Giảm độ dài
            }
            
            $content = $this->cleanUtf8($content); // Làm sạch UTF-8 → Tránh lỗi encoding
            $docContext .= $content . "\n\n"; // Thêm nội dung vào docContext → Hoàn thiện context của tài liệu
            
            if ($currentLength + strlen($docContext) > $maxLength) { // Nếu vượt quá giới hạn độ dài
                $remaining = $maxLength - $currentLength - 100; // Tính số ký tự còn lại → Trừ 100 để có buffer
                if ($remaining > 0) { // Nếu còn chỗ
                    $docContext = substr($docContext, 0, $remaining) . "\n\n[... nội dung bị cắt bớt ...]"; // Cắt bớt và thêm thông báo → Báo cho AI biết đã cắt
                } else { // Nếu không còn chỗ
                    break; // Dừng vòng lặp → Không thêm tài liệu này
                }
            }
            
            $context .= $docContext; // Thêm docContext vào context tổng → Hoàn thiện context
            $currentLength = strlen($context); // Cập nhật độ dài hiện tại → Dùng để kiểm tra tiếp
            
            if ($currentLength >= $maxLength) { // Nếu đã đạt giới hạn
                break; // Dừng vòng lặp → Không thêm tài liệu nữa
            }
        }

        $context .= "\n=== KẾT THÚC TÀI LIỆU ===\n\n"; // Footer context → Đánh dấu kết thúc tài liệu
        $context .= "NHẮC LẠI:\n"; // Header nhắc lại → Nhấn mạnh quy tắc
        $context .= "- Trả lời HOÀN TOÀN bằng tiếng Việt, ngắn gọn, không dùng ký tự đặc biệt\n"; // Nhắc lại quy tắc → Đảm bảo AI nhớ
        $context .= "- Dịch tất cả thuật ngữ tiếng Anh sang tiếng Việt\n"; // Nhắc lại quy tắc → Đảm bảo AI nhớ
        $context .= "- Về hệ thống: Chỉ dùng thông tin từ tài liệu\n"; // Nhắc lại quy tắc → Đảm bảo AI nhớ
        $context .= "- Về câu hỏi ngoài lề: NÊN trả lời dựa trên kiến thức chung một cách hữu ích, bắt đầu bằng 'Thông tin này không có trong tài liệu hệ thống, nhưng dựa trên kiến thức chung...'\n"; // Nhắc lại quy tắc → Đảm bảo AI nhớ
        $context .= "- Về thông tin liên hệ: CHỈ dùng thông tin trong tài liệu, KHÔNG bịa đặt\n"; // Nhắc lại quy tắc → Đảm bảo AI nhớ

        return $context; // Trả về context hoàn chỉnh → Gửi cho Gemini API
    }

    /**
     * Trích xuất tiêu đề từ nội dung markdown
     */
    protected function extractTitle(string $content): string
    {
        // Lấy dòng đầu tiên có # (heading)
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^#+\s+(.+)$/', $line, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return 'Tài liệu không có tiêu đề';
    }

    /**
     * Trích xuất từ khóa từ câu hỏi
     */
    protected function extractKeywords(string $query): array
    {
        // Loại bỏ các từ dừng (stop words) tiếng Việt
        $stopWords = ['là', 'của', 'và', 'với', 'cho', 'từ', 'đến', 'trong', 'có', 'không', 'được', 'một', 'các', 'nào', 'gì', 'thế', 'như', 'bằng', 'về'];
        
        $words = preg_split('/\s+/', mb_strtolower($query));
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_values($keywords);
    }

    /**
     * Tính điểm liên quan
     */
    protected function calculateRelevanceScore(array $doc, array $keywords): float
    {
        $content = mb_strtolower($doc['content']);
        $title = mb_strtolower($doc['title']);
        $filename = mb_strtolower($doc['filename']);
        $path = mb_strtolower($doc['path'] ?? '');
        
        $score = 0;
        
        foreach ($keywords as $keyword) {
            // Đếm số lần xuất hiện trong nội dung
            $count = substr_count($content, $keyword);
            $score += $count * 1;
            
            // Bonus nếu có trong tiêu đề
            if (strpos($title, $keyword) !== false) {
                $score += 10;
            }
            
            // Bonus nếu có trong tên file
            if (strpos($filename, $keyword) !== false) {
                $score += 5;
            }
            
            // Bonus nếu có trong đường dẫn
            if (strpos($path, $keyword) !== false) {
                $score += 3;
            }
        }
        
        // Ưu tiên tài liệu từ user-guides (chi tiết hơn)
        if ($doc['category'] !== 'ai-doc') {
            $score *= 1.2; // Tăng 20% điểm cho user-guides
        }
        
        return $score;
    }

    /**
     * Trích xuất các phần liên quan từ tài liệu dài
     */
    protected function extractRelevantSections(string $content, array $keywords): string
    {
        $lines = explode("\n", $content);
        $relevantSections = [];
        $currentSection = '';
        $sectionScore = 0;
        
        foreach ($lines as $line) {
            $lineLower = mb_strtolower($line);
            $lineScore = 0;
            
            foreach ($keywords as $keyword) {
                if (strpos($lineLower, $keyword) !== false) {
                    $lineScore += substr_count($lineLower, $keyword);
                }
            }
            
            // Nếu là heading, bắt đầu section mới
            if (preg_match('/^#+\s+/', $line)) {
                if ($sectionScore > 0 && !empty($currentSection)) {
                    $relevantSections[] = $currentSection;
                }
                $currentSection = $line . "\n";
                $sectionScore = $lineScore;
            } else {
                $currentSection .= $line . "\n";
                $sectionScore += $lineScore;
            }
        }
        
        // Thêm section cuối
        if ($sectionScore > 0 && !empty($currentSection)) {
            $relevantSections[] = $currentSection;
        }
        
        // Lấy 500 ký tự đầu tiên + các sections liên quan
        $result = substr($content, 0, 500) . "\n\n";
        
        if (!empty($relevantSections)) {
            $result .= "=== CÁC PHẦN LIÊN QUAN ===\n\n";
            foreach (array_slice($relevantSections, 0, 5) as $section) {
                $result .= $section . "\n";
            }
        }
        
        return $result;
    }

    /**
     * Lấy danh sách tất cả tài liệu
     */
    public function getAllDocuments(): array
    {
        return $this->loadAllDocuments();
    }

    /**
     * Lấy label cho category
     */
    protected function getCategoryLabel(string $category): string
    {
        $labels = [
            'staff' => 'Hướng dẫn Staff',
            'tenant' => 'Hướng dẫn Tenant',
            'superadmin' => 'Hướng dẫn SuperAdmin',
            'workflows' => 'Quy trình nghiệp vụ',
            'common' => 'Tài liệu chung',
            'ai-doc' => 'Tài liệu kỹ thuật',
            'other' => 'Khác',
        ];

        return $labels[$category] ?? $category;
    }

    /**
     * Làm sạch và chuẩn hóa UTF-8
     */
    protected function cleanUtf8(string $text): string
    {
        // Loại bỏ BOM nếu có
        $text = preg_replace('/\x{FEFF}/u', '', $text);
        
        // Loại bỏ các ký tự control không hợp lệ (trừ \n, \r, \t)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Chuyển đổi sang UTF-8 và loại bỏ ký tự không hợp lệ
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Thử convert từ các encoding phổ biến
            $encodings = ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'CP1252'];
            foreach ($encodings as $encoding) {
                $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
                if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                    $text = $converted;
                    break;
                }
            }
        }
        
        // Đảm bảo encoding UTF-8 hợp lệ
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Loại bỏ các ký tự đặc biệt có thể gây lỗi JSON
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        
        // Loại bỏ các ký tự không in được (ngoại trừ whitespace hợp lệ)
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
        
        return $text;
    }
}

