<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service: SepayWebhookService
 * 
 * MỤC ĐÍCH:
 * Service xử lý webhook từ SePay payment gateway - nhận thông báo thanh toán từ SePay,
 * xác thực webhook, tìm hóa đơn tương ứng, và tạo payment record tự động
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. validateWebhook(): Xác thực webhook từ SePay → Kiểm tra API key trong Authorization header
 * 2. parseWebhookData(): Parse dữ liệu webhook → Chuyển đổi format từ SePay sang format nội bộ
 * 3. checkDuplicate(): Kiểm tra webhook trùng lặp → Tránh xử lý lại giao dịch đã xử lý
 * 4. findInvoiceByContent(): Tìm hóa đơn dựa trên nội dung chuyển khoản → Extract mã hóa đơn từ content
 * 5. createPaymentRecord(): Tạo payment record → Lưu thông tin thanh toán vào database
 * 6. findOrCreatePaymentRecord(): Tìm hoặc tạo payment record → Tránh duplicate payment
 * 7. processWebhook(): Xử lý webhook chính → Validate, tìm invoice, tạo payment, update webhook log
 * 8. retryWebhook(): Thử xử lý lại webhook thất bại → Dùng cho manual retry
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Invoice (bảng invoices) - Tìm hóa đơn từ mã trong nội dung chuyển khoản
 * - Model: Payment (bảng payments) - Kiểm tra payment đã tồn tại chưa
 * - Model: PaymentMethod (bảng payment_methods) - Lấy payment method cho SePay
 * - Model: WebhookLog (bảng webhook_logs) - Lưu log webhook để tracking
 * - Config: services.sepay.api_key - API key để xác thực webhook
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng payments: Tạo payment record khi nhận được thanh toán
 * - Bảng webhook_logs: Lưu log webhook (status: pending, processed, failed, duplicate)
 * - Logs: Ghi log quá trình xử lý webhook
 * 
 * LƯU Ý:
 * - Chỉ xử lý giao dịch tiền vào (transfer_type = 'in')
 * - Tìm hóa đơn bằng cách extract mã hóa đơn (format HD-YYYYMM-XXXX) từ nội dung chuyển khoản
 * - Kiểm tra trùng lặp dựa trên sepay_transaction_id để tránh xử lý lại
 * - Tự động tạo payment record với status 'success' khi nhận được webhook
 * - Hỗ trợ retry webhook thất bại để xử lý lại
 */
class SepayWebhookService
{
    /**
     * Xác thực webhook từ SePay
     * 
     * MỤC ĐÍCH:
     * Xác thực webhook từ SePay bằng cách kiểm tra API key trong Authorization header
     * - Format: "Apikey YOUR_API_KEY"
     * - So sánh với API key trong config
     * 
     * INPUT:
     * - request: HTTP request từ SePay webhook
     * - Config: services.sepay.api_key
     * 
     * OUTPUT:
     * - bool: true nếu xác thực thành công, false nếu thất bại
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy Authorization header từ request → Kiểm tra có header không
     * 2. Extract API key từ format "Apikey YOUR_API_KEY" → Parse header
     * 3. Lấy API key từ config → So sánh với API key được cung cấp
     * 4. So sánh API key → Xác thực webhook
     * 5. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config services.sepay.api_key: API key để xác thực
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning/error nếu xác thực thất bại
     * 
     * LƯU Ý:
     * - Format Authorization header: "Apikey YOUR_API_KEY"
     * - API key phải được cấu hình trong config/services.php
     */
    public function validateWebhook($request)
    {
        $authHeader = $request->header('Authorization'); // Lấy Authorization header → Kiểm tra có header không
        
        if (!$authHeader) { // Nếu không có Authorization header
            Log::warning('SePay webhook: Missing Authorization header'); // Ghi log warning → Để tracking
            return false; // Trả về false → Xác thực thất bại
        }

        if (!preg_match('/^Apikey\s+(.+)$/i', $authHeader, $matches)) { // Extract API key từ format "Apikey YOUR_API_KEY" → Parse header
            Log::warning('SePay webhook: Invalid Authorization header format'); // Ghi log warning → Để tracking
            return false; // Trả về false → Format không đúng
        }

        $providedApiKey = trim($matches[1]); // Lấy API key được cung cấp → Dùng để so sánh
        $expectedApiKey = config('services.sepay.api_key'); // Lấy API key từ config → Dùng để so sánh

        if (empty($expectedApiKey)) { // Nếu không có API key trong config
            Log::error('SePay webhook: SEPAY_API_KEY not configured'); // Ghi log error → Để debug
            return false; // Trả về false → Chưa cấu hình
        }

        if ($providedApiKey !== $expectedApiKey) { // So sánh API key → Xác thực webhook
            Log::warning('SePay webhook: Invalid API key provided'); // Ghi log warning → Để tracking
            return false; // Trả về false → API key không đúng
        }

        return true; // Trả về true → Xác thực thành công
    }

