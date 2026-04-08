<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CashOutflow;
use App\Models\Vendor;
use App\Models\User;
use App\Models\CompanyInvoice;
use App\Models\PaymentMethod;
use App\Traits\ChecksCapabilities;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controller: CashOutflowController
 * 
 * MỤC ĐÍCH:
 * Controller quản lý cash outflows (dòng tiền ra) trong hệ thống.
 * Controller này xử lý việc tạo, xem, cập nhật, xóa, và quản lý trạng thái cash outflows.
 * Cash outflows được sử dụng để ghi nhận các khoản chi tiêu của organization (thanh toán cho vendors, etc.).
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách cash outflows
 *    - Tính statistics (total, pending, success, failed, reversed, amounts)
 *    - Filter theo payment method, status, vendor, date range
 *    - Support HTMX/AJAX requests
 * 2. create(): Hiển thị form tạo cash outflow mới
 * 3. store(): Tạo cash outflow mới
 *    - Auto-generate transaction_ref
 *    - Upload documents/images (sử dụng ImageService)
 *    - Link với CompanyInvoice (nếu có)
 * 4. show(): Hiển thị chi tiết cash outflow
 * 5. edit(): Hiển thị form chỉnh sửa (chỉ khi status != 'success')
 * 6. update(): Cập nhật cash outflow
 *    - Upload/remove documents/images
 *    - Không cho phép chỉnh sửa nếu status = 'success'
 * 7. destroy(): Xóa cash outflow (soft delete)
 * 8. markAsSuccess(): Đánh dấu thành công
 *    - Tự động update CompanyInvoice status to 'paid'
 * 9. markAsFailed(): Đánh dấu thất bại
 * 10. updateStatus(): Cập nhật trạng thái
 * 11. reverse(): Hoàn trả cash outflow
 * 12. bulkAction(): Thao tác hàng loạt (mark-success, mark-failed, reverse)
 * 13. getCompanyInvoiceInfo(): Lấy thông tin CompanyInvoice qua API
 * 14. statistics(): Lấy statistics cho cash outflows
 * 
 * ENDPOINTS:
 * - GET /staff/cash-outflows: Danh sách cash outflows
 * - GET /staff/cash-outflows/create: Form tạo mới
 * - POST /staff/cash-outflows: Tạo cash outflow
 * - GET /staff/cash-outflows/{id}: Chi tiết cash outflow
 * - GET /staff/cash-outflows/{id}/edit: Form chỉnh sửa
 * - PUT/PATCH /staff/cash-outflows/{id}: Cập nhật cash outflow
 * - DELETE /staff/cash-outflows/{id}: Xóa cash outflow
 * - POST /staff/cash-outflows/{id}/mark-success: Đánh dấu thành công
 * - POST /staff/cash-outflows/{id}/mark-failed: Đánh dấu thất bại
 * - POST /staff/cash-outflows/{id}/update-status: Cập nhật trạng thái
 * - POST /staff/cash-outflows/{id}/reverse: Hoàn trả
 * - POST /staff/cash-outflows/bulk-action: Thao tác hàng loạt
 * - GET /staff/cash-outflows/company-invoice/{id}: Lấy thông tin CompanyInvoice
 * - GET /staff/cash-outflows/statistics: Lấy statistics
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: CashOutflow (bảng cash_outflows)
 * - Model: CompanyInvoice (bảng company_invoices)
 * - Model: Vendor (bảng vendors)
 * - Model: PaymentMethod (bảng payment_methods)
 * - Model: Document (bảng documents) - Lưu documents/images
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng cash_outflows: Tạo, cập nhật, xóa cash outflows
 * - Bảng company_invoices: Tự động update status to 'paid' khi cash outflow thành công
 * - Bảng documents: Lưu documents/images liên quan
 * - File storage: Lưu uploaded documents/images
 * - Logs: Ghi log các thao tác quan trọng
 * 
 * SERVICES SỬ DỤNG:
 * - ImageService: Upload và quản lý documents/images
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (finance.access, finance.cash_outflow.*)
 * 
 * CAPABILITY CHECKING:
 * - finance.access: Cần có để truy cập module Finance
 * - finance.cash_outflow.view: Xem cash outflows
 * - finance.cash_outflow.create: Tạo cash outflow (chỉ manager)
 * - finance.cash_outflow.update: Cập nhật cash outflow (chỉ manager)
 * - finance.cash_outflow.delete: Xóa cash outflow (chỉ manager)
 * 
 * STATUS FLOW:
 * - pending -> success (khi mark as success)
 * - pending -> failed (khi mark as failed)
 * - success -> reversed (khi reverse)
 * - failed -> reversed (khi reverse)
 * 
 * TRANSACTION REF GENERATION:
 * - Auto-generate transaction_ref sử dụng SequenceGenerator
 * - Format: CO-{payment_method_code}-{sequence}
 * - Unique và thread-safe
 * 
 * DOCUMENT/IMAGE UPLOAD:
 * - Sử dụng ImageService để upload documents/images
 * - Documents được lưu vào bảng documents với owner_type = CashOutflow
 * - Support multiple file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF
 * - Max file size: 20MB cho documents, 2MB cho images
 * 
 * COMPANY INVOICE INTEGRATION:
 * - Cash outflow có thể link với CompanyInvoice
 * - Khi cash outflow thành công, tự động update CompanyInvoice status to 'paid'
 * - Update paid_at, paid_by trong CompanyInvoice
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng byOrganization() scope để filter theo organization
 * - Eager loading relationships (companyInvoice, paymentMethod, documents)
 * - Tính statistics từ base query trước khi apply filters
 * - Sử dụng clone query để tính nhiều statistics
 * 
 * HTMX/AJAX SUPPORT:
 * - Support HTMX requests (HX-Request header)
 * - Support legacy AJAX requests
 * - Return partial HTML với hx-swap-oob cho statistics cards
 * 
 * LƯU Ý:
 * - Chỉ manager có thể tạo, cập nhật, xóa cash outflows
 * - Agent chỉ có thể xem cash outflows
 * - Không thể chỉnh sửa cash outflow đã thành công (status = 'success')
 * - Không thể xóa cash outflow đã thành công
 * - transaction_ref được auto-generate và không thể chỉnh sửa
 * - Documents/images được lưu vào bảng documents, không lưu trực tiếp vào cash_outflows
 * - Khi cash outflow thành công, CompanyInvoice tự động được đánh dấu đã thanh toán
 */
