<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Lease;
use App\Models\BookingDeposit;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Optimized query with proper index order
            $organizationId = $this->getCurrentOrganizationId();
            
            // Check if user has billing.access capability
            $hasBillingAccess = $this->checkCapability('billing.access');
            if (!$hasBillingAccess) {
                abort(403, 'Bạn không có quyền truy cập module Thanh toán & Hóa đơn.');
            }

            // Check if user can view all invoices or only own invoices
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('billing.invoice');
            
            $query = Invoice::select([
                'invoices.*',
                'leases.contract_no as lease_contract_no',
                'units.code as unit_code',
                'properties.name as property_name'
            ])
            ->leftJoin('leases', 'invoices.lease_id', '=', 'leases.id')
            ->leftJoin('units', 'leases.unit_id', '=', 'units.id')
            ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
            ->leftJoin('booking_deposits', 'invoices.booking_deposit_id', '=', 'booking_deposits.id');
            
            // Apply filters in optimal order: organization_id -> deleted_at -> status
            if ($organizationId) {
                $query->where('invoices.organization_id', $organizationId); // Uses idx_invoices_org_deleted_status
            }
            
            // Tự động filter theo ownership nếu agent chỉ có view_own
            // Invoice filter qua lease.agent_id hoặc booking_deposit.agent_id
            if ($this->shouldFilterByOwnership('billing.invoice')) {
                // Lấy các hợp đồng mà agent này quản lý
                $managedLeaseIds = Lease::where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();

                // Lấy các booking deposits mà agent này quản lý
                $managedBookingIds = BookingDeposit::where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();

                $query->where(function($q) use ($managedLeaseIds, $managedBookingIds) {
                    $q->whereIn('invoices.lease_id', $managedLeaseIds)
                      ->orWhereIn('invoices.booking_deposit_id', $managedBookingIds);
                });
            }
            
            $query->whereNull('invoices.deleted_at'); // Uses idx_invoices_deleted_at_status
            $query->whereNull('leases.deleted_at'); // Uses idx_leases_deleted_at_status

            // Calculate statistics FIRST from base query (before any filters)
            // Query directly from Invoice model to ensure accurate statistics
            $statsQuery = Invoice::where('organization_id', $organizationId)
                ->whereNull('deleted_at');
            
            // Tự động filter theo ownership cho statistics
            if ($this->shouldFilterByOwnership('billing.invoice')) {
                $managedLeaseIds = Lease::where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();

                $managedBookingIds = BookingDeposit::where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();

                $statsQuery->where(function($q) use ($managedLeaseIds, $managedBookingIds) {
                    $q->whereIn('lease_id', $managedLeaseIds)
                      ->orWhereIn('booking_deposit_id', $managedBookingIds);
                });
            }
            
            // Count by status using database aggregation for accuracy
            $stats = [
                'total' => (int) (clone $statsQuery)->count(),
                'draft' => (int) (clone $statsQuery)->where('status', 'draft')->count(),
                'issued' => (int) (clone $statsQuery)->where('status', 'issued')->count(),
                'paid' => (int) (clone $statsQuery)->where('status', 'paid')->count(),
                'overdue' => (int) (clone $statsQuery)->where('status', 'overdue')->count(),
                'cancelled' => (int) (clone $statsQuery)->where('status', 'cancelled')->count(),
            ];

            // Search - keep whereHas for complex relationships, but ensure indexes are used
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoices.invoice_no', 'like', "%{$search}%")
                      ->orWhereHas('lease.tenant', function($tenantQuery) use ($search) {
                          $tenantQuery->where(function($userQuery) use ($search) {
                              $userQuery->where('email', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%")
                                       ->orWhereHas('userProfile', function($profileQuery) use ($search) {
                                           $profileQuery->where('full_name', 'like', "%{$search}%");
                                       });
                          });
                      })
                      ->orWhereHas('lease.unit.property', function($propertyQuery) use ($search) {
                          $propertyQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('bookingDeposit.tenantUser', function($tenantQuery) use ($search) {
                          $tenantQuery->where(function($userQuery) use ($search) {
                              $userQuery->where('email', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%")
                                       ->orWhereHas('userProfile', function($profileQuery) use ($search) {
                                           $profileQuery->where('full_name', 'like', "%{$search}%");
                                       });
                          });
                      })
                      ->orWhereHas('bookingDeposit.lead', function($leadQuery) use ($search) {
                          $leadQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                      })
                      ->orWhereHas('bookingDeposit.unit.property', function($propertyQuery) use ($search) {
                          $propertyQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by status - uses idx_invoices_deleted_at_status or idx_invoices_org_deleted_status
            if ($request->filled('status')) {
                $query->where('invoices.status', $request->status);
            }

            // Filter by invoice type
            if ($request->filled('invoice_type')) {
                $query->where('invoices.invoice_type', $request->invoice_type);
            }

            // Filter by lease - uses idx_invoices_org_lease_deleted
            if ($request->filled('lease_id')) {
                $query->where('invoices.lease_id', $request->lease_id);
            }

            // Filter by booking deposit
            if ($request->filled('booking_deposit_id')) {
                $query->where('invoices.booking_deposit_id', $request->booking_deposit_id);
            }

            // Filter by tenant_id
            if ($request->filled('tenant_id')) {
                $query->where(function($q) use ($request) {
                    $q->where('leases.tenant_id', $request->tenant_id)
                      ->orWhere('booking_deposits.tenant_user_id', $request->tenant_id);
                });
            }

            // Filter by date range - uses idx_invoices_issue_date
            if ($request->filled('date_from')) {
                $query->whereDate('invoices.issue_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('invoices.issue_date', '<=', $request->date_to);
            }

            // Filter by amount range
            if ($request->filled('amount_min')) {
                $query->where('invoices.total_amount', '>=', $request->amount_min);
            }
            if ($request->filled('amount_max')) {
                $query->where('invoices.total_amount', '<=', $request->amount_max);
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
            
            $query->orderBy('invoices.' . $sortBy, $sortOrder);

            $invoices = $query->paginate(10)->withQueryString();
            
            // Eager load relationships for display
            $invoices->load([
                'lease.unit.property',
                'lease.tenant',
                'bookingDeposit.unit.property',
                'bookingDeposit.tenantUser',
                'bookingDeposit.lead',
                'organization',
                'items',
                'payments.method'
            ]);
            
            // Auto-check and mark overdue invoices (only run if last check was more than 5 minutes ago)
            if ($organizationId) {
                $lastCheckKey = 'invoices_last_overdue_check_' . $organizationId;
                $lastCheck = Cache::get($lastCheckKey);
                
                if (!$lastCheck || Carbon::parse($lastCheck)->addMinutes(5)->isPast()) {
                    try {
                        $overdueInvoices = Invoice::where('organization_id', $organizationId)
                            ->where('status', 'issued')
                            ->whereNotNull('due_date')
                            ->where('due_date', '<', now())
                            ->whereNull('deleted_at')
                            ->get();
                        
                        if ($overdueInvoices->count() > 0) {
                            DB::beginTransaction();
                            try {
                                foreach ($overdueInvoices as $invoice) {
                                    $invoice->update([
                                        'status' => 'overdue',
                                    ]);
                                    
                                    Log::info('Invoice automatically marked as overdue', [
                                        'invoice_id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'due_date' => $invoice->due_date,
                                        'lease_id' => $invoice->lease_id,
                                        'booking_deposit_id' => $invoice->booking_deposit_id,
                                    ]);
                                }
                                DB::commit();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error('Error auto-marking overdue invoices: ' . $e->getMessage());
                            }
                        }
                        
                        Cache::put($lastCheckKey, now()->toDateTimeString(), now()->addHours(1));
                    } catch (\Exception $e) {
                        Log::error('Error checking overdue invoices: ' . $e->getMessage());
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in InvoiceController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if HTMX request
            $isHtmx = $request->header('HX-Request') === 'true';
            
            if ($isHtmx) {
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải danh sách hóa đơn: ' . $e->getMessage() . '</div>', 500);
            }
            
            // Legacy AJAX support
            if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải danh sách hóa đơn: ' . $e->getMessage()
                ], 500);
            }
            
            $invoices = Invoice::query()->paginate(10);
        }

        // Get filter data - ensure variables are always defined
        $leases = collect();
        $bookingDeposits = collect();
        $paymentMethods = collect();
        
        try {
            $organizationId = Auth::user()->organization_id ?? null;
            $leases = Lease::with(['unit.property', 'tenant'])
                ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
                ->when($organizationId, function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId); // Uses idx_leases_org_unit_deleted_status
                })
                ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leases: ' . $e->getMessage());
        }
        
        try {
            $organizationId = Auth::user()->organization_id ?? null;
            $bookingDeposits = BookingDeposit::with(['unit.property', 'tenantUser', 'lead'])
                ->whereNull('deleted_at')
                ->when($organizationId, function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId); // Uses idx_bd_organization_id
                })
                ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading booking deposits: ' . $e->getMessage());
        }
        
        try {
            $paymentMethods = PaymentMethod::all();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading payment methods: ' . $e->getMessage());
        }

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng cộng',
                'icon' => 'fa-list',
                'color' => 'primary',
                'filter' => '',
            ],
            'draft' => [
                'value' => $stats['draft'] ?? 0,
                'label' => 'Nháp',
                'icon' => 'fa-file-alt',
                'color' => 'warning',
                'filter' => 'draft',
            ],
            'issued' => [
                'value' => $stats['issued'] ?? 0,
                'label' => 'Đã phát hành',
                'icon' => 'fa-file-invoice',
                'color' => 'info',
                'filter' => 'issued',
            ],
            'paid' => [
                'value' => $stats['paid'] ?? 0,
                'label' => 'Đã thanh toán',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'paid',
            ],
            'overdue' => [
                'value' => $stats['overdue'] ?? 0,
                'label' => 'Quá hạn',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'danger',
                'filter' => 'overdue',
            ],
            'cancelled' => [
                'value' => $stats['cancelled'] ?? 0,
                'label' => 'Đã hủy',
                'icon' => 'fa-times',
                'color' => 'secondary',
                'filter' => 'cancelled',
            ],
        ];
        $currentStatus = $request->get('status', '');

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.billing.invoices.partials.table', [
                'invoices' => $invoices,
                'sortBy' => $request->get('sort_by', 'created_at'),
                'sortOrder' => $request->get('sort_order', 'desc')
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $request->get('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'invoices-table-container',
                'action' => route('staff.invoices.index'),
                'columns' => 6
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
                'table_html' => view('staff.billing.invoices.partials.table', compact('invoices', 'sortBy', 'sortOrder'))->render(),
                'stats_html' => view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => $currentStatus,
                    'filterKey' => 'status',
                    'onFilterClick' => 'filterByStatus',
                    'onClearClick' => 'clearAllFilters',
                    'columns' => 6
                ])->render(),
            ]);
        }

        return view('staff.billing.invoices.index', [
            'invoices' => $invoices,
            'leases' => $leases,
            'bookingDeposits' => $bookingDeposits,
            'paymentMethods' => $paymentMethods,
            'stats' => $stats ?? [
                'total' => 0,
                'draft' => 0,
                'issued' => 0,
                'paid' => 0,
                'overdue' => 0,
                'cancelled' => 0,
            ],
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statsFormatted' => $statsFormatted,
            'currentStatus' => $currentStatus
        ]);
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.create', 'Bạn không có quyền tạo hóa đơn.');
        
        // Check if user has billing.invoice.view capability (manager sees all)
        $canViewAll = $this->canViewAll('billing.invoice');
        $organizationId = $this->getCurrentOrganizationId();
        
        // Ensure variables are always defined
        $leases = collect();
        $bookingDeposits = collect();
        $paymentMethods = collect();
        $prefillData = null;
        $isFirstInvoice = $request->has('first_invoice') && $request->first_invoice == true;
        $isCycleInvoice = $request->has('cycle_invoice') && $request->cycle_invoice == true;
        $selectedLeaseId = $request->get('lease_id');
        
        // Lấy dữ liệu pre-fill từ session nếu có (cho hóa đơn đầu tiên)
        if ($isFirstInvoice && session()->has('first_invoice_prefill')) {
            $prefillData = session('first_invoice_prefill');
            session()->forget('first_invoice_prefill'); // Xóa sau khi lấy
        }
        
        // Lấy dữ liệu pre-fill từ session nếu có (cho hóa đơn chu kỳ)
        if ($isCycleInvoice && session()->has('cycle_invoice_prefill')) {
            $prefillData = session('cycle_invoice_prefill');
            session()->forget('cycle_invoice_prefill'); // Xóa sau khi lấy
        }
        
        // Lấy dữ liệu pre-fill từ session nếu có (cho hóa đơn từ booking deposit)
        $selectedBookingDepositId = $request->get('booking_deposit_id');
        if (session()->has('booking_deposit_invoice_prefill')) {
            $prefillData = session('booking_deposit_invoice_prefill');
            session()->forget('booking_deposit_invoice_prefill'); // Xóa sau khi lấy
        }
        
        // Nếu là hóa đơn thông thường và có lease_id, chỉ fill lease_id
        if (!$isFirstInvoice && !$isCycleInvoice && $selectedLeaseId && !$prefillData) {
            $prefillData = [
                'lease_id' => $selectedLeaseId,
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'draft',
                'currency' => 'VND',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'items' => [],
            ];
        }
        
        // Nếu có booking_deposit_id nhưng chưa có prefillData, tạo prefillData cơ bản
        if ($selectedBookingDepositId && !$prefillData) {
            $prefillData = [
                'booking_deposit_id' => $selectedBookingDepositId,
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'draft',
                'currency' => 'VND',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'items' => [],
            ];
        }
        
        try {
            if ($canViewAll) {
                // Manager sees all leases in organization
                $leases = Lease::with(['unit.property', 'tenant'])
                    ->whereNull('deleted_at')
                    ->when($organizationId, function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    })
                    ->get();
            } else {
                // Agent only sees managed leases
                $leases = Lease::with(['unit.property', 'tenant'])
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leases in create: ' . $e->getMessage());
        }
        
        try {
            if ($canViewAll) {
                // Manager sees all booking deposits in organization
                $bookingDeposits = BookingDeposit::with(['unit.property', 'tenantUser', 'lead'])
                    ->whereNull('deleted_at')
                    ->when($organizationId, function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    })
                    ->get();
            } else {
                // Agent only sees managed booking deposits
                $bookingDeposits = BookingDeposit::with(['unit.property', 'tenantUser', 'lead'])
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading booking deposits in create: ' . $e->getMessage());
        }
        
        try {
            $paymentMethods = PaymentMethod::all();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading payment methods in create: ' . $e->getMessage());
        }
        
        // Lấy danh sách manager và agent để hiển thị trong dropdown created_by
        $managersAndAgents = collect();
        try {
            $managersAndAgents = User::with('userProfile')
                ->whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->where('status', 'active')
                      ->whereNull('deleted_at');
                })
                ->whereHas('userRoles', function($q) {
                    $q->whereIn('key_code', ['manager', 'agent']);
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading managers and agents in create: ' . $e->getMessage());
        }
        
        // Tạo preview số hóa đơn
        $previewInvoiceNo = Invoice::generateInvoiceNumber($organizationId);

        return view('staff.billing.invoices.create', [
            'leases' => $leases,
            'bookingDeposits' => $bookingDeposits,
            'paymentMethods' => $paymentMethods,
            'managersAndAgents' => $managersAndAgents,
            'previewInvoiceNo' => $previewInvoiceNo,
            'prefillData' => $prefillData,
            'isFirstInvoice' => $isFirstInvoice,
            'isCycleInvoice' => $isCycleInvoice,
            'selectedLeaseId' => $selectedLeaseId
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.create', 'Bạn không có quyền tạo hóa đơn.');
        try {
            $validated = $request->validate([
                'lease_id' => 'nullable|exists:leases,id',
                'booking_deposit_id' => 'nullable|exists:booking_deposits,id',
                'invoice_no' => 'nullable|string|max:100|unique:invoices,invoice_no',
                'invoice_type' => 'nullable|in:monthly_rent,first_invoice,booking_deposit,other',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                // Status luôn là 'draft' khi tạo mới, không lấy từ form
                'status' => 'nullable|in:draft,issued,paid,overdue,cancelled',
                'subtotal' => 'required|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'note' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.item_type' => 'required|in:rent,service,meter,deposit,other',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'items.*.unit_price' => 'required|numeric', // Cho phép giá âm (cho item trừ tiền cọc)
                'items.*.amount' => 'required|numeric', // Cho phép số tiền âm (cho item trừ tiền cọc)
                'created_by' => 'nullable|exists:users,id',
            ]);

            // Ensure either lease_id or booking_deposit_id is provided
            if (empty($validated['lease_id']) && empty($validated['booking_deposit_id'])) {
                return back()->withInput()->with('error', 'Vui lòng chọn hợp đồng thuê hoặc đặt cọc.');
            }

            // For agent, check if lease/booking deposit is managed by them
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                if (!empty($validated['lease_id'])) {
                    $lease = Lease::where('id', $validated['lease_id'])
                        ->where('agent_id', $user->id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$lease) {
                        return back()->withInput()->with('error', 'Bạn không có quyền tạo hóa đơn cho hợp đồng này.');
                    }
                }
                
                if (!empty($validated['booking_deposit_id'])) {
                    $bookingDeposit = BookingDeposit::where('id', $validated['booking_deposit_id'])
                        ->where('agent_id', $user->id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$bookingDeposit) {
                        return back()->withInput()->with('error', 'Bạn không có quyền tạo hóa đơn cho đặt cọc này.');
                    }
                }
            }

            DB::beginTransaction();

            // Get organization from current user
            $currentUser = Auth::user();
            $organization = \App\Models\OrganizationUser::where('user_id', $currentUser->id)
                ->whereNull('deleted_at')
                ->first()?->organization;

            // Generate invoice number if not provided
            $invoiceNo = $validated['invoice_no'];
            if (empty($invoiceNo)) {
                // Get organization_id from lease, booking_deposit, or organization
                $orgId = null;
                if (!empty($validated['lease_id']) && isset($lease)) {
                    $orgId = $lease->organization_id ?? null;
                } elseif (!empty($validated['booking_deposit_id']) && isset($bookingDeposit)) {
                    $orgId = $bookingDeposit->organization_id ?? null;
                } elseif ($organization) {
                    $orgId = $organization->id;
                }
                $invoiceNo = Invoice::generateInvoiceNumber($orgId);
            }

            // Tự động tính due_date dựa trên invoice_payment_days
            // (Trừ booking_deposit có logic riêng với payment_due_date)
            $issueDate = \Carbon\Carbon::parse($validated['issue_date']);
            $dueDate = $validated['due_date']; // Giữ due_date từ form nếu user đã nhập
            
            // Nếu user không nhập due_date hoặc không có booking_deposit_id, tính tự động
            // Priority: Lease > Property > Organization Default Cycle
            if ((empty($validated['due_date']) || empty($validated['booking_deposit_id'])) && $organization) {
                // Check if invoice is for a lease
                if (!empty($validated['lease_id'])) {
                    $lease = \App\Models\Lease::find($validated['lease_id']);
                    $property = $lease ? $lease->property : null;
                    
                    if ($property) {
                        $invoicePaymentDays = $property->getEffectiveInvoicePaymentDays();
                    } else {
                        $invoicePaymentDays = $organization->getEffectiveInvoicePaymentDays();
                    }
                } else {
                    // No lease, use organization default
                    $invoicePaymentDays = $organization->getEffectiveInvoicePaymentDays();
                }
                
                $dueDate = $issueDate->copy()->addDays($invoicePaymentDays)->format('Y-m-d');
            }

            // Create invoice
            // Tự động điền created_by với user hiện tại nếu không được cung cấp
            $createdBy = $validated['created_by'] ?? $currentUser->id;
            
            // Determine invoice_type based on context
            $invoiceType = $validated['invoice_type'] ?? null;
            
            // Auto-detect invoice type if not provided
            if (!$invoiceType) {
                if (!empty($validated['booking_deposit_id']) && empty($validated['lease_id'])) {
                    // Booking deposit only
                    $invoiceType = Invoice::TYPE_BOOKING_DEPOSIT;
                } elseif (!empty($validated['booking_deposit_id']) && !empty($validated['lease_id'])) {
                    // Both booking and lease = first invoice with deposit deduction
                    $invoiceType = Invoice::TYPE_FIRST_INVOICE;
                } elseif ($request->has('cycle_invoice') && $request->cycle_invoice == true) {
                    // Cycle invoice
                    $invoiceType = Invoice::TYPE_MONTHLY_RENT;
                } elseif ($request->has('first_invoice') && $request->first_invoice == true) {
                    // First invoice
                    $invoiceType = Invoice::TYPE_FIRST_INVOICE;
                } else {
                    // Default to other
                    $invoiceType = Invoice::TYPE_OTHER;
                }
            }
            
            $invoice = Invoice::create([
                'organization_id' => $organization?->id,
                'is_auto_created' => false, // Manual invoice creation
                'lease_id' => $validated['lease_id'],
                'booking_deposit_id' => $validated['booking_deposit_id'],
                'invoice_no' => $invoiceNo,
                'invoice_type' => $invoiceType,
                'issue_date' => $validated['issue_date'],
                'due_date' => $dueDate,
                'status' => 'draft', // Luôn tạo hóa đơn ở trạng thái nháp
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'] ?? 'VND',
                'note' => $validated['note'],
                'created_by' => $createdBy,
            ]);

            // Add invoice items
            foreach ($validated['items'] as $itemData) {
                $invoice->items()->create([
                    'item_type' => $itemData['item_type'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'amount' => $itemData['amount'],
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được tạo thành công!',
                    'redirect' => route('staff.invoices.show', $invoice->id)
                ]);
            }

            return redirect()->route('staff.invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo hóa đơn: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo hóa đơn: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has billing.access capability
        $hasBillingAccess = $this->checkCapability('billing.access');
        if (!$hasBillingAccess) {
            abort(403, 'Bạn không có quyền truy cập module Thanh toán & Hóa đơn.');
        }
        
        $invoice = Invoice::with([
            'lease.unit.property.propertyType',
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.tenant',
            'lease.agent',
            'bookingDeposit.unit.property.propertyType',
            'bookingDeposit.unit.property.location',
            'bookingDeposit.unit.property.location2025',
            'bookingDeposit.tenantUser',
            'bookingDeposit.lead',
            'bookingDeposit.agent',
            'organization',
            'items',
            'payments.method',
            'payments.payerUser'
        ])->findOrFail($id);

        // For agent, check if invoice belongs to managed lease/booking deposit
        $canViewAll = $this->canViewAll('billing.invoice');
        if (!$canViewAll) {
            $hasAccess = false;
            if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                $hasAccess = true;
            } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền xem hóa đơn này.');
            }
        }

        return view('staff.billing.invoices.show', compact('invoice'));
    }

    /**
     * Download invoice PDF
     */
    public function download($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has billing.access capability
        $hasBillingAccess = $this->checkCapability('billing.access');
        if (!$hasBillingAccess) {
            abort(403, 'Bạn không có quyền truy cập module Thanh toán & Hóa đơn.');
        }
        
        $invoice = Invoice::with([
            'organization',
            'lease.unit.property.location',
            'lease.unit.property.location2025',
            'lease.leaseServiceSet.items.service',
            'lease.tenant',
            'lease.agent',
            'bookingDeposit.unit.property.location',
            'bookingDeposit.unit.property.location2025',
            'bookingDeposit.tenantUser',
            'bookingDeposit.lead',
            'bookingDeposit.agent',
            'items'
        ])->findOrFail($id);

        // For agent, check if invoice belongs to managed lease/booking deposit
        $canViewAll = $this->canViewAll('billing.invoice');
        if (!$canViewAll) {
            $hasAccess = false;
            if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                $hasAccess = true;
            } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền tải hóa đơn này.');
            }
        }

        // Generate PDF using facade
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice'));
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);
        
        // Return PDF download
        $filename = 'hoa-don-' . $invoice->invoice_no . '.pdf';
        return $pdf->download($filename);
    }

    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.update', 'Bạn không có quyền cập nhật hóa đơn.');
        
        $invoice = Invoice::with([
            'lease.unit.property',
            'lease.tenant',
            'items'
        ])->findOrFail($id);

        // For agent, check if invoice belongs to managed lease/booking deposit
        $canViewAll = $this->canViewAll('billing.invoice');
        if (!$canViewAll) {
            $hasAccess = false;
            if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                $hasAccess = true;
            } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Bạn không có quyền chỉnh sửa hóa đơn này.');
            }
        }

        // Prevent editing if invoice is already paid
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Không thể chỉnh sửa hóa đơn đã thanh toán');
        }

        // Ensure variables are always defined
        $leases = collect();
        $paymentMethods = collect();
        
        try {
            $organizationId = $this->getCurrentOrganizationId();
            if ($canViewAll) {
                // Manager sees all leases
                $leases = Lease::with(['unit.property', 'tenant'])
                    ->whereNull('deleted_at')
                    ->when($organizationId, function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    })
                    ->get();
            } else {
                // Agent only sees managed leases
                $leases = Lease::with(['unit.property', 'tenant'])
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leases in edit: ' . $e->getMessage());
        }
        
        // Ensure variables are always defined
        $bookingDeposits = collect();
        
        try {
            if ($canViewAll) {
                // Manager sees all booking deposits in organization
                $bookingDeposits = BookingDeposit::with(['unit.property', 'tenantUser', 'lead'])
                    ->whereNull('deleted_at')
                    ->when($organizationId, function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    })
                    ->get();
            } else {
                // Agent only sees managed booking deposits
                $bookingDeposits = BookingDeposit::with(['unit.property', 'tenantUser', 'lead'])
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading booking deposits in edit: ' . $e->getMessage());
        }
        
        try {
            $paymentMethods = PaymentMethod::all();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading payment methods in edit: ' . $e->getMessage());
        }
        
        // Lấy danh sách manager và agent để hiển thị trong dropdown created_by
        $managersAndAgents = collect();
        try {
            $managersAndAgents = User::with('userProfile')
                ->whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->where('status', 'active')
                      ->whereNull('deleted_at');
                })
                ->whereHas('userRoles', function($q) {
                    $q->whereIn('key_code', ['manager', 'agent']);
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading managers and agents in edit: ' . $e->getMessage());
        }

        return view('staff.billing.invoices.edit', [
            'invoice' => $invoice,
            'leases' => $leases,
            'bookingDeposits' => $bookingDeposits,
            'paymentMethods' => $paymentMethods,
            'managersAndAgents' => $managersAndAgents
        ]);
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.update', 'Bạn không có quyền cập nhật hóa đơn.');
        try {
            $invoice = Invoice::findOrFail($id);

            // For agent, check if invoice belongs to managed lease/booking deposit
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                $hasAccess = false;
                if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                    $hasAccess = true;
                } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền cập nhật hóa đơn này.');
                }
            }

            // Prevent editing if invoice is already paid
            if ($invoice->status === 'paid') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể chỉnh sửa hóa đơn đã thanh toán'
                    ], 400);
                }
                return back()->with('error', 'Không thể chỉnh sửa hóa đơn đã thanh toán');
            }

            $validated = $request->validate([
                'lease_id' => 'nullable|exists:leases,id',
                'booking_deposit_id' => 'nullable|exists:booking_deposits,id',
                // Không cho phép thay đổi số hóa đơn - bỏ validation và giữ nguyên số cũ
                // 'invoice_no' => 'nullable|string|max:100|unique:invoices,invoice_no,' . $id,
                'invoice_type' => 'nullable|in:monthly_rent,first_invoice,booking_deposit,other',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'status' => 'required|in:draft,issued,paid,overdue,cancelled',
                'subtotal' => 'required|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'note' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.item_type' => 'required|in:rent,service,meter,deposit,other',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'items.*.unit_price' => 'required|numeric', // Cho phép giá âm (cho item trừ tiền cọc)
                'items.*.amount' => 'required|numeric', // Cho phép số tiền âm (cho item trừ tiền cọc)
                'created_by' => 'nullable|exists:users,id',
            ]);

            DB::beginTransaction();

            // Ensure either lease_id or booking_deposit_id is provided
            if (empty($validated['lease_id']) && empty($validated['booking_deposit_id'])) {
                return back()->withInput()->with('error', 'Vui lòng chọn hợp đồng thuê hoặc đặt cọc.');
            }

            // Update invoice
            // Tự động điền created_by với user hiện tại nếu không được cung cấp
            $currentUser = Auth::user();
            $createdBy = $validated['created_by'] ?? $invoice->created_by ?? $currentUser->id;
            
            $invoice->update([
                'lease_id' => $validated['lease_id'],
                'booking_deposit_id' => $validated['booking_deposit_id'],
                'invoice_no' => $invoice->invoice_no, // Keep existing invoice_no, don't allow changes
                'invoice_type' => $validated['invoice_type'] ?? $invoice->invoice_type,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'] ?? 'VND',
                'note' => $validated['note'],
                'created_by' => $createdBy,
            ]);

            // Update invoice items
            $invoice->items()->delete();
            foreach ($validated['items'] as $itemData) {
                $invoice->items()->create([
                    'item_type' => $itemData['item_type'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'amount' => $itemData['amount'],
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được cập nhật thành công!',
                    'redirect' => route('staff.invoices.show', $invoice->id)
                ]);
            }

            return redirect()->route('staff.invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật hóa đơn: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật hóa đơn: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.delete', 'Bạn không có quyền xóa hóa đơn.');
        try {
            $invoice = Invoice::findOrFail($id);
            
            // For agent, check if invoice belongs to managed lease/booking deposit
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                $hasAccess = false;
                if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                    $hasAccess = true;
                } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền xóa hóa đơn này.');
                }
            }

            // Prevent deletion if invoice is already paid
            if ($invoice->status === 'paid') {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa hóa đơn đã thanh toán'
                    ], 400);
                }
                return back()->with('error', 'Không thể xóa hóa đơn đã thanh toán');
            }
            
            // Soft delete the invoice (trait sẽ tự động set deleted_by)
            $invoice->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.invoices.index')
                ->with('success', 'Hóa đơn đã được xóa thành công!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting invoice: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa hóa đơn: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa hóa đơn: ' . $e->getMessage());
        }
    }

    // API method to get lease details for invoice
    public function getLeaseDetails($leaseId)
    {
        try {
            $lease = Lease::with([
                'unit.property',
                'tenant',
                'leaseServiceSet.items.service'
            ])->findOrFail($leaseId);

            $effectiveSet = $lease->getEffectiveLeaseServiceSet();
            
            // Load items with service relationship if set exists
            if ($effectiveSet && !$effectiveSet->relationLoaded('items')) {
                $effectiveSet->load('items.service');
            }

            $services = collect();
            if ($effectiveSet && $effectiveSet->items && $effectiveSet->items->count() > 0) {
                $services = $effectiveSet->items->map(function($item) {
                    // Ensure service relationship is loaded
                    if (!$item->relationLoaded('service')) {
                        $item->load('service');
                    }
                    
                    // Skip if service doesn't exist
                    if (!$item->service) {
                        return null;
                    }
                    
                    return [
                        'service_id' => $item->service_id,
                        'service_name' => $item->service->name ?? 'N/A',
                        'price' => $item->price ?? 0,
                        'pricing_type' => $item->service->pricing_type ?? 'fixed',
                        'unit_label' => $item->service->unit_label ?? 'tháng'
                    ];
                })->filter(function($service) {
                    // Filter out null values and services without service_id
                    return $service !== null && !empty($service['service_id']);
                });
            }

            return response()->json([
                'success' => true,
                'lease' => $lease,
                'rent_amount' => $lease->rent_amount,
                'services' => $services
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading lease details for invoice: ' . $e->getMessage(), [
                'lease_id' => $leaseId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dịch vụ từ hợp đồng: ' . $e->getMessage(),
                'services' => collect()
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.update', 'Bạn không có quyền cập nhật hóa đơn.');
        
        try {
            $invoice = Invoice::findOrFail($id);
            
            // For agent, check if invoice belongs to managed lease/booking deposit
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                $hasAccess = false;
                if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                    $hasAccess = true;
                } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền cập nhật hóa đơn này.');
                }
            }
            
            if ($invoice->status === 'paid') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hóa đơn này đã được đánh dấu là đã thanh toán.'
                    ], 400);
                }
                return back()->with('error', 'Hóa đơn này đã được đánh dấu là đã thanh toán.');
            }
            
            DB::beginTransaction();
            
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được đánh dấu là đã thanh toán thành công!',
                    'redirect' => route('staff.invoices.show', $invoice->id)
                ]);
            }
            
            return redirect()->route('staff.invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn đã được đánh dấu là đã thanh toán thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error marking invoice as paid: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đánh dấu hóa đơn: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Có lỗi xảy ra khi đánh dấu hóa đơn: ' . $e->getMessage());
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.update', 'Bạn không có quyền cập nhật hóa đơn.');
        
        try {
            $invoice = Invoice::findOrFail($id);
            
            // For agent, check if invoice belongs to managed lease/booking deposit
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                $hasAccess = false;
                if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                    $hasAccess = true;
                } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền cập nhật hóa đơn này.');
                }
            }
            
            $validated = $request->validate([
                'status' => 'required|in:draft,issued,paid,overdue,cancelled',
            ]);
            
            $oldStatus = $invoice->status;
            $newStatus = $validated['status'];
            
            // Validate status transition
            $allowedTransitions = [
                'draft' => ['issued', 'cancelled'],
                'issued' => ['paid', 'overdue', 'cancelled'],
                'paid' => [], // Cannot change from paid
                'overdue' => ['paid', 'cancelled'],
                'cancelled' => [], // Cannot change from cancelled
            ];
            
            if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Không thể chuyển từ trạng thái '{$oldStatus}' sang '{$newStatus}'."
                    ], 400);
                }
                return back()->with('error', "Không thể chuyển từ trạng thái '{$oldStatus}' sang '{$newStatus}'.");
            }
            
            DB::beginTransaction();
            
            $updateData = ['status' => $newStatus];
            
            // Set paid_at when status is paid
            if ($newStatus === 'paid') {
                $updateData['paid_at'] = now();
            }
            
            // Set issue_date when status is issued
            if ($newStatus === 'issued' && $oldStatus === 'draft') {
                $updateData['issue_date'] = now()->format('Y-m-d'); // Ensure date format, not datetime
            }
            
            $invoice->update($updateData);
            
            DB::commit();
            
            $statusLabels = [
                'draft' => 'Nháp',
                'issued' => 'Đã phát hành',
                'paid' => 'Đã thanh toán',
                'overdue' => 'Quá hạn',
                'cancelled' => 'Đã hủy',
            ];
            
            $message = "Trạng thái hóa đơn đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$newStatus]}'.";
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $newStatus,
                    'statusLabel' => $statusLabels[$newStatus] ?? $newStatus
                ]);
            }
            
            return redirect()->route('staff.invoices.show', $invoice->id)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating invoice status: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'error' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'Có lỗi xảy ra khi thay đổi trạng thái hóa đơn: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Issue invoice (change status from draft to issued)
     */
    public function issueInvoice(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.update', 'Bạn không có quyền cập nhật hóa đơn.');
        
        try {
            $invoice = Invoice::findOrFail($id);
            
            // For agent, check if invoice belongs to managed lease/booking deposit
            $canViewAll = $this->canViewAll('billing.invoice');
            if (!$canViewAll) {
                $hasAccess = false;
                if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
                    $hasAccess = true;
                } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
                    $hasAccess = true;
                }
                
                if (!$hasAccess) {
                    abort(403, 'Bạn không có quyền cập nhật hóa đơn này.');
                }
            }
            
            if ($invoice->status !== 'draft') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể phát hành hóa đơn ở trạng thái nháp.'
                    ], 400);
                }
                return back()->with('error', 'Chỉ có thể phát hành hóa đơn ở trạng thái nháp.');
            }
            
            DB::beginTransaction();
            
            $invoice->update([
                'status' => 'issued',
                'issue_date' => now()->format('Y-m-d') // Ensure date format, not datetime
            ]);
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn đã được phát hành thành công!',
                    'redirect' => route('staff.invoices.show', $invoice->id)
                ]);
            }
            
            return redirect()->route('staff.invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn đã được phát hành thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error issuing invoice: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi phát hành hóa đơn: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Có lỗi xảy ra khi phát hành hóa đơn: ' . $e->getMessage());
        }
    }
}