    /**
     * Parse dữ liệu webhook từ SePay
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi dữ liệu webhook từ format SePay sang format nội bộ - normalize dữ liệu
     * để dễ xử lý trong hệ thống
     * 
     * INPUT:
     * - data: Mảng dữ liệu webhook từ SePay
     * 
     * OUTPUT:
     * - array: Mảng dữ liệu đã được parse với các trường: sepay_transaction_id, gateway,
     *   transaction_date, account_number, content, transfer_type, amount, reference_code, etc.
     * 
     * LUỒNG XỬ LÝ:
     * 1. Extract các trường từ data → Chuyển đổi format
     * 2. Parse transaction_date sang Carbon → Dễ xử lý date
     * 3. Set default values → Đảm bảo có giá trị mặc định
     * 4. Trả về mảng đã parse
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ xử lý data được truyền vào)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ transform data)
     */
    public function parseWebhookData($data)
    {
        return [
            'sepay_transaction_id' => $data['id'] ?? null, // ID giao dịch từ SePay → Dùng để check duplicate
            'gateway' => $data['gateway'] ?? null, // Gateway thanh toán → Dùng để tracking
            'transaction_date' => isset($data['transactionDate']) ? Carbon::parse($data['transactionDate']) : null, // Ngày giao dịch → Parse sang Carbon
            'account_number' => $data['accountNumber'] ?? null, // Số tài khoản → Dùng để tracking
            'content' => $data['content'] ?? null, // Nội dung chuyển khoản → Dùng để tìm mã hóa đơn
            'transfer_type' => $data['transferType'] ?? 'in', // Loại giao dịch (in/out) → Chỉ xử lý 'in'
            'amount' => $data['transferAmount'] ?? 0, // Số tiền → Dùng để tạo payment
            'reference_code' => $data['referenceCode'] ?? null, // Mã tham chiếu → Dùng để tracking
            'sub_account' => $data['subAccount'] ?? null, // Sub account → Dùng để tracking
            'description' => $data['description'] ?? null, // Mô tả → Dùng để tracking
        ];
    }

    /**
     * Kiểm tra webhook trùng lặp
     * 
     * MỤC ĐÍCH:
     * Kiểm tra webhook đã được xử lý chưa dựa trên sepay_transaction_id - tránh xử lý lại
     * giao dịch đã xử lý (SePay có thể gửi webhook nhiều lần)
     * 
     * INPUT:
     * - transactionId: ID giao dịch từ SePay (sepay_transaction_id)
     * 
     * OUTPUT:
     * - bool: true nếu đã tồn tại (duplicate), false nếu chưa có (chưa xử lý)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm webhook log với sepay_transaction_id → Kiểm tra đã xử lý chưa
     * 2. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng webhook_logs: Kiểm tra webhook đã tồn tại chưa
     * 
     * LƯU Ý:
     * - Dùng sepay_transaction_id để check duplicate (unique trong SePay)
     */
    public function checkDuplicate($transactionId)
    {
        return WebhookLog::where('sepay_transaction_id', $transactionId)->exists(); // Kiểm tra webhook đã tồn tại chưa → Tránh xử lý lại
    }

