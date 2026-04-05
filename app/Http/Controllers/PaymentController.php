<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaymentController extends Controller
{
    use AuthorizesRequests, ChecksCapabilities;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check capability
        $this->requireCapability('payment.view', 'Bạn không có quyền xem thanh toán.');

        $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? null;
        
        // Optimized query using JOINs and proper index order
        $query = Payment::select([
            'payments.*',
            'invoices.invoice_no',
            'invoices.lease_id',
            'payer_users.full_name as payer_name',
            'payment_methods.name as method_name'
        ])
        ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
        ->leftJoin('users as payer_users', 'payments.payer_user_id', '=', 'payer_users.id')
        ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
        ->where('payments.organization_id', $organizationId);
        
        // Apply filters in optimal order: organization_id -> deleted_at -> status
        $query->whereNull('payments.deleted_at') // Uses idx_payments_invoice_deleted_status
              ->whereNull('invoices.deleted_at'); // Uses idx_invoices_deleted_at_status

        // Apply filters - uses idx_payments_invoice_deleted_status
        if ($request->filled('status')) {
            $query->where('payments.status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payments.txn_ref', 'like', "%{$search}%")
                  ->orWhere('payments.note', 'like', "%{$search}%")
                  ->orWhere('payer_users.full_name', 'like', "%{$search}%")
                  ->orWhere('payer_users.email', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('payments.created_at', 'desc')->paginate(20);
        
        // Eager load relationships for display
        $payments->load(['payerUser', 'method', 'invoice.lease' => function($q) {
            $q->withTrashed()->with('property');
        }]);

        $statuses = [
            Payment::STATUS_PENDING => 'Chờ thanh toán',
            Payment::STATUS_SUCCESS => 'Thành công',
            Payment::STATUS_FAILED => 'Thất bại',
            Payment::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];

        return view('staff.billing.payments.index', compact('payments', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check capability
        $this->requireCapability('payment.create', 'Bạn không có quyền tạo thanh toán.');

        $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? null;
        
        // Tự động lấy khách hàng có liên quan đến hóa đơn chưa thanh toán
        $users = $this->getRelevantUsers($organizationId);

        $invoices = Invoice::whereHas('lease', function($q) use ($organizationId) {
            $q->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                $pq->where('organization_id', $organizationId);
            });
        })->where('invoices.status', 'pending')
            ->with(['user', 'lease' => function($q) {
                $q->withTrashed()->with('property');
            }])
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
            'statuses'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check capability
        $this->requireCapability('payment.create', 'Bạn không có quyền tạo thanh toán.');

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
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $payment = Payment::create($validated);

            DB::commit();

            return redirect()->route('staff.payments.show', $payment)
                ->with('success', 'Thanh toán đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        // Check capability
        $this->requireCapability('payment.view', 'Bạn không có quyền xem thanh toán.');

        $this->authorize('view', $payment);

        $payment->load(['payerUser', 'method', 'invoice.lease' => function($q) {
            $q->withTrashed()->with('property');
        }]);

        return view('staff.billing.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        // Check capability
        $this->requireCapability('payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        $this->authorize('update', $payment);

        $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? null;
        
        // Tự động lấy khách hàng có liên quan đến hóa đơn chưa thanh toán
        $users = $this->getRelevantUsers($organizationId);

        $invoices = Invoice::whereHas('lease', function($q) use ($organizationId) {
            $q->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                $pq->where('organization_id', $organizationId);
            });
        })->with(['user', 'lease' => function($q) {
            $q->withTrashed()->with('property');
        }])
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
        $this->requireCapability('payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        $this->authorize('update', $payment);

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
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $payment->update($validated);

            DB::commit();

            return redirect()->route('staff.payments.show', $payment)
                ->with('success', 'Thanh toán đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
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
        $this->requireCapability('payment.delete', 'Bạn không có quyền xóa thanh toán.');

        $this->authorize('delete', $payment);

        try {
            $payment->delete();

            return redirect()->route('staff.payments.index')
                ->with('success', 'Thanh toán đã được xóa thành công.');

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra khi xóa thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(Payment $payment)
    {
        // Check capability
        $this->requireCapability('payment.update', 'Bạn không có quyền cập nhật thanh toán.');

        $this->authorize('update', $payment);

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return back()->with('error', 'Thanh toán này đã được đánh dấu là thành công.');
        }

        try {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
            ]);

            return back()->with('success', 'Thanh toán đã được đánh dấu là thành công.');

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $organizationId = \App\Models\OrganizationUser::where('user_id', Auth::id())->first()?->organization_id ?? null;
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $stats = [
            'total_amount' => Payment::forOrganization($organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'success_amount' => Payment::forOrganization($organizationId)
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'pending_amount' => Payment::forOrganization($organizationId)
                ->where('status', Payment::STATUS_PENDING)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'total_count' => Payment::forOrganization($organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'success_count' => Payment::forOrganization($organizationId)
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'pending_count' => Payment::forOrganization($organizationId)
                ->where('status', Payment::STATUS_PENDING)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
        ];

        return response()->json($stats);
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
                $invoiceQuery->where('invoices.status', 'pending')
                    ->whereHas('lease', function($leaseQuery) use ($organizationId) {
                        $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                            $propertyQuery->where('organization_id', $organizationId);
                        });
                    });
            })
            ->with(['invoices' => function($query) use ($organizationId) {
                $query->where('invoices.status', 'pending')
                    ->whereHas('lease', function($leaseQuery) use ($organizationId) {
                        $leaseQuery->withTrashed()->whereHas('property', function($propertyQuery) use ($organizationId) {
                            $propertyQuery->where('organization_id', $organizationId);
                        });
                    });
            }])
            ->orderBy('full_name')
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
            ->whereNotIn('id', $usersWithPendingInvoices->pluck('id'))
            ->orderBy('full_name')
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
            ->whereNotIn('id', $usersWithPendingInvoices->pluck('id'))
            ->whereNotIn('id', $usersWithPaidInvoices->pluck('id'))
            ->orderBy('full_name')
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