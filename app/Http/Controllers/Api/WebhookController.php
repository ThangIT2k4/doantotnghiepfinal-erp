<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SepayWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller: WebhookController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý các webhook callbacks từ SePay payment gateway.
 * Controller này nhận và xử lý các thông báo về trạng thái thanh toán từ SePay.
 * 
 * LUỒNG XỬ LÝ:
 * 1. sepayVerify(): Xác thực URL webhook (cho SePay test hoặc browser access)
 * 2. sepay(): Nhận và xử lý webhook callback từ SePay
 *    - Log incoming webhook
 *    - Xác thực webhook (kiểm tra API key)
 *    - Xử lý webhook data qua SepayWebhookService
 *    - Trả về response phù hợp
 * 
 * ENDPOINTS:
 * - GET /api/webhooks/sepay/verify - Xác thực URL webhook
 * - POST /api/webhooks/sepay - Nhận webhook callback từ SePay
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Request: Webhook data từ SePay (transaction_id, amount, status, etc.)
 * - Headers: Authorization header (API key)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log tất cả webhook requests (info, warning, error)
 * - Database: Cập nhật payment status qua SepayWebhookService
 * 
 * SERVICE ĐƯỢC GỌI:
 * - App\Services\SepayWebhookService
 *   Methods:
 *   + validateWebhook($request) - Xác thực webhook (kiểm tra API key)
 *   + processWebhook($data) - Xử lý webhook data và cập nhật database
 * 
 * AUTHENTICATION:
 * - SePay gửi webhook với Authorization header: "Apikey YOUR_API_KEY"
 * - validateWebhook() sẽ kiểm tra API key này
 * 
 * HTTP RESPONSE CODES:
 * - 200: Success (webhook verified hoặc processed successfully)
 * - 201: Created (webhook processed và tạo mới record)
 * - 401: Unauthorized (authentication failed)
 * - 500: Internal Server Error (exception occurred)
 * 
 * LƯU Ý:
 * - Webhook endpoint phải trả về 200/201 để SePay không retry
 * - Chỉ trả về 4xx/5xx cho lỗi hệ thống thực sự
 * - Tất cả webhook requests đều được log để debug
 */
class WebhookController extends Controller
{
    /**
     * SepayWebhookService instance (được inject qua constructor)
     * 
     * Service này xử lý:
     * - Xác thực webhook (validate API key)
     * - Xử lý webhook data (cập nhật payment status)
     * 
     * @var SepayWebhookService
     */
    protected $sepayService;

    /**
     * Constructor: Khởi tạo controller với SepayWebhookService
     * 
     * Dependency Injection:
     * - Laravel tự động resolve SepayWebhookService từ service container
     * - Service này nằm tại: app/Services/SepayWebhookService.php
     * 
     * @param SepayWebhookService $sepayService Service xử lý SePay webhook
     */
    public function __construct(SepayWebhookService $sepayService)
    {
        /**
         * Lưu service instance vào property
         * 
         * $this->sepayService = $sepayService - Gán service instance vào property
         *   - $this->sepayService là property của class
         *   - $sepayService là tham số được inject từ Laravel container
         *   - Service sẽ được sử dụng trong các methods để xử lý webhook
         */
        $this->sepayService = $sepayService;
    }