    /**
     * Tìm hóa đơn dựa trên nội dung chuyển khoản
     * 
     * MỤC ĐÍCH:
     * Tìm hóa đơn tương ứng với thanh toán bằng cách extract mã hóa đơn từ nội dung chuyển khoản
     * - Ưu tiên tìm theo pattern chính xác: HD-YYYYMM-XXXX
     * - Fallback: Tìm bất kỳ invoice_no nào có trong content
     * 
     * INPUT:
     * - content: Nội dung chuyển khoản từ SePay (có thể chứa mã hóa đơn)
     * 
     * OUTPUT:
     * - Invoice|null: Hóa đơn tìm thấy hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra content có rỗng không → Return null nếu rỗng
     * 2. Tìm theo pattern chính xác HD-YYYYMM-XXXX → Ưu tiên pattern chuẩn
     * 3. Nếu không tìm thấy: Tìm bất kỳ invoice_no nào trong content → Fallback
     * 4. Trả về Invoice hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng invoices: Tìm hóa đơn theo invoice_no
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log khi tìm thấy hoặc không tìm thấy invoice
     * 
     * LƯU Ý:
     * - Format mã hóa đơn: HD-YYYYMM-XXXX (ví dụ: HD-202510-0001)
     * - Chỉ tìm hóa đơn chưa bị xóa và chưa bị hủy
     */
    public function findInvoiceByContent($content)
    {
        if (empty($content)) {
            return null;
        }

        // Loại bỏ dấu gạch trong content để so sánh
        $cleanContent = str_replace('-', '', $content);
        Log::info("SePay webhook: Searching invoice for content: {$content} (clean: {$cleanContent})");

        // 1. Tìm Subscription Invoice (SUB{id} hoặc SUB{invoice_number})
        if (preg_match('/SUB(\d+)/i', $cleanContent, $matches)) {
            $idOrNo = $matches[1];
            $fullMatch = strtoupper($matches[0]);
            
            // Thử tìm theo ID trước (mới)
            $subscriptionInvoice = \App\Models\SubscriptionInvoice::find($idOrNo);
            
            // Nếu không tìm thấy theo ID, thử tìm theo invoice_number (cũ)
            if (!$subscriptionInvoice) {
                $subscriptionInvoice = \App\Models\SubscriptionInvoice::where('invoice_number', $fullMatch)->first();
            }
            
            if ($subscriptionInvoice && $subscriptionInvoice->status !== 'paid') {
                Log::info("SePay webhook: Found subscription invoice via ID/No: {$fullMatch}");
                return ['type' => 'subscription', 'invoice' => $subscriptionInvoice];
            }
        }

        // 2. Tìm Regular Invoice (HD{id} hoặc HD{invoice_no})
        if (preg_match('/HD(\d+)/i', $cleanContent, $matches)) {
            $idOrNo = $matches[1];
            $fullMatch = strtoupper($matches[0]);
            
            // Thử tìm theo ID trước (mới)
            $invoice = Invoice::where('id', $idOrNo)
                ->whereNull('deleted_at')
                ->first();
            
            // Nếu không tìm thấy theo ID, thử tìm theo invoice_no (cũ)
            if (!$invoice) {
                $invoice = Invoice::where('invoice_no', $fullMatch)
                    ->whereNull('deleted_at')
                    ->first();
            }
            
            if ($invoice && $invoice->status !== 'cancelled') {
                Log::info("SePay webhook: Found invoice via ID/No: {$fullMatch}");
                return ['type' => 'regular', 'invoice' => $invoice];
            }
        }

        // 3. Tìm Booking Deposit (BD{id} hoặc BD{reference_number})
        if (preg_match('/BD(\d+)/i', $cleanContent, $matches)) {
            $idOrNo = $matches[1];
            $fullMatch = strtoupper($matches[0]);
            
            // Thử tìm theo ID trước (mới)
            $bookingDeposit = \App\Models\BookingDeposit::find($idOrNo);
            
            // Nếu không tìm thấy theo ID, thử tìm theo reference_number (cũ)
            if (!$bookingDeposit) {
                $bookingDeposit = \App\Models\BookingDeposit::where('reference_number', $fullMatch)->first();
            }
            
            if ($bookingDeposit) {
                // Lấy invoice mới nhất liên quan đến booking deposit này
                $invoice = Invoice::where('booking_deposit_id', $bookingDeposit->id)
                    ->whereNull('deleted_at')
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if ($invoice) {
                    Log::info("SePay webhook: Found invoice {$invoice->invoice_no} via Booking Deposit ID/No: {$fullMatch}");
                    return ['type' => 'regular', 'invoice' => $invoice];
                }
            }
        }

        // Fallback: Tìm bằng substring (loại bỏ dấu gạch khi so sánh)
        $invoices = Invoice::whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($invoices as $invoice) {
            $cleanInvoiceNo = str_replace('-', '', $invoice->invoice_no);
            if (stripos($cleanContent, $cleanInvoiceNo) !== false) {
                Log::info("SePay webhook: Found invoice {$invoice->invoice_no} by substring match");
                return ['type' => 'regular', 'invoice' => $invoice];
            }
        }

        Log::warning("SePay webhook: No invoice found for content: {$content}");
        return null;
    }

