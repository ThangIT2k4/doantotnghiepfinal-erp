<?php

namespace App\Console\Commands;

use App\Models\PaymentToken;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Command: CheckPaymentToken
 * 
 * MỤC ĐÍCH:
 * Kiểm tra tính hợp lệ của payment token trong hệ thống.
 * Command này được dùng để debug và kiểm tra token thanh toán khi có vấn đề.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số từ command line: token (bắt buộc), invoice_id (tùy chọn)
 * 2. Tìm token từ: Model PaymentToken (bảng payment_tokens) qua method findByToken()
 * 3. Kiểm tra tính hợp lệ: Gọi method isValid() và getValidationError() của PaymentToken
 * 4. Hiển thị thông tin: Token, Invoice liên quan, trạng thái hợp lệ
 * 
 * CÁCH CHẠY:
 * php artisan payment:check-token {token} [invoice_id]
 * 
 * Ví dụ:
 * php artisan payment:check-token abc123xyz456 123
 */
class CheckPaymentToken extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Tham số:
     * - {token}: Token cần kiểm tra (bắt buộc)
     * - {invoice_id?}: ID của invoice (tùy chọn, dùng để so sánh)
     * 
     * @var string
     */
    protected $signature = 'payment:check-token {token} {invoice_id?}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Check payment token validity';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy tham số từ command line: token và invoice_id (nếu có)
     * 2. Chuẩn hóa token: trim() để loại bỏ khoảng trắng
     * 3. Tìm token trong database:
     *    - Gọi PaymentToken::findByToken($token)
     *    - Method này tìm trong bảng payment_tokens
     * 4. Nếu không tìm thấy token:
     *    - Hiển thị lỗi
     *    - Nếu có invoice_id: Kiểm tra invoice và hiển thị các token của invoice đó
     * 5. Nếu tìm thấy token:
     *    - Hiển thị thông tin token (ID, Invoice ID, Is Used, Expires At, Created At)
     *    - Kiểm tra tính hợp lệ: Gọi paymentToken->isValid()
     *    - Nếu không hợp lệ: Hiển thị lỗi qua getValidationError()
     *    - Kiểm tra invoice liên quan: Load relationship paymentToken->invoice
     *    - So sánh invoice_id (nếu được cung cấp)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\PaymentToken (bảng payment_tokens)
     *   Method: PaymentToken::findByToken($token) - tìm token trong database
     * - Model: App\Models\Invoice (bảng invoices) - qua relationship
     * 
     * METHOD ĐƯỢC GỌI:
     * - PaymentToken::findByToken($token) - tìm token trong database
     * - PaymentToken->isValid() - kiểm tra tính hợp lệ (chưa dùng, chưa hết hạn)
     * - PaymentToken->getValidationError() - lấy thông báo lỗi nếu không hợp lệ
     * - Invoice->paymentTokens() - relationship để lấy các token của invoice
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        /**
         * Bước 1: Lấy tham số từ command line
         * 
         * $this->argument('token') - Lấy giá trị của argument 'token' từ command line
         *   - argument() là method của Laravel Command để lấy giá trị của command argument
         *   - 'token' là tên argument được định nghĩa trong $signature (bắt buộc)
         *   - Nếu user chạy: php artisan payment:check-token abc123xyz456 => trả về "abc123xyz456"
         *   - Trả về string (token cần kiểm tra)
         * 
         * $this->argument('invoice_id') - Lấy giá trị của argument 'invoice_id' từ command line
         *   - 'invoice_id' là tên argument được định nghĩa trong $signature (tùy chọn, có dấu ?)
         *   - Nếu user chạy: php artisan payment:check-token abc123xyz456 123 => trả về "123"
         *   - Nếu user không cung cấp argument này => trả về null
         *   - Trả về string (ID của invoice) hoặc null
         * 
         * $token - Biến lưu token cần kiểm tra
         * $invoiceId - Biến lưu ID của invoice (nếu được cung cấp)
         */
        $token = $this->argument('token');
        $invoiceId = $this->argument('invoice_id');
        
        /**
         * Hiển thị token (chỉ hiển thị 20 ký tự đầu để bảo mật)
         * 
         * substr($token, 0, 20) - Lấy 20 ký tự đầu tiên của token
         *   - substr($string, $start, $length) là PHP built-in function để lấy substring
         *   - $token là string chứa token đầy đủ
         *   - 0 là vị trí bắt đầu (ký tự đầu tiên)
         *   - 20 là số ký tự cần lấy
         *   - Ví dụ: Nếu $token = "abc123xyz456def789ghi012", substr($token, 0, 20) = "abc123xyz456def789gh"
         * 
         * "..." - Thêm dấu ba chấm để báo hiệu token bị cắt ngắn
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Checking token: " . substr($token, 0, 20) . "..."
         *   - Dấu . là string concatenation operator trong PHP
         *   - Ví dụ: "Checking token: abc123xyz456def789gh..."
         *   - Chỉ hiển thị một phần token để bảo mật (không hiển thị toàn bộ token)
         */
        $this->info("Checking token: " . substr($token, 0, 20) . "...");
        
        /**
         * Bước 2: Chuẩn hóa token
         * 
         * trim($token) - Loại bỏ khoảng trắng ở đầu và cuối token
         *   - trim() là PHP built-in function để loại bỏ whitespace (spaces, tabs, newlines) ở đầu và cuối string
         *   - Ví dụ: trim("  abc123  ") = "abc123"
         *   - Đảm bảo token không có khoảng trắng thừa (có thể do user nhập nhầm)
         * 
         * $token = trim($token) - Gán lại giá trị đã được trim vào biến $token
         *   - Token đã được chuẩn hóa, sẵn sàng để tìm trong database
         */
        $token = trim($token);
        
        /**
         * Bước 3: Tìm token trong database
         * 
         * PaymentToken::findByToken($token) - Gọi static method để tìm token trong database
         *   - findByToken() là static method trong PaymentToken model
         *   - Method này tìm token trong bảng payment_tokens với điều kiện token = $token
         *   - File: app/Models/PaymentToken.php
         *   - Method này sẽ execute SQL query: SELECT * FROM payment_tokens WHERE token = '$token' LIMIT 1
         *   - Trả về PaymentToken model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         * 
         * $paymentToken - Biến lưu PaymentToken model instance (hoặc null)
         *   - Nếu tìm thấy: $paymentToken là instance của App\Models\PaymentToken
         *   - Nếu không tìm thấy: $paymentToken = null
         */
        $paymentToken = PaymentToken::findByToken($token);
        
        /**
         * Bước 4: Xử lý khi không tìm thấy token
         * 
         * if (!$paymentToken) - Kiểm tra xem $paymentToken có null không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $paymentToken = null, !null = true, vào block if
         *   - Nếu $paymentToken != null, !$paymentToken = false, không vào block if
         */
        if (!$paymentToken) {
            /**
             * Hiển thị lỗi token không tìm thấy
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             *   - "Token not found in database"
             *   - Thông báo cho user biết token không tồn tại trong database
             */
            $this->error("Token not found in database");
            
            /**
             * Nếu có invoice_id, kiểm tra invoice và hiển thị các token của invoice
             * 
             * if ($invoiceId) - Kiểm tra xem có invoice_id được cung cấp không
             *   - $invoiceId có thể là string (ID của invoice) hoặc null
             *   - Nếu có giá trị (không null, không empty), vào block if
             *   - Mục đích: Giúp debug khi token không tìm thấy nhưng muốn xem các token khác của invoice
             */
            if ($invoiceId) {
                /**
                 * Tìm invoice trong database bằng ID
                 * 
                 * Invoice::find($invoiceId) - Tìm invoice trong database bằng primary key
                 *   - find() là method của Eloquent Model để tìm record bằng ID
                 *   - find() sẽ execute SQL query: SELECT * FROM invoices WHERE id = $invoiceId LIMIT 1
                 *   - Trả về Invoice model instance nếu tìm thấy
                 *   - Trả về null nếu không tìm thấy
                 * 
                 * $invoice - Biến lưu Invoice model instance (hoặc null)
                 */
                $invoice = Invoice::find($invoiceId);
                
                /**
                 * Nếu tìm thấy invoice, hiển thị thông tin và các token của invoice
                 * 
                 * if ($invoice) - Kiểm tra xem $invoice có null không
                 *   - Nếu tìm thấy invoice, vào block if
                 */
                if ($invoice) {
                    /**
                     * Hiển thị thông tin invoice
                     * 
                     * $this->info() - Hiển thị message màu xanh trong console
                     *   - "Invoice exists: {$invoice->invoice_no}"
                     *   - {$invoice->invoice_no} là string interpolation, sẽ thay thế bằng giá trị invoice_no
                     *   - Ví dụ: "Invoice exists: INV-2024-001"
                     *   - Thông báo cho user biết invoice tồn tại
                     */
                    $this->info("Invoice exists: {$invoice->invoice_no}");
                    
                    /**
                     * Hiển thị số lượng payment tokens của invoice
                     * 
                     * $invoice->paymentTokens() - Truy cập relationship paymentTokens() từ Invoice model
                     *   - paymentTokens() là method trong Invoice model, trả về HasMany relationship
                     *   - Relationship này query từ bảng payment_tokens với điều kiện invoice_id = $invoice->id
                     *   - Trả về query builder, chưa thực thi query
                     * 
                     * ->count() - Đếm số lượng payment tokens
                     *   - count() sẽ execute SQL query: SELECT COUNT(*) FROM payment_tokens WHERE invoice_id = $invoice->id
                     *   - Trả về integer (số lượng tokens)
                     * 
                     * $this->info() - Hiển thị message
                     *   - "Invoice has " . $invoice->paymentTokens()->count() . " payment tokens"
                     *   - Dấu . là string concatenation operator
                     *   - Ví dụ: "Invoice has 3 payment tokens"
                     */
                    $this->info("Invoice has " . $invoice->paymentTokens()->count() . " payment tokens");
                    
                    /**
                     * Lấy tất cả payment tokens của invoice
                     * 
                     * $invoice->paymentTokens() - Truy cập relationship paymentTokens()
                     *   - Trả về query builder, chưa thực thi query
                     * 
                     * ->get() - Thực thi query và trả về Collection chứa các PaymentToken models
                     *   - get() sẽ execute SQL query: SELECT * FROM payment_tokens WHERE invoice_id = $invoice->id
                     *   - Trả về Collection chứa các PaymentToken models
                     * 
                     * $tokens - Biến lưu Collection chứa các PaymentToken models
                     */
                    $tokens = $invoice->paymentTokens()->get();
                    
                    /**
                     * Hiển thị thông tin từng token của invoice
                     * 
                     * foreach ($tokens as $t) - Lặp qua từng phần tử trong Collection
                     * - $tokens là Collection chứa các PaymentToken models
                     * - $t là từng PaymentToken model trong Collection
                     * - Mỗi lần lặp, $t sẽ là một instance của App\Models\PaymentToken
                     * 
                     * $this->line() - Hiển thị message màu trắng trong console
                     *   - "  - Token ID: {$t->id}, Used: " . ($t->is_used ? 'Yes' : 'No') . ", Expires: " . ($t->expires_at ? $t->expires_at->format('Y-m-d H:i:s') : 'Never')
                     *   - {$t->id} - ID của token (string interpolation)
                     *   - ($t->is_used ? 'Yes' : 'No') - Ternary operator: Nếu is_used = true thì 'Yes', ngược lại 'No'
                     *     - ? : là ternary operator trong PHP (if-else shorthand)
                     *   - ($t->expires_at ? $t->expires_at->format('Y-m-d H:i:s') : 'Never') - Ternary operator
                     *     - Nếu expires_at != null: format date thành string YYYY-MM-DD HH:MM:SS
                     *     - Nếu expires_at = null: hiển thị 'Never' (không bao giờ hết hạn)
                     *   - Ví dụ: "  - Token ID: 5, Used: No, Expires: 2024-01-20 10:30:00"
                     */
                    foreach ($tokens as $t) {
                        $this->line("  - Token ID: {$t->id}, Used: " . ($t->is_used ? 'Yes' : 'No') . ", Expires: " . ($t->expires_at ? $t->expires_at->format('Y-m-d H:i:s') : 'Never'));
                    }
                } else {
                    /**
                     * Hiển thị lỗi invoice không tìm thấy
                     * 
                     * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
                     *   - "Invoice not found"
                     *   - Thông báo cho user biết invoice không tồn tại trong database
                     */
                    $this->error("Invoice not found");
                }
            }
            
            // Trả về 1 (Command::FAILURE) để báo command thất bại
            // Command sẽ dừng ở đây, không thực thi code phía dưới
            return 1;
        }
        
        /**
         * Bước 5: Hiển thị thông tin token đã tìm thấy
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Token found!" - Thông báo đã tìm thấy token
         *   - "  Token ID: {$paymentToken->id}" - Hiển thị ID của token
         *     - {$paymentToken->id} là string interpolation, sẽ thay thế bằng giá trị id
         *   - "  Invoice ID: {$paymentToken->invoice_id}" - Hiển thị ID của invoice liên quan
         *   - "  Is Used: " . ($paymentToken->is_used ? 'Yes' : 'No') - Hiển thị trạng thái đã sử dụng
         *     - Ternary operator: Nếu is_used = true thì 'Yes', ngược lại 'No'
         *   - "  Expires At: " . ($paymentToken->expires_at ? $paymentToken->expires_at->format('Y-m-d H:i:s') : 'Never')
         *     - Nếu expires_at != null: format date thành string YYYY-MM-DD HH:MM:SS
         *     - Nếu expires_at = null: hiển thị 'Never'
         *   - "  Created At: " . $paymentToken->created_at->format('Y-m-d H:i:s')
         *     - $paymentToken->created_at là Carbon date instance (đã được cast trong model)
         *     - ->format('Y-m-d H:i:s') chuyển date thành string format YYYY-MM-DD HH:MM:SS
         */
        $this->info("Token found!");
        $this->info("  Token ID: {$paymentToken->id}");
        $this->info("  Invoice ID: {$paymentToken->invoice_id}");
        $this->info("  Is Used: " . ($paymentToken->is_used ? 'Yes' : 'No'));
        $this->info("  Expires At: " . ($paymentToken->expires_at ? $paymentToken->expires_at->format('Y-m-d H:i:s') : 'Never'));
        $this->info("  Created At: " . $paymentToken->created_at->format('Y-m-d H:i:s'));
        
        /**
         * Bước 6: Kiểm tra tính hợp lệ của token
         * 
         * $paymentToken->isValid() - Gọi method để kiểm tra tính hợp lệ của token
         *   - isValid() là method trong PaymentToken model
         *   - Method này kiểm tra:
         *     - Token chưa được sử dụng (is_used = false)
         *     - Token chưa hết hạn (expires_at > now() hoặc expires_at = null)
         *   - Trả về boolean: true nếu hợp lệ, false nếu không hợp lệ
         * 
         * if ($paymentToken->isValid()) - Kiểm tra kết quả
         *   - Nếu true (hợp lệ), vào block if
         *   - Nếu false (không hợp lệ), vào block else
         */
        if ($paymentToken->isValid()) {
            /**
             * Hiển thị thông báo token hợp lệ
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             *   - "  Status: VALID ✓"
             *   - ✓ là ký tự checkmark (Unicode)
             *   - Thông báo cho user biết token hợp lệ (có thể sử dụng)
             */
            $this->info("  Status: VALID ✓");
        } else {
            /**
             * Hiển thị thông báo token không hợp lệ
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             *   - "  Status: INVALID ✗"
             *   - ✗ là ký tự cross mark (Unicode)
             *   - Thông báo cho user biết token không hợp lệ (không thể sử dụng)
             */
            $this->error("  Status: INVALID ✗");
            
            /**
             * Lấy thông báo lỗi chi tiết từ method getValidationError()
             * 
             * $paymentToken->getValidationError() - Gọi method để lấy thông báo lỗi
             *   - getValidationError() là method trong PaymentToken model
             *   - Method này trả về string mô tả lý do token không hợp lệ
             *   - Ví dụ: "Token has already been used", "Token has expired", etc.
             *   - Trả về string (thông báo lỗi) hoặc null (nếu không có lỗi cụ thể)
             * 
             * $error - Biến lưu thông báo lỗi (hoặc null)
             */
            $error = $paymentToken->getValidationError();
            
            /**
             * Hiển thị thông báo lỗi chi tiết nếu có
             * 
             * if ($error) - Kiểm tra xem có thông báo lỗi không
             *   - Nếu $error != null và != empty, vào block if
             * 
             * $this->error() - Hiển thị message màu đỏ trong console
             *   - "  Error: {$error}"
             *   - {$error} là string interpolation, sẽ thay thế bằng giá trị $error
             *   - Ví dụ: "  Error: Token has already been used"
             *   - Hiển thị lý do cụ thể tại sao token không hợp lệ
             */
            if ($error) {
                $this->error("  Error: {$error}");
            }
        }
        
        /**
         * Bước 7: Kiểm tra invoice liên quan
         * 
         * $paymentToken->invoice - Truy cập relationship invoice() từ PaymentToken model
         *   - invoice() là method trong PaymentToken model, trả về BelongsTo relationship
         *   - Relationship này query từ bảng invoices với điều kiện id = $paymentToken->invoice_id
         *   - Trả về Invoice model instance (hoặc null nếu không tồn tại)
         *   - Invoice đã được eager load hoặc lazy load khi truy cập
         * 
         * if ($paymentToken->invoice) - Kiểm tra xem invoice có tồn tại không
         *   - Nếu invoice != null, vào block if
         *   - Nếu invoice = null, vào block else
         */
        if ($paymentToken->invoice) {
            /**
             * Lưu invoice vào biến để sử dụng
             * 
             * $invoice = $paymentToken->invoice - Lưu Invoice model instance vào biến
             *   - $invoice là instance của App\Models\Invoice
             *   - Sẽ được sử dụng để hiển thị thông tin và so sánh invoice_id
             */
            $invoice = $paymentToken->invoice;
            
            /**
             * Hiển thị thông tin invoice
             * 
             * $this->info() - Hiển thị message màu xanh trong console
             *   - "  Invoice: {$invoice->invoice_no}" - Hiển thị số hóa đơn
             *     - {$invoice->invoice_no} là string interpolation
             *     - Ví dụ: "  Invoice: INV-2024-001"
             *   - "  Invoice Status: {$invoice->status}" - Hiển thị trạng thái invoice
             *     - {$invoice->status} là string interpolation
             *     - Ví dụ: "  Invoice Status: pending"
             */
            $this->info("  Invoice: {$invoice->invoice_no}");
            $this->info("  Invoice Status: {$invoice->status}");
            
            /**
             * So sánh invoice_id nếu được cung cấp
             * 
             * if ($invoiceId && (int)$paymentToken->invoice_id !== (int)$invoiceId) - Kiểm tra điều kiện
             *   - $invoiceId - Kiểm tra xem có invoice_id được cung cấp không (không null, không empty)
             *   - && - Logical AND operator (cả hai điều kiện phải true)
             *   - (int)$paymentToken->invoice_id !== (int)$invoiceId - So sánh invoice_id
             *     - (int) là type casting, chuyển giá trị thành integer
             *     - !== là strict not equal operator (so sánh cả giá trị và kiểu dữ liệu)
             *     - Chuyển cả hai về integer để so sánh chính xác (tránh so sánh string với integer)
             *     - Nếu invoice_id của token khác với invoice_id được cung cấp, vào block if
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (cảnh báo)
             *   - "  WARNING: Token invoice_id ({$paymentToken->invoice_id}) does not match requested invoice_id ({$invoiceId})"
             *   - {$paymentToken->invoice_id} và {$invoiceId} là string interpolation
             *   - Ví dụ: "  WARNING: Token invoice_id (123) does not match requested invoice_id (456)"
             *   - Cảnh báo user rằng token không đúng với invoice được yêu cầu
             *   - Có thể là: token bị nhầm, hoặc invoice_id được cung cấp sai
             */
            if ($invoiceId && (int)$paymentToken->invoice_id !== (int)$invoiceId) {
                $this->error("  WARNING: Token invoice_id ({$paymentToken->invoice_id}) does not match requested invoice_id ({$invoiceId})");
            }
        } else {
            /**
             * Hiển thị lỗi invoice không tìm thấy
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             *   - "  Invoice not found!"
             *   - Thông báo cho user biết invoice liên quan đến token không tồn tại
             *   - Có thể do: invoice đã bị xóa, hoặc invoice_id trong token không hợp lệ
             */
            $this->error("  Invoice not found!");
        }
        
        // Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành thành công
        // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
        return 0;
    }
}

