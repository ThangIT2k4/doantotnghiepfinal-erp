<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentationService;
use App\Services\ChatPermissionService;
use App\Services\VertexAiService;
use App\Services\VertexAiChatToolsService;

/**
 * Controller: ChatController
 * 
 * MỤC ĐÍCH:
 * Xử lý chức năng Chat với AI - cho phép người dùng hỏi đáp về hệ thống ZoroRMS thông qua AI (Vertex AI Gemini)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị giao diện chat, kiểm tra quyền subscription
    * 2. sendMessage(): Xử lý tin nhắn từ user, tìm tài liệu liên quan, gọi Vertex AI Gemini, trả về câu trả lời
 * 3. clearCache(): Xóa cache tài liệu khi có lỗi encoding
 * 
 * ENDPOINTS:
 * - GET /chat: Hiển thị trang chat
 * - POST /chat/send: Gửi tin nhắn và nhận phản hồi từ AI
 * - POST /chat/clear-cache: Xóa cache tài liệu
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Service: DocumentationService - Lấy context từ tài liệu hệ thống (docs/user-guides, docs/AI-doc)
 * - Service: ChatPermissionService - Kiểm tra quyền sử dụng chat dựa trên subscription
 * - Service VertexAiService: Gọi Gemini thông qua Vertex AI bằng service account OAuth
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log lỗi khi có exception hoặc lỗi API
 * 
 * LƯU Ý:
 * - Yêu cầu user phải đăng nhập (middleware auth)
 * - Yêu cầu organization phải có subscription với feature enable_chat
 * - Response từ AI được làm sạch và format để loại bỏ markdown, ký tự đặc biệt
 */
class ChatController extends Controller
{
    protected $documentationService; // Service xử lý tài liệu → Dùng để tìm context từ docs
    protected $chatPermissionService; // Service kiểm tra quyền chat → Dùng để validate subscription
    protected VertexAiService $vertexAiService; // Service gọi Vertex AI → Dùng để auth và gửi prompt

    protected VertexAiChatToolsService $vertexAiChatToolsService; // Chat có function calling (tạo lease, tra cứu)

    public function __construct(
        DocumentationService $documentationService,
        ChatPermissionService $chatPermissionService,
        VertexAiService $vertexAiService,
        VertexAiChatToolsService $vertexAiChatToolsService
    ) {
        $this->documentationService = $documentationService; // Inject DocumentationService → Dùng để load và search tài liệu
        $this->chatPermissionService = $chatPermissionService; // Inject ChatPermissionService → Dùng để check quyền
        $this->vertexAiService = $vertexAiService; // Inject VertexAiService → Dùng để gọi Gemini qua Vertex AI
        $this->vertexAiChatToolsService = $vertexAiChatToolsService;
    }

    /**
     * Hiển thị trang chat
     * 
     * MỤC ĐÍCH:
     * Hiển thị giao diện chat với AI cho người dùng, kiểm tra quyền subscription trước khi hiển thị
     * 
     * INPUT:
     * - Session: user_id (từ middleware auth) → Dùng để lấy organization và check quyền
     * 
     * OUTPUT:
     * - View: chat.index → Giao diện chat với AI
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền sử dụng chat dựa trên subscription của organization
     * 2. Nếu có quyền → Trả về view chat
     * 3. Nếu không có quyền → Trả về lỗi 403
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Service ChatPermissionService: Kiểm tra organization có subscription và feature enable_chat
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     */
    public function index()
    {
        $this->chatPermissionService->requireChatPermission(); // Kiểm tra quyền chat → Dừng nếu không có quyền subscription

        return view('chat.index'); // Trả về view chat → Hiển thị giao diện chat với AI
    }

