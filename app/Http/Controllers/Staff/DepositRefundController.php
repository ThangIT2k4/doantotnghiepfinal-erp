<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DepositRefund;
use App\Models\Lease;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositRefundController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            abort(403, 'Bạn không có quyền truy cập module Contract.');
        }
        
        // Check capability - manager can view all, agent can only view their own
        $this->requireCapability('contract.deposit_refund.view', 'Bạn không có quyền xem Deposit Refunds.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId)
            ->with(['lease.unit.property', 'tenant', 'agent', 'approver', 'payer', 'creator']);
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        // DepositRefund filter qua lease.agent_id
        if ($this->shouldFilterByOwnership('contract.deposit_refund')) {
            $query->whereHas('lease', function($q) use ($user) {
                $q->where('agent_id', $user->id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by refund method
        if ($request->filled('refund_method')) {
            $query->where('refund_method', $request->refund_method);
        }

        // Filter by agent
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by lease contract number, tenant name, or agent name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search, $canViewAll) {
                $q->whereHas('lease', function($leaseQuery) use ($search) {
                    $leaseQuery->where('contract_no', 'like', "%{$search}%");
                })->orWhereHas('tenant', function($tenantQuery) use ($search) {
                    $tenantQuery->whereHas('userProfile', function($profileQuery) use ($search) {
                        $profileQuery->where('full_name', 'like', "%{$search}%");
                    })->orWhere('email', 'like', "%{$search}%");
                });
                // Only search by agent if manager
                if ($canViewAll) {
                    $q->orWhereHas('agent', function($agentQuery) use ($search) {
                        $agentQuery->whereHas('userProfile', function($profileQuery) use ($search) {
                            $profileQuery->where('full_name', 'like', "%{$search}%");
                        })->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        $depositRefunds = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics - only show stats for user's own refunds if agent
        $stats = [];
        if ($canViewAll) {
            // Manager sees all stats
            $stats = [
                'total' => DepositRefund::where('organization_id', $organizationId)->count(),
                'pending' => DepositRefund::where('organization_id', $organizationId)->where('status', 'pending')->count(),
                'approved' => DepositRefund::where('organization_id', $organizationId)->where('status', 'approved')->count(),
                'paid' => DepositRefund::where('organization_id', $organizationId)->where('status', 'paid')->count(),
                'cancelled' => DepositRefund::where('organization_id', $organizationId)->where('status', 'cancelled')->count(),
                'total_amount' => DepositRefund::where('organization_id', $organizationId)->sum('refund_amount'),
            ];
            
            // Get agents for filter (only for manager)
            $agents = \App\Services\CapabilityService::getUsersWithModuleAccess('contract', $organizationId);
        } else {
            // Agent sees only their own stats
            $stats = [
                'total' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->count(),
                'pending' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->where('status', 'pending')->count(),
                'approved' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->where('status', 'approved')->count(),
                'paid' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->where('status', 'paid')->count(),
                'cancelled' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->where('status', 'cancelled')->count(),
                'total_amount' => DepositRefund::where('organization_id', $organizationId)->where('agent_id', $user->id)->sum('refund_amount'),
            ];
            
            $agents = collect([]);
        }

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // For HTMX requests, return HTML instead of JSON
        if ($isHtmx || ($request->ajax() && $request->header('Accept') === 'text/html')) {
            // Return only table HTML for HTMX
            $tableHtml = view('staff.contract.deposit-refunds.partials.table', [
                'depositRefunds' => $depositRefunds,
            ])->render();
            
            // Return HTML response
            return response($tableHtml)
                ->header('HX-Push-Url', $request->fullUrl()); // Update URL
                
        } elseif ($request->ajax()) {
            // Keep existing JSON response for backward compatibility
            $tableHtml = view('staff.contract.deposit-refunds.partials.table', [
                'depositRefunds' => $depositRefunds,
            ])->render();
            
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
            ]);
        }
        
        // Regular page load
        return view('staff.contract.deposit-refunds.index', compact('depositRefunds', 'stats', 'agents', 'request'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.create', 'Bạn không có quyền tạo Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        // Get active leases - manager sees all, agent sees only their own
        $leaseQuery = Lease::whereHas('unit.property', function($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })
        ->where('status', 'active')
        ->where('deposit_amount', '>', 0);
        
        if (!$canViewAll) {
            $leaseQuery->where('agent_id', $user->id);
        }
        
        $leases = $leaseQuery->with(['unit.property', 'tenant', 'agent'])->get();

        return view('staff.contract.deposit-refunds.create', compact('leases'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.create', 'Bạn không có quyền tạo Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');

        // Validate request
        $request->validate([
            'lease_id' => 'required|exists:leases,id',
            'refund_method' => 'required|in:cash,bank_transfer,wallet',
            'notes' => 'nullable|string|max:1000',
        ], [
            'lease_id.required' => 'Vui lòng chọn hợp đồng.',
            'lease_id.exists' => 'Hợp đồng không tồn tại.',
            'refund_method.required' => 'Vui lòng chọn phương thức hoàn tiền.',
        ]);

        try {
            DB::beginTransaction();

            // Get lease - manager can access all, agent can only access their own
            $leaseQuery = Lease::whereHas('unit.property', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->where('id', $request->lease_id);
            
            if (!$canViewAll) {
                $leaseQuery->where('agent_id', $user->id);
            }
            
            $lease = $leaseQuery->firstOrFail();

            // Check if lease already has a pending or approved refund
            $existingRefund = DepositRefund::where('lease_id', $lease->id)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingRefund) {
                return back()->with('error', 'Hợp đồng này đã có yêu cầu hoàn tiền đang chờ xử lý.');
            }

            // Calculate deducted amount from ticket deposits
            $deductedAmount = \App\Models\TicketLog::whereHas('ticket', function($query) use ($lease) {
                $query->where('unit_id', $lease->unit_id);
            })
            ->whereNull('deleted_at')
            ->where('charge_to', 'tenant_deposit')
            ->where('cost_amount', '>', 0)
            ->sum('cost_amount');

            // Calculate refund amount
            $refundAmount = $lease->deposit_amount - $deductedAmount;

            if ($refundAmount <= 0) {
                return back()->with('error', 'Không có tiền cọc để hoàn lại (đã bị trừ hết từ chi phí sửa chữa).');
            }

            // Auto-generate refund_reference
            $depositRefund = new DepositRefund();
            $depositRefund->organization_id = $organizationId;
            $refundReference = $depositRefund->generateRefundReference($organizationId);

            // Create deposit refund record
            $depositRefund = DepositRefund::create([
                'lease_id' => $lease->id,
                'organization_id' => $organizationId,
                'unit_id' => $lease->unit_id,
                'tenant_id' => $lease->tenant_id,
                'agent_id' => $lease->agent_id,
                'original_deposit_amount' => $lease->deposit_amount,
                'deducted_amount' => $deductedAmount,
                'refund_amount' => $refundAmount,
                'status' => DepositRefund::STATUS_PENDING,
                'refund_method' => $request->refund_method,
                'refund_reference' => $refundReference,
                'notes' => $request->notes,
                'deduction_details' => [
                    'ticket_deposits' => $deductedAmount,
                    'created_manually' => true,
                    'created_by_manager' => $canViewAll,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
                'created_by' => $user->id,
            ]);

            // Deposit refund is already linked to lease via deposit_refunds.lease_id

            DB::commit();

            return redirect()->route('staff.deposit-refunds.show', $depositRefund->id)
                ->with('success', 'Yêu cầu hoàn tiền cọc đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating deposit refund: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo yêu cầu hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            abort(403, 'Bạn không có quyền truy cập module Contract.');
        }
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.view', 'Bạn không có quyền xem Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId);
        
        // For agent, only show their own deposit refunds
        if (!$canViewAll) {
            $query->where('agent_id', $user->id);
        }
        
        $depositRefund = $query->with([
                'lease.unit.property',
                'tenant',
                'agent',
                'approver',
                'payer',
                'creator',
                'lease.ticketDeposits.logs' => function($query) {
                    $query->where('charge_to', 'tenant_deposit')
                          ->where('cost_amount', '>', 0);
                }
            ])
            ->findOrFail($id);

        return view('staff.contract.deposit-refunds.show', compact('depositRefund'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.update', 'Bạn không có quyền chỉnh sửa Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId);
        
        // For agent, only show their own deposit refunds
        if (!$canViewAll) {
            $query->where('agent_id', $user->id);
        }
        
        $depositRefund = $query->findOrFail($id);

        // Prevent editing if deposit refund is already paid
        if ($depositRefund->status === DepositRefund::STATUS_PAID) {
            return back()->with('error', 'Không thể chỉnh sửa hoàn tiền cọc đã thanh toán');
        }

        // Only allow editing if status is pending
        if ($depositRefund->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể chỉnh sửa hoàn tiền cọc ở trạng thái chờ duyệt');
        }

        return view('staff.contract.deposit-refunds.edit', compact('depositRefund'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.update', 'Bạn không có quyền cập nhật Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId);
        
        // For agent, only show their own deposit refunds
        if (!$canViewAll) {
            $query->where('agent_id', $user->id);
        }
        
        $depositRefund = $query->findOrFail($id);

        // Prevent editing if deposit refund is already paid
        if ($depositRefund->status === DepositRefund::STATUS_PAID) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa hoàn tiền cọc đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể chỉnh sửa hoàn tiền cọc đã thanh toán');
        }

        // Only allow editing if status is pending
        if ($depositRefund->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể chỉnh sửa hoàn tiền cọc ở trạng thái chờ duyệt'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể chỉnh sửa hoàn tiền cọc ở trạng thái chờ duyệt');
        }

        // Validate request
        $request->validate([
            'refund_method' => 'required|in:cash,bank_transfer,wallet',
            'notes' => 'nullable|string|max:1000',
        ], [
            'refund_method.required' => 'Vui lòng chọn phương thức hoàn tiền.',
        ]);

        try {
            $depositRefund->update([
                'refund_method' => $request->refund_method,
                'notes' => $request->notes,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Yêu cầu hoàn tiền đã được cập nhật thành công!',
                    'redirect' => route('staff.deposit-refunds.show', $depositRefund->id)
                ]);
            }

            return redirect()->route('staff.deposit-refunds.show', $depositRefund->id)
                ->with('success', 'Yêu cầu hoàn tiền đã được cập nhật thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating deposit refund: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật yêu cầu hoàn tiền: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật yêu cầu hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.delete', 'Bạn không có quyền xóa Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId);
        
        // For agent, only show their own deposit refunds
        if (!$canViewAll) {
            $query->where('agent_id', $user->id);
        }
        
        $depositRefund = $query->findOrFail($id);

        // Prevent deletion if deposit refund is already paid
        if ($depositRefund->status === DepositRefund::STATUS_PAID) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa hoàn tiền cọc đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa hoàn tiền cọc đã thanh toán');
        }

        // Only allow deletion if status is pending
        if ($depositRefund->status !== 'pending') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể xóa hoàn tiền cọc ở trạng thái chờ duyệt'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể xóa hoàn tiền cọc ở trạng thái chờ duyệt');
        }

        try {
            $depositRefund->delete();

            return redirect()->route('staff.deposit-refunds.index')
                ->with('success', 'Yêu cầu hoàn tiền đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting deposit refund: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa yêu cầu hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Approve deposit refund
     */
    public function approve(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can approve
        $this->requireCapability('contract.deposit_refund.approve', 'Bạn không có quyền duyệt Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        $depositRefund = DepositRefund::where('organization_id', $organizationId)
            ->findOrFail($id);

        if (!$depositRefund->canBeApproved()) {
            return back()->with('error', 'Không thể phê duyệt yêu cầu hoàn tiền này.');
        }

        try {
            DB::beginTransaction();

            $depositRefund->update([
                'status' => DepositRefund::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);

            // Create company invoice if not exists
            $existingInvoice = \App\Models\CompanyInvoice::where('deposit_refund_id', $depositRefund->id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$existingInvoice) {
                \App\Models\CompanyInvoice::createFromDepositRefund($depositRefund, [
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Yêu cầu hoàn tiền đã được phê duyệt!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving deposit refund: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi phê duyệt hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Mark deposit refund as paid - Redirect to company invoice show for payment
     */
    public function markPaid(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can mark as paid
        $this->requireCapability('contract.deposit_refund.pay', 'Bạn không có quyền thanh toán Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        $depositRefund = DepositRefund::where('organization_id', $organizationId)
            ->with('companyInvoice')
            ->findOrFail($id);

        if (!$depositRefund->canBePaid()) {
            return back()->with('error', 'Không thể đánh dấu đã thanh toán cho yêu cầu hoàn tiền này.');
        }

        // Find or create company invoice for this deposit refund
        $companyInvoice = \App\Models\CompanyInvoice::where('deposit_refund_id', $depositRefund->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$companyInvoice) {
            // Create company invoice if not exists
            try {
                $companyInvoice = \App\Models\CompanyInvoice::createFromDepositRefund($depositRefund, [
                    'created_by' => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating company invoice for deposit refund: ' . $e->getMessage());
                return back()->with('error', 'Có lỗi xảy ra khi tạo hóa đơn hoàn cọc: ' . $e->getMessage());
            }
        }

        // Redirect to company invoice show page for payment
        return redirect()->route('staff.company-invoices.show', $companyInvoice->id)
            ->with('info', 'Vui lòng thực hiện thanh toán hóa đơn hoàn cọc.');
    }

    /**
     * Cancel deposit refund
     */
    public function cancel(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - manager can cancel any, agent can only cancel their own
        $this->requireCapability('contract.deposit_refund.update', 'Bạn không có quyền hủy Deposit Refund.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->back()->with('error', 'Bạn chưa được gán vào tổ chức nào.');
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        $query = DepositRefund::where('organization_id', $organizationId);
        
        // For agent, only show their own deposit refunds
        if (!$canViewAll) {
            $query->where('agent_id', $user->id);
        }
        
        $depositRefund = $query->findOrFail($id);

        if (!$depositRefund->canBeCancelled()) {
            return back()->with('error', 'Không thể hủy yêu cầu hoàn tiền này.');
        }

        try {
            $depositRefund->update([
                'status' => DepositRefund::STATUS_CANCELLED,
            ]);

            return back()->with('success', 'Yêu cầu hoàn tiền đã được hủy!');

        } catch (\Exception $e) {
            Log::error('Error cancelling deposit refund: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi hủy hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Get organization statistics
     */
    public function statistics()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            return response()->json(['error' => 'Bạn không có quyền truy cập module Contract.'], 403);
        }
        
        // Check capability
        $this->requireCapability('contract.deposit_refund.view', 'Bạn không có quyền xem Deposit Refunds.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn chưa được gán vào tổ chức nào.'], 400);
        }
        
        // Check if user has contract.deposit_refund.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('contract.deposit_refund');
        
        // Build query based on user role
        $baseQuery = DepositRefund::where('organization_id', $organizationId);
        
        if (!$canViewAll) {
            $baseQuery->where('agent_id', $user->id);
        }
        
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'total_amount' => (clone $baseQuery)->sum('refund_amount'),
            'pending_amount' => (clone $baseQuery)->where('status', 'pending')->sum('refund_amount'),
            'approved_amount' => (clone $baseQuery)->where('status', 'approved')->sum('refund_amount'),
        ];

        return response()->json($stats);
    }
}