class CashOutflowController extends Controller
{
    /**
     * ChecksCapabilities trait instance
     * 
     * Trait này cung cấp các methods:
     * - requireCapability(): Kiểm tra và abort nếu không có capability
     * - checkCapability(): Kiểm tra capability (trả về boolean)
     * - getCurrentOrganizationId(): Lấy organization ID từ session
     */
    use ChecksCapabilities;
    
    /**
     * ImageService instance (được inject qua constructor)
     * 
     * Service này xử lý upload và quản lý documents/images.
     * 
     * @var ImageService
     */
    protected $imageService;
    
    /**
     * Constructor: Inject ImageService
     * 
     * Dependency Injection:
     * - Laravel tự động resolve ImageService từ service container
     * - Service này nằm tại: app/Services/ImageService.php
     * 
     * @param ImageService $imageService Service xử lý upload documents/images
     */
    public function __construct(ImageService $imageService)
    {
        /**
         * Lưu service instance vào property
         * 
         * $this->imageService = $imageService - Gán service instance
         *   - $this->imageService là property của class
         *   - $imageService là tham số được inject từ Laravel container
         *   - Service sẽ được sử dụng để upload documents/images trong các methods
         */
        $this->imageService = $imageService;
    }
    
    /**
     * Hiển thị danh sách cash outflows
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Kiểm tra capabilities (finance.access, finance.cash_outflow.view)
     * 2. Build query với filters (payment method, status, vendor, date range)
     * 3. Tính statistics từ base query (trước khi apply filters)
     * 4. Apply search và filters
     * 5. Sort và paginate results
     * 6. Support HTMX/AJAX requests
     * 
     * STATISTICS:
     * - total: Tổng số cash outflows
     * - pending: Số cash outflows đang chờ
     * - success: Số cash outflows thành công
     * - failed: Số cash outflows thất bại
     * - reversed: Số cash outflows đã hoàn trả
     * - total_amount: Tổng số tiền
     * - success_amount: Tổng số tiền thành công
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng byOrganization() scope để filter theo organization
     * - Eager loading relationships để tránh N+1 queries
     * - Tính statistics từ base query trước khi apply filters
     * 
     * @param Request $request HTTP request (có thể chứa search, filters, HTMX headers)
     * @return \Illuminate\View\View|\Illuminate\Http\Response View hoặc HTMX/AJAX response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability - manager can manage all, agent can only view
        $this->requireCapability('finance.cash_outflow.view', 'Bạn không có quyền xem Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            $query = CashOutflow::byOrganization($organizationId)
                ->with(['companyInvoice.vendor', 'companyInvoice.creator', 'companyInvoice.user', 'paymentMethod']);

            // Calculate statistics FIRST from base query (before any filters)
            $statsQuery = CashOutflow::byOrganization($organizationId);
            
            $stats = [
                'total' => (int) (clone $statsQuery)->count(),
                'pending' => (int) (clone $statsQuery)->where('status', CashOutflow::STATUS_PENDING)->count(),
                'success' => (int) (clone $statsQuery)->where('status', CashOutflow::STATUS_SUCCESS)->count(),
                'failed' => (int) (clone $statsQuery)->where('status', CashOutflow::STATUS_FAILED)->count(),
                'reversed' => (int) (clone $statsQuery)->where('status', CashOutflow::STATUS_REVERSED)->count(),
                'total_amount' => (float) (clone $statsQuery)->sum('amount'),
                'success_amount' => (float) (clone $statsQuery)->where('status', CashOutflow::STATUS_SUCCESS)->sum('amount'),
            ];

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('transaction_ref', 'like', "%{$search}%")
                      ->orWhere('note', 'like', "%{$search}%")
                      ->orWhereHas('companyInvoice.creator', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('companyInvoice.vendor', function($vendorQuery) use ($search) {
                          $vendorQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by payment method
            if ($request->filled('payment_method_id')) {
                $query->where('payment_method_id', $request->payment_method_id);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by vendor
            if ($request->filled('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Filter by date range
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Handle sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Map sort fields to actual database columns
            $sortFields = [
                'created_at' => 'cash_outflows.created_at',
                'paid_at' => 'cash_outflows.paid_at',
                'amount' => 'cash_outflows.amount',
                'status' => 'cash_outflows.status',
            ];
            
            $sortField = $sortFields[$sortBy] ?? 'cash_outflows.created_at';
            $query->orderBy($sortField, $sortOrder);

            $cashOutflows = $query->paginate(20);

            // Get filter options
            $vendors = Vendor::byOrganization($organizationId)->orderBy('name')->get();
            $statuses = [
                CashOutflow::STATUS_PENDING => 'Đang chờ',
                CashOutflow::STATUS_SUCCESS => 'Thành công',
                CashOutflow::STATUS_FAILED => 'Thất bại',
                CashOutflow::STATUS_REVERSED => 'Đã hoàn trả'
            ];
            $paymentMethods = PaymentMethod::orderBy('name')->get();

            // Format stats for statistics-cards component
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'pending' => [
                    'value' => $stats['pending'] ?? 0,
                    'label' => 'Đang chờ',
                    'icon' => 'fa-clock',
                    'color' => 'warning',
                    'filter' => 'pending',
                ],
                'success' => [
                    'value' => $stats['success'] ?? 0,
                    'label' => 'Thành công',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => 'success',
                ],
                'failed' => [
                    'value' => $stats['failed'] ?? 0,
                    'label' => 'Thất bại',
                    'icon' => 'fa-times-circle',
                    'color' => 'danger',
                    'filter' => 'failed',
                ],
                'reversed' => [
                    'value' => $stats['reversed'] ?? 0,
                    'label' => 'Đã hoàn trả',
                    'icon' => 'fa-undo',
                    'color' => 'info',
                    'filter' => 'reversed',
                ],
            ];

            // Check if user has manage capability (only manager)
            $canManage = $this->checkCapability('finance.cash_outflow.create');

            // Check if HTMX request
            $isHtmx = $request->header('HX-Request') === 'true';
            
            // If HTMX request, return table partial with statistics cards update via hx-swap-oob
            if ($isHtmx) {
                $tableHtml = view('staff.finance.cash-outflows.partials.table', [
                    'cashOutflows' => $cashOutflows,
                    'statuses' => $statuses,
                    'paymentMethods' => $paymentMethods,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ])->render();
                
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'cash-outflows-table-container',
                    'action' => route('staff.cash-outflows.index'),
                    'columns' => 5
                ])->render();
                
                // Return table HTML with statistics cards update via hx-swap-oob
                $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            }
            
            // Legacy AJAX support (for backward compatibility)
            if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
                return response()->json([
                    'success' => true,
                    'table_html' => view('staff.finance.cash-outflows.partials.table', [
                        'cashOutflows' => $cashOutflows,
                        'statuses' => $statuses,
                        'paymentMethods' => $paymentMethods,
                        'sortBy' => $sortBy,
                        'sortOrder' => $sortOrder
                    ])->render(),
                    'stats_html' => view('staff.components.statistics-cards', [
                        'stats' => $statsFormatted,
                        'currentFilter' => request('status', ''),
                        'filterKey' => 'status',
                        'onFilterClick' => 'filterByStatus',
                        'onClearClick' => 'clearAllFilters',
                        'columns' => 5
                    ])->render(),
                    'stats' => $stats,
                    'pagination' => [
                        'current_page' => $cashOutflows->currentPage(),
                        'last_page' => $cashOutflows->lastPage(),
                        'per_page' => $cashOutflows->perPage(),
                        'total' => $cashOutflows->total(),
                    ]
                ]);
            }
            
            return view('staff.finance.cash-outflows.index', compact(
                'cashOutflows', 'vendors', 'statuses', 'paymentMethods', 'canManage', 'stats', 'statsFormatted', 'sortBy', 'sortOrder'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải danh sách dòng tiền ra');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('finance.cash_outflow.create', 'Bạn không có quyền tạo Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            $statuses = [
                CashOutflow::STATUS_PENDING => 'Đang chờ',
                CashOutflow::STATUS_SUCCESS => 'Thành công',
                CashOutflow::STATUS_FAILED => 'Thất bại',
                CashOutflow::STATUS_REVERSED => 'Đã hoàn trả'
            ];
            $paymentMethods = PaymentMethod::orderBy('name')->get();
            
            // Get company invoices for selection
            $companyInvoices = CompanyInvoice::byOrganization($organizationId)
                ->whereIn('status', ['pending', 'approved', 'overdue'])
                ->with(['vendor'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'vendor_name' => $invoice->vendor ? $invoice->vendor->name : 'N/A',
                        'total_amount' => $invoice->total_amount,
                        'status' => $invoice->status,
                        'outstanding_amount' => $invoice->outstanding_amount ?? $invoice->total_amount,
                    ];
                });

            return view('staff.finance.cash-outflows.create', compact(
                'statuses', 'paymentMethods', 'companyInvoices'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@create: ' . $e->getMessage());
            return redirect()->route('staff.cash-outflows.index')
                ->with('error', 'Có lỗi xảy ra khi tải trang tạo mới');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('finance.cash_outflow.create', 'Bạn không có quyền tạo Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            // Status luôn là 'pending' khi tạo mới
            $request->merge(['status' => $request->input('status', CashOutflow::STATUS_PENDING)]);
            
            $validator = Validator::make($request->all(), [
                'company_invoice_id' => 'nullable|exists:company_invoices,id',
                'amount' => 'required|numeric|min:0',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'status' => 'required|in:pending,success,failed,reversed',
                'transaction_ref' => 'nullable|string|max:150',
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'paid_at' => 'nullable|date',
                'note' => 'nullable|string|max:1000',
            ], [
                'company_invoice_id.exists' => 'Hóa đơn công ty không tồn tại',
                'amount.required' => 'Vui lòng nhập số tiền',
                'amount.numeric' => 'Số tiền phải là số',
                'amount.min' => 'Số tiền phải lớn hơn 0',
                'payment_method_id.required' => 'Vui lòng chọn phương thức thanh toán',
                'payment_method_id.exists' => 'Phương thức thanh toán không hợp lệ',
                'status.required' => 'Vui lòng chọn trạng thái',
                'status.in' => 'Trạng thái không hợp lệ',
                'transaction_ref.max' => 'Mã giao dịch không được quá 150 ký tự',
                'document.file' => 'Tài liệu phải là file',
                'document.max' => 'Tài liệu không được quá 20MB',
                'document.mimes' => 'Tài liệu phải là định dạng: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, gif',
                'paid_at.date' => 'Ngày thanh toán không hợp lệ',
                'note.max' => 'Ghi chú không được quá 1000 ký tự',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Auto-generate transaction_ref using SequenceGenerator
            $cashOutflow = new CashOutflow();
            $transactionRef = $cashOutflow->generateTransactionRef($organizationId, $request->payment_method_id);

            // Handle document upload - save as document attachment (not to transaction_ref)
            $documentToAttach = null;
            if ($request->hasFile('document')) {
                try {
                    $file = $request->file('document');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'cash-outflows', 'cash-outflow-documents');

                    // Document will be attached after cash_outflow is created
                    $documentToAttach = [
                        'path' => $uploadedFile['original'],
                        'original_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                    ];
                } catch (\Exception $e) {
                    Log::error('Error preparing document for cash outflow: ' . $e->getMessage());
                    $documentToAttach = null;
                }
            }

            $cashOutflow = CashOutflow::create([
                'company_invoice_id' => $request->company_invoice_id ?? null,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'status' => $request->status,
                'transaction_ref' => $transactionRef,
                'paid_at' => $request->paid_at ? now()->parse($request->paid_at) : null,
                'note' => $request->note,
            ]);

            // Handle document upload - save as document attachment
            if ($documentToAttach) {
                try {
                    $document = \App\Models\Document::create([
                        'owner_type' => CashOutflow::class,
                        'owner_id' => $cashOutflow->id,
                        'file_url' => $documentToAttach['path'],
                        'file_name' => $documentToAttach['original_name'],
                        'mime_type' => $documentToAttach['mime_type'],
                        'file_size' => $documentToAttach['file_size'],
                        'document_type' => 'document',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow document: ' . $e->getMessage(), [
                        'cash_outflow_id' => $cashOutflow->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if document upload fails
                }
            }

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'cash-outflows', 'cash-outflow-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => CashOutflow::class,
                        'owner_id' => $cashOutflow->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'document_type' => 'image',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            DB::commit();

            return redirect()->route('staff.cash-outflows.show', $cashOutflow)
                ->with('success', 'Dòng tiền ra đã được tạo thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@store: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo dòng tiền ra')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.cash_outflow.view', 'Bạn không có quyền xem Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );
        
        try {

            $cashOutflow->load([
                'companyInvoice.vendor', 
                'companyInvoice.creator', 
                'companyInvoice.user', 
                'paymentMethod',
                'documents' => function($q) {
                    $q->orderBy('sort_order')
                      ->orderBy('created_at');
                }
            ]);
            
            // Check if user has manage capability (only manager)
            $canManage = $this->checkCapability('finance.cash_outflow.create');

            $statuses = [
                'pending' => 'Đang chờ',
                'success' => 'Thành công',
                'failed' => 'Thất bại',
                'reversed' => 'Đã hoàn trả'
            ];
            $paymentMethods = PaymentMethod::orderBy('name')->get();

            return view('staff.finance.cash-outflows.show', compact(
                'cashOutflow', 'statuses', 'paymentMethods', 'canManage'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@show: ' . $e->getMessage());
            return redirect()->route('staff.cash-outflows.index')
                ->with('error', 'Có lỗi xảy ra khi tải chi tiết dòng tiền ra');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền chỉnh sửa Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );

        // Prevent editing if cash outflow is already successful
        if ($cashOutflow->status === 'success') {
            return back()->with('error', 'Không thể chỉnh sửa dòng tiền ra đã thành công');
        }
        
        try {
            $statuses = [
                CashOutflow::STATUS_PENDING => 'Đang chờ',
                CashOutflow::STATUS_SUCCESS => 'Thành công',
                CashOutflow::STATUS_FAILED => 'Thất bại',
                CashOutflow::STATUS_REVERSED => 'Đã hoàn trả'
            ];
            $paymentMethods = PaymentMethod::orderBy('name')->get();

            return view('staff.finance.cash-outflows.edit', compact(
                'cashOutflow', 'statuses', 'paymentMethods'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@edit: ' . $e->getMessage());
            return redirect()->route('staff.cash-outflows.index')
                ->with('error', 'Có lỗi xảy ra khi tải trang chỉnh sửa');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );

        // Prevent editing if cash outflow is already successful
        if ($cashOutflow->status === 'success') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa dòng tiền ra đã thành công'
                ], 400);
            }
            return back()->with('error', 'Không thể chỉnh sửa dòng tiền ra đã thành công');
        }
        
        try {
            $validator = Validator::make($request->all(), [
                'company_invoice_id' => 'nullable|exists:company_invoices,id',
                'amount' => 'required|numeric|min:0',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'status' => 'required|in:pending,success,failed,reversed',
                'transaction_ref' => 'nullable|string|max:150',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'paid_at' => 'nullable|date',
                'note' => 'nullable|string|max:1000',
            ], [
                'company_invoice_id.exists' => 'Hóa đơn công ty không tồn tại',
                'amount.required' => 'Vui lòng nhập số tiền',
                'amount.numeric' => 'Số tiền phải là số',
                'amount.min' => 'Số tiền phải lớn hơn 0',
                'payment_method_id.required' => 'Vui lòng chọn phương thức thanh toán',
                'payment_method_id.exists' => 'Phương thức thanh toán không hợp lệ',
                'status.required' => 'Vui lòng chọn trạng thái',
                'status.in' => 'Trạng thái không hợp lệ',
                'transaction_ref.max' => 'Mã giao dịch không được quá 150 ký tự',
                'image.image' => 'File ảnh không hợp lệ',
                'image.mimes' => 'File ảnh phải là định dạng: jpeg, png, jpg, gif',
                'image.max' => 'File ảnh không được quá 2MB',
                'document.file' => 'Tài liệu phải là file',
                'document.max' => 'Tài liệu không được quá 20MB',
                'document.mimes' => 'Tài liệu phải là định dạng: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, gif',
                'paid_at.date' => 'Ngày thanh toán không hợp lệ',
                'note.max' => 'Ghi chú không được quá 1000 ký tự',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Handle image upload or removal - save as document attachment
            if ($request->has('remove_image') && $request->remove_image == '1') {
                // Delete old receipt image if exists
                $oldReceiptImage = $cashOutflow->documents()
                    ->where('document_type', 'image')
                    ->first();
                
                if ($oldReceiptImage) {
                    // Delete file from storage (lưu trực tiếp vào public/storage)
                    $filePath = $oldReceiptImage->getRawOriginal('file_url');
                    $fullPath = public_path('storage/' . $filePath);
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    // Delete document record
                    $oldReceiptImage->delete();
                }
            } elseif ($request->hasFile('image')) {
                try {
                    // Delete old receipt image if exists
                    $oldReceiptImage = $cashOutflow->documents()
                        ->where('document_type', 'image')
                        ->first();
                    
                    if ($oldReceiptImage) {
                        // Delete file from storage (lưu trực tiếp vào public/storage)
                        $filePath = $oldReceiptImage->getRawOriginal('file_url');
                        $fullPath = public_path('storage/' . $filePath);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        // Delete document record
                        $oldReceiptImage->delete();
                    }

                    // Upload new image
                    $file = $request->file('image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'cash-outflows', 'cash-outflow-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => CashOutflow::class,
                        'owner_id' => $cashOutflow->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            // Handle document upload or removal - save as document attachment (not to transaction_ref)
            if ($request->has('remove_document') && $request->remove_document == '1') {
                // Delete old document if exists
                $oldDocument = $cashOutflow->documents()
                    ->where('document_type', '!=', 'image')
                    ->first();
                
                if ($oldDocument) {
                    // Delete file from storage (lưu trực tiếp vào public/storage)
                    $filePath = $oldDocument->getRawOriginal('file_url');
                    $fullPath = public_path('storage/' . $filePath);
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    // Delete document record
                    $oldDocument->delete();
                }
            } elseif ($request->hasFile('document')) {
                try {
                    // Delete old document if exists
                    $oldDocument = $cashOutflow->documents()
                        ->where('document_type', '!=', 'image')
                        ->first();
                    
                    if ($oldDocument) {
                        // Delete file from storage (lưu trực tiếp vào public/storage)
                        $filePath = $oldDocument->getRawOriginal('file_url');
                        $fullPath = public_path('storage/' . $filePath);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        // Delete document record
                        $oldDocument->delete();
                    }

                    // Upload new document
                    $file = $request->file('document');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'cash-outflows', 'cash-outflow-documents');

                    $document = \App\Models\Document::create([
                        'owner_type' => CashOutflow::class,
                        'owner_id' => $cashOutflow->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'document',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow document: ' . $e->getMessage());
                    // Don't fail the entire request if document upload fails
                }
            }

            // transaction_ref is auto-generated and not editable
            $cashOutflow->update([
                'company_invoice_id' => $request->company_invoice_id ?? $cashOutflow->company_invoice_id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'status' => $request->status,
                'paid_at' => $request->paid_at ? now()->parse($request->paid_at) : null,
                'note' => $request->note,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dòng tiền ra đã được cập nhật thành công!',
                    'redirect' => route('staff.cash-outflows.show', $cashOutflow->id)
                ]);
            }

            return redirect()->route('staff.cash-outflows.show', $cashOutflow)
                ->with('success', 'Dòng tiền ra đã được cập nhật thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@update: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật dòng tiền ra')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete
        $this->requireCapability('finance.cash_outflow.delete', 'Bạn không có quyền xóa Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );

        // Prevent deletion if cash outflow is already successful
        if ($cashOutflow->status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa dòng tiền ra đã thành công'
            ], 400);
        }
        
        try {
            DB::beginTransaction();

            $cashOutflow->update([
                'deleted_by' => $user->id
            ]);
            $cashOutflow->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dòng tiền ra đã được xóa thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa dòng tiền ra'
            ], 500);
        }
    }

    /**
     * Mark cash outflow as success
     */
    public function markAsSuccess(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can mark as success
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );
        
