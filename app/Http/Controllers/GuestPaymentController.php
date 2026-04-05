<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentToken;
use App\Models\PaymentMethod;
use App\Models\BookingDeposit;
use App\Models\Lead;
use App\Models\User;
use App\Models\OrganizationBanking;
use App\Services\WebhooksPermissionService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GuestPaymentController extends Controller
{
    protected $webhooksPermissionService;
    protected $imageService;

    public function __construct(WebhooksPermissionService $webhooksPermissionService, ImageService $imageService)
    {
        $this->webhooksPermissionService = $webhooksPermissionService;
        $this->imageService = $imageService;
    }

    /**
     * Hiển thị trang thanh toán công khai cho lead
     */
    public function show(Request $request, $invoiceId)
    {
        $token = $request->query('token');
        
        if (!$token) {
            Log::warning('Guest payment: Missing token', [
                'invoice_id' => $invoiceId,
                'url' => $request->fullUrl()
            ]);
            return view('guest.payment.error', [
                'message' => 'Token không hợp lệ. Vui lòng kiểm tra lại link thanh toán.'
            ]);
        }
        
        // Normalize token (trim whitespace)
        $token = trim($token);
        
        if (empty($token)) {
            Log::warning('Guest payment: Empty token after trim', [
                'invoice_id' => $invoiceId,
                'url' => $request->fullUrl()
            ]);
            return view('guest.payment.error', [
                'message' => 'Token không hợp lệ. Vui lòng kiểm tra lại link thanh toán.'
            ]);
        }

        // Get invoice first to check if it exists
        $invoice = Invoice::with(['bookingDeposit.unit.property', 'bookingDeposit.lead'])
            ->findOrFail($invoiceId);

        // Find payment token
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken) {
            Log::warning('Guest payment: Token not found', [
                'token' => substr($token, 0, 20) . '...',
                'invoice_id' => $invoiceId,
                'url' => $request->fullUrl()
            ]);
            return view('guest.payment.error', [
                'message' => 'Token không hợp lệ. Vui lòng kiểm tra lại link thanh toán hoặc liên hệ với chúng tôi để được hỗ trợ.'
            ]);
        }
        
        // Check if token is valid
        if (!$paymentToken->isValid()) {
            $errorMessage = $paymentToken->getValidationError() ?? 'Token không hợp lệ hoặc đã hết hạn';
            
            // If token is expired but not used, try to generate a new one for the same invoice
            if ($paymentToken->invoice_id == $invoice->id && !$paymentToken->is_used && $paymentToken->expires_at && $paymentToken->expires_at->isPast()) {
                Log::info('Guest payment: Token expired, attempting to generate new token', [
                    'old_token_id' => $paymentToken->id,
                    'invoice_id' => $invoice->id
                ]);
                
                // Generate new token for this invoice
                try {
                    $newToken = $invoice->generatePaymentToken();
                    Log::info('Guest payment: New token generated', [
                        'new_token_id' => $newToken->id,
                        'invoice_id' => $invoice->id
                    ]);
                    
                    // Redirect to new token URL
                    return redirect()->route('guest.payment.show', [
                        'invoice' => $invoice->id,
                        'token' => $newToken->token
                    ]);
                } catch (\Exception $e) {
                    Log::error('Guest payment: Failed to generate new token', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::warning('Guest payment: Token invalid', [
                'token_id' => $paymentToken->id,
                'invoice_id' => $paymentToken->invoice_id,
                'is_used' => $paymentToken->is_used,
                'expires_at' => $paymentToken->expires_at?->toDateTimeString(),
                'expires_at_timestamp' => $paymentToken->expires_at?->timestamp,
                'now_timestamp' => now()->timestamp,
                'requested_invoice_id' => $invoiceId,
                'error' => $errorMessage
            ]);
            return view('guest.payment.error', [
                'message' => $errorMessage . '. Vui lòng liên hệ với chúng tôi để được hỗ trợ.'
            ]);
        }

        // Verify token belongs to this invoice (use == for type comparison)
        if ((int)$paymentToken->invoice_id !== (int)$invoice->id) {
            Log::warning('Guest payment: Token invoice mismatch', [
                'token_id' => $paymentToken->id,
                'token_invoice_id' => $paymentToken->invoice_id,
                'requested_invoice_id' => $invoiceId,
                'token_type' => gettype($paymentToken->invoice_id),
                'invoice_type' => gettype($invoice->id)
            ]);
            return view('guest.payment.error', [
                'message' => 'Token không hợp lệ cho hóa đơn này. Vui lòng kiểm tra lại link thanh toán.'
            ]);
        }

        // Kiểm tra invoice đã thanh toán chưa
        if ($invoice->status === 'paid') {
            return view('guest.payment.already-paid', compact('invoice'));
        }

        // Kiểm tra invoice có phải từ booking deposit không
        if (!$invoice->booking_deposit_id) {
            abort(404, 'Hóa đơn không hợp lệ');
        }

        $bookingDeposit = $invoice->bookingDeposit;
        // Load lead without global scopes để tránh filter theo organization
        $lead = $bookingDeposit->lead_id ? \App\Models\Lead::withoutGlobalScopes()->find($bookingDeposit->lead_id) : null;

        // Nếu không có lead, kiểm tra tenant_user
        if (!$lead && !$bookingDeposit->tenant_user_id) {
            abort(404, 'Không tìm thấy thông tin khách hàng');
        }

        // Get tenant info (lead hoặc user)
        $tenantInfo = $bookingDeposit->getTenantInfo();

        // Lấy thông tin ngân hàng tổ chức
        $organizationBank = $this->getBankingAccount($invoice);
        $bankConfig = $organizationBank ? $this->getBankConfigForView($organizationBank) : null;

        // Check if organization can use sepay
        $canUseSepay = false;
        if ($invoice->organization_id) {
            $canUseSepay = $this->webhooksPermissionService->canUseSepay($invoice->organization_id);
            
            // Debug logging
            Log::info('GuestPaymentController: Checking Sepay permission', [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'can_use_sepay' => $canUseSepay,
            ]);
        }

        // Sepay config: Nếu tổ chức có quyền dùng sepay, dùng ngân hàng SaaS từ config
        // Nếu không, dùng ngân hàng tổ chức (giống bank_qr)
        if ($canUseSepay) {
            // Dùng ngân hàng SaaS từ config (đã đăng ký webhook)
            $sepayConfig = [
                'bank_name' => config('services.sepay.bank_name', 'TPBank'),
                'account_number' => config('services.sepay.account_number', '46166378666'),
                'account_name' => config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
            ];
        } else {
            // Không có quyền sepay, dùng ngân hàng tổ chức (giống bank_qr)
            $sepayConfig = $bankConfig;
        }

        // Kiểm tra xem có payment nào đã được tạo chưa
        $existingPayment = Payment::with('method')
            ->where('invoice_id', $invoice->id)
            ->whereIn('status', ['pending', 'success'])
            ->orderBy('created_at', 'desc')
            ->first();

        // Nếu payment đã thành công, reload invoice để kiểm tra status
        if ($existingPayment && $existingPayment->status === 'success') {
            $invoice->refresh();
            if ($invoice->status === 'paid') {
                return view('guest.payment.already-paid', compact('invoice', 'existingPayment'));
            }
        }

        // Nếu payment đang pending, redirect đến already-paid với trạng thái chờ giao dịch
        if ($existingPayment && $existingPayment->status === 'pending') {
            return view('guest.payment.already-paid', compact('invoice', 'existingPayment'));
        }

        return view('guest.payment.show', compact('invoice', 'bookingDeposit', 'tenantInfo', 'token', 'bankConfig', 'sepayConfig', 'organizationBank', 'existingPayment', 'canUseSepay'));
    }

    /**
     * Xử lý thanh toán tiền mặt cho lead (tạo payment pending)
     */
    public function processCashPayment(Request $request, $invoiceId)
    {
        $token = $request->token;
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 403);
        }

        // Verify token
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken || !$paymentToken->isValid() || $paymentToken->invoice_id != $invoiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 403);
        }

        $invoice = Invoice::findOrFail($invoiceId);

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã được thanh toán.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Tìm payment method cho tiền mặt
            $cashMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'cash'],
                [
                    'name' => 'Tiền mặt',
                    'is_active' => true,
                    'description' => 'Thanh toán bằng tiền mặt'
                ]
            );

            // Lấy lead_id từ booking deposit nếu có
            $leadId = null;
            if ($invoice->booking_deposit_id) {
                $bookingDeposit = $invoice->bookingDeposit;
                if ($bookingDeposit && $bookingDeposit->lead_id) {
                    $leadId = $bookingDeposit->lead_id;
                }
            }

            // Validate: ít nhất một trong hai payer_user_id hoặc lead_id phải có giá trị
            if (empty($leadId)) {
                throw new \Exception('Không tìm thấy lead_id từ booking deposit. Không thể tạo payment cho guest.');
            }

            // Tạo payment record với status pending
            $paymentData = [
                'invoice_id' => $invoice->id,
                'method_id' => $cashMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => 'pending',
                'lead_id' => $leadId, // Set lead_id nếu có
                'note' => 'Thanh toán tiền mặt từ guest - chờ agent xác thực',
                'txn_ref' => 'CASH_GUEST_' . time() . '_' . $invoice->id
            ];
            // Chỉ set payer_user_id nếu có giá trị (guest payment không có user ID)
            
            $payment = Payment::create($paymentData);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                $file = null;
                try {
                    $file = $request->file('image');
                    
                    // Validate file
                    if (!$file->isValid()) {
                        throw new \Exception('File upload không hợp lệ');
                    }
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    // Đảm bảo payment đã có ID
                    if (!$payment->id) {
                        throw new \Exception('Payment chưa có ID');
                    }

                    // Tạo document với đầy đủ thông tin
                    $documentData = [
                        'owner_type' => \App\Models\Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'is_primary' => false,
                        'created_at' => now(),
                    ];
                    // Chỉ set uploaded_by nếu có giá trị (guest payment không có user ID)
                    // Không set uploaded_by để tránh lỗi constraint nếu column không nullable
                    
                    $document = \App\Models\Document::create($documentData);

                    // Đảm bảo document đã có ID
                    if (!$document->id) {
                        throw new \Exception('Document chưa có ID sau khi tạo');
                    }

                    Log::info('Guest payment image document created successfully', [
                        'payment_id' => $payment->id,
                        'document_id' => $document->id,
                        'file_path' => $uploadedFile['original'],
                        'file_size' => $uploadedFile['size']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading guest payment image: ' . $e->getMessage(), [
                        'payment_id' => $payment->id ?? null,
                        'invoice_id' => $invoice->id ?? null,
                        'file_name' => isset($uploadedFile) ? ($uploadedFile['original_name'] ?? null) : ($file ? $file->getClientOriginalName() : null),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if image upload fails
                    // Nhưng log chi tiết để debug
                }
            }

            DB::commit();

            Log::info("Guest cash payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo yêu cầu thanh toán tiền mặt. Vui lòng chờ agent xác thực.',
                'payment_id' => $payment->id,
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating guest cash payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý thanh toán chuyển khoản trực tuyến (bank_qr) - dùng ngân hàng tổ chức
     */
    public function processBankQrPayment(Request $request, $invoiceId)
    {
        $token = $request->token;
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 403);
        }

        // Verify token
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken || !$paymentToken->isValid() || $paymentToken->invoice_id != $invoiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 403);
        }

        $invoice = Invoice::findOrFail($invoiceId);

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã được thanh toán.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Tìm payment method cho bank_qr
            $bankTransferMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'bank_qr'],
                [
                    'name' => 'Chuyển khoản trực tuyến',
                    'is_active' => true
                ]
            );

            // Lấy lead_id từ booking deposit nếu có
            $leadId = null;
            if ($invoice->booking_deposit_id) {
                $bookingDeposit = $invoice->bookingDeposit;
                if ($bookingDeposit && $bookingDeposit->lead_id) {
                    $leadId = $bookingDeposit->lead_id;
                }
            }

            // Validate: ít nhất một trong hai payer_user_id hoặc lead_id phải có giá trị
            if (empty($leadId)) {
                throw new \Exception('Không tìm thấy lead_id từ booking deposit. Không thể tạo payment cho guest.');
            }

            // Tạo payment record với status pending
            $paymentData = [
                'invoice_id' => $invoice->id,
                'method_id' => $bankTransferMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => 'pending',
                'lead_id' => $leadId, // Set lead_id nếu có
                'note' => 'Chuyển khoản trực tuyến vào ngân hàng tổ chức - chờ xác nhận',
                'txn_ref' => 'BANK_TRANSFER_GUEST_' . time() . '_' . $invoice->id
            ];
            // Chỉ set payer_user_id nếu có giá trị (guest payment không có user ID)
            
            $payment = Payment::create($paymentData);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                $file = null;
                try {
                    $file = $request->file('image');
                    
                    // Validate file
                    if (!$file->isValid()) {
                        throw new \Exception('File upload không hợp lệ');
                    }
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    // Đảm bảo payment đã có ID
                    if (!$payment->id) {
                        throw new \Exception('Payment chưa có ID');
                    }

                    // Tạo document với đầy đủ thông tin
                    $documentData = [
                        'owner_type' => \App\Models\Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'is_primary' => false,
                        'created_at' => now(),
                    ];
                    // Chỉ set uploaded_by nếu có giá trị (guest payment không có user ID)
                    // Không set uploaded_by để tránh lỗi constraint nếu column không nullable
                    
                    $document = \App\Models\Document::create($documentData);

                    // Đảm bảo document đã có ID
                    if (!$document->id) {
                        throw new \Exception('Document chưa có ID sau khi tạo');
                    }

                    Log::info('Guest payment image document created successfully', [
                        'payment_id' => $payment->id,
                        'document_id' => $document->id,
                        'file_path' => $uploadedFile['original'],
                        'file_size' => $uploadedFile['size']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading guest payment image: ' . $e->getMessage(), [
                        'payment_id' => $payment->id ?? null,
                        'invoice_id' => $invoice->id ?? null,
                        'file_name' => isset($uploadedFile) ? ($uploadedFile['original_name'] ?? null) : ($file ? $file->getClientOriginalName() : null),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if image upload fails
                    // Nhưng log chi tiết để debug
                }
            }

            // Lấy thông tin ngân hàng từ database
            $bankingAccount = $this->getBankingAccount($invoice);
            
            // Lấy tên ngân hàng SePay chuẩn
            $bankName = null;
            if (is_object($bankingAccount) && isset($bankingAccount->bank_name)) {
                $bankName = $bankingAccount->bank_name;
            } elseif (is_object($bankingAccount) && isset($bankingAccount->sepayBank)) {
                // Fallback: lấy từ sepayBank relationship
                $bankName = $bankingAccount->sepayBank->sepay_name 
                    ?? $bankingAccount->sepayBank->short_name 
                    ?? $bankingAccount->sepayBank->name 
                    ?? null;
            }
            
            // Nếu vẫn không có, fallback về config
            if (!$bankName) {
                $bankName = config('services.sepay.bank_name', 'TPBank');
            }
            
            // Tạo thông tin chuyển khoản
            $bankInfo = [
                'bank_name' => $bankName,
                'account_number' => $bankingAccount->account_number ?? config('services.sepay.account_number', '46166378666'),
                'account_name' => $bankingAccount->account_name ?? config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
                'amount' => $invoice->total_amount,
                'content' => str_replace('-', '', $invoice->invoice_no), // Chỉ mã hóa đơn không có dấu gạch
                'payment_id' => $payment->id
            ];

            // Tạo URL QR code SePay
            $qrUrl = $this->generateSepayQRUrl($bankInfo);
            
            Log::info("Generated QR URL for guest Sepay payment", [
                'payment_id' => $payment->id,
                'qr_url' => $qrUrl,
                'bank_info' => $bankInfo
            ]);

            DB::commit();

            Log::info("Guest bank transfer payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount,
                'bank' => $bankName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo mã QR thanh toán. Vui lòng chuyển khoản vào tài khoản ngân hàng của tổ chức.',
                'payment_id' => $payment->id,
                'bank_info' => $bankInfo,
                'qr_url' => $qrUrl,
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating guest bank transfer payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý thanh toán qua SePay (dùng ngân hàng SaaS, tự động cập nhật)
     */
    public function processSepayPayment(Request $request, $invoiceId)
    {
        $token = $request->token;
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 403);
        }

        // Verify token
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken || !$paymentToken->isValid() || $paymentToken->invoice_id != $invoiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 403);
        }

        $invoice = Invoice::findOrFail($invoiceId);

        // Check subscription permission for webhooks/sepay
        if ($invoice->organization_id) {
            if (!$this->webhooksPermissionService->canUseSepay($invoice->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gói dịch vụ của tổ chức không hỗ trợ phương thức thanh toán SePay. Vui lòng nâng cấp gói để sử dụng tính năng Webhooks.'
                ], 403);
            }
        }

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã được thanh toán.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Tìm payment method cho sepay (tự động)
            $sepayMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'sepay'],
                [
                    'name' => 'Chuyển khoản qua SePay',
                    'is_active' => true
                ]
            );

            // Lấy lead_id từ booking deposit nếu có
            $leadId = null;
            if ($invoice->booking_deposit_id) {
                $bookingDeposit = $invoice->bookingDeposit;
                if ($bookingDeposit && $bookingDeposit->lead_id) {
                    $leadId = $bookingDeposit->lead_id;
                }
            }

            // Validate: ít nhất một trong hai payer_user_id hoặc lead_id phải có giá trị
            if (empty($leadId)) {
                throw new \Exception('Không tìm thấy lead_id từ booking deposit. Không thể tạo payment cho guest.');
            }

            // Tạo payment record với status pending
            $paymentData = [
                'invoice_id' => $invoice->id,
                'method_id' => $sepayMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => 'pending',
                'lead_id' => $leadId,
                'note' => 'Chuyển khoản qua SePay - tự động cập nhật khi nhận được webhook',
                'txn_ref' => 'SEPAY_AUTO_GUEST_' . time() . '_' . $invoice->id
            ];
            
            $payment = Payment::create($paymentData);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                $file = null;
                try {
                    $file = $request->file('image');
                    
                    // Validate file
                    if (!$file->isValid()) {
                        throw new \Exception('File upload không hợp lệ');
                    }
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    // Đảm bảo payment đã có ID
                    if (!$payment->id) {
                        throw new \Exception('Payment chưa có ID');
                    }

                    // Tạo document với đầy đủ thông tin
                    $documentData = [
                        'owner_type' => \App\Models\Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'is_primary' => false,
                        'created_at' => now(),
                    ];

                    $document = \App\Models\Document::create($documentData);

                    Log::info('Document created for guest sepay payment', [
                        'document_id' => $document->id,
                        'payment_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'file_size' => $uploadedFile['size']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading guest sepay payment image: ' . $e->getMessage(), [
                        'payment_id' => $payment->id ?? null,
                        'invoice_id' => $invoice->id ?? null,
                        'file_name' => isset($uploadedFile) ? ($uploadedFile['original_name'] ?? null) : ($file ? $file->getClientOriginalName() : null),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if image upload fails
                }
            }

            // Lấy thông tin ngân hàng từ .env (ngân hàng SaaS đã đăng ký webhook)
            $bankName = config('services.sepay.bank_name', 'TPBank');
            $accountNumber = config('services.sepay.account_number', '46166378666');
            $accountName = config('services.sepay.account_name', 'Le Xuan Thanh Quan');
            
            // Tạo thông tin chuyển khoản
            $bankInfo = [
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'amount' => $invoice->total_amount,
                'content' => str_replace('-', '', $invoice->invoice_no), // Loại bỏ dấu gạch
                'payment_id' => $payment->id
            ];

            // Tạo URL QR code SePay
            $qrUrl = $this->generateSepayQRUrl($bankInfo);
            
            Log::info("Generated QR URL for guest SePay auto payment", [
                'payment_id' => $payment->id,
                'qr_url' => $qrUrl,
                'bank_info' => $bankInfo
            ]);

            DB::commit();

            Log::info("Guest SePay auto payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo mã QR thanh toán. Chuyển khoản sẽ được cập nhật tự động.',
                'payment_id' => $payment->id,
                'bank_info' => $bankInfo,
                'qr_url' => $qrUrl,
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating guest SePay auto payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus(Request $request, $invoiceId, $paymentId)
    {
        $token = $request->query('token');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ'
            ], 403);
        }

        // Verify token
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken || !$paymentToken->isValid() || $paymentToken->invoice_id != $invoiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 403);
        }

        $invoice = Invoice::findOrFail($invoiceId);

        $payment = Payment::where('invoice_id', $invoiceId)
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin thanh toán'
            ], 404);
        }

        // Reload invoice để có status mới nhất
        $invoice->refresh();

        return response()->json([
            'success' => true,
            'payment_status' => $payment->status,
            'invoice_status' => $invoice->status,
            'message' => $this->getPaymentStatusMessage($payment->status)
        ]);
    }

    /**
     * Lấy thông tin tài khoản ngân hàng cho invoice
     * Ưu tiên: mặc định > gần nhất > null (không fallback về .env)
     */
    private function getBankingAccount(Invoice $invoice)
    {
        // Lấy organization_id từ invoice
        $organizationId = $invoice->organization_id;
        
        if (!$organizationId) {
            // Fallback: lấy từ lease hoặc booking deposit
            if ($invoice->lease_id) {
                $organizationId = $invoice->lease->organization_id ?? null;
            } elseif ($invoice->booking_deposit_id) {
                $organizationId = $invoice->bookingDeposit->organization_id ?? null;
            }
        }
        
        if (!$organizationId) {
            return null;
        }
        
        // 1. Ưu tiên: Lấy tài khoản mặc định (is_default = true, is_active = true)
        $bankingAccount = OrganizationBanking::with('sepayBank')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
        
        // 2. Nếu không có mặc định, lấy tài khoản active gần nhất
        if (!$bankingAccount) {
            $bankingAccount = OrganizationBanking::with('sepayBank')
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        // 3. Nếu vẫn không có, trả về null (KHÔNG fallback về .env)
        if (!$bankingAccount) {
            return null;
        }
        
        // Đảm bảo sepayBank được load
        if (!$bankingAccount->relationLoaded('sepayBank')) {
            $bankingAccount->load('sepayBank');
        }
        
        return $bankingAccount;
    }

    /**
     * Generate SePay QR URL
     */
    private function generateSepayQRUrl($bankInfo)
    {
        // Validate required fields
        if (empty($bankInfo['account_number']) || empty($bankInfo['bank_name'])) {
            Log::error("Missing required fields for QR code generation", [
                'bank_info' => $bankInfo
            ]);
            throw new \Exception('Thiếu thông tin ngân hàng để tạo mã QR');
        }
        
        // Tạo URL QR code SePay
        $params = [
            'acc' => $bankInfo['account_number'],
            'bank' => $bankInfo['bank_name'],
        ];
        
        // Thêm amount nếu có
        if (!empty($bankInfo['amount'])) {
            $params['amount'] = $bankInfo['amount'];
        }
        
        // Thêm nội dung nếu có
        if (!empty($bankInfo['content'])) {
            $params['des'] = $bankInfo['content'];
        }
        
        $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query($params);
        
        Log::info("Generated SePay QR URL", [
            'params' => $params,
            'qr_url' => $qrUrl
        ]);
        
        return $qrUrl;
    }

    /**
     * Get bank config formatted for view từ banking account
     */
    private function getBankConfigForView($bankingAccount)
    {
        if (!$bankingAccount) {
            return null;
        }
        
        // Lấy tên ngân hàng SePay chuẩn
        $bankName = null;
        
        // Ưu tiên lấy từ sepayBank relationship
        if (isset($bankingAccount->sepayBank)) {
            $bankName = $bankingAccount->sepayBank->sepay_name 
                ?? $bankingAccount->sepayBank->short_name 
                ?? $bankingAccount->sepayBank->name 
                ?? null;
        }
        
        // Fallback: lấy từ trường bank_name
        if (!$bankName && isset($bankingAccount->bank_name)) {
            $bankName = $bankingAccount->bank_name;
        }
        
        return [
            'bank_name' => $bankName ?? 'N/A',
            'account_number' => $bankingAccount->account_number ?? 'N/A',
            'account_name' => $bankingAccount->account_name ?? 'N/A',
            'branch' => $bankingAccount->branch ?? 'N/A',
        ];
    }

    /**
     * Get payment status message
     */
    private function getPaymentStatusMessage($status)
    {
        return match($status) {
            'pending' => 'Đang chờ xác nhận thanh toán',
            'completed' => 'Thanh toán thành công',
            'failed' => 'Thanh toán thất bại',
            'refunded' => 'Đã hoàn tiền',
            default => 'Trạng thái không xác định'
        };
    }
}

