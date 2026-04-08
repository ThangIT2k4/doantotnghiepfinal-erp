<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvoice;
use App\Models\CompanyInvoiceItem;
use App\Models\CashOutflow;
use App\Models\Vendor;
use App\Models\MasterLease;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\DepositRefund;
use App\Models\PayrollPayslip;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CompanyInvoiceController extends Controller
{
    use ChecksCapabilities;
    
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    
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
        $this->requireCapability('finance.company_invoice.view', 'Bạn không có quyền xem Company Invoices.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            // Start with basic query filtered by organization
            $query = CompanyInvoice::where('organization_id', $organizationId)
                ->with([
                    'vendor',
                    'organization',
                    'creator',
                    'masterLease',
                    'ticket',
                    'ticketLog',
                    'depositRefund',
                    'payrollPayslip',
                    'cashOutflows'
                ]);

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                          $vendorQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->filled('invoice_type')) {
                $query->where('invoice_type', $request->invoice_type);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->where('issue_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('issue_date', '<=', $request->date_to);
            }

            // Filter by vendor
            if ($request->filled('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Validate sort fields
            $allowedSortFields = ['id', 'created_at', 'invoice_no', 'invoice_type', 'issue_date', 'due_date', 'total_amount', 'status'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);

            // Calculate statistics (before pagination, on base query)
            $baseQuery = CompanyInvoice::where('organization_id', $organizationId);
            
            $stats = [
                'total' => (clone $baseQuery)->count(),
                'master_lease' => (clone $baseQuery)->where('invoice_type', 'master_lease')->count(),
                'ticket_cost' => (clone $baseQuery)->where('invoice_type', 'ticket_cost')->count(),
                'deposit_refund' => (clone $baseQuery)->where('invoice_type', 'deposit_refund')->count(),
                'payroll_payslip' => (clone $baseQuery)->where('invoice_type', 'payroll_payslip')->count(),
                'utility' => (clone $baseQuery)->where('invoice_type', 'utility')->count(),
                'maintenance' => (clone $baseQuery)->where('invoice_type', 'maintenance')->count(),
                'service' => (clone $baseQuery)->where('invoice_type', 'service')->count(),
                'supply' => (clone $baseQuery)->where('invoice_type', 'supply')->count(),
                'other' => (clone $baseQuery)->where('invoice_type', 'other')->count(),
            ];

            // Paginate
            $invoices = $query->paginate(20)->withQueryString();

            // Get filter options
            $vendors = Vendor::where('organization_id', $organizationId)->orderBy('name')->get();
            $statuses = [
                'draft' => 'Nháp',
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'paid' => 'Đã thanh toán',
                'overdue' => 'Quá hạn',
                'cancelled' => 'Đã hủy'
            ];
            $types = [
                'master_lease' => 'Hợp đồng tổng',
                'ticket_cost' => 'Chi phí ticket',
                'deposit_refund' => 'Hoàn tiền cọc',
                'payroll_payslip' => 'Lương nhân viên',
                'utility' => 'Tiện ích',
                'maintenance' => 'Bảo trì',
                'service' => 'Dịch vụ',
                'supply' => 'Cung cấp',
                'other' => 'Khác'
            ];

            // Check if user has manage capability (only manager)
            $canManage = $this->checkCapability('finance.company_invoice.create');
            
            // Get sort parameters
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Format stats for statistics-cards component
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'master_lease' => [
                    'value' => $stats['master_lease'] ?? 0,
                    'label' => 'Hợp đồng tổng',
                    'icon' => 'fa-file-contract',
                    'color' => 'info',
                    'filter' => 'master_lease',
                ],
                'ticket_cost' => [
                    'value' => $stats['ticket_cost'] ?? 0,
                    'label' => 'Chi phí ticket',
                    'icon' => 'fa-ticket-alt',
                    'color' => 'warning',
                    'filter' => 'ticket_cost',
                ],
                'deposit_refund' => [
                    'value' => $stats['deposit_refund'] ?? 0,
                    'label' => 'Hoàn tiền cọc',
                    'icon' => 'fa-money-bill-wave',
                    'color' => 'success',
                    'filter' => 'deposit_refund',
                ],
                'payroll_payslip' => [
                    'value' => $stats['payroll_payslip'] ?? 0,
                    'label' => 'Lương nhân viên',
                    'icon' => 'fa-money-check-alt',
                    'color' => 'primary',
                    'filter' => 'payroll_payslip',
                ],
                'utility' => [
                    'value' => $stats['utility'] ?? 0,
                    'label' => 'Tiện ích',
                    'icon' => 'fa-bolt',
                    'color' => 'secondary',
                    'filter' => 'utility',
                ],
                'maintenance' => [
                    'value' => $stats['maintenance'] ?? 0,
                    'label' => 'Bảo trì',
                    'icon' => 'fa-tools',
                    'color' => 'danger',
                    'filter' => 'maintenance',
                ],
                'service' => [
                    'value' => $stats['service'] ?? 0,
                    'label' => 'Dịch vụ',
                    'icon' => 'fa-concierge-bell',
                    'color' => 'info',
                    'filter' => 'service',
                ],
                'supply' => [
                    'value' => $stats['supply'] ?? 0,
                    'label' => 'Cung cấp',
                    'icon' => 'fa-box',
                    'color' => 'warning',
                    'filter' => 'supply',
                ],
                'other' => [
                    'value' => $stats['other'] ?? 0,
                    'label' => 'Khác',
                    'icon' => 'fa-ellipsis-h',
                    'color' => 'dark',
                    'filter' => 'other',
                ],
            ];
            $currentType = $request->get('invoice_type', '');
            
            // Check if HTMX request
            $isHtmx = $request->header('HX-Request') === 'true';
            
            // If HTMX request, return table partial with statistics cards update via hx-swap-oob
            if ($isHtmx) {
                $tableHtml = view('staff.finance.company-invoices.partials.table', compact('invoices', 'statuses', 'types', 'sortBy', 'sortOrder'))->render();
                
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => $currentType,
                    'filterKey' => 'invoice_type',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'company-invoices-table-container',
                    'action' => route('staff.company-invoices.index'),
                    'columns' => 6
                ])->render();
                
                // Return table HTML with statistics cards update via hx-swap-oob
                $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            }
            
            return view('staff.finance.company-invoices.index', compact(
                'invoices',
                'vendors',
                'statuses',
                'types',
                'stats',
                'canManage',
                'sortBy',
                'sortOrder'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if HTMX request
            $isHtmx = $request->header('HX-Request') === 'true';
            
            if ($isHtmx) {
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải danh sách hóa đơn công ty: ' . $e->getMessage() . '</div>', 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi tải danh sách hóa đơn công ty');
        }
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('finance.company_invoice.create', 'Bạn không có quyền tạo Company Invoices.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            // Get organization for queries
            $organization = \App\Models\Organization::find($organizationId);

            // Get vendors
            $vendors = Vendor::where('organization_id', $organizationId)
                ->orderBy('name')
                ->get();

            // Get users in organization (for recipient as user)
            $users = User::whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->with('userProfile')
                ->get()
                ->sortBy(function($user) {
                    return $user->full_name;
                })
                ->values();

            // Get payment methods
            $paymentMethods = PaymentMethod::all();

            // Get source data based on type
            $sourceData = [];
            $selectedMasterLease = null;
            
            // Handle master_lease_id from query parameter
            if ($request->filled('master_lease_id')) {
                $masterLeaseId = $request->master_lease_id;
                $selectedMasterLease = MasterLease::where('organization_id', $organizationId)
                    ->where('id', $masterLeaseId)
                    ->with(['property', 'landlord'])
                    ->first();
                
                if ($selectedMasterLease) {
                    // Master lease is already selected
                }
            }
            
            // Get source data based on invoice_type for form dropdowns
            $sourceData = null;
            if ($request->filled('invoice_type')) {
                switch ($request->invoice_type) {
                    case 'master_lease':
                        $sourceData = MasterLease::where('organization_id', $organizationId)
                            ->with(['property', 'landlord'])
                            ->get();
                        break;
                    case 'ticket_cost':
                        $sourceData = Ticket::where('organization_id', $organizationId)
                            ->with(['unit.property'])
                            ->get();
                        break;
                    case 'deposit_refund':
                        $sourceData = DepositRefund::where('organization_id', $organizationId)
                            ->with(['lease.tenant', 'lease.unit.property'])
                            ->get();
                        break;
                    case 'payroll_payslip':
                        $sourceData = PayrollPayslip::whereHas('payrollCycle', function($q) use ($organizationId) {
                            $q->where('organization_id', $organizationId);
                        })->with(['user', 'payrollCycle'])->get();
                        break;
                }
            }

            $types = [
                'master_lease' => 'Hợp đồng tổng',
                'ticket_cost' => 'Chi phí ticket',
                'deposit_refund' => 'Hoàn tiền cọc',
                'payroll_payslip' => 'Lương nhân viên',
                'utility' => 'Tiện ích',
                'maintenance' => 'Bảo trì',
                'service' => 'Dịch vụ',
                'supply' => 'Cung cấp',
                'other' => 'Khác'
            ];

            // Calculate pre-filled data from master lease if available
            $prefilledData = [];
            if ($selectedMasterLease) {
                // Calculate issue date (next billing date or today)
                $issueDate = now();
                if ($selectedMasterLease->billing_day) {
                    $billingDay = (int) $selectedMasterLease->billing_day;
                    $today = now();
                    $issueDate = $today->copy()->day(min($billingDay, $today->daysInMonth));
                    if ($issueDate->lt($today)) {
                        $issueDate->addMonth()->day(min($billingDay, $issueDate->daysInMonth));
                    }
                }
                
                // Calculate due date (issue_date + due_in_days)
                $dueDate = $issueDate->copy()->addDays((int) ($selectedMasterLease->due_in_days ?? 5));
                
                // Calculate amount (base_rent for one billing cycle)
                $amount = $selectedMasterLease->base_rent;
                
                // Generate description
                $propertyName = $selectedMasterLease->property->name ?? '';
                $contractNo = $selectedMasterLease->contract_no ?? '';
                $billingCycleMonths = $selectedMasterLease->billing_cycle ?? 1;
                $billingCycleLabel = $billingCycleMonths == 1 ? 'Hàng tháng' : "{$billingCycleMonths} tháng";
                $description = "Hóa đơn thuê tài sản - {$propertyName} - Hợp đồng {$contractNo} ({$billingCycleLabel})";
                
                $prefilledData = [
                    'issue_date' => $issueDate->format('Y-m-d'),
                    'due_date' => $dueDate->format('Y-m-d'),
                    'subtotal' => $amount,
                    'total_amount' => $amount,
                    'currency' => $selectedMasterLease->rent_currency ?? 'VND',
                    'description' => $description,
                    'invoice_type' => 'master_lease',
                ];
            }

            return view('staff.finance.company-invoices.create', compact(
                'vendors',
                'users',
                'paymentMethods',
                'sourceData',
                'types',
                'selectedMasterLease',
                'prefilledData'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@create: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tải trang tạo hóa đơn');
        }
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('finance.company_invoice.create', 'Bạn không có quyền tạo Company Invoices.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            // Normalize numeric fields before validation (remove thousand separators)
            $requestData = $request->all();
            $normalizeNumber = function($value) {
                return is_string($value) && $value !== '' ? str_replace([',', '.'], '', trim($value)) ?: null : $value;
            };
            
            foreach (['subtotal', 'tax_amount', 'discount_amount', 'total_amount'] as $field) {
                if (isset($requestData[$field])) {
                    $requestData[$field] = $normalizeNumber($requestData[$field]);
                }
            }
            
            if (isset($requestData['items']) && is_array($requestData['items'])) {
                foreach ($requestData['items'] as &$item) {
                    foreach (['quantity', 'unit_price', 'amount'] as $field) {
                        if (isset($item[$field])) {
                            $item[$field] = $normalizeNumber($item[$field]);
                        }
                    }
                }
                unset($item);
            }
            
            $request->merge($requestData);
            
            $validator = Validator::make($request->all(), [
                'vendor_id' => 'nullable|exists:vendors,id',
                'user_id' => 'nullable|exists:users,id',
                'invoice_type' => 'required|in:master_lease,ticket_cost,deposit_refund,payroll_payslip,utility,maintenance,service,supply,other',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'status' => 'required|in:draft,pending,approved,paid,overdue,cancelled',
                'subtotal' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'description' => 'nullable|string|max:1000',
                'note' => 'nullable|string|max:1000',
                'attachment' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'attachment_url' => 'nullable|url|max:500',
                'created_by' => 'nullable|exists:users,id',
                'master_lease_id' => 'nullable|exists:master_leases,id',
                'ticket_id' => 'nullable|exists:tickets,id',
                'ticket_log_id' => 'nullable|exists:ticket_logs,id',
                'deposit_refund_id' => 'nullable|exists:deposit_refunds,id',
                'payroll_payslip_id' => 'nullable|exists:payroll_payslips,id',
                // Items validation
                'items' => 'nullable|array',
                'items.*.item_type' => 'required_with:items|string|in:rent,service,meter,deposit,ticket_cost,other',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.quantity' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'required_with:items|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'items.*.meta_json' => 'nullable|array',
            ], [
                'vendor_id.exists' => 'Nhà cung cấp không tồn tại',
                'user_id.exists' => 'Người dùng không tồn tại',
                'invoice_type.required' => 'Vui lòng chọn loại hóa đơn',
                'invoice_type.in' => 'Loại hóa đơn không hợp lệ',
                'issue_date.required' => 'Vui lòng nhập ngày phát hành',
                'issue_date.date' => 'Ngày phát hành không hợp lệ',
                'due_date.required' => 'Vui lòng nhập ngày đến hạn',
                'due_date.date' => 'Ngày đến hạn không hợp lệ',
                'due_date.after_or_equal' => 'Ngày đến hạn phải sau hoặc bằng ngày phát hành',
                'status.required' => 'Vui lòng chọn trạng thái',
                'status.in' => 'Trạng thái không hợp lệ',
                'subtotal.numeric' => 'Tổng tiền trước thuế phải là số',
                'subtotal.min' => 'Tổng tiền trước thuế phải lớn hơn 0',
                'tax_amount.numeric' => 'Số tiền thuế phải là số',
                'tax_amount.min' => 'Số tiền thuế phải lớn hơn hoặc bằng 0',
                'discount_amount.numeric' => 'Số tiền giảm giá phải là số',
                'discount_amount.min' => 'Số tiền giảm giá phải lớn hơn hoặc bằng 0',
                'total_amount.numeric' => 'Tổng tiền thanh toán phải là số',
                'total_amount.min' => 'Tổng tiền thanh toán phải lớn hơn 0',
                'currency.max' => 'Đơn vị tiền tệ không được quá 3 ký tự',
                'description.max' => 'Mô tả không được quá 1000 ký tự',
                'note.max' => 'Ghi chú không được quá 1000 ký tự',
                'attachment_url.url' => 'URL tài liệu đính kèm không hợp lệ',
                'attachment_url.max' => 'URL tài liệu đính kèm không được quá 500 ký tự',
                'items.*.item_type.in' => 'Loại mục không hợp lệ',
                'items.*.unit_price.numeric' => 'Đơn giá phải là số',
                'items.*.quantity.numeric' => 'Số lượng phải là số',
            ]);

            // Require at least one of vendor_id or user_id
            // Also validate type-specific required fields
            $validator->after(function ($validator) use ($request) {
                if (empty($request->vendor_id) && empty($request->user_id)) {
                    $validator->errors()->add('recipient', 'Vui lòng chọn nhà cung cấp hoặc người dùng làm người nhận.');
                }
                
                // Validate type-specific required fields
                $invoiceType = $request->input('invoice_type');
                if ($invoiceType === 'master_lease' && empty($request->master_lease_id)) {
                    $validator->errors()->add('master_lease_id', 'Hóa đơn loại master_lease cần có master_lease_id');
                }
                if ($invoiceType === 'ticket_cost' && empty($request->ticket_id) && empty($request->ticket_log_id)) {
                    $validator->errors()->add('ticket_id', 'Hóa đơn loại ticket_cost cần có ticket_id hoặc ticket_log_id');
                }
                if ($invoiceType === 'deposit_refund' && empty($request->deposit_refund_id)) {
                    $validator->errors()->add('deposit_refund_id', 'Hóa đơn loại deposit_refund cần có deposit_refund_id');
                }
                if ($invoiceType === 'payroll_payslip' && empty($request->payroll_payslip_id)) {
                    $validator->errors()->add('payroll_payslip_id', 'Hóa đơn loại payroll_payslip cần có payroll_payslip_id');
                }
            });

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.');
            }

            $validated = $validator->validated();

            // Enforce mutual exclusivity: prefer recipient_type if provided
            $recipientType = $request->input('recipient_type');
            if ($recipientType === 'vendor') {
                $validated['user_id'] = null;
            } elseif ($recipientType === 'user') {
                $validated['vendor_id'] = null;
            } else {
                if (!empty($validated['vendor_id'])) {
                    $validated['user_id'] = null;
                } elseif (!empty($validated['user_id'])) {
                    $validated['vendor_id'] = null;
                }
            }

            DB::beginTransaction();

            // Numbers are already normalized before validation, no need to normalize again

            // If items provided, compute subtotal/total from items
            $itemsInput = $request->input('items', []);
            $computedSubtotal = 0;
            if (is_array($itemsInput) && count($itemsInput) > 0) {
                foreach ($itemsInput as $it) {
                    $qty = isset($it['quantity']) ? (float) $it['quantity'] : 1;
                    $unit = isset($it['unit_price']) ? (float) $it['unit_price'] : 0;
                    $lineAmount = isset($it['amount']) ? (float) $it['amount'] : ($qty * $unit);
                    $computedSubtotal += $lineAmount;
                }
                $validated['subtotal'] = $computedSubtotal;
                $validated['total_amount'] = max(0, ($computedSubtotal + ($validated['tax_amount'] ?? 0)) - ($validated['discount_amount'] ?? 0));
            }

            // Create company invoice
            // Status luôn là 'draft' khi tạo mới
            $invoice = CompanyInvoice::create([
                'organization_id' => $organizationId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'invoice_type' => $validated['invoice_type'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => $validated['status'] ?? 'draft',
                'subtotal' => $validated['subtotal'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'] ?? 0,
                'currency' => $validated['currency'] ?? 'VND',
                'description' => $validated['description'],
                'note' => $validated['note'],
                // 'attachment_url' removed - use document attachments instead
                'master_lease_id' => $validated['master_lease_id'],
                'ticket_id' => $validated['ticket_id'],
                'ticket_log_id' => $validated['ticket_log_id'],
                'deposit_refund_id' => $validated['deposit_refund_id'],
                'payroll_payslip_id' => $validated['payroll_payslip_id'],
                'created_by' => $validated['created_by'] ?? $user->id,
            ]);

            // Handle file upload if provided - save as document attachment
            if ($request->hasFile('attachment')) {
                try {
                    $file = $request->file('attachment');
                    if (!$file->isValid()) {
                        throw new \Exception('File upload không hợp lệ');
                    }
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'company-invoices', 'company-invoice-attachments');
                    
                    if (!$invoice->id) {
                        throw new \Exception('Company invoice chưa có ID');
                    }
                    $document = \App\Models\Document::create([
                        'owner_type' => CompanyInvoice::class,
                        'owner_id' => $invoice->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'attachment',
                        'is_primary' => false,
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);
                    if (!$document->id) {
                        throw new \Exception('Document chưa có ID sau khi tạo');
                    }
                    Log::info('Company invoice attachment document created successfully', [
                        'company_invoice_id' => $invoice->id,
                        'document_id' => $document->id,
                        'file_path' => $uploadedFile['original'],
                        'file_size' => $uploadedFile['size']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading company invoice attachment: ' . $e->getMessage(), [
                        'company_invoice_id' => $invoice->id ?? null,
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if attachment upload fails
                }
            }

            // Persist items if provided
            if (is_array($itemsInput) && count($itemsInput) > 0) {
                foreach ($itemsInput as $it) {
                    $qty = isset($it['quantity']) ? (float) $it['quantity'] : 1;
                    $unit = isset($it['unit_price']) ? (float) $it['unit_price'] : 0;
                    // Always calculate amount from quantity * unit_price to satisfy check constraint
                    // Check constraint requires: amount = quantity * unit_price
                    $lineAmount = $qty * $unit;
                    CompanyInvoiceItem::create([
                        'company_invoice_id' => $invoice->id,
                        'item_type' => $it['item_type'] ?? 'other',
                        'description' => $it['description'] ?? null,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'amount' => $lineAmount, // Always calculated from qty * unit_price
                        'meta_json' => isset($it['meta_json']) && is_array($it['meta_json']) ? $it['meta_json'] : null,
                    ]);
                }

                // Recompute totals from saved items to ensure consistency
                $subtotal = $invoice->items()->sum('amount');
                $tax = $validated['tax_amount'] ?? 0;
                $discount = $validated['discount_amount'] ?? 0;
                $invoice->update([
                    'subtotal' => $subtotal,
                    'total_amount' => max(0, ($subtotal + $tax) - $discount),
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn công ty đã được tạo thành công!',
                    'redirect' => route('staff.company-invoices.show', $invoice->id)
                ]);
            }

            return redirect()->route('staff.company-invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn công ty đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@store: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo hóa đơn công ty: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo hóa đơn công ty: ' . $e->getMessage());
        }
    }

    public function show(CompanyInvoice $companyInvoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.company_invoice.view', 'Bạn không có quyền xem Company Invoices.');
        
        $this->checkOrganizationAccess(
            $companyInvoice->organization_id,
            'Unauthorized access to company invoice.',
            'company_invoice',
            $companyInvoice->id
        );
        try {
            // Load base relationships (non-nested first)
            $companyInvoice->load([
                'vendor',
                'organization',
                'creator',
                'items',
                'documents' => function($q) {
                    $q->orderBy('sort_order')
                      ->orderBy('created_at');
                }
            ]);
            
            // Load nested relationships conditionally
            if ($companyInvoice->master_lease_id) {
                $companyInvoice->load('masterLease.property');
            }
            
            if ($companyInvoice->ticket_id) {
                $companyInvoice->load('ticket.unit.property');
            }
            
            if ($companyInvoice->ticket_log_id) {
                $companyInvoice->load('ticketLog');
            }
            
            if ($companyInvoice->deposit_refund_id) {
                $companyInvoice->load('depositRefund.lease.tenant');
            }
            
            if ($companyInvoice->payroll_payslip_id) {
                $companyInvoice->load([
                    'payrollPayslip.user.userProfile',
                    'payrollPayslip.payrollCycle'
                ]);
            }
            
            // Load cashOutflows (payerUser is not a real relationship, it's a method that returns companyInvoice->creator())
            // So we just load cashOutflows, and payerUser will be accessed via the method
            $companyInvoice->load('cashOutflows');

            return view('staff.finance.company-invoices.show', compact('companyInvoice'));

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@show: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tải chi tiết hóa đơn công ty');
        }
    }

    public function edit(CompanyInvoice $companyInvoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit
        $this->requireCapability('finance.company_invoice.update', 'Bạn không có quyền chỉnh sửa Company Invoices.');
        
        $this->checkOrganizationAccess(
            $companyInvoice->organization_id,
            'Unauthorized access to company invoice.',
            'company_invoice',
            $companyInvoice->id
        );

        $organizationId = $this->getCurrentOrganizationId();

        // Prevent editing if invoice is already paid
        if ($companyInvoice->status === 'paid') {
            return back()->with('error', 'Không thể chỉnh sửa hóa đơn công ty đã thanh toán');
        }

        try {
            // Load items and relationships for the invoice
            $companyInvoice->load(['items', 'masterLease.property', 'ticket', 'ticketLog.ticket', 'depositRefund', 'payrollPayslip', 'documents.uploader']);

            // Get organization for queries
            $organization = \App\Models\Organization::find($organizationId);

            // Get vendors
            $vendors = Vendor::where('organization_id', $organizationId)
                ->orderBy('name')
                ->get();

            // Get users in organization (for recipient as user)
            $users = User::whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->with('userProfile')
                ->get()
                ->sortBy(function($user) {
                    return $user->full_name;
                })
                ->values();

            $types = [
                'master_lease' => 'Hợp đồng tổng',
                'ticket_cost' => 'Chi phí ticket',
                'deposit_refund' => 'Hoàn tiền cọc',
                'payroll_payslip' => 'Lương nhân viên',
                'utility' => 'Tiện ích',
                'maintenance' => 'Bảo trì',
                'service' => 'Dịch vụ',
                'supply' => 'Cung cấp',
                'other' => 'Khác'
            ];

            $statuses = [
                'draft' => 'Nháp',
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'paid' => 'Đã thanh toán',
                'overdue' => 'Quá hạn',
                'cancelled' => 'Đã hủy'
            ];

            return view('staff.finance.company-invoices.edit', compact(
                'companyInvoice',
                'vendors',
                'users',
                'types',
                'statuses'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@edit: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tải trang chỉnh sửa hóa đơn công ty');
        }
    }

    public function update(Request $request, CompanyInvoice $companyInvoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update
        $this->requireCapability('finance.company_invoice.update', 'Bạn không có quyền cập nhật Company Invoices.');
        
        $this->checkOrganizationAccess(
            $companyInvoice->organization_id,
            'Unauthorized access to company invoice.',
            'company_invoice',
            $companyInvoice->id
        );

        // Prevent editing if invoice is already paid
        if ($companyInvoice->status === 'paid') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa hóa đơn công ty đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể chỉnh sửa hóa đơn công ty đã thanh toán');
        }

        try {
            // Normalize numeric fields before validation (remove thousand separators)
            $requestData = $request->all();
            $normalizeNumber = function($value) {
                return is_string($value) && $value !== '' ? str_replace([',', '.'], '', trim($value)) ?: null : $value;
            };
            
            foreach (['subtotal', 'tax_amount', 'discount_amount', 'total_amount'] as $field) {
                if (isset($requestData[$field])) {
                    $requestData[$field] = $normalizeNumber($requestData[$field]);
                }
            }
            
            if (isset($requestData['items']) && is_array($requestData['items'])) {
                foreach ($requestData['items'] as &$item) {
                    foreach (['quantity', 'unit_price', 'amount'] as $field) {
                        if (isset($item[$field])) {
                            $item[$field] = $normalizeNumber($item[$field]);
                        }
                    }
                }
                unset($item);
            }
            
            $request->merge($requestData);
            
            $validated = $request->validate([
                'vendor_id' => 'nullable|exists:vendors,id',
                'user_id' => 'nullable|exists:users,id',
                'invoice_type' => 'required|in:master_lease,ticket_cost,deposit_refund,payroll_payslip,utility,maintenance,service,supply,other',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'status' => 'required|in:draft,pending,approved,paid,overdue,cancelled',
                'subtotal' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'description' => 'nullable|string',
                'note' => 'nullable|string',
                'attachment' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                // 'attachment_url' removed - use document attachments instead
                'master_lease_id' => 'nullable|exists:master_leases,id',
                'ticket_id' => 'nullable|exists:tickets,id',
                'ticket_log_id' => 'nullable|exists:ticket_logs,id',
                'deposit_refund_id' => 'nullable|exists:deposit_refunds,id',
                'payroll_payslip_id' => 'nullable|exists:payroll_payslips,id',
                'items' => 'nullable|array',
                'items.*.id' => 'nullable|integer|exists:company_invoice_items,id',
                'items.*.item_type' => 'required_with:items|string|in:rent,service,meter,deposit,ticket_cost,other',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.quantity' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'required_with:items|numeric|min:0',
                'items.*.amount' => 'nullable|numeric|min:0',
                'items.*.meta_json' => 'nullable|array',
            ]);

            if (empty($validated['vendor_id']) && empty($validated['user_id'])) {
                return back()->withInput()->withErrors(['recipient' => 'Vui lòng chọn nhà cung cấp hoặc người dùng làm người nhận.']);
            }
            
            // Validate type-specific required fields
            $invoiceType = $validated['invoice_type'] ?? null;
            if ($invoiceType === 'master_lease' && empty($validated['master_lease_id'])) {
                return back()->withInput()->withErrors(['master_lease_id' => 'Hóa đơn loại master_lease cần có master_lease_id']);
            }
            if ($invoiceType === 'ticket_cost' && empty($validated['ticket_id']) && empty($validated['ticket_log_id'])) {
                return back()->withInput()->withErrors(['ticket_id' => 'Hóa đơn loại ticket_cost cần có ticket_id hoặc ticket_log_id']);
            }
            if ($invoiceType === 'deposit_refund' && empty($validated['deposit_refund_id'])) {
                return back()->withInput()->withErrors(['deposit_refund_id' => 'Hóa đơn loại deposit_refund cần có deposit_refund_id']);
            }
            if ($invoiceType === 'payroll_payslip' && empty($validated['payroll_payslip_id'])) {
                return back()->withInput()->withErrors(['payroll_payslip_id' => 'Hóa đơn loại payroll_payslip cần có payroll_payslip_id']);
            }

            DB::beginTransaction();

            // Enforce mutual exclusivity: prefer recipient_type if provided
            $recipientType = $request->input('recipient_type');
            if ($recipientType === 'vendor') {
                $validated['user_id'] = null;
            } elseif ($recipientType === 'user') {
                $validated['vendor_id'] = null;
            } else {
                if (!empty($validated['vendor_id'])) {
                    $validated['user_id'] = null;
                } elseif (!empty($validated['user_id'])) {
                    $validated['vendor_id'] = null;
                }
            }

            // Remove attachment_url from validated if present (not in database anymore)
            unset($validated['attachment_url']);

            // Numbers are already normalized before validation, no need to normalize again
            
            // Ensure tax_amount and discount_amount are not null (default to 0)
            if (!isset($validated['tax_amount']) || $validated['tax_amount'] === null || $validated['tax_amount'] === '') {
                $validated['tax_amount'] = 0;
            }
            if (!isset($validated['discount_amount']) || $validated['discount_amount'] === null || $validated['discount_amount'] === '') {
                $validated['discount_amount'] = 0;
            }

            // If items provided, upsert items and recalc totals
            $itemsInput = $request->input('items', []);

            $companyInvoice->update($validated);

            // Handle file upload if provided - save as document attachment
            if ($request->hasFile('attachment')) {
                try {
                    $file = $request->file('attachment');
                    if (!$file->isValid()) {
                        throw new \Exception('File upload không hợp lệ');
                    }
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'company-invoices', 'company-invoice-attachments');
                    
                    if (!$companyInvoice->id) {
                        throw new \Exception('Company invoice chưa có ID');
                    }
                    $document = \App\Models\Document::create([
                        'owner_type' => CompanyInvoice::class,
                        'owner_id' => $companyInvoice->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'attachment',
                        'is_primary' => false,
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);
                    if (!$document->id) {
                        throw new \Exception('Document chưa có ID sau khi tạo');
                    }
                    Log::info('Company invoice attachment document created successfully', [
                        'company_invoice_id' => $companyInvoice->id,
                        'document_id' => $document->id,
                        'file_path' => $uploadedFile['original'],
                        'file_size' => $uploadedFile['size']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error creating document for company invoice', [
                        'company_invoice_id' => $companyInvoice->id,
                        'document_id' => $document->id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            if (is_array($itemsInput)) {
                $existingIds = [];
                foreach ($itemsInput as $it) {
                    $qty = isset($it['quantity']) ? (float) $it['quantity'] : 1;
                    $unit = isset($it['unit_price']) ? (float) $it['unit_price'] : 0;
                    // Always calculate amount from quantity * unit_price to satisfy check constraint
                    // Check constraint requires: amount = quantity * unit_price
                    $lineAmount = $qty * $unit;

                    if (!empty($it['id'])) {
                        // Update existing
                        $item = CompanyInvoiceItem::where('company_invoice_id', $companyInvoice->id)
                            ->where('id', $it['id'])->first();
                        if ($item) {
                            // Update all fields together to ensure constraint is satisfied
                            $item->update([
                                'item_type' => $it['item_type'] ?? $item->item_type,
                                'description' => $it['description'] ?? $item->description,
                                'quantity' => $qty,
                                'unit_price' => $unit,
                                'amount' => $lineAmount, // Always calculated from qty * unit_price
                                'meta_json' => isset($it['meta_json']) && is_array($it['meta_json']) ? $it['meta_json'] : $item->meta_json,
                            ]);
                            $existingIds[] = $item->id;
                        }
                    } else {
                        // Create new
                        $new = CompanyInvoiceItem::create([
                            'company_invoice_id' => $companyInvoice->id,
                            'item_type' => $it['item_type'] ?? 'other',
                            'description' => $it['description'] ?? null,
                            'quantity' => $qty,
                            'unit_price' => $unit,
                            'amount' => $lineAmount, // Always calculated from qty * unit_price
                            'meta_json' => isset($it['meta_json']) && is_array($it['meta_json']) ? $it['meta_json'] : null,
                        ]);
                        $existingIds[] = $new->id;
                    }
                }

                // Remove items not present anymore
                if (count($existingIds) > 0) {
                    CompanyInvoiceItem::where('company_invoice_id', $companyInvoice->id)
                        ->whereNotIn('id', $existingIds)
                        ->delete();
                }

                // Recompute totals
                $subtotal = $companyInvoice->items()->sum('amount');
                $tax = $validated['tax_amount'] ?? $companyInvoice->tax_amount ?? 0;
                $discount = $validated['discount_amount'] ?? $companyInvoice->discount_amount ?? 0;
                $companyInvoice->update([
                    'subtotal' => $subtotal,
                    'total_amount' => max(0, ($subtotal + $tax) - $discount),
                ]);
            }

            DB::commit();

            // Refresh model to ensure all data is up to date
            $companyInvoice->refresh();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn công ty đã được cập nhật thành công!',
                    'redirect' => route('staff.company-invoices.show', $companyInvoice->id)
                ]);
            }

            return redirect()->route('staff.company-invoices.show', $companyInvoice->id)
                ->with('success', 'Hóa đơn công ty đã được cập nhật thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors()
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@update: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', '_token', 'attachment'])
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật hóa đơn công ty: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật hóa đơn công ty: ' . $e->getMessage());
        }
    }

    public function destroy(CompanyInvoice $companyInvoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete
        $this->requireCapability('finance.company_invoice.delete', 'Bạn không có quyền xóa Company Invoices.');
        
        $this->checkOrganizationAccess(
            $companyInvoice->organization_id,
            'Unauthorized access to company invoice.',
            'company_invoice',
            $companyInvoice->id
        );

        // Prevent deletion if invoice is already paid
        if ($companyInvoice->status === 'paid') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa hóa đơn công ty đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa hóa đơn công ty đã thanh toán');
        }

        try {
            DB::beginTransaction();

            // Check if invoice has payments
            if ($companyInvoice->cashOutflows()->count() > 0) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa hóa đơn đã có thanh toán'
                    ], 400);
                }
                return back()->with('error', 'Không thể xóa hóa đơn đã có thanh toán');
            }

            $companyInvoice->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn công ty đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.company-invoices.index')
                ->with('success', 'Hóa đơn công ty đã được xóa thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@destroy: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa hóa đơn công ty: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa hóa đơn công ty: ' . $e->getMessage());
        }
    }

    public function markAsPaid(Request $request, CompanyInvoice $companyInvoice)
    {
        try {
            $validated = $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'paid_at' => 'required|date',
                'transaction_ref' => 'nullable|string|max:150',
                'note' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Save invoice data before update
            $oldStatus = $companyInvoice->status;
            $invoiceId = $companyInvoice->id;
            $organizationId = $companyInvoice->organization_id;
            $totalAmount = $companyInvoice->total_amount;
            $invoiceNo = $companyInvoice->invoice_no;
            $depositRefundId = $companyInvoice->deposit_refund_id;
            $payrollPayslipId = $companyInvoice->payroll_payslip_id;
            
            // Update invoice status without triggering events to avoid relationship errors
            CompanyInvoice::withoutEvents(function() use ($invoiceId) {
                DB::table('company_invoices')
                    ->where('id', $invoiceId)
                    ->update(['status' => 'paid']);
            });
            
            // Manually handle Observer logic if status changed (simplified to avoid relationship issues)
            if ($oldStatus !== 'paid') {
                try {
                    // Update deposit refund if invoice is linked
                    if ($depositRefundId) {
                        try {
                            DepositRefund::withoutEvents(function() use ($depositRefundId) {
                                DB::table('deposit_refunds')
                                    ->where('id', $depositRefundId)
                                    ->where('status', '!=', DepositRefund::STATUS_PAID)
                                    ->update([
                                        'status' => DepositRefund::STATUS_PAID,
                                        'paid_at' => now(),
                                        'paid_by' => Auth::id()
                                    ]);
                            });
                        } catch (\Exception $e) {
                            Log::debug('Error updating deposit refund: ' . $e->getMessage());
                        }
                    }

                    // Update payroll payslip if invoice is linked
                    if ($payrollPayslipId) {
                        try {
                            PayrollPayslip::withoutEvents(function() use ($payrollPayslipId) {
                                DB::table('payroll_payslips')
                                    ->where('id', $payrollPayslipId)
                                    ->where('status', '!=', 'paid')
                                    ->update([
                                        'status' => 'paid',
                                        'paid_at' => now()
                                    ]);
                            });
                        } catch (\Exception $e) {
                            Log::debug('Error updating payroll payslip: ' . $e->getMessage());
                        }
                    }
                } catch (\Exception $observerError) {
                    // Log but don't fail the update
                    Log::warning('Error in manual Observer logic: ' . $observerError->getMessage(), [
                        'invoice_id' => $invoiceId
                    ]);
                }
            }

            // Get payment method ID for bank_transfer
            $paymentMethod = PaymentMethod::where('key_code', 'bank_qr')->first();
            $paymentMethodId = $paymentMethod ? $paymentMethod->id : null;
            
            // Auto-generate transaction_ref if not provided
            $transactionRef = $validated['transaction_ref'] ?? null;
            if (empty($transactionRef)) {
                $cashOutflow = new CashOutflow();
                $transactionRef = $cashOutflow->generateTransactionRef($organizationId, $paymentMethodId);
            }
            
            // Create cash outflow
            CashOutflow::create([
                'amount' => $totalAmount,
                'payment_method_id' => $paymentMethodId,
                'paid_at' => $validated['paid_at'],
                'status' => CashOutflow::STATUS_SUCCESS,
                'transaction_ref' => $transactionRef,
                'note' => $validated['note'] ?? "Thanh toán hóa đơn {$invoiceNo}",
                'company_invoice_id' => $invoiceId,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được đánh dấu là đã thanh toán!'
                ]);
            }

            return back()->with('success', 'Hóa đơn đã được đánh dấu là đã thanh toán!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors()
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@markAsPaid: ' . $e->getMessage(), [
                'invoice_id' => $companyInvoice->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đánh dấu thanh toán: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi đánh dấu thanh toán: ' . $e->getMessage());
        }
    }

    public function getSourceData(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - anyone with company_invoice.view can get source data
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        try {
            $invoiceType = $request->get('invoice_type');

            $data = [];

            switch ($invoiceType) {
                case 'master_lease':
                    $data = MasterLease::where('organization_id', $organizationId)
                        ->with(['property', 'landlord'])
                        ->get()
                        ->map(function($item) {
                            return [
                                'id' => $item->id,
                                'text' => ($item->contract_no ?? 'Hợp đồng #' . $item->id) . ' - ' . ($item->property->name ?? 'N/A'),
                                'amount' => $item->base_rent ?? 0
                            ];
                        });
                    break;

                case 'ticket':
                    $data = Ticket::where('organization_id', $organizationId)
                        ->with(['unit.property'])
                        ->get()
                        ->map(function($item) {
                            $propertyName = $item->unit && $item->unit->property ? $item->unit->property->name : 'N/A';
                            return [
                                'id' => $item->id,
                                'text' => "Ticket #{$item->id} - {$propertyName}",
                                'amount' => 0
                            ];
                        });
                    break;

                case 'deposit_refund':
                    $data = DepositRefund::where('organization_id', $organizationId)
                        ->with(['lease.tenant', 'lease.unit.property'])
                        ->get()
                        ->map(function($item) {
                            $tenantName = $item->lease && $item->lease->tenant ? $item->lease->tenant->full_name : 'N/A';
                            $propertyName = $item->lease && $item->lease->unit && $item->lease->unit->property ? $item->lease->unit->property->name : 'N/A';
                            return [
                                'id' => $item->id,
                                'text' => "Hoàn cọc - {$tenantName} - {$propertyName}",
                                'amount' => $item->refund_amount ?? 0
                            ];
                        });
                    break;

                case 'payroll_payslip':
                    $data = PayrollPayslip::whereHas('payrollCycle', function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    })->with(['user', 'payrollCycle'])->get()
                        ->map(function($item) {
                            $userName = $item->user ? $item->user->full_name : 'N/A';
                            $periodMonth = $item->payrollCycle ? $item->payrollCycle->period_month : 'N/A';
                            return [
                                'id' => $item->id,
                                'text' => "Lương {$userName} - Tháng {$periodMonth}",
                                'amount' => $item->net_amount ?? 0
                            ];
                        });
                    break;
            }

            // Convert collection to array if needed
            $dataArray = is_array($data) ? $data : $data->values()->all();
            
            return response()->json([
                'success' => true,
                'data' => $dataArray
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@getSourceData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu nguồn: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve invoice
     */
    public function approve(Request $request, CompanyInvoice $companyInvoice)
    {
        try {

            if ($companyInvoice->status !== 'pending') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể duyệt hóa đơn ở trạng thái chờ duyệt'
                    ], 400);
                }
                return back()->with('error', 'Chỉ có thể duyệt hóa đơn ở trạng thái chờ duyệt');
            }

            DB::beginTransaction();

            $companyInvoice->update(['status' => 'approved']);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được duyệt thành công!',
                    'status' => 'approved'
                ]);
            }

            return back()->with('success', 'Hóa đơn đã được duyệt thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@approve: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi duyệt hóa đơn: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi duyệt hóa đơn: ' . $e->getMessage());
        }
    }

    /**
     * Update invoice status (for cancelled status transitions)
     */
    public function updateStatus(Request $request, CompanyInvoice $companyInvoice)
    {
        try {
            $this->requireCapability('finance.company_invoice.update', 'Bạn không có quyền cập nhật Company Invoices.');
            
            // Check if invoice belongs to organization using checkOrganizationAccess
            // This method handles type conversion and logging properly
            $this->checkOrganizationAccess(
                $companyInvoice->organization_id,
                'Unauthorized access to company invoice.',
                'company_invoice',
                $companyInvoice->id
            );
            
            $validated = $request->validate([
                'status' => 'required|in:draft,pending,approved,overdue,paid,cancelled'
            ]);
            
            $newStatus = $validated['status'];
            $currentStatus = $companyInvoice->status;
            
            // Allow status transitions from draft to other statuses
            if ($currentStatus === 'draft') {
                if (!in_array($newStatus, ['pending', 'approved', 'cancelled'])) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Từ trạng thái "Nháp" chỉ có thể chuyển sang: Chờ duyệt, Đã duyệt, hoặc Đã hủy'
                        ], 400);
                    }
                    return back()->with('error', 'Từ trạng thái "Nháp" chỉ có thể chuyển sang: Chờ duyệt, Đã duyệt, hoặc Đã hủy');
                }
            } 
            // Allow status transitions from cancelled to other statuses
            else if ($currentStatus === 'cancelled') {
                if (!in_array($newStatus, ['draft', 'pending', 'approved'])) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Từ trạng thái "Đã hủy" chỉ có thể chuyển sang: Nháp, Chờ duyệt, hoặc Đã duyệt'
                        ], 400);
                    }
                    return back()->with('error', 'Từ trạng thái "Đã hủy" chỉ có thể chuyển sang: Nháp, Chờ duyệt, hoặc Đã duyệt');
                }
            } 
            // For other statuses, use existing validation logic
            else {
                if ($currentStatus === 'paid' && $newStatus !== 'paid') {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Không thể thay đổi trạng thái của hóa đơn đã thanh toán'
                        ], 400);
                    }
                    return back()->with('error', 'Không thể thay đổi trạng thái của hóa đơn đã thanh toán');
                }
            }
            
            DB::beginTransaction();
            
            $companyInvoice->update(['status' => $newStatus]);
            
            DB::commit();
            
            $statusLabels = [
                'draft' => 'Nháp',
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'overdue' => 'Quá hạn',
                'paid' => 'Đã thanh toán',
                'cancelled' => 'Đã hủy'
            ];
            
            $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Hóa đơn đã được chuyển sang trạng thái \"{$statusLabel}\" thành công!",
                    'status' => $newStatus
                ]);
            }
            
            return back()->with('success', "Hóa đơn đã được chuyển sang trạng thái \"{$statusLabel}\" thành công!");
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors()
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@updateStatus: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Cancel invoice
     */
    public function cancel(Request $request, CompanyInvoice $companyInvoice)
    {
        try {

            if (in_array($companyInvoice->status, ['paid', 'cancelled'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể hủy hóa đơn đã thanh toán hoặc đã hủy'
                    ], 400);
                }
                return back()->with('error', 'Không thể hủy hóa đơn đã thanh toán hoặc đã hủy');
            }

            DB::beginTransaction();

            $companyInvoice->update(['status' => 'cancelled']);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được hủy thành công!',
                    'status' => 'cancelled'
                ]);
            }

            return back()->with('success', 'Hóa đơn đã được hủy thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@cancel: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi hủy hóa đơn: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi hủy hóa đơn: ' . $e->getMessage());
        }
    }

    /**
     * Mark as overdue
     */
    public function markOverdue(Request $request, CompanyInvoice $companyInvoice)
    {
        try {

            if ($companyInvoice->status !== 'approved') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể đánh dấu quá hạn hóa đơn đã duyệt'
                    ], 400);
                }
                return back()->with('error', 'Chỉ có thể đánh dấu quá hạn hóa đơn đã duyệt');
            }

            DB::beginTransaction();

            $companyInvoice->update(['status' => 'overdue']);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được đánh dấu quá hạn!',
                    'status' => 'overdue'
                ]);
            }

            return back()->with('success', 'Hóa đơn đã được đánh dấu quá hạn!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@markOverdue: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đánh dấu quá hạn: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi đánh dấu quá hạn: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:approve,cancel,mark_overdue,delete',
                'invoice_ids' => 'required|array|min:1',
                'invoice_ids.*' => 'exists:company_invoices,id'
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->with('error', 'Dữ liệu không hợp lệ');
            }

            $action = $request->action;
            $invoiceIds = $request->invoice_ids;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($invoiceIds as $invoiceId) {
                try {
                    $invoice = CompanyInvoice::findOrFail($invoiceId);

                    switch ($action) {
                        case 'approve':
                            if ($invoice->status === 'pending') {
                                $invoice->update(['status' => 'approved']);
                                $successCount++;
                            } else {
                                $errorCount++;
                                $errors[] = "Hóa đơn {$invoice->invoice_no} không ở trạng thái chờ duyệt";
                            }
                            break;

                        case 'cancel':
                            if (!in_array($invoice->status, ['paid', 'cancelled'])) {
                                $invoice->update(['status' => 'cancelled']);
                                $successCount++;
                            } else {
                                $errorCount++;
                                $errors[] = "Hóa đơn {$invoice->invoice_no} không thể hủy";
                            }
                            break;

                        case 'mark_overdue':
                            if ($invoice->status === 'approved') {
                                $invoice->update(['status' => 'overdue']);
                                $successCount++;
                            } else {
                                $errorCount++;
                                $errors[] = "Hóa đơn {$invoice->invoice_no} không ở trạng thái đã duyệt";
                            }
                            break;

                        case 'delete':
                            if ($invoice->cashOutflows()->count() === 0) {
                                $invoice->delete();
                                $successCount++;
                            } else {
                                $errorCount++;
                                $errors[] = "Hóa đơn {$invoice->invoice_no} đã có thanh toán";
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Lỗi xử lý hóa đơn {$invoiceId}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Đã xử lý thành công {$successCount} hóa đơn";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} hóa đơn gặp lỗi";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@bulkAction: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xử lý hàng loạt: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xử lý hàng loạt: ' . $e->getMessage());
        }
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - manager can view all statistics
        $this->requireCapability('finance.company_invoice.view', 'Bạn không có quyền xem Company Invoices.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            $query = CompanyInvoice::where('organization_id', $organizationId);

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->where('issue_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('issue_date', '<=', $request->date_to);
            }

            $stats = [
                'total_count' => $query->count(),
                'total_amount' => $query->sum('total_amount'),
                'draft_count' => $query->clone()->where('status', 'draft')->count(),
                'pending_count' => $query->clone()->where('status', 'pending')->count(),
                'approved_count' => $query->clone()->where('status', 'approved')->count(),
                'paid_count' => $query->clone()->where('status', 'paid')->count(),
                'overdue_count' => $query->clone()->where('status', 'overdue')->count(),
                'cancelled_count' => $query->clone()->where('status', 'cancelled')->count(),
                'draft_amount' => $query->clone()->where('status', 'draft')->sum('total_amount'),
                'pending_amount' => $query->clone()->where('status', 'pending')->sum('total_amount'),
                'approved_amount' => $query->clone()->where('status', 'approved')->sum('total_amount'),
                'paid_amount' => $query->clone()->where('status', 'paid')->sum('total_amount'),
                'overdue_amount' => $query->clone()->where('status', 'overdue')->sum('total_amount'),
                'cancelled_amount' => $query->clone()->where('status', 'cancelled')->sum('total_amount'),
            ];

            // Statistics by type
            $typeStats = $query->clone()
                ->select('invoice_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total_amount'))
                ->groupBy('invoice_type')
                ->get();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'type_stats' => $typeStats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê'
            ], 500);
        }
    }

    /**
     * Show payment page for company invoice
     */
    public function payment(CompanyInvoice $companyInvoice)
    {
        try {
            // Check if invoice can be paid
            if ($companyInvoice->status === 'paid') {
                return redirect()->route('staff.company-invoices.show', $companyInvoice)
                    ->with('warning', 'Hóa đơn này đã được thanh toán');
            }

            if ($companyInvoice->status === 'cancelled') {
                return redirect()->route('staff.company-invoices.show', $companyInvoice)
                    ->with('error', 'Không thể thanh toán hóa đơn đã bị hủy');
            }

            return view('staff.finance.company-invoices.payment', compact('companyInvoice'));

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@payment: ' . $e->getMessage());
            return redirect()->route('staff.company-invoices.index')
                ->with('error', 'Có lỗi xảy ra khi tải trang thanh toán');
        }
    }

    /**
     * Process cash payment for company invoice
     */
    public function processCashPayment(Request $request, CompanyInvoice $companyInvoice)
    {
        try {
            // Validate document if provided
            $request->validate([
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            ]);

            DB::beginTransaction();

            // Get payment method ID for cash (create if not exists)
            $paymentMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'cash'],
                [
                    'name' => 'Tiền mặt',
                    'is_active' => true,
                    'description' => 'Thanh toán bằng tiền mặt'
                ]
            );
            $paymentMethodId = $paymentMethod->id;
            
            // Always generate transaction_ref using SequenceGenerator
            $orgId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? $companyInvoice->organization_id;
            $cashOutflow = new CashOutflow();
            $transactionRef = $cashOutflow->generateTransactionRef($orgId, $paymentMethodId);

            // Handle document upload - prepare for document attachment (not to transaction_ref)
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

            // Create cash outflow record
            $cashOutflow = CashOutflow::create([
                'amount' => $companyInvoice->total_amount,
                'payment_method_id' => $paymentMethodId,
                'status' => 'success',
                'paid_at' => now(),
                'company_invoice_id' => $companyInvoice->id,
                'transaction_ref' => $transactionRef,
                'note' => "Thanh toán hóa đơn công ty: {$companyInvoice->invoice_no}",
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

                    // Attach document to cash outflow using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow document: ' . $e->getMessage(), [
                        'cash_outflow_id' => $cashOutflow->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if document upload fails
                }
            }

            // Update invoice status
            $companyInvoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'paid_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán tiền mặt đã được tạo thành công',
                'payment_id' => $cashOutflow->id,
                'cash_outflow_id' => $cashOutflow->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@processCashPayment: ' . $e->getMessage(), [
                'invoice_id' => $companyInvoice->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý thanh toán tiền mặt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Sepay payment for company invoice
     */
    public function processSepayPayment(Request $request, CompanyInvoice $companyInvoice)
    {
        try {
            // Validate document if provided
            $request->validate([
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            ]);

            DB::beginTransaction();

            // Get recipient bank info (vendor or user)
            if ($companyInvoice->vendor_id) {
                $recipient = $companyInvoice->vendor->load('sepayBank');
                $recipientType = 'vendor';
                $recipientProfile = null;
                $recipientBanking = $recipient;
                $sepayBank = $recipient->sepayBank;
            } elseif ($companyInvoice->user_id) {
                $recipient = $companyInvoice->user->load('userProfile.sepayBank');
                $recipientType = 'user';
                $recipientProfile = $recipient->userProfile;
                $recipientBanking = $recipientProfile;
                $sepayBank = $recipientProfile?->sepayBank;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin người nhận'
                ], 400);
            }
            
            // Validate recipient has bank info
            if (!$recipient || !$recipientBanking || !$recipientBanking->sepay_bank_id || !$recipientBanking->account_number) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người nhận chưa có thông tin ngân hàng để thanh toán'
                ], 400);
            }

            // Generate QR code URL and expected content
            $invoiceNo = $companyInvoice->invoice_no ?? 'HDCT' . str_pad($companyInvoice->id, 6, '0', STR_PAD_LEFT);
            $content = "THANH TOAN HOA DON CT {$invoiceNo}";

            // Validate sepayBank relationship exists
            if (!$sepayBank || !$sepayBank->short_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thông tin ngân hàng không hợp lệ hoặc không được hỗ trợ'
                ], 400);
            }

            // Get payment method ID for sepay (create if not exists)
            $paymentMethod = PaymentMethod::firstOrCreate(
                ['key_code' => 'sepay'],
                [
                    'name' => 'SePay',
                    'is_active' => true,
                    'description' => 'Thanh toán qua SePay'
                ]
            );
            $paymentMethodId = $paymentMethod->id;
            
            // Always generate transaction_ref using SequenceGenerator
            $orgId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? $companyInvoice->organization_id;
            $cashOutflow = new CashOutflow();
            $transactionRef = $cashOutflow->generateTransactionRef($orgId, $paymentMethodId);

            // Handle document upload - prepare for document attachment (not to transaction_ref)
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

            // Create cash outflow record
            $cashOutflow = CashOutflow::create([
                'amount' => $companyInvoice->total_amount,
                'payment_method_id' => $paymentMethodId,
                'status' => 'pending',
                'company_invoice_id' => $companyInvoice->id,
                'paid_at' => now(),
                'transaction_ref' => $transactionRef,
                'note' => "Thanh toán hóa đơn công ty: {$companyInvoice->invoice_no}",
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

                    // Attach document to cash outflow using the new many-to-many relationship
                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    Log::error('Error uploading cash outflow document: ' . $e->getMessage(), [
                        'cash_outflow_id' => $cashOutflow->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if document upload fails
                }
            }
            
            // Get Sepay bank short_name for QR code
            $sepayBankName = $sepayBank->short_name;
            
            $qrParams = [
                'acc' => $recipientBanking->account_number,
                'bank' => $sepayBankName,
                'amount' => $companyInvoice->total_amount,
                'des' => $content
            ];

            $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query($qrParams);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán Sepay đã được tạo thành công',
                'payment_id' => $cashOutflow->id,
                'cash_outflow_id' => $cashOutflow->id,
                'qr_url' => $qrUrl,
                'bank_info' => [
                    'bank_name' => $sepayBank?->name,
                    'bank_code' => $sepayBank?->code,
                    'bank_short_name' => $sepayBank?->short_name,
                    'account_number' => $recipientBanking->account_number,
                    'account_name' => $recipientType === 'vendor' ? $recipient->name : $recipient->full_name,
                    'amount' => $companyInvoice->total_amount,
                    'content' => $content
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CompanyInvoiceController@processSepayPayment: ' . $e->getMessage(), [
                'invoice_id' => $companyInvoice->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý thanh toán Sepay: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, CompanyInvoice $companyInvoice, $paymentId)
    {
        try {
            $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id;
            
            // Query cash outflow through company_invoice relationship
            $cashOutflow = CashOutflow::where('id', $paymentId)
                ->where('company_invoice_id', $companyInvoice->id)
                ->whereHas('companyInvoice', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->with(['paymentMethod', 'companyInvoice'])
                ->first();

            // If AJAX request, return JSON
            if ($request->expectsJson()) {
                if (!$cashOutflow) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin thanh toán'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'payment' => [
                        'id' => $cashOutflow->id,
                        'status' => $cashOutflow->status,
                        'amount' => $cashOutflow->amount,
                        'payment_method' => $cashOutflow->paymentMethod ? $cashOutflow->paymentMethod->key_code : null,
                        'payment_method_name' => $cashOutflow->paymentMethod ? $cashOutflow->paymentMethod->name : null,
                        'note' => $cashOutflow->note,
                        'created_at' => $cashOutflow->created_at,
                        'updated_at' => $cashOutflow->updated_at,
                    ]
                ]);
            }

            // If regular request, return view
            return view('staff.finance.company-invoices.payment-status', [
                'companyInvoice' => $companyInvoice,
                'payment' => $cashOutflow
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@getPaymentStatus: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải trạng thái thanh toán'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải trạng thái thanh toán');
        }
    }

    /**
     * Show payment status page
     */
    public function paymentStatus(CompanyInvoice $companyInvoice, $paymentId)
    {
        try {
            $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id;
            
            // Query cash outflow through company_invoice relationship
            $payment = CashOutflow::where('id', $paymentId)
                ->where('company_invoice_id', $companyInvoice->id)
                ->whereHas('companyInvoice', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->with(['paymentMethod', 'companyInvoice'])
                ->first();

            return view('staff.finance.company-invoices.payment-status', [
                'companyInvoice' => $companyInvoice,
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            Log::error('Error in CompanyInvoiceController@paymentStatus: ' . $e->getMessage());
            return redirect()->route('staff.company-invoices.show', $companyInvoice)
                ->with('error', 'Có lỗi xảy ra khi tải trạng thái thanh toán');
        }
    }

    /**
     * Get Sepay-compatible bank name from SepayBank model
     */
    private function getSepayBankName($bankCode)
    {
        // Tìm ngân hàng theo mã ngân hàng
        $sepayBank = \App\Models\SepayBank::where('code', $bankCode)->first();
            
        if ($sepayBank) {
            return $sepayBank->sepay_name;
        }
        
        // Fallback mapping cho các mã ngân hàng
        $bankCodeMapping = [
            'MB' => 'MBBank',
            'ICB' => 'VietinBank',
            'VCB' => 'Vietcombank',
            'ACB' => 'ACB',
            'VPB' => 'VPBank',
            'TPB' => 'TPBank',
            'MSB' => 'MSB',
            'LPB' => 'LienVietPostBank',
            'VCCB' => 'VietCapitalBank',
            'BIDV' => 'BIDV',
            'STB' => 'Sacombank',
            'VIB' => 'VIB',
            'HDB' => 'HDBank',
            'SEAB' => 'SeABank',
            'VBA' => 'Agribank',
            'TCB' => 'Techcombank',
            'BAB' => 'BacABank',
            'ABB' => 'ABBANK',
            'EIB' => 'Eximbank',
            'PBVN' => 'PublicBank',
            'OCB' => 'OCB',
            'KLB' => 'KienLongBank',
            'SHBVN' => 'ShinhanBank',
            'VIETBANK' => 'VietBank',
            'BVB' => 'BaoVietBank',
            'SHB' => 'SHB',
            'SGICB' => 'Saigonbank',
            'DOB' => 'DongABank',
            'NAB' => 'NamABank',
            'PGB' => 'PGBank',
            'NCB' => 'NCB',
            'SCB' => 'SCB',
            'VAB' => 'VietABank',
            'GPB' => 'GPBank',
            'PVCB' => 'PVcomBank',
            'PVCBP' => 'PVcomBankPay',
            'Oceanbank' => 'Oceanbank',
            'VRB' => 'VRB',
            'IVB' => 'IndovinaBank',
            'CBB' => 'CBBank',
            'CIMB' => 'CIMB',
            'HSBC' => 'HSBC',
            'DBS' => 'DBSBank',
            'NHB HN' => 'Nonghyup',
            'HLBVN' => 'HongLeong',
            'IBK - HN' => 'IBK Bank',
            'IBK - HCM' => 'IBK Bank',
            'WVN' => 'Woori',
            'UOB' => 'UnitedOverseas',
            'KBHN' => 'KookminHN',
            'KBHCM' => 'KookminHCM',
            'COOPBANK' => 'COOPBANK',
            'SCVN' => 'StandardChartered',
        ];
        
        return $bankCodeMapping[$bankCode] ?? $bankCode;
    }
}