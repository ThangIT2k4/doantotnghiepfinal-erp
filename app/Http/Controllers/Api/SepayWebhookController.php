<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashOutflow;
use App\Models\CompanyInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller: SepayWebhookController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý webhook callbacks từ SePay payment gateway để cập nhật trạng thái thanh toán.
 * Controller này nhận thông báo về giao dịch từ SePay và cập nhật CashOutflow và CompanyInvoice tương ứng.
 * 
 * LUỒNG XỬ LÝ:
 * 1. handleWebhook(): Nhận và xử lý webhook callback từ SePay
 *    - Validate webhook data
 *    - Chỉ xử lý incoming transfers (transfer_type = 'in')
 *    - Tìm matching CashOutflow
 *    - Cập nhật trạng thái thanh toán
 * 2. findMatchingCashOutflow(): Tìm CashOutflow tương ứng với webhook data
 * 3. updateCashOutflowStatus(): Cập nhật trạng thái CashOutflow và CompanyInvoice
 * 4. checkPendingPayments(): Manual trigger để kiểm tra pending payments
 * 5. updateRelatedCompanyInvoice(): Cập nhật CompanyInvoice khi CashOutflow thành công
 * 
 * ENDPOINTS:
 * - POST /api/webhooks/sepay/handle - Nhận webhook callback từ SePay
 * - POST /api/webhooks/sepay/check-pending - Manual check pending payments
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Request: Webhook data từ SePay (transaction_id, amount, transfer_type, etc.)
 * - Model: CashOutflow (bảng cash_outflows)
 * - Model: CompanyInvoice (bảng company_invoices)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Cập nhật bảng cash_outflows (status, paid_at, sepay_transaction_id, etc.)
 * - Cập nhật bảng company_invoices (status, paid_at)
 * - Logs: Ghi log tất cả webhook processing
 * 
 * VALIDATION RULES:
 * - transaction_id: required, string
 * - gateway: required, string
 * - transaction_date: required, date
 * - account_number: required, string
 * - transfer_type: required, in:in,out
 * - amount: required, numeric, min:0
 * - content: nullable, string
 * - reference_code: nullable, string
 * 
 * DATABASE TRANSACTIONS:
 * - Sử dụng DB::beginTransaction() và DB::commit() để đảm bảo data consistency
 * - Rollback nếu có lỗi
 * 
 * LƯU Ý:
 * - Chỉ xử lý incoming transfers (transfer_type = 'in')
 * - Tìm matching CashOutflow bằng amount và payment_method
 * - Tự động cập nhật CompanyInvoice khi CashOutflow thành công
 * - Tất cả webhook processing đều được log
 */