    /**
     * Tạo payment record từ webhook data
     * 
     * MỤC ĐÍCH:
     * Tạo payment record mới từ webhook data - lấy payment method, xác định payer (user hoặc lead),
     * và lưu thông tin thanh toán vào database
     * 
     * INPUT:
     * - invoice: Hóa đơn cần tạo payment
     * - webhookData: Dữ liệu webhook đã được parse
     * 
     * OUTPUT:
     * - Payment: Payment record đã được tạo
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy payment method cho SePay (ưu tiên: sepay → sepay_bank → bank_qr) → Xác định phương thức thanh toán
     * 2. Lấy payer_user_id từ lease nếu có → Xác định người thanh toán (tenant)
     * 3. Lấy lead_id từ booking deposit nếu không có payer_user_id → Xác định người thanh toán (lead)
     * 4. Tạo payment record với status 'success' → Lưu thông tin thanh toán
     * 5. Trả về payment record
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng payment_methods: Lấy payment method cho SePay
     * - Invoice relationships: Lấy tenant_id từ lease, lead_id từ booking_deposit
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng payments: Tạo payment record mới
     * - Logs: Ghi log khi tạo payment thành công
     * 
     * LƯU Ý:
     * - Payment method fallback: sepay → sepay_bank → bank_qr
     * - Ưu tiên payer_user_id từ lease, nếu không có thì dùng lead_id từ booking_deposit
     * - Payment status luôn là 'success' vì đã nhận được webhook từ SePay
     */
    public function createPaymentRecord($invoice, $webhookData)
    {
        $paymentMethod = PaymentMethod::where('key_code', 'sepay')->first(); // Lấy payment method cho SePay → Xác định phương thức thanh toán
        
        if (!$paymentMethod) { // Nếu không có sepay
            $paymentMethod = PaymentMethod::where('key_code', 'sepay_bank')->first(); // Fallback to sepay_bank → Phương thức thanh toán thay thế
        }
        
        if (!$paymentMethod) { // Nếu không có sepay_bank
            $paymentMethod = PaymentMethod::where('key_code', 'bank_qr')->first(); // Fallback to bank_qr → Phương thức thanh toán thay thế
        }

        $payerUserId = null;
        if ($invoice->lease && $invoice->lease->tenant_id) { // Lấy payer_user_id từ lease → Xác định người thanh toán (tenant)
            $payerUserId = $invoice->lease->tenant_id;
        }
        
        $leadId = null;
        if (!$payerUserId && $invoice->booking_deposit_id) { // Lấy lead_id từ booking deposit nếu không có payer_user_id → Xác định người thanh toán (lead)
            $bookingDeposit = $invoice->bookingDeposit;
            if ($bookingDeposit && $bookingDeposit->lead_id) {
                $leadId = $bookingDeposit->lead_id;
            }
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id, // ID hóa đơn → Liên kết payment với invoice
            'method_id' => $paymentMethod ? $paymentMethod->id : null, // ID payment method → Xác định phương thức thanh toán
            'amount' => $webhookData['amount'], // Số tiền → Từ webhook data
            'paid_at' => $webhookData['transaction_date'] ?? now(), // Ngày thanh toán → Từ webhook hoặc now()
            'txn_ref' => $webhookData['reference_code'], // Mã tham chiếu → Dùng để tracking
            'status' => 'success', // Trạng thái → Luôn là success vì đã nhận được webhook
            'payer_user_id' => $payerUserId, // ID người thanh toán (user) → Dùng để tracking
            'lead_id' => $leadId, // ID lead → Dùng cho booking deposit
            'note' => sprintf(
                'Thanh toán tự động từ SePay - %s. Nội dung: %s',
                $webhookData['gateway'] ?? 'Bank Transfer',
                $webhookData['content'] ?? ''
            ), // Ghi chú → Mô tả thanh toán
            'created_at' => now(), // Thời gian tạo → Dùng để tracking
        ]); // Tạo payment record → Lưu thông tin thanh toán

