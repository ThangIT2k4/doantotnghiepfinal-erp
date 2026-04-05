<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\OrganizationBanking;
use App\Services\SepayWebhookService;
use App\Services\WebhooksPermissionService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TenantPaymentController extends Controller
{
    protected $sepayService;
    protected $webhooksPermissionService;
    protected $imageService;

    public function __construct(
        SepayWebhookService $sepayService,
        WebhooksPermissionService $webhooksPermissionService,
        ImageService $imageService
    ) {
        $this->sepayService = $sepayService;
        $this->webhooksPermissionService = $webhooksPermissionService;
        $this->imageService = $imageService;
    }

    /**
     * Hiển thị danh sách thanh toán của tenant
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Base query - sử dụng whereHas để tránh duplicate
        // whereHas không gây duplicate vì nó chỉ filter, không join
        $query = Payment::with([
            'invoice.lease.unit.property', 
            'method'
        ])
            ->whereHas('invoice.lease', function($query) use ($user) {
                $query->where('tenant_id', $user->id);
            })
            ->whereNull('deleted_at');
        
        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('txn_ref', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                      $invoiceQuery->where('invoice_no', 'like', "%{$search}%");
                  });
            });
        }
        
        // Paginate - whereHas không gây duplicate nên không cần distinct
        $payments = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();
        
        // Đảm bảo không có duplicate trong collection (phòng trường hợp có duplicate trong DB)
        $uniquePayments = $payments->getCollection()->unique('id')->values();
        $payments->setCollection($uniquePayments);

        // Thống kê thanh toán - sử dụng whereHas để đảm bảo chính xác
        $baseQuery = Payment::whereHas('invoice.lease', function($query) use ($user) {
            $query->where('tenant_id', $user->id);
        })
        ->whereNull('deleted_at');
        
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'success' => (clone $baseQuery)->where('status', Payment::STATUS_SUCCESS)->count(),
            'pending' => (clone $baseQuery)->where('status', Payment::STATUS_PENDING)->count(),
            'failed' => (clone $baseQuery)->where('status', Payment::STATUS_FAILED)->count(),
        ];

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            // Prepare stats cards HTML for hx-swap-oob
            $paymentStats = [
                [
                    'icon' => 'fas fa-list',
                    'value' => $stats['total'],
                    'label' => 'Tổng giao dịch',
                    'active' => $request->get('status', 'all') == 'all',
                    'data-filter' => 'all',
                    'statusClass' => 'total',
                    'hx-get' => route('tenant.payments.index', ['status' => 'all', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem tất cả giao dịch'
                ],
                [
                    'icon' => 'fas fa-check-circle',
                    'value' => $stats['success'],
                    'label' => 'Thành công',
                    'active' => $request->get('status') == 'success',
                    'data-filter' => 'success',
                    'statusClass' => 'success',
                    'hx-get' => route('tenant.payments.index', ['status' => 'success', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc giao dịch thành công'
                ],
                [
                    'icon' => 'fas fa-clock',
                    'value' => $stats['pending'],
                    'label' => 'Đang chờ',
                    'active' => $request->get('status') == 'pending',
                    'data-filter' => 'pending',
                    'statusClass' => 'pending',
                    'hx-get' => route('tenant.payments.index', ['status' => 'pending', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc giao dịch đang chờ'
                ],
                [
                    'icon' => 'fas fa-times-circle',
                    'value' => $stats['failed'],
                    'label' => 'Thất bại',
                    'active' => $request->get('status') == 'failed',
                    'data-filter' => 'failed',
                    'statusClass' => 'failed',
                    'hx-get' => route('tenant.payments.index', ['status' => 'failed', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc giao dịch thất bại'
                ]
            ];
            
            // Prepare filter tabs HTML
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => $request->get('status', 'all') == 'all',
                    'hx-get' => route('tenant.payments.index', ['status' => 'all', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Thành công',
                    'value' => 'success',
                    'active' => $request->get('status') == 'success',
                    'hx-get' => route('tenant.payments.index', ['status' => 'success', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Đang chờ',
                    'value' => 'pending',
                    'active' => $request->get('status') == 'pending',
                    'hx-get' => route('tenant.payments.index', ['status' => 'pending', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-clock'
                ],
                [
                    'label' => 'Thất bại',
                    'value' => 'failed',
                    'active' => $request->get('status') == 'failed',
                    'hx-get' => route('tenant.payments.index', ['status' => 'failed', 'search' => $request->get('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-times-circle'
                ]
            ];
            
            $paymentsListHtml = view('tenant.payments.partials.payments-list', compact('payments'))->render();
            $statsCardsHtml = view('tenant.components.stats-cards', [
                'stats' => $paymentStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])->render();
            
            $filterSectionHtml = view('tenant.components.filter-section', [
                'searchPlaceholder' => 'Tìm kiếm theo mã giao dịch, hóa đơn...',
                'searchValue' => $request->get('search'),
                'filters' => $filterTabs,
                'formId' => 'filterForm',
                'searchInputId' => 'searchInput',
                'hxGet' => route('tenant.payments.index'),
                'hxTarget' => '#payments-list-container',
                'hxSwap' => 'innerHTML',
                'hxPushUrl' => 'true',
                'hxIndicator' => '#htmx-loading',
                'hxTrigger' => 'input delay:500ms from:#searchInput',
            ])->render();
            
            // Return payments list with stats cards and filter section update via hx-swap-oob
            // Ensure we only return the inner content, not the container itself
            $html = $paymentsListHtml 
                . "\n<div id='stats-cards-container' hx-swap-oob='true'>" . $statsCardsHtml . "</div>"
                . "\n<div id='filter-section-container' hx-swap-oob='true'>" . $filterSectionHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl())
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        return view('tenant.payments.index', compact('payments', 'stats'));
    }

    /**
     * Hiển thị form chọn phương thức thanh toán
     */
    public function showPaymentMethods($invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::with(['lease.unit.property', 'items'])
            ->where('id', $invoiceId)
            ->whereHas('lease', function($q) use ($user) {
                $q->where('tenant_id', $user->id);
            })
            ->firstOrFail();

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return redirect()->route('tenant.invoices.show', $invoice->id)
                ->with('info', 'Hóa đơn này đã được thanh toán.');
        }

        if (!in_array($invoice->status, ['issued', 'overdue'])) {
            return redirect()->route('tenant.invoices.index')
                ->with('error', 'Hóa đơn này không thể thanh toán.');
        }

        // Lấy thông tin ngân hàng tổ chức
        $organizationBank = $this->getBankingAccount($invoice);
        $orgBankConfig = $organizationBank ? [
            'bank_name' => $organizationBank->bank_name ?? '',
            'account_number' => $organizationBank->account_number ?? '',
            'account_name' => $organizationBank->account_name ?? '',
        ] : null;

        // Check if organization can use sepay
        $canUseSepay = false;
        if ($invoice->organization_id) {
            $canUseSepay = $this->webhooksPermissionService->canUseSepay($invoice->organization_id);
            
            // Debug logging
            Log::info('TenantPaymentController: Checking Sepay permission', [
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'can_use_sepay' => $canUseSepay,
            ]);
        }

        // Sepay config: Nếu tổ chức có quyền dùng sepay, dùng ngân hàng SaaS từ config
        // Nếu không, dùng ngân hàng tổ chức (giống bank_qr)
        if ($canUseSepay) {
            // Dùng ngân hàng SaaS từ config (đã đăng ký webhook)
            $sepayBankConfig = [
                'bank_name' => config('services.sepay.bank_name', 'TPBank'),
                'account_number' => config('services.sepay.account_number', '46166378666'),
                'account_name' => config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
            ];
        } else {
            // Không có quyền sepay, dùng ngân hàng tổ chức (giống bank_qr)
            $sepayBankConfig = $orgBankConfig;
        }

        return view('tenant.payments.methods', compact('invoice', 'orgBankConfig', 'sepayBankConfig', 'canUseSepay'));
    }

    /**
     * Xử lý thanh toán tiền mặt
     * Tạo payment record với status pending để agent xác thực
     */
    public function processCashPayment(Request $request, $invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->whereHas('lease', function($q) use ($user) {
                $q->where('tenant_id', $user->id);
            })
            ->firstOrFail();

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã được thanh toán.'
            ], 400);
        }

        if (!in_array($invoice->status, ['issued', 'overdue'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này không thể thanh toán.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Tìm payment method cho tiền mặt
            $cashMethod = PaymentMethod::where('name', 'Tiền mặt')->first();
            if (!$cashMethod) {
                $cashMethod = PaymentMethod::create([
                    'name' => 'Tiền mặt',
                    'key_code' => 'cash',
                    'is_active' => true,
                    'description' => 'Thanh toán bằng tiền mặt'
                ]);
            }

            // Tạo payment record với status pending
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'method_id' => $cashMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => Payment::STATUS_PENDING,
                'payer_user_id' => $user->id,
                'note' => 'Thanh toán tiền mặt - chờ agent xác thực',
                'txn_ref' => 'CASH_' . time() . '_' . $invoice->id
            ]);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);

                    // Attach document to payment using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading payment image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            DB::commit();

            Log::info("Cash payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
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
            Log::error("Error creating cash payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý thanh toán Sepay
     * Tạo QR code với thông tin ngân hàng và tạo payment record
     */
    /**
     * Xử lý thanh toán chuyển khoản trực tuyến (bank_qr) - dùng ngân hàng tổ chức
     */
    public function processBankQrPayment(Request $request, $invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->whereHas('lease', function($q) use ($user) {
                $q->where('tenant_id', $user->id);
            })
            ->firstOrFail();

        // Kiểm tra trạng thái hóa đơn
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này đã được thanh toán.'
            ], 400);
        }

        if (!in_array($invoice->status, ['issued', 'overdue'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này không thể thanh toán.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Tìm payment method cho bank_qr
            $bankQrMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'bank_qr'],
                [
                    'name' => 'Chuyển khoản trực tuyến',
                    'is_active' => true
                ]
            );

            // Tạo payment record với status pending
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'method_id' => $bankQrMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => Payment::STATUS_PENDING,
                'payer_user_id' => $user->id,
                'note' => 'Chuyển khoản trực tuyến vào ngân hàng tổ chức - chờ xác nhận thủ công',
                'txn_ref' => 'BANK_QR_' . time() . '_' . $invoice->id
            ]);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);

                    // Attach document to payment using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading payment image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

        // Lấy thông tin ngân hàng từ database
        $bankingAccount = $this->getBankingAccount($invoice);
        
        // Lấy tên ngân hàng SePay chuẩn
        $bankName = $bankingAccount->bank_name; // Uses accessor from sepayBank
        
        // Tạo thông tin chuyển khoản
        $bankInfo = [
            'bank_name' => $bankName,
            'account_number' => $bankingAccount->account_number,
            'account_name' => $bankingAccount->account_name,
            'amount' => $invoice->total_amount,
            'content' => str_replace('-', '', $invoice->invoice_no), // Chỉ mã hóa đơn không có dấu gạch
            'payment_id' => $payment->id
        ];

            // Tạo URL QR code SePay
            $qrUrl = $this->generateSepayQRUrl($bankInfo);
            
            Log::info("Generated QR URL for bank QR payment", [
                'payment_id' => $payment->id,
                'qr_url' => $qrUrl,
                'bank_info' => $bankInfo
            ]);

            DB::commit();

            Log::info("Bank QR payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $payment->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo yêu cầu chuyển khoản. Vui lòng chuyển vào ngân hàng tổ chức.',
                'payment_id' => $payment->id,
                'status' => 'pending',
                'bank_info' => $bankInfo,
                'qr_url' => $qrUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating bank QR payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý thanh toán qua SePay (tự động cập nhật) - dùng ngân hàng SaaS
     */
    public function processSepayPayment(Request $request, $invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->whereHas('lease', function($q) use ($user) {
                $q->where('tenant_id', $user->id);
            })
            ->firstOrFail();

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

        if (!in_array($invoice->status, ['issued', 'overdue'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn này không thể thanh toán.'
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

            // Tạo payment record với status pending
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'method_id' => $sepayMethod->id,
                'amount' => $invoice->total_amount,
                'paid_at' => now(),
                'status' => Payment::STATUS_PENDING,
                'payer_user_id' => $user->id,
                'note' => 'Chuyển khoản qua SePay - tự động cập nhật khi nhận được webhook',
                'txn_ref' => 'SEPAY_AUTO_' . time() . '_' . $invoice->id
            ]);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'payments', 'payment-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading payment image: ' . $e->getMessage());
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
            
            Log::info("Generated QR URL for SePay auto payment", [
                'payment_id' => $payment->id,
                'qr_url' => $qrUrl,
                'bank_info' => $bankInfo
            ]);

            DB::commit();

            Log::info("SePay auto payment created for invoice #{$invoice->invoice_no}", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $payment->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo mã QR thanh toán. Chuyển khoản sẽ được cập nhật tự động.',
                'payment_id' => $payment->id,
                'status' => 'pending',
                'bank_info' => $bankInfo,
                'qr_url' => $qrUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating SePay auto payment: " . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'user_id' => $user->id
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
    public function checkPaymentStatus($paymentId)
    {
        $user = Auth::user();
        
        // Sử dụng cùng logic như index() - kiểm tra qua invoice.lease.tenant_id
        $payment = Payment::with(['invoice.lease', 'method'])
            ->where('id', $paymentId)
            ->whereHas('invoice.lease', function($query) use ($user) {
                $query->where('tenant_id', $user->id);
            })
            ->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin thanh toán này hoặc bạn không có quyền truy cập.',
                'error' => 'not_found'
            ], 404);
        }

        $response = [
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
                'paid_at' => $payment->paid_at,
                'invoice_status' => $payment->invoice->status,
                'invoice' => [
                    'id' => $payment->invoice->id,
                    'invoice_no' => $payment->invoice->invoice_no ?? 'HD' . str_pad($payment->invoice->id, 6, '0', STR_PAD_LEFT)
                ]
            ]
        ];

        // Include method info if available
        if ($payment->method) {
            $response['payment']['method'] = [
                'id' => $payment->method->id,
                'name' => $payment->method->name,
                'key_code' => $payment->method->key_code
            ];

            // If it's a Sepay payment and pending, generate QR code info
            if ($payment->method->key_code === 'sepay' && $payment->status === 'pending') {
                $bankingAccount = $this->getBankingAccount($payment->invoice);
                $bankName = $bankingAccount->bank_name;
                $accountNumber = $bankingAccount->account_number;
                $content = str_replace('-', '', $response['payment']['invoice']['invoice_no']); // Chỉ mã hóa đơn không có dấu gạch
                
                $bankInfo = [
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'account_name' => $bankingAccount->account_name ?? '',
                    'amount' => $payment->amount,
                    'content' => $content
                ];
                
                $qrUrl = $this->generateSepayQRUrl($bankInfo);
                
                $response['payment']['qr_info'] = [
                    'qr_url' => $qrUrl,
                    'bank_info' => $bankInfo
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Tạo URL QR code SePay cho chuyển khoản
     */
    private function generateSepayQRUrl($bankInfo)
    {
        // Tạo URL QR code SePay
        $params = [
            'acc' => $bankInfo['account_number'],
            'bank' => $bankInfo['bank_name'],
            'amount' => $bankInfo['amount'],
            'des' => $bankInfo['content']
        ];
        
        $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query($params);
        
        Log::info("Generated SePay QR URL", [
            'params' => $params,
            'qr_url' => $qrUrl
        ]);
        
        return $qrUrl;
    }

    /**
     * Lấy thông tin cấu hình ngân hàng (để điền vào placeholder)
     */
    public function getBankConfig()
    {
        $user = Auth::user();
        
        // Lấy organization_id từ user
        $organizationId = null;
        if ($user) {
            // Lấy organization từ invoice nếu có
            $invoice = Invoice::whereHas('lease', function($q) use ($user) {
                $q->where('tenant_id', $user->id);
            })->first();
            
            if ($invoice) {
                $organizationId = $invoice->organization_id;
            }
        }
        
        // Lấy thông tin ngân hàng từ database với sepayBank relationship
        $bankingAccount = null;
        if ($organizationId) {
            $bankingAccount = OrganizationBanking::with('sepayBank')
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
        }
        
        // Fallback về config nếu không có trong database
        if (!$bankingAccount) {
            $bankConfig = [
                'bank_name' => config('services.sepay.bank_name', 'TPBank'),
                'account_number' => config('services.sepay.account_number', '46166378666'),
                'account_name' => config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
                'branch' => config('services.sepay.branch', 'Chi nhánh Hà Nội')
            ];
        } else {
            $bankConfig = $bankingAccount->getBankConfigArray();
            $bankConfig['branch'] = $bankingAccount->branch;
        }

        return response()->json([
            'success' => true,
            'bank_config' => $bankConfig
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
        return $bankingAccount;
    }

    /**
     * Test QR URL generation
     */
    public function testQRUrl()
    {
        $testBankInfo = [
            'bank_name' => 'TPBank',
            'account_number' => '46166378666',
            'account_name' => 'Le Xuan Thanh Quan',
            'amount' => 100000,
            'content' => 'TEST QR CODE'
        ];

        $qrUrl = $this->generateSepayQRUrl($testBankInfo);

        return response()->json([
            'success' => true,
            'qr_url' => $qrUrl,
            'test_url' => 'https://qr.sepay.vn/img?acc=46166378666&bank=TPBank&amount=100000&des=TEST%20QR%20CODE'
        ]);
    }
}