    /**
     * Xác thực URL webhook (cho SePay test hoặc browser access)
     * 
     * MỤC ĐÍCH:
     * - Cho phép SePay hoặc developer test webhook endpoint
     * - Xác nhận endpoint đang hoạt động
     * - Cung cấp thông tin về cách sử dụng endpoint
     * 
     * ENDPOINT:
     * - GET /api/webhooks/sepay/verify
     * 
     * @param Request $request HTTP request (có thể từ browser hoặc SePay test)
     * @return \Illuminate\Http\JsonResponse JSON response với thông tin endpoint
     */
    public function sepayVerify(Request $request)
    {
        /**
         * Trả về JSON response với thông tin endpoint
         * 
         * response()->json([...], 200) - Tạo JSON response
         *   - response() là helper function của Laravel để tạo HTTP response
         *   - json() là method để tạo JSON response
         *   - Tham số 1: Array chứa data sẽ được convert thành JSON
         *   - Tham số 2: 200 - HTTP status code (OK)
         * 
         * Array chứa:
         * - 'success' => true - Trạng thái thành công
         * - 'message' => '...' - Thông báo mô tả endpoint
         * - 'endpoint' => '/api/webhooks/sepay' - Đường dẫn endpoint thực tế
         * - 'method' => 'POST' - HTTP method mà SePay sẽ sử dụng
         * - 'note' => '...' - Ghi chú về cách SePay gửi webhook (POST với Authorization header)
         * 
         * Response này giúp:
         * - Xác nhận endpoint đang hoạt động
         * - Cung cấp thông tin cho SePay để cấu hình webhook
         * - Hướng dẫn developer cách test webhook
         */
        return response()->json([
            'success' => true,
            'message' => 'SePay webhook endpoint is active. This endpoint only accepts POST requests.',
            'endpoint' => '/api/webhooks/sepay',
            'method' => 'POST',
            'note' => 'SePay will send webhook callbacks using POST method with Authorization header: Apikey YOUR_API_KEY'
        ], 200);
    }