        Log::info("SePay webhook: Created payment #{$payment->id} for invoice #{$invoice->id}"); // Ghi log info → Để tracking

        return $payment; // Trả về payment record → Dùng để update webhook log
    }

    /**
     * Xử lý webhook từ SePay
     * 
     * @param array $data
     * @return array ['success' => bool, 'message' => string, 'webhook_log' => WebhookLog]
     */
    public function processWebhook($data)
    {
        try {
            // Parse webhook data
            $parsedData = $this->parseWebhookData($data);

            // Kiểm tra dữ liệu bắt buộc
            if (!$parsedData['sepay_transaction_id']) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction ID',
                    'webhook_log' => null,
                ];
            }

            // Kiểm tra trùng lặp - nếu đã xử lý thì thông báo thành công
            if ($this->checkDuplicate($parsedData['sepay_transaction_id'])) {
                $existingLog = WebhookLog::where('sepay_transaction_id', $parsedData['sepay_transaction_id'])->first();
                
                if (!$existingLog->isDuplicate()) {
                    $existingLog->markAsDuplicate();
                }
                
                // Lấy thông tin invoice từ webhook log để hiển thị thông báo tốt hơn
                $invoiceInfo = '';
                if ($existingLog->invoice_id) {
                    $invoice = Invoice::find($existingLog->invoice_id);
                    if ($invoice) {
                        $invoiceInfo = " (Hóa đơn: {$invoice->invoice_no})";
                    }
                }
                
                // Tìm subscription invoice từ content nếu có
                if (!$invoiceInfo && $parsedData['content']) {
                    $subInvoiceResult = $this->findInvoiceByContent($parsedData['content']);
                    if ($subInvoiceResult && $subInvoiceResult['type'] === 'subscription') {
                        $subInvoice = $subInvoiceResult['invoice'];
                        $invoiceInfo = " (Hóa đơn: {$subInvoice->invoice_number})";
                    }
                }
                
                Log::info("SePay webhook: Duplicate transaction #{$parsedData['sepay_transaction_id']} - Invoice already paid successfully");
                
                return [
                    'success' => true,
                    'message' => "Hóa đơn đã thanh toán thành công{$invoiceInfo}. Giao dịch đã được xử lý trước đó.",
                    'webhook_log' => $existingLog,
                    'redirect_url' => route('staff.subscriptions.index'),
                ];
            }

            // Chỉ xử lý giao dịch tiền vào
            if ($parsedData['transfer_type'] !== 'in') {
                $webhookLog = WebhookLog::create(array_merge($parsedData, [
                    'status' => 'failed',
                    'error_message' => 'Chỉ xử lý giao dịch tiền vào (transfer_type = in)',
                    'raw_data' => $data,
                    'processed_at' => now(),
                ]));

                return [
                    'success' => false,
                    'message' => 'Only incoming transactions are processed',
                    'webhook_log' => $webhookLog,
                ];
            }

            // Tạo webhook log với status pending
            $webhookLog = WebhookLog::create(array_merge($parsedData, [
                'status' => 'pending',
                'raw_data' => $data,
            ]));

            // Bắt đầu transaction
            DB::beginTransaction();

            try {
                // Tìm hóa đơn (có thể là regular invoice hoặc subscription invoice)
                $invoiceResult = $this->findInvoiceByContent($parsedData['content']);

                if (!$invoiceResult) {
                    $webhookLog->markAsFailed('Không tìm thấy mã hóa đơn trong nội dung chuyển khoản');
                    DB::commit();
                    
                    return [
                        'success' => false,
                        'message' => 'Invoice not found in transaction content',
                        'webhook_log' => $webhookLog,
                    ];
                }

                // Xử lý subscription invoice
                if ($invoiceResult['type'] === 'subscription') {
                    $subscriptionInvoice = $invoiceResult['invoice'];
                    
                    // Kiểm tra trạng thái hóa đơn - nếu đã thanh toán thì thông báo thành công
                    if ($subscriptionInvoice->status === 'paid') {
                        $webhookLog->markAsProcessed(null, null);
                        DB::commit();
                        
                        Log::info("SePay webhook: Subscription invoice {$subscriptionInvoice->invoice_number} already paid successfully");
                        
                        return [
                            'success' => true,
                            'message' => "Hóa đơn subscription {$subscriptionInvoice->invoice_number} đã thanh toán thành công. Giao dịch đã được xử lý trước đó.",
                            'webhook_log' => $webhookLog,
                            'redirect_url' => route('staff.subscriptions.index'),
                        ];
                    }

                    // Đánh dấu hóa đơn đã thanh toán (Observer sẽ tự động kích hoạt subscription)
                    $subscriptionInvoice->markAsPaid(
                        $parsedData['gateway'] ?? 'sepay',
                        $parsedData['sepay_transaction_id']
                    );

                    // Cập nhật webhook log (không có invoice_id và payment_id cho subscription invoice)
                    $webhookLog->markAsProcessed(null, null);

                    DB::commit();

                    Log::info("SePay webhook: Successfully processed subscription invoice #{$subscriptionInvoice->invoice_number}");

                    return [
                        'success' => true,
                        'message' => "Hóa đơn subscription {$subscriptionInvoice->invoice_number} đã thanh toán thành công.",
                        'webhook_log' => $webhookLog,
                        'redirect_url' => route('staff.subscriptions.index'),
                    ];
                }

                // Xử lý regular invoice
                $invoice = $invoiceResult['invoice'];

                // Kiểm tra trạng thái hóa đơn
                if (!in_array($invoice->status, ['draft', 'issued', 'overdue'])) {
                    $webhookLog->markAsFailed("Hóa đơn {$invoice->invoice_no} có trạng thái không hợp lệ: {$invoice->status}");
                    DB::commit();
                    
                    return [
                        'success' => false,
                        'message' => 'Invoice status is not valid for payment',
                        'webhook_log' => $webhookLog,
                    ];
                }

                // Kiểm tra xem hóa đơn đã thanh toán đủ chưa - nếu đã thanh toán thì thông báo thành công
                if ($invoice->isFullyPaid()) {
                    $webhookLog->markAsProcessed($invoice->id, null);
                    DB::commit();
                    
                    Log::info("SePay webhook: Invoice {$invoice->invoice_no} already fully paid successfully");
                    
                    return [
                        'success' => true,
                        'message' => "Hóa đơn {$invoice->invoice_no} đã thanh toán thành công. Giao dịch đã được xử lý trước đó.",
                        'webhook_log' => $webhookLog,
                        'redirect_url' => route('staff.subscriptions.index'),
                    ];
                }

                // Tìm payment record đã tồn tại hoặc tạo mới
                $payment = $this->findOrCreatePaymentRecord($invoice, $parsedData);

                // Cập nhật webhook log
                $webhookLog->markAsProcessed($invoice->id, $payment->id);

                DB::commit();

                Log::info("SePay webhook: Successfully processed transaction #{$parsedData['sepay_transaction_id']} for invoice #{$invoice->invoice_no}");

                return [
                    'success' => true,
                    'message' => "Hóa đơn {$invoice->invoice_no} đã thanh toán thành công.",
                    'webhook_log' => $webhookLog,
                    'redirect_url' => route('staff.subscriptions.index'),
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                
                $errorMessage = 'Lỗi khi xử lý thanh toán: ' . $e->getMessage();
                $webhookLog->markAsFailed($errorMessage);
                
                Log::error("SePay webhook: Error processing transaction - {$e->getMessage()}", [
                    'transaction_id' => $parsedData['sepay_transaction_id'],
                    'trace' => $e->getTraceAsString()
                ]);

                return [
                    'success' => false,
                    'message' => 'Error processing payment',
                    'webhook_log' => $webhookLog,
                ];
            }

        } catch (\Exception $e) {
            Log::error("SePay webhook: Fatal error - {$e->getMessage()}", [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Fatal error processing webhook',
                'webhook_log' => null,
            ];
        }
    }

    /**
     * Tìm payment record đã tồn tại hoặc tạo mới
     * 
     * @param Invoice $invoice
     * @param array $webhookData
     * @return Payment
     */
    public function findOrCreatePaymentRecord($invoice, $webhookData)
    {
        // Tìm payment record đã tồn tại với status pending
        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_PENDING)
            ->where('method_id', function($query) {
                $query->select('id')
                    ->from('payment_methods')
                    ->where('key_code', 'sepay');
            })
            ->first();

        if ($existingPayment) {
            // Cập nhật payment record đã tồn tại
            $existingPayment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => $webhookData['transaction_date'] ?? now(),
                'txn_ref' => $webhookData['reference_code'],
                'note' => sprintf(
                    'Thanh toán tự động từ SePay - %s. Nội dung: %s',
                    $webhookData['gateway'] ?? 'Bank Transfer',
                    $webhookData['content']
                )
            ]);

            Log::info("Updated existing Sepay payment record", [
                'payment_id' => $existingPayment->id,
                'invoice_id' => $invoice->id,
                'amount' => $existingPayment->amount
            ]);

            return $existingPayment;
        } else {
            // Tạo payment record mới
            return $this->createPaymentRecord($invoice, $webhookData);
        }
    }

    /**
     * Thử xử lý lại webhook thất bại
     * 
     * @param WebhookLog $webhookLog
     * @return array
     */
    public function retryWebhook(WebhookLog $webhookLog)
    {
        if (!$webhookLog->isFailed()) {
            return [
                'success' => false,
                'message' => 'Only failed webhooks can be retried',
            ];
        }

        // Reset status to pending
        $webhookLog->update([
            'status' => 'pending',
            'error_message' => null,
            'processed_at' => null,
        ]);

        // Process again
        return $this->processWebhook($webhookLog->raw_data);
    }
}