class SepayWebhookController extends Controller
{
    /**
     * Handle SePay webhook callback
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Validate webhook data với validation rules
     * 2. Nếu validation thất bại: Trả về 400 Bad Request
     * 3. Chỉ xử lý incoming transfers (transfer_type = 'in')
     * 4. Tìm matching CashOutflow bằng amount và payment_method
     * 5. Nếu không tìm thấy: Log và trả về 200 (không retry)
     * 6. Cập nhật CashOutflow status và related CompanyInvoice
     * 7. Trả về 200 Success
     * 
     * ENDPOINT:
     * - POST /api/webhooks/sepay/handle
     * 
     * VALIDATION:
     * - transaction_id, gateway, transaction_date, account_number, transfer_type, amount, content, reference_code
     * 
     * @param Request $request HTTP request chứa webhook data từ SePay
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả xử lý
     */
    public function handleWebhook(Request $request)
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
             * Validate webhook data với validation rules
             * 
             * Validator::make($request->all(), [...]) - Tạo validator instance
             *   - make() là static method của Validator facade
             *   - Tham số 1: $request->all() - Tất cả input data từ request
             *   - Tham số 2: Array chứa validation rules
             * 
             * Validation rules:
             * - 'transaction_id' => 'required|string' - Bắt buộc, phải là string
             *   - required: Field bắt buộc phải có
             *   - string: Phải là string type
             * - 'gateway' => 'required|string' - Bắt buộc, phải là string
             * - 'transaction_date' => 'required|date' - Bắt buộc, phải là date format
             * - 'account_number' => 'required|string' - Bắt buộc, phải là string
             * - 'transfer_type' => 'required|in:in,out' - Bắt buộc, chỉ nhận giá trị 'in' hoặc 'out'
             *   - in:in,out nghĩa là giá trị phải nằm trong array ['in', 'out']
             * - 'amount' => 'required|numeric|min:0' - Bắt buộc, phải là số, tối thiểu 0
             *   - numeric: Phải là số (integer hoặc float)
             *   - min:0: Giá trị tối thiểu là 0
             * - 'content' => 'nullable|string' - Tùy chọn, nếu có thì phải là string
             *   - nullable: Cho phép null hoặc không có
             * - 'reference_code' => 'nullable|string' - Tùy chọn, nếu có thì phải là string
             * 
             * $validator - Validator instance để validate data
             *   - Sẽ được sử dụng để kiểm tra validation và lấy validated data
             */
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
                'gateway' => 'required|string',
                'transaction_date' => 'required|date',
                'account_number' => 'required|string',
                'transfer_type' => 'required|in:in,out',
                'amount' => 'required|numeric|min:0',
                'content' => 'nullable|string',
                'reference_code' => 'nullable|string',
            ]);

            /**
             * Kiểm tra validation có thất bại không
             * 
             * $validator->fails() - Kiểm tra xem validation có thất bại không
             *   - fails() là method của Validator để kiểm tra validation
             *   - Trả về true nếu có lỗi validation
             *   - Trả về false nếu validation thành công
             * 
             * if ($validator->fails()) - Kiểm tra xem có lỗi validation không
             *   - Nếu có lỗi, vào block if
             */
            if ($validator->fails()) {
                /**
                 * Log cảnh báo khi validation thất bại
                 * 
                 * Log::warning() - Ghi log với level WARNING (cảnh báo)
                 *   - Log được ghi vào: storage/logs/laravel.log
                 *   - Format: [YYYY-MM-DD HH:MM:SS] local.WARNING: Message {context}
                 * 
                 * 'SePay webhook validation failed' - Message mô tả cảnh báo
                 * 
                 * Array chứa context data:
                 * - 'errors' => $validator->errors() - Tất cả lỗi validation
                 *   - errors() là method của Validator để lấy tất cả lỗi
                 *   - Trả về MessageBag chứa các lỗi validation
                 *   - Ví dụ: ['transaction_id' => ['The transaction_id field is required.']]
                 * - 'data' => $request->all() - Tất cả data từ request (để debug)
                 * 
                 * Log này giúp:
                 * - Phát hiện các webhook data không hợp lệ
                 * - Debug validation issues
                 * - Theo dõi các request không đúng format
                 */
                Log::warning('SePay webhook validation failed', [
                    'errors' => $validator->errors(),
                    'data' => $request->all()
                ]);
                
                /**
                 * Trả về response 400 Bad Request
                 * 
                 * response()->json([...], 400) - Tạo JSON response với status 400
                 *   - 400 là HTTP status code cho Bad Request
                 *   - Báo cho SePay biết webhook data không hợp lệ
                 *   - SePay có thể retry với data đúng format
                 * 
                 * Array chứa:
                 * - 'error' => 'Invalid webhook data' - Thông báo lỗi
                 * 
                 * Response này báo cho SePay biết webhook data không hợp lệ
                 */
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            /**
             * Lấy validated data từ validator
             * 
             * $validator->validated() - Lấy data đã được validate thành công
             *   - validated() là method của Validator để lấy validated data
             *   - Chỉ trả về các fields đã pass validation
             *   - Tự động loại bỏ các fields không có trong rules
             *   - Trả về associative array chứa validated data
             *   - Ví dụ: ['transaction_id' => '123', 'amount' => 100000, 'transfer_type' => 'in']
             * 
             * $webhookData - Biến lưu validated webhook data
             *   - Sẽ được sử dụng để tìm matching CashOutflow và cập nhật status
             */
            $webhookData = $validator->validated();

            /**
             * Chỉ xử lý incoming transfers (transfer_type = 'in')
             * 
             * if ($webhookData['transfer_type'] !== 'in') - Kiểm tra xem có phải incoming transfer không
             *   - $webhookData['transfer_type'] truy cập key 'transfer_type' từ array
             *   - !== là strict not equal operator (so sánh cả giá trị và kiểu dữ liệu)
             *   - 'in' là giá trị cho incoming transfer (tiền vào)
             *   - Nếu transfer_type != 'in' (ví dụ: 'out'), vào block if
             * 
             * Lý do: Chỉ xử lý incoming transfers vì:
             * - Outgoing transfers (tiền ra) không liên quan đến thanh toán của khách hàng
             * - Chỉ incoming transfers mới là thanh toán từ khách hàng đến hệ thống
             */
            if ($webhookData['transfer_type'] !== 'in') {
                /**
                 * Trả về response 200 OK (ignore outgoing transfer)
                 * 
                 * response()->json([...], 200) - Tạo JSON response với status 200
                 *   - 200 là HTTP status code cho OK
                 *   - Báo cho SePay biết webhook đã được nhận (nhưng bị ignore)
                 *   - SePay sẽ không retry webhook này
                 * 
                 * Array chứa:
                 * - 'message' => 'Ignoring outgoing transfer' - Thông báo bỏ qua
                 * 
                 * Response này báo cho SePay biết webhook đã được nhận nhưng bị bỏ qua (không phải incoming)
                 */
                return response()->json(['message' => 'Ignoring outgoing transfer'], 200);
            }

            /**
             * Tìm matching CashOutflow bằng content và amount
             * 
             * $this->findMatchingCashOutflow($webhookData) - Gọi private method để tìm CashOutflow
             *   - findMatchingCashOutflow() là private method trong controller này
             *   - Method này nhận array $webhookData làm tham số
             *   - Method này sẽ:
             *     1. Query từ bảng cash_outflows với điều kiện:
             *        - status = 'pending'
             *        - amount = webhook amount
             *        - payment_method = 'sepay'
             *        - created_at >= 7 days ago (chỉ kiểm tra payments gần đây)
             *     2. Trả về CashOutflow model instance nếu tìm thấy
             *     3. Trả về null nếu không tìm thấy
             * 
             * $cashOutflow - Biến lưu CashOutflow model instance (hoặc null)
             *   - Nếu tìm thấy: $cashOutflow là instance của App\Models\CashOutflow
             *   - Nếu không tìm thấy: $cashOutflow = null
             */
            $cashOutflow = $this->findMatchingCashOutflow($webhookData);

            /**
             * Kiểm tra xem có tìm thấy CashOutflow không
             * 
             * if (!$cashOutflow) - Kiểm tra xem $cashOutflow có null không
             *   - ! là NOT operator, đảo ngược giá trị boolean
             *   - Nếu $cashOutflow = null, !null = true, vào block if
             *   - Nếu $cashOutflow != null, !$cashOutflow = false, không vào block if
             */
            if (!$cashOutflow) {
                /**
                 * Log thông tin khi không tìm thấy matching CashOutflow
                 * 
                 * Log::info() - Ghi log với level INFO (thông tin bình thường)
                 *   - Log được ghi vào: storage/logs/laravel.log
                 *   - Format: [YYYY-MM-DD HH:MM:SS] local.INFO: Message {context}
                 * 
                 * 'No matching cash outflow found for SePay webhook' - Message mô tả sự kiện
                 * 
                 * Array chứa context data:
                 * - 'transaction_id' => $webhookData['transaction_id'] - Transaction ID từ SePay
                 * - 'amount' => $webhookData['amount'] - Số tiền từ webhook
                 * - 'content' => $webhookData['content'] - Nội dung giao dịch (nếu có)
                 * 
                 * Log này giúp:
                 * - Theo dõi các webhook không tìm thấy matching payment
                 * - Debug khi có vấn đề matching
                 * - Có thể do: payment chưa được tạo, amount không khớp, payment_method khác, etc.
                 */
                Log::info('No matching cash outflow found for SePay webhook', [
                    'transaction_id' => $webhookData['transaction_id'],
                    'amount' => $webhookData['amount'],
                    'content' => $webhookData['content']
                ]);
                
                /**
                 * Trả về response 200 OK (không retry)
                 * 
                 * response()->json([...], 200) - Tạo JSON response với status 200
                 *   - 200 là HTTP status code cho OK
                 *   - Báo cho SePay biết webhook đã được nhận (nhưng không tìm thấy matching payment)
                 *   - SePay sẽ không retry webhook này
                 * 
                 * Lưu ý: Trả về 200 thay vì 404 để SePay không retry
                 * - Nếu trả về 404, SePay có thể retry webhook
                 * - Logic error (không tìm thấy payment) không nên retry
                 * 
                 * Array chứa:
                 * - 'message' => 'No matching payment found' - Thông báo không tìm thấy payment
                 * 
                 * Response này báo cho SePay biết webhook đã được nhận nhưng không tìm thấy matching payment
                 */
                return response()->json(['message' => 'No matching payment found'], 200);
            }

            /**
             * Cập nhật trạng thái CashOutflow và related CompanyInvoice
             * 
             * $this->updateCashOutflowStatus($cashOutflow, $webhookData) - Gọi private method để cập nhật
             *   - updateCashOutflowStatus() là private method trong controller này
             *   - Method này nhận CashOutflow model instance và array $webhookData làm tham số
             *   - Method này sẽ:
             *     1. Bắt đầu database transaction
             *     2. Cập nhật CashOutflow: status = 'success', paid_at, sepay_transaction_id, etc.
             *     3. Cập nhật related CompanyInvoice (nếu có): status = 'paid', paid_at
             *     4. Commit transaction
             *     5. Ghi log thành công
             *   - Method này có thể throw exception nếu có lỗi (sẽ được catch ở đây)
             */
            $this->updateCashOutflowStatus($cashOutflow, $webhookData);

            /**
             * Trả về response 200 Success
             * 
             * response()->json([...], 200) - Tạo JSON response với status 200
             *   - 200 là HTTP status code cho OK
             *   - Báo cho SePay biết webhook đã được xử lý thành công
             *   - SePay sẽ không retry webhook này
             * 
             * Array chứa:
             * - 'message' => 'Webhook processed successfully' - Thông báo thành công
             * 
             * Response này báo cho SePay biết webhook đã được xử lý thành công
             */
            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            /**
             * Xử lý exception khi xử lý webhook
             * 
             * catch (\Exception $e) - Bắt exception khi xử lý webhook thất bại
             * - Có thể là: database error, validation error, etc.
             * - $e là exception object chứa thông tin về lỗi
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
             * 
             * 'SePay webhook processing error: ' . $e->getMessage() - Message mô tả lỗi
             *   - $e->getMessage() trả về error message của exception
             *   - Dấu . là string concatenation operator
             * 
             * Array chứa context data:
             * - 'data' => $request->all() - Webhook data từ request (để debug)
             * - 'trace' => $e->getTraceAsString() - Stack trace của exception
             *   - getTraceAsString() là method của Exception để lấy stack trace
             *   - Stack trace cho biết vị trí chính xác trong code nơi lỗi xảy ra
             * 
             * Log này giúp:
             * - Debug lỗi hệ thống
             * - Theo dõi các exception
             * - Audit trail cho errors
             */
            Log::error('SePay webhook processing error: ' . $e->getMessage(), [
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
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
             * - 'error' => 'Internal server error' - Thông báo lỗi chung
             *   - Không trả về error message chi tiết để bảo mật
             *   - Error message chi tiết đã được ghi vào log
             * 
             * Response này báo cho SePay biết có lỗi hệ thống
             * SePay có thể retry webhook này sau một khoảng thời gian
             */
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Tìm CashOutflow tương ứng với webhook data
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy amount và content từ webhook data
     * 2. Query từ bảng cash_outflows với điều kiện:
     *    - status = 'pending'
     *    - amount = webhook amount
     *    - payment_method = 'sepay'
     *    - created_at >= 7 days ago (chỉ kiểm tra payments gần đây)
     * 3. Trả về CashOutflow model instance nếu tìm thấy, null nếu không
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Array $webhookData: Webhook data từ SePay
     * - Model: CashOutflow (bảng cash_outflows)
     * 
     * MATCHING LOGIC:
     * - Match bằng amount (số tiền phải khớp chính xác)
     * - Match bằng payment_method = 'sepay'
     * - Chỉ tìm payments trong 7 ngày gần đây (tránh match nhầm với payments cũ)
     * 
     * @param array $webhookData Webhook data từ SePay
     * @return CashOutflow|null CashOutflow model instance hoặc null
     */
    private function findMatchingCashOutflow(array $webhookData)
    {
        /**
         * Lấy amount từ webhook data
         * 
         * $webhookData['amount'] - Truy cập key 'amount' từ array
         *   - 'amount' là số tiền của giao dịch từ SePay
         *   - Ví dụ: 100000 (VND)
         * 
         * $amount - Biến lưu số tiền từ webhook
         *   - Sẽ được sử dụng để tìm matching CashOutflow
         */
        $amount = $webhookData['amount'];
        
        /**
         * Lấy content từ webhook data (có thể null)
         * 
         * $webhookData['content'] ?? '' - Null coalescing operator
         *   - ?? là null coalescing operator trong PHP 7.0+
         *   - Nếu $webhookData['content'] != null, sử dụng giá trị đó
         *   - Nếu $webhookData['content'] = null, sử dụng '' (empty string)
         *   - 'content' là nội dung giao dịch từ SePay (có thể null)
         * 
         * $content - Biến lưu nội dung giao dịch (hoặc empty string)
         *   - Hiện tại chưa được sử dụng trong matching logic
         *   - Có thể được sử dụng trong tương lai để match chính xác hơn
         */
        $content = $webhookData['content'] ?? '';

        /**
         * Tìm pending cash outflows bằng amount và payment method
         * 
         * CashOutflow::where('status', 'pending') - Bắt đầu query với điều kiện status = 'pending'
         *   - where() là method của Eloquent model để thêm điều kiện WHERE
         *   - 'status' là tên column trong bảng cash_outflows
         *   - 'pending' là giá trị cần tìm (chỉ tìm payments chưa được xử lý)
         * 
         * ->where('amount', $amount) - Thêm điều kiện amount = $amount
         *   - 'amount' là tên column trong bảng cash_outflows
         *   - $amount là số tiền từ webhook
         *   - Match bằng số tiền chính xác (không có tolerance)
         * 
         * ->where('payment_method', 'sepay') - Thêm điều kiện payment_method = 'sepay'
         *   - 'payment_method' là tên column trong bảng cash_outflows
         *   - 'sepay' là giá trị cho SePay payment method
         *   - Chỉ tìm payments từ SePay
         * 
         * ->where('created_at', '>=', now()->subDays(7)) - Thêm điều kiện created_at >= 7 days ago
         *   - 'created_at' là tên column trong bảng cash_outflows
         *   - now() là helper function của Laravel, trả về Carbon instance của thời gian hiện tại
         *   - subDays(7) là method của Carbon để trừ 7 ngày
         *   - >= là greater than or equal operator
         *   - Chỉ tìm payments được tạo trong 7 ngày gần đây
         *   - Lý do: Tránh match nhầm với payments cũ (có thể có cùng amount)
         * 
         * ->first() - Lấy bản ghi đầu tiên (hoặc null nếu không tìm thấy)
         *   - first() là method của Eloquent query builder
         *   - Trả về CashOutflow model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - Chỉ lấy 1 bản ghi (không lấy tất cả)
         * 
         * $cashOutflow - Biến lưu CashOutflow model instance (hoặc null)
         *   - Nếu tìm thấy: $cashOutflow là instance của App\Models\CashOutflow
         *   - Nếu không tìm thấy: $cashOutflow = null
         */
        $cashOutflow = CashOutflow::where('status', 'pending')
            ->where('amount', $amount)
            ->where('payment_method', 'sepay')
            ->where('created_at', '>=', now()->subDays(7)) // Only check recent payments
            ->first();

        /**
         * Kiểm tra xem có tìm thấy CashOutflow không
         * 
         * if ($cashOutflow) - Kiểm tra xem $cashOutflow có truthy không
         *   - Nếu $cashOutflow != null (tìm thấy), vào block if
         *   - Nếu $cashOutflow = null (không tìm thấy), không vào block if
         */
        if ($cashOutflow) {
            /**
             * Trả về CashOutflow model instance
             * 
             * return $cashOutflow - Trả về CashOutflow đã tìm thấy
             *   - $cashOutflow là instance của App\Models\CashOutflow
             *   - Sẽ được sử dụng để cập nhật trạng thái thanh toán
             */
            return $cashOutflow;
        }

        /**
         * Trả về null nếu không tìm thấy
         * 
         * return null - Trả về null
         *   - null nghĩa là không tìm thấy matching CashOutflow
         *   - Có thể do: payment chưa được tạo, amount không khớp, payment_method khác, payment quá cũ (> 7 days)
         */
        return null;
    }

    /**
     * Update cash outflow status and related company invoice
     */
    private function updateCashOutflowStatus(CashOutflow $cashOutflow, array $webhookData)
    {
        DB::beginTransaction();

        try {
            // Update cash outflow
            $cashOutflow->update([
                'status' => 'success',
                'paid_at' => now(),
                'sepay_transaction_id' => $webhookData['transaction_id'],
                'external_transaction_id' => $webhookData['transaction_id'],
                'sepay_callback_data' => json_encode($webhookData),
                'external_callback_data' => json_encode($webhookData),
                'sepay_callback_at' => now(),
                'external_callback_at' => now(),
            ]);

            // Update related company invoice if exists
            $this->updateRelatedCompanyInvoice($cashOutflow, $webhookData);

            DB::commit();

            Log::info('Cash outflow status updated via SePay webhook', [
                'cash_outflow_id' => $cashOutflow->id,
                'sepay_transaction_id' => $webhookData['transaction_id'],
                'amount' => $webhookData['amount']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating cash outflow status: ' . $e->getMessage(), [
                'cash_outflow_id' => $cashOutflow->id,
                'webhook_data' => $webhookData
            ]);
            throw $e;
        }
    }

    /**
     * Manual trigger to check pending payments
     */
    public function checkPendingPayments(Request $request)
    {
        try {
            $pendingOutflows = CashOutflow::where('status', 'pending')
                ->where('payment_method', 'sepay')
                ->where('created_at', '>=', now()->subDays(7)) // Only check recent payments
                ->get();

            $updatedCount = 0;

            foreach ($pendingOutflows as $outflow) {
                // Here you could implement polling logic to check SePay API
                // For now, we'll just log the pending payments
                Log::info('Pending payment found for manual check', [
                    'cash_outflow_id' => $outflow->id,
                    'amount' => $outflow->amount,
                    'note' => $outflow->note,
                    'created_at' => $outflow->created_at
                ]);
            }

            return response()->json([
                'message' => 'Manual check completed',
                'pending_count' => $pendingOutflows->count(),
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error in manual payment check: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Update related company invoice status when cash outflow becomes successful
     */
    private function updateRelatedCompanyInvoice(CashOutflow $cashOutflow, array $webhookData = [])
    {
        if ($cashOutflow->company_invoice_id) {
            $companyInvoice = CompanyInvoice::find($cashOutflow->company_invoice_id);
            
            if ($companyInvoice && $companyInvoice->status !== 'paid') {
                $companyInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                Log::info('Company invoice automatically marked as paid via SePay webhook', [
                    'company_invoice_id' => $companyInvoice->id,
                    'cash_outflow_id' => $cashOutflow->id,
                    'sepay_transaction_id' => $webhookData['transaction_id'] ?? null
                ]);
            }
        }
    }
}