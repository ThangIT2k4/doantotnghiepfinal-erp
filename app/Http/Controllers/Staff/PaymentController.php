<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Traits\ChecksCapabilities;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use ChecksCapabilities;
    
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check capability
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền xem thanh toán.');

        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $user = Auth::user();
        
        // Optimized query using JOINs and proper index order
        // Kiểm tra invoice thuộc organization qua nhiều cách (tương tự như store và getInvoiceDetails)
        $query = Payment::select([
            'payments.*',
            'invoices.invoice_no',
            'invoices.lease_id',
            'invoices.organization_id',
            'payer_profiles.full_name as payer_name',
            'payment_methods.name as method_name'
        ])
        ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
        ->leftJoin('users as payer_users', 'payments.payer_user_id', '=', 'payer_users.id')
        ->leftJoin('user_profiles as payer_profiles', 'payer_users.id', '=', 'payer_profiles.user_id')
        ->leftJoin('payment_methods', 'payments.method_id', '=', 'payment_methods.id')
        ->whereHas('invoice', function($invoiceQ) use ($organizationId) {
            $invoiceQ->where(function($q) use ($organizationId) {
                // Invoice có organization_id trực tiếp
                $q->where('invoices.organization_id', $organizationId)
                // Hoặc invoice từ lease
                ->orWhereHas('lease', function($leaseQ) use ($organizationId) {
                    $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                        $pq->where('organization_id', $organizationId);
                    });
                })
                // Hoặc invoice từ booking deposit
                ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                    $bookingQ->where('organization_id', $organizationId);
                });
            })
            ->whereNull('invoices.deleted_at');
        });
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        // Payment filter qua invoice.lease.agent_id hoặc invoice.booking_deposit.agent_id
        if ($this->shouldFilterByOwnership('billing.payment')) {
            $query->where(function($q) use ($user) {
                $q->whereHas('invoice', function($invoiceQ) use ($user) {
                    $invoiceQ->where(function($invSubQ) use ($user) {
                        $invSubQ->whereHas('lease', function($leaseQ) use ($user) {
                            $leaseQ->where('agent_id', $user->id);
                        })->orWhereHas('bookingDeposit', function($bookingQ) use ($user) {
                            $bookingQ->where('agent_id', $user->id);
                        });
                    });
                });
            });
        }
        
        // Apply filters in optimal order: organization_id -> deleted_at -> status
        $query->whereNull('payments.deleted_at') // Uses idx_payments_invoice_deleted_status
              ->whereNull('invoices.deleted_at'); // Uses idx_invoices_deleted_at_status

        // Calculate statistics FIRST from base query (before any filters)
        // Query directly from Payment model to ensure accurate statistics
        // Kiểm tra qua invoice với đầy đủ các trường hợp (tương tự như query chính)
        $statsQuery = Payment::whereHas('invoice', function($invoiceQ) use ($organizationId) {
            $invoiceQ->where(function($q) use ($organizationId) {
                // Invoice có organization_id trực tiếp
                $q->where('invoices.organization_id', $organizationId)
                // Hoặc invoice từ lease
                ->orWhereHas('lease', function($leaseQ) use ($organizationId) {
                    $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                        $pq->where('organization_id', $organizationId);
                    });
                })
                // Hoặc invoice từ booking deposit
                ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                    $bookingQ->where('organization_id', $organizationId);
                });
            })
            ->whereNull('invoices.deleted_at');
        })
        ->whereNull('deleted_at');
        
        // Count by status using database aggregation for accuracy
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) (clone $statsQuery)->where('status', Payment::STATUS_PENDING)->count(),
            'success' => (int) (clone $statsQuery)->where('status', Payment::STATUS_SUCCESS)->count(),
            'failed' => (int) (clone $statsQuery)->where('status', Payment::STATUS_FAILED)->count(),
            'refunded' => (int) (clone $statsQuery)->where('status', Payment::STATUS_REFUNDED)->count(),
        ];

        // Apply filters - uses idx_payments_invoice_deleted_status
        if ($request->filled('status')) {
            $query->where('payments.status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payments.txn_ref', 'like', "%{$search}%")
                  ->orWhere('payments.note', 'like', "%{$search}%")
                  ->orWhere('payer_profiles.full_name', 'like', "%{$search}%")
                  ->orWhere('payer_users.email', 'like', "%{$search}%")
                  ->orWhere('invoices.invoice_no', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map sort fields to actual database columns
        $sortFields = [
            'created_at' => 'payments.created_at',
            'paid_at' => 'payments.paid_at',
            'amount' => 'payments.amount',
            'status' => 'payments.status',
            'invoice_no' => 'invoices.invoice_no',
        ];
        
        $sortField = $sortFields[$sortBy] ?? 'payments.created_at';
        $query->orderBy($sortField, $sortOrder);
        
        $payments = $query->paginate(20);
        
        // Eager load relationships for display
        $payments->load([
            'payerUser', 
            'method', 
            'invoice.lease' => function($q) {
                $q->withTrashed()->with(['property', 'tenant']);
            },
            'invoice.bookingDeposit.tenantUser'
        ]);

        $statuses = [
            Payment::STATUS_PENDING => 'Chờ thanh toán',
            Payment::STATUS_SUCCESS => 'Thành công',
            Payment::STATUS_FAILED => 'Thất bại',
            Payment::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];

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
                'label' => 'Chờ thanh toán',
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
            'refunded' => [
                'value' => $stats['refunded'] ?? 0,
                'label' => 'Đã hoàn tiền',
                'icon' => 'fa-undo',
                'color' => 'info',
                'filter' => 'refunded',
            ],
        ];

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.billing.payments.partials.table', [
                'payments' => $payments,
                'statuses' => $statuses,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'payments-table-container',
                'action' => route('staff.payments.index'),
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
                'table_html' => view('staff.billing.payments.partials.table', [
                    'payments' => $payments,
                    'statuses' => $statuses,
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
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ]
            ]);
        }

        return view('staff.billing.payments.index', compact(
            'payments', 
            'statuses', 
            'stats', 
            'statsFormatted',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Check capability
        $this->requireCapability('billing.payment.create', 'Bạn không có quyền tạo thanh toán.');

        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        // Lấy invoice_id từ query parameter nếu có
        $selectedInvoiceId = $request->get('invoice_id');
        $selectedInvoice = null;
        
        if ($selectedInvoiceId) {
            $selectedInvoice = Invoice::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->with([
                    'lease' => function($q) {
                        $q->withTrashed()->with(['property', 'tenant']);
                    },
                    'bookingDeposit.tenantUser',
                    'bookingDeposit.lead',
                    'bookingDeposit.unit.property'
                ])
                ->find($selectedInvoiceId);
        }
        
        // Tự động lấy khách hàng có liên quan đến hóa đơn chưa thanh toán
        $users = $this->getRelevantUsers($organizationId);

        // Lấy danh sách leads cho dropdown
        $leads = collect();
        try {
            $leads = \App\Models\Lead::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leads in payment create: ' . $e->getMessage());
        }

        $invoices = Invoice::where(function($q) use ($organizationId) {
            // Hóa đơn từ lease
            $q->whereHas('lease', function($leaseQ) use ($organizationId) {
                $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                    $pq->where('organization_id', $organizationId);
                });
            })
            // Hoặc hóa đơn từ booking deposit
            ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                $bookingQ->where('organization_id', $organizationId);
            });
        })
        ->where('organization_id', $organizationId) // Đảm bảo invoice thuộc tổ chức
        ->whereNull('deleted_at') // Loại bỏ invoice đã bị xóa
        ->where('invoices.status', 'issued') // Chỉ hiển thị hóa đơn đã phát hành
        ->with([
            'user', 
            'lease' => function($q) {
                $q->withTrashed()->with(['property', 'tenant']);
            }, 
            'bookingDeposit.tenantUser',
            'bookingDeposit.lead',
            'bookingDeposit.unit.property'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        $paymentMethods = PaymentMethod::all();

        $statuses = [
            Payment::STATUS_PENDING => 'Chờ thanh toán',
            Payment::STATUS_SUCCESS => 'Thành công',
            Payment::STATUS_FAILED => 'Thất bại',
            Payment::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];

        return view('staff.billing.payments.create', compact(
            'users',
            'invoices',
            'paymentMethods',
            'statuses',
            'selectedInvoice',
            'leads'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check capability
        $this->requireCapability('billing.payment.create', 'Bạn không có quyền tạo thanh toán.');

        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }

        // Status luôn là 'pending' khi tạo mới
        $request->merge(['status' => $request->input('status', Payment::STATUS_PENDING)]);
        
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'method_id' => 'nullable|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'required|date',
            'txn_ref' => 'nullable|string|max:150',
            'status' => [
                'required',
                Rule::in([
                    Payment::STATUS_PENDING,
                    Payment::STATUS_SUCCESS,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_REFUNDED,
                ])
            ],
            'payer_user_id' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'note' => 'nullable|string',
        ]);

        // Validate that at least one of payer_user_id or lead_id is provided
        if (empty($validated['payer_user_id']) && empty($validated['lead_id'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.'
                ], 422);
            }
            return back()->withInput()->with('error', 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.');
        }

        // Verify invoice belongs to organization
        // Kiểm tra cả invoice từ lease và booking deposit
        $invoice = Invoice::where('id', $validated['invoice_id'])
            ->where(function($q) use ($organizationId) {
                // Hóa đơn từ lease
                $q->whereHas('lease', function($leaseQ) use ($organizationId) {
                    $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                        $pq->where('organization_id', $organizationId);
                    });
                })
                // Hoặc hóa đơn từ booking deposit
                ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                    $bookingQ->where('organization_id', $organizationId);
                })
                // Hoặc invoice có organization_id trực tiếp
                ->orWhere('organization_id', $organizationId);
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$invoice) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo thanh toán cho hóa đơn này.'
                ], 403);
            }
            return back()->withInput()->with('error', 'Bạn không có quyền tạo thanh toán cho hóa đơn này.');
        }

        try {
            DB::beginTransaction();

            $payment = Payment::create([
                ...$validated,
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
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error uploading payment image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            // Update invoice status if payment is successful
            // Note: PaymentObserver sẽ tự động cập nhật invoice và booking deposit
            if ($payment->status === Payment::STATUS_SUCCESS) {
                $payment->invoice->refresh();
                if ($payment->invoice->status !== 'paid') {
                    $payment->invoice->update(['status' => 'paid']);
                }
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được tạo thành công!',
                    'redirect' => route('staff.payments.show', $payment)
                ]);
            }

            return redirect()->route('staff.payments.show', $payment)
                ->with('success', 'Thanh toán đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo thanh toán: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can access this payment (organization + ownership check)
     */
    protected function canAccessPayment(Payment $payment): bool
    {
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return false;
        }
        
        // Sử dụng query builder để kiểm tra payment có thuộc organization không
        // Tương tự như logic trong method index, store và getInvoiceDetails
        // Kiểm tra qua invoice của payment
        $paymentExists = Payment::where('payments.id', $payment->id)
            ->whereHas('invoice', function($invoiceQ) use ($organizationId) {
                $invoiceQ->where(function($q) use ($organizationId) {
                    // Invoice có organization_id trực tiếp
                    $q->where('invoices.organization_id', $organizationId)
                    // Hoặc invoice từ lease
                    ->orWhereHas('lease', function($leaseQ) use ($organizationId) {
                        $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                            $pq->where('organization_id', $organizationId);
                        });
                    })
                    // Hoặc invoice từ booking deposit
                    ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                        $bookingQ->where('organization_id', $organizationId);
                    });
                })
                ->whereNull('invoices.deleted_at');
            })
            ->whereNull('payments.deleted_at')
            ->exists();
        
        if (!$paymentExists) {
            return false;
        }
        
        // If user can view all (manager), allow access
        if (!$this->shouldFilterByOwnership('billing.payment')) {
            return true;
        }
        
        // Agent: check if payment belongs to their managed invoice
        $user = Auth::user();
        $payment->load(['invoice.lease', 'invoice.bookingDeposit']);
        $invoice = $payment->invoice;
        
        if (!$invoice) {
            return false;
        }
        
        // Check if invoice belongs to agent's lease or booking deposit
        if ($invoice->lease && $invoice->lease->agent_id === $user->id) {
            return true;
        }
        
        if ($invoice->bookingDeposit && $invoice->bookingDeposit->agent_id === $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.view', 'Bạn không có quyền xem thanh toán.');

        // Check organization and ownership
        if (!$this->canAccessPayment($payment)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem thanh toán này.'
                ], 403);
            }
            return redirect()->route('staff.payments.index')
                ->with('error', 'Bạn không có quyền xem thanh toán này.');
        }

        $payment->load([
            'payerUser', 
            'method', 
            'invoice.lease' => function($q) {
                $q->withTrashed()->with(['property', 'tenant']);
            },
            'invoice.bookingDeposit.tenantUser',
            'invoice.bookingDeposit.lead',
            'invoice.bookingDeposit.unit.property',
            'documents' => function($q) {
                $q->where('document_type', 'image')
                  ->orderBy('sort_order')
                  ->orderBy('created_at');
            }
        ]);

        return view('staff.billing.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        // Check organization and ownership
        if (!$this->canAccessPayment($payment)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền chỉnh sửa thanh toán này.'
                ], 403);
            }
            return redirect()->route('staff.payments.index')
                ->with('error', 'Bạn không có quyền chỉnh sửa thanh toán này.');
        }

        // Prevent editing if payment is already successful
        if ($payment->status === Payment::STATUS_SUCCESS) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.'
                ], 400);
            }
            return redirect()->route('staff.payments.show', $payment)
                ->with('error', 'Không thể chỉnh sửa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.');
        }

        $organizationId = $this->getCurrentOrganizationId();

        // Tự động lấy khách hàng có liên quan đến hóa đơn chưa thanh toán
        $users = $this->getRelevantUsers($organizationId);

        $invoices = Invoice::where(function($q) use ($organizationId) {
            // Hóa đơn từ lease
            $q->whereHas('lease', function($leaseQ) use ($organizationId) {
                $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                    $pq->where('organization_id', $organizationId);
                });
            })
            // Hoặc hóa đơn từ booking deposit
            ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                $bookingQ->where('organization_id', $organizationId);
            });
        })
        ->where('organization_id', $organizationId) // Đảm bảo invoice thuộc tổ chức
        ->whereNull('deleted_at') // Loại bỏ invoice đã bị xóa
        ->where('invoices.status', 'issued') // Chỉ hiển thị hóa đơn đã phát hành
        ->with([
            'user', 
            'lease' => function($q) {
                $q->withTrashed()->with(['property', 'tenant']);
            }, 
            'bookingDeposit.tenantUser',
            'bookingDeposit.lead',
            'bookingDeposit.unit.property'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        $paymentMethods = PaymentMethod::all();

        $statuses = [
            Payment::STATUS_PENDING => 'Chờ thanh toán',
            Payment::STATUS_SUCCESS => 'Thành công',
            Payment::STATUS_FAILED => 'Thất bại',
            Payment::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];

        return view('staff.billing.payments.edit', compact(
            'payment',
            'users',
            'invoices',
            'paymentMethods',
            'statuses'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        // Check organization and ownership
        if (!$this->canAccessPayment($payment)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật thanh toán này.'
                ], 403);
            }
            return redirect()->route('staff.payments.index')
                ->with('error', 'Bạn không có quyền cập nhật thanh toán này.');
        }

        // Prevent editing if payment is already successful
        if ($payment->status === Payment::STATUS_SUCCESS) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.'
                ], 400);
            }
            return redirect()->route('staff.payments.show', $payment)
                ->with('error', 'Không thể chỉnh sửa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.');
        }

        $organizationId = $this->getCurrentOrganizationId();

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'method_id' => 'nullable|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'required|date',
            'txn_ref' => 'nullable|string|max:150',
            'status' => [
                'required',
                Rule::in([
                    Payment::STATUS_PENDING,
                    Payment::STATUS_SUCCESS,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_REFUNDED,
                ])
            ],
            'payer_user_id' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'note' => 'nullable|string',
        ]);

        // Validate that at least one of payer_user_id or lead_id is provided
        if (empty($validated['payer_user_id']) && empty($validated['lead_id'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.'
                ], 422);
            }
            return back()->withInput()->with('error', 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.');
        }

        // Verify invoice belongs to organization
        $invoice = Invoice::where('id', $validated['invoice_id'])
            ->where(function($q) use ($organizationId) {
                // Hóa đơn từ lease
                $q->whereHas('lease', function($leaseQ) use ($organizationId) {
                    $leaseQ->whereHas('property', function($pq) use ($organizationId) {
                        $pq->where('organization_id', $organizationId);
                    });
                })
                // Hoặc hóa đơn từ booking deposit
                ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                    $bookingQ->where('organization_id', $organizationId);
                });
            })->first();

        if (!$invoice) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật thanh toán cho hóa đơn này.'
                ], 403);
            }
            return back()->withInput()->with('error', 'Bạn không có quyền cập nhật thanh toán cho hóa đơn này.');
        }

        try {
            DB::beginTransaction();

            $payment->update($validated);

            // Handle image upload - save as document attachment
            if ($request->hasFile('image')) {
                try {
                    // Delete old primary image if exists
                    $oldPrimaryImage = $payment->documents()
                        ->where('document_type', 'image')
                        ->first();
                    
                    if ($oldPrimaryImage) {
                        // Delete file from storage
                        $filePath = $oldPrimaryImage->getRawOriginal('file_url');
                        // Delete file from storage (lưu trực tiếp vào public/storage)
                        $fullPath = public_path('storage/' . $filePath);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                        // Delete document record
                        $oldPrimaryImage->delete();
                    }

                    // Upload new image
                    $file = $request->file('image');
                    
                    // Lưu thông tin file TRƯỚC KHI move (vì sau khi move file sẽ không còn ở /tmp)
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();
                    
                    $filename = time() . '_' . $originalName;
                    
                    // Lưu trực tiếp vào public/storage/ (giống subscription-invoice)
                    $directory = 'payment-documents/' . date('Y/m');
                    $publicStoragePath = public_path('storage/' . $directory);
                    
                    // Tạo thư mục nếu chưa tồn tại
                    if (!is_dir($publicStoragePath)) {
                        mkdir($publicStoragePath, 0775, true);
                    }
                    
                    // Di chuyển file
                    $file->move($publicStoragePath, $filename);
                    
                    // Đường dẫn để lưu vào database (không có storage/ prefix)
                    $normalizedPath = $directory . '/' . $filename;

                    $document = \App\Models\Document::create([
                        'owner_type' => Payment::class,
                        'owner_id' => $payment->id,
                        'file_url' => $normalizedPath,
                        'file_name' => $originalName,
                        'mime_type' => $mimeType,
                        'file_size' => $fileSize,
                        'document_type' => 'image',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error uploading payment image: ' . $e->getMessage());
                    // Don't fail the entire request if image upload fails
                }
            }

            // Update invoice status based on payment status
            // Note: PaymentObserver sẽ tự động cập nhật invoice và booking deposit
            if ($payment->status === Payment::STATUS_SUCCESS) {
                // Reload invoice để đảm bảo có dữ liệu mới nhất
                $payment->invoice->refresh();
                if ($payment->invoice->status !== 'paid') {
                    $payment->invoice->update(['status' => 'paid']);
                }
            } else if ($payment->status === Payment::STATUS_REFUNDED) {
                // If payment is refunded, cancel the invoice
                $payment->invoice->refresh();
                if ($payment->invoice->status !== 'cancelled') {
                    $payment->invoice->update(['status' => 'cancelled']);
                }
            } else {
                // If payment status is no longer success (but not refunded), revert invoice status if it was paid by this payment
                if ($payment->invoice->status === 'paid') {
                    $payment->invoice->update(['status' => 'issued']);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được cập nhật thành công!',
                    'redirect' => route('staff.payments.show', $payment)
                ]);
            }

            return redirect()->route('staff.payments.show', $payment)
                ->with('success', 'Thanh toán đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật thanh toán: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.delete', 'Bạn không có quyền xóa thanh toán.');

        // Check organization and ownership
        if (!$this->canAccessPayment($payment)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa thanh toán này.'
                ], 403);
            }
            return redirect()->route('staff.payments.index')
                ->with('error', 'Bạn không có quyền xóa thanh toán này.');
        }

        // Prevent deletion if payment is already successful
        if ($payment->status === Payment::STATUS_SUCCESS) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.'
                ], 400);
            }
            return redirect()->route('staff.payments.show', $payment)
                ->with('error', 'Không thể xóa thanh toán đã thành công. Vui lòng liên hệ quản trị viên nếu cần thay đổi.');
        }

        try {
            DB::beginTransaction();

            // Optionally revert invoice status if this payment was the one that marked it paid
            if ($payment->status === Payment::STATUS_SUCCESS && $payment->invoice->status === 'paid') {
                $payment->invoice->update(['status' => 'issued']);
            }

            $payment->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.payments.index')
                ->with('success', 'Thanh toán đã được xóa thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa thanh toán: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Có lỗi xảy ra khi xóa thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        // Check organization and ownership (sử dụng canAccessPayment để kiểm tra qua invoice)
        if (!$this->canAccessPayment($payment)) {
            abort(403, 'Bạn không có quyền cập nhật thanh toán này.');
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return back()->with('error', 'Thanh toán này đã được đánh dấu là thành công.');
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
            ]);

            // Update invoice status
            // Note: PaymentObserver sẽ tự động cập nhật invoice và booking deposit
            $payment->invoice->refresh();
            if ($payment->invoice->status !== 'paid') {
                $payment->invoice->update(['status' => 'paid']);
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được đánh dấu là thành công!',
                    'payment' => $payment->fresh(['payerUser', 'method', 'invoice'])
                ]);
            }

            return back()->with('success', 'Thanh toán đã được đánh dấu là thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái thanh toán: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Get invoice details for payment form
     */
    public function getInvoiceDetails($invoiceId)
    {
        try {
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gắn vào tổ chức nào!'
                ], 403);
            }

            // Check if invoice exists and belongs to organization
            // Sử dụng query builder để kiểm tra tất cả các trường hợp cùng lúc (giống như method store)
            $invoice = Invoice::where('id', $invoiceId)
                ->where(function($q) use ($organizationId) {
                    // Hóa đơn từ lease
                    $q->whereHas('lease', function($leaseQ) use ($organizationId) {
                        $leaseQ->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                            $pq->where('organization_id', $organizationId);
                        });
                    })
                    // Hoặc hóa đơn từ booking deposit
                    ->orWhereHas('bookingDeposit', function($bookingQ) use ($organizationId) {
                        $bookingQ->where('organization_id', $organizationId);
                    })
                    // Hoặc invoice có organization_id trực tiếp
                    ->orWhere('organization_id', $organizationId);
                })
                ->whereNull('deleted_at')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn không tồn tại hoặc bạn không có quyền truy cập hóa đơn này.'
                ], 404);
            }

            // Load relationships based on invoice type
            if ($invoice->lease_id) {
                // Invoice has a lease - load lease relationships
                $invoice->load(['lease' => function($q) {
                    $q->withTrashed()->with(['property', 'tenant']);
                }]);
            } elseif ($invoice->booking_deposit_id) {
                // Invoice is for booking deposit - load booking deposit relationships
                $invoice->load(['bookingDeposit' => function($q) {
                    $q->withTrashed()->with(['unit.property', 'tenantUser', 'lead']);
                }]);
            }

            // Get payer user ID: prefer lease.tenant_id, then booking_deposit.tenant_user_id, fallback to invoice.user_id
            $payerUserId = null;
            if ($invoice->lease && $invoice->lease->tenant_id) {
                $payerUserId = $invoice->lease->tenant_id;
            } elseif ($invoice->bookingDeposit && $invoice->bookingDeposit->tenant_user_id) {
                $payerUserId = $invoice->bookingDeposit->tenant_user_id;
            } elseif ($invoice->user_id) {
                $payerUserId = $invoice->user_id;
            }

            // Build response data
            $invoiceData = [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'total_amount' => $invoice->total_amount,
                'payer_user_id' => $payerUserId,
                'user_id' => $invoice->user_id,
            ];

            // Add lease data if available
            if ($invoice->lease) {
                $invoiceData['lease'] = [
                    'id' => $invoice->lease->id,
                    'tenant_id' => $invoice->lease->tenant_id,
                    'property' => $invoice->lease->property ? [
                        'id' => $invoice->lease->property->id,
                        'name' => $invoice->lease->property->name,
                    ] : null,
                ];
            }

            // Get lead ID if booking deposit has lead
            $leadId = null;
            if ($invoice->bookingDeposit && $invoice->bookingDeposit->lead_id) {
                $leadId = $invoice->bookingDeposit->lead_id;
            }

            // Add booking deposit data if available
            if ($invoice->bookingDeposit) {
                $invoiceData['booking_deposit'] = [
                    'id' => $invoice->bookingDeposit->id,
                    'tenant_user_id' => $invoice->bookingDeposit->tenant_user_id,
                    'lead_id' => $leadId,
                    'property' => $invoice->bookingDeposit->unit && $invoice->bookingDeposit->unit->property ? [
                        'id' => $invoice->bookingDeposit->unit->property->id,
                        'name' => $invoice->bookingDeposit->unit->property->name,
                    ] : null,
                ];
            }
            
            // Add lead_id to main invoice data
            $invoiceData['lead_id'] = $leadId;

            return response()->json([
                'success' => true,
                'invoice' => $invoiceData
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn không tồn tại.'
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading invoice details for payment: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin hóa đơn: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $organizationId = $this->getCurrentOrganizationId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $stats = [
            'total_amount' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->sum('payments.amount'),
            'success_amount' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->where('payments.status', Payment::STATUS_SUCCESS)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->sum('payments.amount'),
            'pending_amount' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->where('payments.status', Payment::STATUS_PENDING)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->sum('payments.amount'),
            'total_count' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->count(),
            'success_count' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->where('payments.status', Payment::STATUS_SUCCESS)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->count(),
            'pending_count' => Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.organization_id', $organizationId)
                ->where('payments.status', Payment::STATUS_PENDING)
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        // Check capability
        $this->requireCapability('billing.payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        // Check organization and ownership (sử dụng canAccessPayment để kiểm tra qua invoice)
        if (!$this->canAccessPayment($payment)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật thanh toán này.'
                ], 403);
            }
            abort(403, 'Bạn không có quyền cập nhật thanh toán này.');
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    Payment::STATUS_PENDING,
                    Payment::STATUS_SUCCESS,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_REFUNDED,
                ])
            ],
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $payment->status;
            $payment->update([
                'status' => $validated['status'],
                'paid_at' => $validated['status'] === Payment::STATUS_SUCCESS ? now() : $payment->paid_at,
            ]);

            // Update invoice status based on payment status
            if ($payment->status === Payment::STATUS_SUCCESS) {
                $payment->invoice->refresh();
                if ($payment->invoice->status !== 'paid') {
                    $payment->invoice->update(['status' => 'paid']);
                }
            } else if ($payment->status === Payment::STATUS_REFUNDED) {
                // If payment is refunded, cancel the invoice
                $payment->invoice->refresh();
                if ($payment->invoice->status !== 'cancelled') {
                    $payment->invoice->update(['status' => 'cancelled']);
                }
            } else if ($oldStatus === Payment::STATUS_SUCCESS && $payment->status !== Payment::STATUS_SUCCESS) {
                // If payment status changed from success to something else (but not refunded), revert invoice status
                if ($payment->invoice->status === 'paid') {
                    $payment->invoice->update(['status' => 'issued']);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trạng thái thanh toán đã được cập nhật thành công!',
                    'payment' => $payment->fresh(['payerUser', 'method', 'invoice'])
                ]);
            }

            return back()->with('success', 'Trạng thái thanh toán đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
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
     * Tự động lấy danh sách khách hàng có liên quan
     * Ưu tiên khách hàng có hóa đơn chưa thanh toán
     */
    private function getRelevantUsers($organizationId)
    {
        // Lấy khách hàng có hóa đơn chưa thanh toán (ưu tiên cao)
        $usersWithPendingInvoices = User::whereHas('organizations', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->whereHas('invoices', function($invoiceQuery) use ($organizationId) {
                $invoiceQuery->whereIn('invoices.status', ['issued', 'overdue'])
                    ->whereHas('lease', function($leaseQuery) use ($organizationId) {
                        $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                            $propertyQuery->where('organization_id', $organizationId);
                        });
                    });
            })
            ->with(['invoices' => function($query) use ($organizationId) {
                $query->whereIn('invoices.status', ['issued', 'overdue'])
                    ->whereHas('lease', function($leaseQuery) use ($organizationId) {
                        $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                            $propertyQuery->where('organization_id', $organizationId);
                        });
                    });
            }])
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->select('users.*')
            ->orderByRaw('COALESCE(user_profiles.full_name, users.email) ASC')
            ->get();

        // Lấy khách hàng có hóa đơn đã thanh toán (ưu tiên thấp hơn)
        $usersWithPaidInvoices = User::whereHas('organizations', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->whereHas('invoices', function($invoiceQuery) use ($organizationId) {
                $invoiceQuery->where('invoices.status', 'paid')
                    ->whereHas('lease', function($leaseQuery) use ($organizationId) {
                        $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                            $propertyQuery->where('organization_id', $organizationId);
                        });
                    });
            })
            ->whereNotIn('users.id', $usersWithPendingInvoices->pluck('id'))
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->select('users.*')
            ->orderByRaw('COALESCE(user_profiles.full_name, users.email) ASC')
            ->get();

        // Lấy khách hàng có lease nhưng chưa có hóa đơn
        $usersWithLeases = User::whereHas('organizations', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->whereHas('leasesAsTenant', function($leaseQuery) use ($organizationId) {
                $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                    $propertyQuery->where('organization_id', $organizationId);
                });
            })
            ->whereNotIn('users.id', $usersWithPendingInvoices->pluck('id'))
            ->whereNotIn('users.id', $usersWithPaidInvoices->pluck('id'))
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->select('users.*')
            ->orderByRaw('COALESCE(user_profiles.full_name, users.email) ASC')
            ->get();

        // Gộp tất cả và đánh dấu ưu tiên
        $allUsers = collect();
        
        // Thêm khách hàng có hóa đơn chưa thanh toán (ưu tiên cao nhất)
        $allUsers = $allUsers->merge($usersWithPendingInvoices->map(function($user) {
            $user->priority = 'high';
            $user->pending_invoices_count = $user->invoices->count();
            return $user;
        }));
        
        // Thêm khách hàng có hóa đơn đã thanh toán
        $allUsers = $allUsers->merge($usersWithPaidInvoices->map(function($user) {
            $user->priority = 'medium';
            return $user;
        }));
        
        // Thêm khách hàng có lease
        $allUsers = $allUsers->merge($usersWithLeases->map(function($user) {
            $user->priority = 'low';
            return $user;
        }));

        return $allUsers->sortBy('full_name')->values();
    }
}