    /**
     * Xóa cache tài liệu
     * 
     * MỤC ĐÍCH:
     * Xóa cache tài liệu trong DocumentationService, dùng khi có lỗi encoding hoặc cần reload tài liệu
     * 
     * INPUT:
     * - Request: Không có tham số
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."} → Kết quả xóa cache
     * 
     * LUỒNG XỬ LÝ:
     * 1. Gọi clearCache() từ DocumentationService
     * 2. Trả về JSON success nếu thành công
     * 3. Trả về JSON error nếu có lỗi
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Service DocumentationService: clearCache() → Xóa cache documents
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log nếu có exception
     */
    public function clearCache()
    {
        try {
            $this->documentationService->clearCache(); // Xóa cache tài liệu → Để reload tài liệu mới khi có lỗi encoding
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa cache tài liệu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi xóa cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
    * Xử lý tin nhắn chat với Vertex AI Gemini
     * 
     * MỤC ĐÍCH:
    * Nhận câu hỏi từ user, tìm tài liệu liên quan, gửi cho Vertex AI Gemini và trả về câu trả lời đã được làm sạch
     * 
     * INPUT:
     * - Request: message (bắt buộc, max 5000 ký tự) → Câu hỏi của user
     * - Request: conversation_history (tùy chọn, array) → Lịch sử hội thoại trước đó
    * - Config: services.vertex_ai → Cấu hình Vertex AI và service account
     * - Service DocumentationService: getContextForQuery() → Lấy context từ tài liệu liên quan
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} → Câu trả lời từ AI đã được làm sạch
     * - JSON: {success: false, error: "..."} → Lỗi nếu có (API key chưa config, lỗi API, encoding, ...)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền chat (subscription)
     * 2. Validate input (message, conversation_history)
    * 3. Kiểm tra cấu hình Vertex AI có tồn tại không
     * 4. Lấy context từ tài liệu liên quan (DocumentationService)
     * 5. Xây dựng payload với system context, conversation history, và câu hỏi hiện tại
     * 6. Làm sạch text trước khi gửi (cleanText)
     * 7. Validate JSON payload
    * 8. Gọi Vertex AI Gemini với model cấu hình sẵn
     * 9. Làm sạch và format response (cleanAndFormatResponse)
     * 10. Trả về JSON với câu trả lời
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Service DocumentationService: getContextForQuery() → Lấy context từ docs/user-guides và docs/AI-doc
    * - Service VertexAiService: Gọi Gemini qua OAuth service account
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log lỗi khi có exception, lỗi JSON encoding, hoặc lỗi API
     * 
     * LƯU Ý:
     * - Context tối đa 8000 ký tự để tránh vượt quá token limit
     * - Response được làm sạch để loại bỏ markdown, ký tự đặc biệt, và dịch thuật ngữ tiếng Anh
     */
    public function sendMessage(Request $request)
    {
        $this->chatPermissionService->requireChatPermission(); // Kiểm tra quyền chat → Dừng nếu không có quyền subscription

        $request->validate([
            'message' => 'required|string|max:5000', // message: bắt buộc, string, tối đa 5000 ký tự
            'conversation_history' => 'nullable|array' // conversation_history: tùy chọn, phải là array
        ]);

        $projectId = VertexAiService::resolveProjectId();
        $credsPath = VertexAiService::resolveCredentialsPath();
        $credsJson = VertexAiService::resolveCredentialsJson();
        $hasReadableFile = $credsPath !== '' && is_readable($credsPath);

        if ($projectId === '' || (! $hasReadableFile && $credsJson === '')) {
            return response()->json([
                'success' => false,
                'error' => 'Vertex AI chưa được cấu hình. Vui lòng thêm GOOGLE_CLOUD_PROJECT_ID và GOOGLE_APPLICATION_CREDENTIALS vào file .env'
            ], 400); // Trả về lỗi 400 → Yêu cầu cấu hình Vertex AI
        }

        try {
            $systemContext = $this->documentationService->getContextForQuery($request->message, 8000); // Lấy context từ tài liệu liên quan → Tối đa 8000 ký tự để tránh vượt token limit
            $systemContext = !empty($systemContext) ? $this->cleanText($systemContext) : '';

            $useTools = (bool) config('services.vertex_ai.enable_chat_tools', true);

            if ($useTools) {
                return $this->sendMessageWithTools($request, $systemContext);
            }

            $contents = []; // Mảng chứa nội dung gửi cho API → Dùng để build payload

            if ($systemContext !== '') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $systemContext]],
                ];
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => 'Tôi đã hiểu. Tôi sẽ trả lời hoàn toàn bằng tiếng Việt, ngắn gọn, không dùng ký tự đặc biệt, và dịch tất cả thuật ngữ tiếng Anh sang tiếng Việt dễ hiểu.']],
                ];
            }

            if ($request->has('conversation_history') && is_array($request->conversation_history)) {
                foreach ($request->conversation_history as $history) {
                    if (isset($history['role']) && isset($history['content'])) {
                        $historyContent = $this->cleanText($history['content']);
                        $contents[] = [
                            'role' => $this->mapHistoryRoleToGemini($history['role']),
                            'parts' => [['text' => $historyContent]],
                        ];
                    }
                }
            }

            $userMessage = $this->cleanText($request->message);
            if ($systemContext !== '') {
                $userMessage = "Câu hỏi: ".$userMessage."\n\n";
                $userMessage .= "Yêu cầu: Trả lời HOÀN TOÀN bằng tiếng Việt, ngắn gọn, không dùng ký tự đặc biệt. ";
                $userMessage .= "Dịch tất cả thuật ngữ tiếng Anh sang tiếng Việt dễ hiểu. ";
                $userMessage .= "Chỉ dùng thông tin từ tài liệu đã cung cấp.";
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]],
            ];

            $response = $this->vertexAiService->generateContent($contents, [
                'temperature' => 0.7, // Temperature 0.7 → Cân bằng giữa sáng tạo và chính xác
                'topK' => 40, // TopK 40 → Giới hạn số từ khóa được xem xét
                'topP' => 0.95, // TopP 0.95 → Giới hạn xác suất tích lũy
                'maxOutputTokens' => 2048, // Tối đa 2048 tokens → Giới hạn độ dài response
            ]);

            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) { // Nếu có text trong response
                $aiResponse = $response['candidates'][0]['content']['parts'][0]['text']; // Lấy text response → Câu trả lời từ AI

                $aiResponse = $this->cleanAndFormatResponse($aiResponse); // Làm sạch và format response → Loại bỏ markdown, ký tự đặc biệt, dịch thuật ngữ

                return response()->json([
                    'success' => true,
                    'message' => $aiResponse // Trả về câu trả lời đã làm sạch → Hiển thị cho user
                ]);
            } else { // Nếu response không đúng cấu trúc
                Log::error('Vertex AI response structure unexpected', ['response' => $response]); // Ghi log → Để debug
                return response()->json([
                    'success' => false,
                    'error' => 'Phản hồi từ Vertex AI không đúng định dạng'
                ], 500); // Trả về lỗi 500 → Lỗi server
            }
        } catch (\Throwable $e) {
            Log::error('Chat error', [
                'message' => $e->getMessage(), // Thông báo lỗi → Để debug
                'trace' => $e->getTraceAsString() // Stack trace → Để debug
            ]); // Ghi log exception → Để debug
            
            $errorMessage = $e->getMessage(); // Lấy thông báo lỗi → Để xử lý
            
            if (strpos($errorMessage, 'json_encode') !== false || strpos($errorMessage, 'UTF-8') !== false) { // Nếu là lỗi encoding
                $errorMessage = 'Lỗi encoding dữ liệu. Vui lòng thử lại hoặc liên hệ quản trị viên.'; // Thay bằng message thân thiện → Dễ hiểu hơn cho user
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Đã xảy ra lỗi: ' . $errorMessage // Trả về lỗi → Thông báo cho user
            ], 500); // Trả về lỗi 500 → Lỗi server
        }
    }

    /**
     * Chat có Gemini function calling — tạo lease, tìm tenant/unit trong DB.
     */
    protected function sendMessageWithTools(Request $request, string $documentationContext): \Illuminate\Http\JsonResponse
    {
        $contents = [];

        if ($request->has('conversation_history') && is_array($request->conversation_history)) {
            foreach ($request->conversation_history as $history) {
                if (isset($history['role']) && isset($history['content'])) {
                    $historyContent = $this->cleanText($history['content']);
                    $contents[] = [
                        'role' => $this->mapHistoryRoleToGemini($history['role']),
                        'parts' => [['text' => $historyContent]],
                    ];
                }
            }
        }

        $userMessage = $this->cleanText($request->message);
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $docBlock = $documentationContext !== ''
            ? "Tài liệu tham khảo (trích từ hướng dẫn hệ thống):\n".$documentationContext."\n\n"
            : '';

        $systemInstruction = $docBlock.<<<'TXT'
Bạn là trợ lý ZoroRMS. Luôn trả lời bằng tiếng Việt, rõ ràng.

Khi người dùng muốn tạo hợp đồng thuê (lease) hoặc tra cứu phòng/khách trong ERP, bạn PHẢI gọi các hàm được cung cấp:
- Nếu chưa có tenant_id hoặc unit_id: gọi search_tenants và/hoặc search_units để tìm đúng ID trong hệ thống.
- Khi đã có đủ tenant_id, unit_id, ngày bắt đầu/kết thúc (YYYY-MM-DD) và tiền thuê: gọi create_lease_contract.

Không được bịa tenant_id hay unit_id. Nếu thiếu ngày hoặc số tiền, hỏi lại một lần, ngắn gọn.

Khi công cụ trả về success và open_url, hãy nhắc người dùng mở đường dẫn đó (trong ZoroRMS) để xem hợp đồng nháp và hoàn tất các bước còn lại trên giao diện.
TXT;

        $result = $this->vertexAiChatToolsService->chat($contents, $systemInstruction);

        if (empty($result['success'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Lỗi xử lý AI.',
            ], 500);
        }

        $aiResponse = $this->cleanAndFormatResponse($result['message'] ?? '');

        return response()->json([
            'success' => true,
            'message' => $aiResponse,
        ]);
    }

    protected function mapHistoryRoleToGemini(string $role): string
    {
        return match (strtolower($role)) {
            'model', 'assistant', 'ai' => 'model',
            default => 'user',
        };
    }

    /**
     * Làm sạch text trước khi gửi cho API
     * 
     * MỤC ĐÍCH:
     * Loại bỏ các ký tự không hợp lệ, BOM, và zero-width characters để tránh lỗi encoding khi gửi cho Gemini API
     * 
     * INPUT:
     * - text (string): Text cần làm sạch → Có thể chứa ký tự không hợp lệ
     * 
     * OUTPUT:
     * - string: Text đã được làm sạch → UTF-8 hợp lệ, không có ký tự control
     * 
     * LUỒNG XỬ LÝ:
     * 1. Loại bỏ BOM (Byte Order Mark)
     * 2. Loại bỏ các ký tự control không hợp lệ (trừ \n, \r, \t)
     * 3. Kiểm tra và chuyển đổi encoding sang UTF-8 nếu cần
     * 4. Loại bỏ zero-width characters
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Tham số text: Text input từ user hoặc tài liệu
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     */
    protected function cleanText(string $text): string
    {
        $text = preg_replace('/\x{FEFF}/u', '', $text); // Loại bỏ BOM → Tránh lỗi encoding
        
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text); // Loại bỏ ký tự control không hợp lệ → Tránh lỗi JSON encoding
        
        if (!mb_check_encoding($text, 'UTF-8')) { // Nếu không phải UTF-8 hợp lệ
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8'); // Chuyển đổi sang UTF-8 → Đảm bảo encoding đúng
        }
        
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text); // Loại bỏ zero-width characters → Tránh lỗi hiển thị
        
        return $text; // Trả về text đã làm sạch → Dùng để gửi cho API
    }

    /**
     * Làm sạch và format lại response từ AI
     * 
     * MỤC ĐÍCH:
     * Loại bỏ markdown, ký tự đặc biệt, và dịch thuật ngữ tiếng Anh trong response từ AI để hiển thị đẹp và dễ đọc
     * 
     * INPUT:
     * - text (string): Response từ AI → Có thể chứa markdown, ký tự đặc biệt, thuật ngữ tiếng Anh
     * 
     * OUTPUT:
     * - string: Text đã được làm sạch và format → Không có markdown, ký tự đặc biệt, thuật ngữ đã dịch
     * 
     * LUỒNG XỬ LÝ:
     * 1. Làm sạch cơ bản (cleanText)
     * 2. Loại bỏ các dòng separator (===, ---, ___, ***)
     * 3. Loại bỏ markdown headers (###, ##, #)
     * 4. Loại bỏ markdown bold/italic (**text**, __text__, *text*, _text_)
     * 5. Loại bỏ markdown code blocks (```code```, `code`)
     * 6. Loại bỏ markdown links và images
     * 7. Chuẩn hóa khoảng trắng
     * 8. Dịch thuật ngữ tiếng Anh sang tiếng Việt
     * 9. Loại bỏ ký tự lạ và khoảng trắng thừa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Tham số text: Response từ Gemini API
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     */
    protected function cleanAndFormatResponse(string $text): string
    {
        $text = $this->cleanText($text); // Làm sạch cơ bản → Loại bỏ BOM, ký tự control
        
        $text = trim($text); // Loại bỏ khoảng trắng đầu/cuối → Chuẩn hóa text
        
        $text = preg_replace('/^={2,}.*$/m', '', $text); // Loại bỏ dòng separator === → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/^-{2,}.*$/m', '', $text); // Loại bỏ dòng separator --- → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/^_{2,}.*$/m', '', $text); // Loại bỏ dòng separator ___ → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/^\*{2,}.*$/m', '', $text); // Loại bỏ dòng separator *** → Tránh hiển thị ký tự đặc biệt
        
        $text = preg_replace('/^#{1,6}\s+(.+)$/m', '$1', $text); // Loại bỏ markdown headers → Chỉ giữ lại text
        
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text); // Loại bỏ markdown bold **text** → Chỉ giữ lại text
        $text = preg_replace('/__([^_]+)__/', '$1', $text); // Loại bỏ markdown bold __text__ → Chỉ giữ lại text
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text); // Loại bỏ markdown italic *text* → Chỉ giữ lại text
        $text = preg_replace('/_([^_]+)_/', '$1', $text); // Loại bỏ markdown italic _text_ → Chỉ giữ lại text
        
        $text = preg_replace('/```[\s\S]*?```/', '', $text); // Loại bỏ markdown code blocks ```code``` → Xóa toàn bộ code block
        $text = preg_replace('/`([^`]+)`/', '$1', $text); // Loại bỏ markdown inline code `code` → Chỉ giữ lại text
        
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text); // Loại bỏ markdown links [text](url) → Chỉ giữ lại text
        
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '', $text); // Loại bỏ markdown images ![alt](url) → Xóa toàn bộ image
        
        $text = preg_replace('/^>\s+/m', '', $text); // Loại bỏ markdown blockquote > → Chỉ giữ lại text
        
        $text = preg_replace('/\n{3,}/', "\n\n", $text); // Chuẩn hóa khoảng trắng → Nhiều dòng trống thành 2 dòng
        
        $text = preg_replace('/\*{2,}/', '', $text); // Loại bỏ ký tự markdown còn sót ** → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/_{2,}/', '', $text); // Loại bỏ ký tự markdown còn sót __ → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/={2,}/', '', $text); // Loại bỏ ký tự markdown còn sót == → Tránh hiển thị ký tự đặc biệt
        $text = preg_replace('/-{2,}/', '', $text); // Loại bỏ ký tự markdown còn sót -- → Tránh hiển thị ký tự đặc biệt
        
        $englishTerms = [
            'Lease', 'Leases', 'Invoice', 'Invoices', 'Payment', 'Payments',
            'Booking Deposit', 'Booking Deposits', 'Tenant', 'Tenants',
            'Property', 'Properties', 'Unit', 'Units', 'Lead', 'Leads',
            'Viewing', 'Viewings', 'Ticket', 'Tickets', 'Review', 'Reviews',
            'Staff', 'Manager', 'Managers', 'Agent', 'Agents',
            'Contract', 'Contracts', 'Deposit', 'Deposits', 'Refund', 'Refunds',
            'Commission', 'Payroll', 'Salary', 'Meter', 'Meters',
            'Organization', 'Organizations', 'User', 'Users', 'Profile', 'Profiles'
        ]; // Danh sách thuật ngữ tiếng Anh → Dùng để dịch sang tiếng Việt
        foreach ($englishTerms as $term) {
            $text = preg_replace('/\b' . preg_quote($term, '/') . '\b/i', $this->translateTerm($term), $text); // Thay thế từ tiếng Anh → Dịch sang tiếng Việt (chỉ thay từ riêng biệt)
        }
        
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text); // Loại bỏ ký tự lạ → Chỉ giữ lại ký tự hợp lệ
        
        $text = preg_replace('/[ \t]+/', ' ', $text); // Loại bỏ khoảng trắng thừa → Nhiều space thành 1 space
        $text = preg_replace('/\n[ \t]+/', "\n", $text); // Loại bỏ khoảng trắng đầu dòng → Chuẩn hóa format
        
        return trim($text); // Trả về text đã làm sạch → Hiển thị cho user
    }

    /**
     * Dịch thuật ngữ tiếng Anh sang tiếng Việt
     * 
     * MỤC ĐÍCH:
     * Dịch các thuật ngữ tiếng Anh phổ biến trong hệ thống sang tiếng Việt để response dễ hiểu hơn
     * 
     * INPUT:
     * - term (string): Thuật ngữ tiếng Anh cần dịch → Ví dụ: "Lease", "Invoice"
     * 
     * OUTPUT:
     * - string: Thuật ngữ tiếng Việt tương ứng → Ví dụ: "Hợp đồng thuê", "Hóa đơn"
     * 
     * LUỒNG XỬ LÝ:
     * 1. Chuyển term sang lowercase để so sánh
     * 2. Tìm trong mảng translations
     * 3. Trả về bản dịch nếu có, nếu không trả về term gốc
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Tham số term: Thuật ngữ tiếng Anh
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     */
    protected function translateTerm(string $term): string
    {
        $termLower = strtolower($term); // Chuyển sang lowercase → Để so sánh không phân biệt hoa thường
        
        $translations = [
            // Contracts
            'lease' => 'Hợp đồng thuê',
            'leases' => 'Hợp đồng thuê',
            'contract' => 'Hợp đồng',
            'contracts' => 'Hợp đồng',
            
            // Billing
            'invoice' => 'Hóa đơn',
            'invoices' => 'Hóa đơn',
            'payment' => 'Thanh toán',
            'payments' => 'Thanh toán',
            
            // Deposits
            'booking deposit' => 'Đặt cọc',
            'booking deposits' => 'Đặt cọc',
            'deposit' => 'Tiền cọc',
            'deposits' => 'Tiền cọc',
            'refund' => 'Hoàn tiền',
            'refunds' => 'Hoàn tiền',
            
            // People
            'tenant' => 'Người thuê',
            'tenants' => 'Người thuê',
            'lead' => 'Khách hàng tiềm năng',
            'leads' => 'Khách hàng tiềm năng',
            'staff' => 'Nhân viên',
            'manager' => 'Quản lý',
            'managers' => 'Quản lý',
            'agent' => 'Nhân viên kinh doanh',
            'agents' => 'Nhân viên kinh doanh',
            'user' => 'Người dùng',
            'users' => 'Người dùng',
            
            // Properties
            'property' => 'Bất động sản',
            'properties' => 'Bất động sản',
            'unit' => 'Phòng/Căn hộ',
            'units' => 'Phòng/Căn hộ',
            
            // Others
            'viewing' => 'Lịch xem phòng',
            'viewings' => 'Lịch xem phòng',
            'ticket' => 'Phiếu yêu cầu',
            'tickets' => 'Phiếu yêu cầu',
            'review' => 'Đánh giá',
            'reviews' => 'Đánh giá',
            'commission' => 'Hoa hồng',
            'payroll' => 'Bảng lương',
            'salary' => 'Lương',
            'meter' => 'Đồng hồ',
            'meters' => 'Đồng hồ',
            'organization' => 'Tổ chức',
            'organizations' => 'Tổ chức',
            'profile' => 'Hồ sơ',
            'profiles' => 'Hồ sơ',
        ]; // Mảng bản dịch → Key là tiếng Anh, value là tiếng Việt
        
        return $translations[$termLower] ?? $term; // Trả về bản dịch nếu có, nếu không trả về term gốc → Giữ nguyên nếu không tìm thấy
    }
}