    /**
     * Nhận và xử lý webhook từ SePay
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Log incoming webhook (IP, data, headers)
     * 2. Xác thực webhook (kiểm tra API key qua SepayWebhookService)
     * 3. Nếu authentication failed: Trả về 401 Unauthorized
     * 4. Lấy dữ liệu từ request
     * 5. Xử lý webhook qua SepayWebhookService->processWebhook()
     * 6. Trả về response phù hợp (201 nếu success, 200 nếu logic error)
     * 
     * ENDPOINT:
     * - POST /api/webhooks/sepay
     * 
     * AUTHENTICATION:
     * - SePay gửi webhook với Authorization header: "Apikey YOUR_API_KEY"
     * - validateWebhook() sẽ kiểm tra API key này
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: Webhook data từ SePay (transaction_id, amount, status, etc.)
     * - Headers: Authorization header (API key)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log tất cả webhook requests
     * - Database: Cập nhật payment status qua SepayWebhookService
     * 
     * HTTP RESPONSE CODES:
     * - 201: Created (webhook processed successfully)
     * - 200: OK (logic error, nhưng không retry)
     * - 401: Unauthorized (authentication failed)
     * - 500: Internal Server Error (exception occurred)
     * 
     * @param Request $request HTTP request chứa webhook data từ SePay
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả xử lý
     */
    public function sepay(Request $request)
    {
        /**
         * Xử lý lỗi khi xử lý webhook
         * 
         * try { ... } catch { ... } - Xử lý exception
         * - Nếu xử lý thành công: tiếp tục
         * - Nếu có exception: catch, ghi log, và trả về 500
         * - Đảm bảo webhook luôn trả về response (không crash)
         */
        try {
            /**
             * Log incoming webhook để debug và theo dõi
             * 
             * Log::info() - Ghi log với level INFO (thông tin bình thường)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
             * 
             * 'SePay webhook received' - Message mô tả sự kiện
             * 
             * Array chứa context data:
             * - 'ip' => $request->ip() - IP address của request
             *   - ip() là method của Request để lấy IP address
             *   - Ví dụ: "192.168.1.1"
             *   - Dùng để theo dõi và debug
             * - 'data' => $request->all() - Tất cả data từ request
             *   - all() là method của Request để lấy tất cả input data
             *   - Bao gồm: transaction_id, amount, status, etc.
             *   - Dùng để debug webhook data
             * - 'headers' => $request->headers->all() - Tất cả headers từ request
             *   - headers là property của Request chứa HeaderBag
             *   - all() là method để lấy tất cả headers
             *   - Bao gồm: Authorization, Content-Type, etc.
             *   - Dùng để debug authentication
             * 
             * Log này giúp:
             * - Theo dõi tất cả webhook requests
             * - Debug khi có vấn đề
             * - Audit trail cho security
             */
            Log::info('SePay webhook received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            /**
             * Xác thực webhook (kiểm tra API key)
             * 
             * $this->sepayService->validateWebhook($request) - Gọi method để xác thực webhook
             *   - validateWebhook() là method của SepayWebhookService
             *   - Method này nhận Request instance làm tham số
             *   - Method này sẽ:
             *     1. Lấy Authorization header từ request
             *     2. Kiểm tra API key có hợp lệ không (so sánh với config)
             *     3. Trả về boolean: true nếu hợp lệ, false nếu không
             * 
             * if (!$this->sepayService->validateWebhook($request)) - Kiểm tra xem authentication có thất bại không
             *   - ! là NOT operator, đảo ngược giá trị boolean
             *   - Nếu validateWebhook() trả về false, !false = true, vào block if
             *   - Nếu validateWebhook() trả về true, !true = false, không vào block if
             */
            if (!$this->sepayService->validateWebhook($request)) {
                /**
                 * Log cảnh báo khi authentication thất bại
                 * 
                 * Log::warning() - Ghi log với level WARNING (cảnh báo)
                 *   - Log được ghi vào: storage/logs/laravel.log
                 *   - Format: [YYYY-MM-DD HH:MM:SS] local.WARNING: Message {context}
                 * 
                 * 'SePay webhook: Authentication failed' - Message mô tả cảnh báo
                 * 
                 * Array chứa context data:
                 * - 'ip' => $request->ip() - IP address của request
                 *   - Dùng để theo dõi các request không hợp lệ
                 *   - Có thể là: attacker, misconfigured SePay, etc.
                 * 
                 * Log này giúp:
                 * - Phát hiện các request không hợp lệ
                 * - Security monitoring
                 * - Debug authentication issues
                 */
                Log::warning('SePay webhook: Authentication failed', [
                    'ip' => $request->ip(),
                ]);

                /**
                 * Trả về response 401 Unauthorized
                 * 
                 * response()->json([...], 401) - Tạo JSON response với status 401
                 *   - 401 là HTTP status code cho Unauthorized
                 *   - Báo cho SePay biết authentication failed
                 *   - SePay có thể retry với API key đúng
                 * 
                 * Array chứa:
                 * - 'success' => false - Trạng thái thất bại
                 * - 'message' => 'Unauthorized' - Thông báo lỗi
                 * 
                 * Response này báo cho SePay biết cần kiểm tra lại API key
                 */
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            /**
             * Lấy dữ liệu từ request
             * 
             * $request->all() - Lấy tất cả input data từ request
             *   - all() là method của Request để lấy tất cả input data
             *   - Bao gồm: form data, JSON body, query parameters
             *   - Trả về associative array chứa tất cả data
             *   - Ví dụ: ['transaction_id' => '123', 'amount' => 100000, 'status' => 'success']
             * 
             * $data - Biến lưu webhook data từ SePay
             *   - Sẽ được truyền vào SepayWebhookService để xử lý
             */
            $data = $request->all();

            /**
             * Xử lý webhook qua service
             * 
             * $this->sepayService->processWebhook($data) - Gọi method để xử lý webhook
             *   - processWebhook() là method của SepayWebhookService
             *   - Method này nhận array $data làm tham số
             *   - Method này sẽ:
             *     1. Parse webhook data (transaction_id, amount, status, etc.)
             *     2. Tìm payment record tương ứng trong database
             *     3. Cập nhật payment status (pending -> success/failed)
             *     4. Cập nhật related records (invoices, etc.)
             *     5. Trả về array: ['success' => true/false, 'message' => '...']
             * 
             * $result - Biến lưu kết quả xử lý
             *   - Array chứa: ['success' => boolean, 'message' => string]
             *   - Sẽ được sử dụng để tạo response
             */
            $result = $this->sepayService->processWebhook($data);

            /**
             * Trả về response theo yêu cầu của SePay
             * 
             * if ($result['success']) - Kiểm tra xem xử lý có thành công không
             *   - $result['success'] truy cập key 'success' từ array $result
             *   - Nếu true (thành công), vào block if
             *   - Nếu false (logic error), vào block else
             */
            if ($result['success']) {
                /**
                 * Trả về response 201 Created khi xử lý thành công
                 * 
                 * response()->json([...], 201) - Tạo JSON response với status 201
                 *   - 201 là HTTP status code cho Created
                 *   - Báo cho SePay biết webhook đã được xử lý thành công
                 *   - SePay sẽ không retry webhook này
                 * 
                 * Array chứa:
                 * - 'success' => true - Trạng thái thành công
                 * - 'message' => $result['message'] - Thông báo từ service
                 *   - $result['message'] là message từ SepayWebhookService
                 *   - Ví dụ: "Payment processed successfully"
                 * 
                 * Response này báo cho SePay biết webhook đã được xử lý thành công
                 */
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ], 201);
            } else {
                /**
                 * Trả về response 200 OK khi có logic error (không retry)
                 * 
                 * Lưu ý: Vẫn trả về 200 để SePay không retry các lỗi logic
                 * - Nếu trả về 4xx/5xx, SePay sẽ retry webhook
                 * - Logic errors (ví dụ: payment không tìm thấy) không nên retry
                 * - Chỉ trả về 4xx/5xx cho lỗi hệ thống thực sự (database error, etc.)
                 * 
                 * response()->json([...], 200) - Tạo JSON response với status 200
                 *   - 200 là HTTP status code cho OK
                 *   - Báo cho SePay biết webhook đã được nhận (nhưng có logic error)
                 *   - SePay sẽ không retry webhook này
                 * 
                 * Array chứa:
                 * - 'success' => false - Trạng thái thất bại (logic error)
                 * - 'message' => $result['message'] - Thông báo lỗi từ service
                 *   - $result['message'] là message từ SepayWebhookService
                 *   - Ví dụ: "Payment not found"
                 * 
                 * Response này báo cho SePay biết webhook đã được nhận nhưng có logic error
                 */
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 200);
            }

        } catch (\Exception $e) {
            /**
             * Xử lý exception khi xử lý webhook
             * 
             * catch (\Exception $e) - Bắt exception khi xử lý webhook thất bại
             * - Có thể là: database error, service error, validation error, etc.
             * - $e là exception object chứa thông tin về lỗi
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
             * 
             * 'SePay webhook: Exception occurred' - Message mô tả lỗi
             * 
             * Array chứa context data:
             * - 'error' => $e->getMessage() - Error message của exception
             *   - getMessage() là method của Exception để lấy error message
             *   - Ví dụ: "Database connection failed"
             * - 'trace' => $e->getTraceAsString() - Stack trace của exception
             *   - getTraceAsString() là method của Exception để lấy stack trace
             *   - Stack trace cho biết vị trí chính xác trong code nơi lỗi xảy ra
             *   - Giúp debug khi có lỗi
             * - 'data' => $request->all() - Webhook data từ request
             *   - Dùng để debug webhook data khi có lỗi
             * 
             * Log này giúp:
             * - Debug lỗi hệ thống
             * - Theo dõi các exception
             * - Audit trail cho errors
             */
            Log::error('SePay webhook: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            /**
             * Trả về response 500 Internal Server Error
             * 
             * response()->json([...], 500) - Tạo JSON response với status 500
             *   - 500 là HTTP status code cho Internal Server Error
             *   - Báo cho SePay biết có lỗi hệ thống
             *   - SePay có thể retry webhook này (nếu được cấu hình)
             * 
             * Array chứa:
             * - 'success' => false - Trạng thái thất bại
             * - 'message' => 'Internal server error' - Thông báo lỗi chung
             *   - Không trả về error message chi tiết để bảo mật
             *   - Error message chi tiết đã được ghi vào log
             * 
             * Response này báo cho SePay biết có lỗi hệ thống
             * SePay có thể retry webhook này sau một khoảng thời gian
             */
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}