        try {
            DB::beginTransaction();

            $cashOutflow->update([
                'status' => 'success',
                'paid_at' => now(),
            ]);

            // Update related company invoice if exists
            $this->updateRelatedCompanyInvoice($cashOutflow);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dòng tiền ra đã được đánh dấu thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@markAsSuccess: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đánh dấu thành công'
            ], 500);
        }
    }

    /**
     * Mark cash outflow as failed
     */
    public function markAsFailed(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can mark as failed
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );
        
        try {
            DB::beginTransaction();

            $cashOutflow->update([
                'status' => 'failed',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dòng tiền ra đã được đánh dấu thất bại'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@markAsFailed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đánh dấu thất bại'
            ], 500);
        }
    }

    /**
     * Update status of cash outflow
     */
    public function updateStatus(Request $request, CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );
        
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,success,failed,reversed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trạng thái không hợp lệ'
                ], 400);
            }

            DB::beginTransaction();

            $oldStatus = $cashOutflow->status;
            $newStatus = $request->status;

            $cashOutflow->update([
                'status' => $newStatus,
                'paid_at' => $newStatus === 'success' ? now() : ($newStatus === 'reversed' ? null : $cashOutflow->paid_at),
            ]);

            // Update related company invoice if status becomes success
            if ($newStatus === 'success') {
                $this->updateRelatedCompanyInvoice($cashOutflow);
            }

            DB::commit();

            $statusLabels = [
                'pending' => 'Đang chờ',
                'success' => 'Thành công',
                'failed' => 'Thất bại',
                'reversed' => 'Đã hoàn trả'
            ];

            return response()->json([
                'success' => true,
                'message' => "Đã chuyển trạng thái sang '{$statusLabels[$newStatus]}' thành công",
                'redirect' => route('staff.cash-outflows.show', $cashOutflow->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@updateStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái'
            ], 500);
        }
    }

    /**
     * Reverse cash outflow
     */
    public function reverse(CashOutflow $cashOutflow)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can reverse
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $this->checkOrganizationAccess(
            $cashOutflow->organization_id,
            'Unauthorized access to cash outflow.',
            'cash_outflow',
            $cashOutflow->id
        );
        
        try {
            DB::beginTransaction();

            $cashOutflow->update([
                'status' => 'reversed',
                'paid_at' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dòng tiền ra đã được hoàn trả'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@reverse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hoàn trả'
            ], 500);
        }
    }

    /**
     * Bulk action for multiple cash outflows
     */
    public function bulkAction(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can perform bulk actions
        $this->requireCapability('finance.cash_outflow.update', 'Bạn không có quyền cập nhật Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:mark-success,mark-failed,reverse',
                'cash_outflow_ids' => 'required|array|min:1',
                'cash_outflow_ids.*' => 'exists:cash_outflows,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ], 400);
            }

            $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id;
            $cashOutflowIds = $request->cash_outflow_ids;
            $action = $request->action;

            DB::beginTransaction();

            $cashOutflows = CashOutflow::whereIn('id', $cashOutflowIds)
                ->byOrganization($organizationId)
                ->get();

            if ($cashOutflows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy dòng tiền ra nào để xử lý'
                ], 404);
            }

            $updatedCount = 0;
            foreach ($cashOutflows as $cashOutflow) {
                switch ($action) {
                    case 'mark-success':
                        $cashOutflow->update([
                            'status' => 'success',
                            'paid_at' => now(),
                        ]);
                        break;
                    case 'mark-failed':
                        $cashOutflow->update([
                            'status' => 'failed',
                        ]);
                        break;
                    case 'reverse':
                        $cashOutflow->update([
                            'status' => 'reversed',
                            'paid_at' => null,
                        ]);
                        break;
                }
                $updatedCount++;
            }

            DB::commit();

            $actionText = [
                'mark-success' => 'đánh dấu thành công',
                'mark-failed' => 'đánh dấu thất bại',
                'reverse' => 'hoàn trả'
            ];

            return response()->json([
                'success' => true,
                'message' => "Đã {$actionText[$action]} {$updatedCount} dòng tiền ra"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CashOutflowController@bulkAction: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý hàng loạt'
            ], 500);
        }
    }

    /**
     * Get company invoice information via API
     */
    public function getCompanyInvoiceInfo(Request $request, $companyInvoiceId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.cash_outflow.view', 'Bạn không có quyền xem Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
        try {
            $companyInvoice = CompanyInvoice::byOrganization($organizationId)
                ->with(['vendor', 'user', 'creator'])
                ->find($companyInvoiceId);
            
            if (!$companyInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn công ty không tồn tại'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $companyInvoice->id,
                    'invoice_no' => $companyInvoice->invoice_no,
                    'vendor_id' => $companyInvoice->vendor_id,
                    'vendor_name' => $companyInvoice->vendor ? $companyInvoice->vendor->name : null,
                    'user_id' => $companyInvoice->user_id,
                    'user_name' => $companyInvoice->user ? ($companyInvoice->user->full_name ?? $companyInvoice->user->name) : null,
                    'total_amount' => $companyInvoice->total_amount,
                    'outstanding_amount' => $companyInvoice->outstanding_amount ?? $companyInvoice->total_amount,
                    'status' => $companyInvoice->status,
                    'description' => $companyInvoice->description,
                    'note' => $companyInvoice->note,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@getCompanyInvoiceInfo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thông tin hóa đơn công ty'
            ], 500);
        }
    }

    /**
     * Get statistics for cash outflows
     */
    public function statistics(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - manager can view all statistics
        $this->requireCapability('finance.cash_outflow.view', 'Bạn không có quyền xem Cash Outflows.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            $query = CashOutflow::byOrganization($organizationId);

            // Apply same filters as index
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('payment_method_id')) {
                $query->where('payment_method_id', $request->payment_method_id);
            }
            if ($request->filled('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $statistics = [
                'success_count' => (clone $query)->where('status', 'success')->count(),
                'pending_count' => (clone $query)->where('status', 'pending')->count(),
                'failed_count' => (clone $query)->where('status', 'failed')->count(),
                'reversed_count' => (clone $query)->where('status', 'reversed')->count(),
                'total_amount' => (clone $query)->sum('amount'),
                'success_amount' => (clone $query)->where('status', 'success')->sum('amount'),
                'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
            ];

            // Generate detailed HTML
            $detailedHtml = view('staff.finance.cash-outflows.partials.statistics', compact('statistics'))->render();

            return response()->json([
                'success' => true,
                'statistics' => array_merge($statistics, ['detailed_html' => $detailedHtml])
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowController@statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê'
            ], 500);
        }
    }

    /**
     * Update related company invoice status when cash outflow becomes successful
     */
    private function updateRelatedCompanyInvoice(CashOutflow $cashOutflow)
    {
        if ($cashOutflow->company_invoice_id) {
            $companyInvoice = CompanyInvoice::find($cashOutflow->company_invoice_id);
            
            if ($companyInvoice && $companyInvoice->status !== 'paid') {
                $companyInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::id(),
                ]);

                Log::info('Company invoice automatically marked as paid via cash outflow success', [
                    'company_invoice_id' => $companyInvoice->id,
                    'cash_outflow_id' => $cashOutflow->id,
                ]);
            }
        }
    }
}