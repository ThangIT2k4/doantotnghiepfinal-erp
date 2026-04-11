<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PayrollPayslip;
use App\Models\PayrollPayslipItem;
use App\Models\PayrollCycle;
use App\Models\SalaryContract;
use App\Models\CommissionEvent;
use App\Models\User;
use App\Models\CompanyInvoice;
use App\Models\CompanyInvoiceItem;
use App\Models\Vendor;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollPayslipController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability - manager can view all, agent can only view their own
        $this->requireCapability('finance.payroll.view', 'Bạn không có quyền xem Payroll Payslips.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all payroll payslips or only own payslips
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.payroll');
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        $shouldFilter = $this->shouldFilterByOwnership('finance.payroll');
        
        $query = PayrollPayslip::with(['user', 'payrollCycle'])
            ->whereNull('deleted_at')
            ->whereHas('payrollCycle', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->whereNull('deleted_at');
            });
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        if ($shouldFilter) {
            $query->where('user_id', $user->id);
        }

        // Base query for statistics (NOT filtered - always show all)
        if ($shouldFilter) {
            $statsQuery = PayrollPayslip::whereNull('deleted_at')
                ->where('user_id', $user->id)
                ->whereHas('payrollCycle', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->whereNull('deleted_at');
                });
        } else {
            $statsQuery = PayrollPayslip::whereNull('deleted_at')
                ->whereHas('payrollCycle', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->whereNull('deleted_at');
                });
        }

        // Calculate statistics from base query (not filtered)
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) (clone $statsQuery)->where('status', 'pending')->count(),
            'paid' => (int) (clone $statsQuery)->where('status', 'paid')->count(),
        ];

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->whereHas('userProfile', function($profileQuery) use ($search) {
                    $profileQuery->where('full_name', 'like', "%{$search}%");
                })->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cycle_id')) {
            $query->where('payroll_cycle_id', $request->cycle_id);
        }

        if ($request->filled('date_from')) {
            $query->whereHas('payrollCycle', function($q) use ($request) {
                $q->where('period_month', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('payrollCycle', function($q) use ($request) {
                $q->where('period_month', '<=', $request->date_to);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['created_at', 'status', 'gross_amount', 'net_amount', 'paid_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $payslips = $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString();

        // Get cycles for filter dropdown
        $cycles = PayrollCycle::where('organization_id', $organizationId)
            ->orderBy('period_month', 'desc')
            ->get();

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng cộng',
                'icon' => 'fa-file-invoice-dollar',
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
            'paid' => [
                'value' => $stats['paid'] ?? 0,
                'label' => 'Đã thanh toán',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'paid',
            ],
        ];
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.finance.payroll-payslips.partials.table', [
                'payslips' => $payslips,
                'isManager' => $canViewAll,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'payslips-table-container',
                'action' => route('staff.payroll-payslips.index'),
                'columns' => 3
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.finance.payroll-payslips.partials.table', [
                'payslips' => $payslips,
                'isManager' => $canViewAll,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'filterByStatus',
                'onClearClick' => 'clearAllFilters',
                'columns' => 3
            ])->render();
            
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
            ]);
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.payroll-payslips.index', compact('payslips', 'cycles', 'isManager', 'stats', 'sortBy', 'sortOrder', 'statsFormatted'));
    }

    public function show(PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability
        $this->requireCapability('finance.payroll.view', 'Bạn không có quyền xem Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );
        
        // Check if user can view all payroll payslips or only own payslips
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.payroll');
        
        // For agent, only allow viewing their own payslips
        if (!$canViewAll && $payrollPayslip->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xem phiếu lương của người khác.');
        }

        $payrollPayslip->load(['user.userProfile', 'payrollCycle']);

        // Get salary breakdown
        $salaryContract = SalaryContract::where('user_id', $payrollPayslip->user_id)
            ->where('status', 'active')
            ->where('effective_from', '<=', Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->endOfMonth())
            ->where(function($q) use ($payrollPayslip) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->startOfMonth());
            })
            ->first();

        // Get commission details for the period
        $periodStart = Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->startOfMonth();
        $periodEnd = Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->endOfMonth();

        $commissionEvents = CommissionEvent::where('agent_id', $payrollPayslip->user_id)
            ->where('status', 'paid')
            ->whereBetween('occurred_at', [$periodStart, $periodEnd])
            ->with(['policy', 'lease', 'unit'])
            ->get();

        $totalCommission = $commissionEvents->sum('commission_total');

        return view('staff.finance.payroll-payslips.show', compact('payrollPayslip', 'salaryContract', 'commissionEvents', 'totalCommission'));
    }

    public function edit(PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit payslips
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền chỉnh sửa Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        // Only allow editing if cycle is not locked
        if ($payrollPayslip->payrollCycle->status === 'locked') {
            return back()->with('error', 'Không thể chỉnh sửa phiếu lương của kỳ lương đã khóa');
        }

        // Prevent editing if payslip is already paid
        if ($payrollPayslip->status === 'paid') {
            return back()->with('error', 'Không thể chỉnh sửa phiếu lương đã thanh toán');
        }

        // Load items
        $payrollPayslip->load('items');

        return view('staff.finance.payroll-payslips.edit', compact('payrollPayslip'));
    }

    public function update(Request $request, PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update payslips
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền cập nhật Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        // Only allow editing if cycle is not locked
        if ($payrollPayslip->payrollCycle->status === 'locked') {
            return back()->with('error', 'Không thể chỉnh sửa phiếu lương của kỳ lương đã khóa');
        }

        // Prevent editing if payslip is already paid
        if ($payrollPayslip->status === 'paid') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa phiếu lương đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể chỉnh sửa phiếu lương đã thanh toán');
        }

        $request->validate([
            'gross_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:payroll_payslip_items,id',
            'items.*.item_type' => 'required|string',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.sign' => 'required|in:1,-1',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Calculate totals from items if provided
            $grossAmount = 0;
            $deductionAmount = 0;
            
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $itemData) {
                    $amount = floatval($itemData['amount']);
                    $sign = intval($itemData['sign']);
                    
                    // Dựa trên sign: 1 = thu nhập, -1 = khấu trừ
                    if ($sign === 1) {
                        $grossAmount += $amount;
                    } else {
                        $deductionAmount += $amount;
                    }
                }
            } else {
                // Fallback to direct input if no items provided
                $grossAmount = floatval($request->gross_amount ?? $payrollPayslip->gross_amount);
                $deductionAmount = floatval($request->deduction_amount ?? $payrollPayslip->deduction_amount);
            }
            
            $netAmount = $grossAmount - $deductionAmount;

            // Update payslip
            $payrollPayslip->update([
                'gross_amount' => $grossAmount,
                'deduction_amount' => $deductionAmount,
                'net_amount' => $netAmount,
                'note' => $request->note,
            ]);

            // Update items if provided
            if ($request->has('items') && is_array($request->items)) {
                // Delete existing items
                PayrollPayslipItem::where('payroll_payslip_id', $payrollPayslip->id)->delete();

                // Create new items
                foreach ($request->items as $itemData) {
                    // Lưu amount với sign: nếu sign = -1 thì lưu amount âm, nếu sign = 1 thì lưu amount dương
                    $amount = floatval($itemData['amount']);
                    $sign = intval($itemData['sign']);
                    $finalAmount = $sign === -1 ? -abs($amount) : abs($amount);
                    
                    PayrollPayslipItem::create([
                        'payroll_payslip_id' => $payrollPayslip->id,
                        'item_type' => $itemData['item_type'],
                        'item_name' => $itemData['item_name'],
                        'sign' => $sign,
                        'amount' => $finalAmount,
                        'ref_type' => $itemData['ref_type'] ?? null,
                        'ref_id' => $itemData['ref_id'] ?? null,
                        'note' => $itemData['note'] ?? null,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Phiếu lương đã được cập nhật thành công!',
                    'redirect' => route('staff.payroll-payslips.show', $payrollPayslip->id),
                    'data' => $payrollPayslip
                ]);
            }

            return redirect()->route('staff.payroll-payslips.show', $payrollPayslip->id)
                ->with('success', 'Phiếu lương đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating payslip: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật phiếu lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật phiếu lương');
        }
    }

    public function destroy(PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete payslips
        $this->requireCapability('finance.payroll.delete', 'Bạn không có quyền xóa Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        // Only allow deletion if cycle is not locked
        if ($payrollPayslip->payrollCycle->status === 'locked') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa phiếu lương của kỳ lương đã khóa'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa phiếu lương của kỳ lương đã khóa');
        }

        // Prevent deletion if payslip is already paid
        if ($payrollPayslip->status === 'paid') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa phiếu lương đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa phiếu lương đã thanh toán');
        }

        try {
            $payrollPayslip->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Phiếu lương đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.payroll-payslips.index')
                ->with('success', 'Phiếu lương đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting payslip: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa phiếu lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa phiếu lương');
        }
    }

    public function markAsPaid(PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can mark payslips as paid
        $this->requireCapability('finance.payroll.pay', 'Bạn không có quyền thanh toán Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        try {
            DB::beginTransaction();
            
            $payrollPayslip->update([
                'status' => 'paid', // Ensure status is always a string
                'paid_at' => now()
            ]);

            // Kiểm tra và cập nhật trạng thái kỳ lương nếu tất cả phiếu lương đã được thanh toán
            $this->checkAndUpdateCycleStatus($payrollPayslip->payrollCycle);

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Phiếu lương đã được đánh dấu là đã thanh toán!'
                ]);
            }

            return back()->with('success', 'Phiếu lương đã được đánh dấu là đã thanh toán!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking payslip as paid: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đánh dấu phiếu lương: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi đánh dấu phiếu lương');
        }
    }

    /**
     * Kiểm tra và cập nhật trạng thái kỳ lương nếu tất cả phiếu lương đã được thanh toán
     * 
     * @param PayrollCycle $payrollCycle
     * @return void
     */
    private function checkAndUpdateCycleStatus(PayrollCycle $payrollCycle)
    {
        try {
            // Refresh cycle để đảm bảo có dữ liệu mới nhất
            $payrollCycle->refresh();
            
            // Chỉ cập nhật nếu cycle đang ở trạng thái 'locked'
            if ($payrollCycle->status !== 'locked') {
                return;
            }

            // Đếm tổng số phiếu lương trong cycle (không bao gồm đã xóa)
            $totalPayslips = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                ->whereNull('deleted_at')
                ->count();

            // Nếu không có phiếu lương nào, không cập nhật
            if ($totalPayslips === 0) {
                return;
            }

            // Đếm số phiếu lương đã thanh toán
            $paidPayslips = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                ->where('status', 'paid')
                ->whereNull('deleted_at')
                ->count();

            // Nếu tất cả phiếu lương đã được thanh toán, cập nhật trạng thái cycle thành 'paid'
            if ($paidPayslips >= $totalPayslips) {
                $payrollCycle->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);

                Log::info('Payroll cycle automatically updated to paid status', [
                    'cycle_id' => $payrollCycle->id,
                    'period_month' => $payrollCycle->period_month,
                    'total_payslips' => $totalPayslips,
                    'paid_payslips' => $paidPayslips,
                    'triggered_by' => 'PayrollPayslipController::markAsPaid'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error checking and updating cycle status: ' . $e->getMessage(), [
                'cycle_id' => $payrollCycle->id,
                'error' => $e->getTraceAsString()
            ]);
            // Không throw exception để không ảnh hưởng đến flow chính
        }
    }

    public function recalculate(PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can recalculate payslips
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền tính lại Payroll Payslips.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        // Only allow recalculation if cycle is not locked
        if ($payrollPayslip->payrollCycle->status === 'locked') {
            return back()->with('error', 'Không thể tính lại phiếu lương của kỳ lương đã khóa');
        }

        try {
            DB::beginTransaction();

            $periodStart = Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->endOfMonth();

            // Get salary contract
            $salaryContract = SalaryContract::where('user_id', $payrollPayslip->user_id)
                ->where('status', 'active')
                ->where('effective_from', '<=', $periodEnd)
                ->where(function($q) use ($periodStart) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $periodStart);
                })
                ->first();

            if (!$salaryContract) {
                throw new \Exception('Không tìm thấy hợp đồng lương cho nhân viên này');
            }

            // Calculate basic salary with prorated logic if contract signed within the period
            $basicSalary = $this->calculateBasicSalary($salaryContract, $periodStart, $periodEnd);

            // Calculate allowances
            $allowances = 0;
            if ($salaryContract->allowances_json) {
                foreach ($salaryContract->allowances_json as $allowance) {
                    $allowances += $allowance;
                }
            }

            // Calculate commission for the period (with apply_limit_months check)
            $commissionService = app(\App\Services\CommissionEventService::class);
            $commission = $commissionService->calculateCommissionForPayroll(
                $payrollPayslip->user_id,
                $periodStart,
                $periodEnd,
                $payrollPayslip->payrollCycle->organization_id
            );

            // Calculate salary advances deduction for the period
            $salaryAdvanceDeduction = 0;
            $salaryAdvances = \App\Models\SalaryAdvance::where('user_id', $payrollPayslip->user_id)
                ->where('organization_id', $payrollPayslip->payrollCycle->organization_id)
                ->where('repayment_method', 'payroll_deduction')
                ->whereIn('status', ['approved', 'partially_repaid'])
                ->where('remaining_amount', '>', 0)
                ->get();

            foreach ($salaryAdvances as $advance) {
                $monthlyDeduction = $advance->calculateMonthlyDeduction();
                $salaryAdvanceDeduction += $monthlyDeduction;
            }

            // Calculate gross amount
            $grossAmount = $basicSalary + $allowances + $commission;
            
            // Calculate net amount (after salary advance deductions)
            $netAmount = $grossAmount - $salaryAdvanceDeduction;

            // Update payslip
            $payrollPayslip->update([
                'gross_amount' => $grossAmount,
                'deduction_amount' => $salaryAdvanceDeduction,
                'net_amount' => $netAmount,
            ]);

            // Delete existing items and recreate them
            PayrollPayslipItem::where('payroll_payslip_id', $payrollPayslip->id)->delete();

            // Create payroll payslip items for detailed breakdown
            // Basic salary item (positive)
            if ($basicSalary > 0) {
                PayrollPayslipItem::create([
                    'payroll_payslip_id' => $payrollPayslip->id,
                    'item_type' => PayrollPayslipItem::TYPE_BASIC_SALARY,
                    'item_name' => 'Lương cơ bản',
                    'sign' => 1, // Positive (income)
                    'amount' => $basicSalary,
                    'ref_type' => SalaryContract::class,
                    'ref_id' => $salaryContract->id,
                    'note' => 'Lương cơ bản',
                ]);
            }

            // Allowances items (positive)
            if ($salaryContract->allowances_json && count($salaryContract->allowances_json) > 0) {
                foreach ($salaryContract->allowances_json as $allowanceName => $allowanceAmount) {
                    if ($allowanceAmount > 0) {
                        PayrollPayslipItem::create([
                            'payroll_payslip_id' => $payrollPayslip->id,
                            'item_type' => PayrollPayslipItem::TYPE_ALLOWANCE,
                            'item_name' => $allowanceName,
                            'sign' => 1, // Positive (income)
                            'amount' => $allowanceAmount,
                            'ref_type' => SalaryContract::class,
                            'ref_id' => $salaryContract->id,
                            'note' => $allowanceName,
                        ]);
                    }
                }
            }

            // Commission item (positive)
            if ($commission > 0) {
                PayrollPayslipItem::create([
                    'payroll_payslip_id' => $payrollPayslip->id,
                    'item_type' => PayrollPayslipItem::TYPE_COMMISSION,
                    'item_name' => 'Hoa hồng',
                    'sign' => 1, // Positive (income)
                    'amount' => $commission,
                    'ref_type' => null,
                    'ref_id' => null,
                    'note' => 'Hoa hồng',
                ]);
            }

            // Salary advance deduction items (negative)
            foreach ($salaryAdvances as $advance) {
                $monthlyDeduction = $advance->calculateMonthlyDeduction();
                if ($monthlyDeduction > 0) {
                    PayrollPayslipItem::create([
                        'payroll_payslip_id' => $payrollPayslip->id,
                        'item_type' => PayrollPayslipItem::TYPE_SALARY_ADVANCE_DEDUCTION,
                        'item_name' => 'Trừ tạm ứng lương',
                        'sign' => -1, // Negative (deduction)
                        'amount' => $monthlyDeduction,
                        'ref_type' => \App\Models\SalaryAdvance::class,
                        'ref_id' => $advance->id,
                        'note' => 'Trừ tạm ứng lương',
                    ]);
                }
            }

            // Update salary advances with actual deductions
            foreach ($salaryAdvances as $advance) {
                $monthlyDeduction = $advance->calculateMonthlyDeduction();
                if ($monthlyDeduction > 0) {
                    $advance->addRepayment($monthlyDeduction);
                }
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Phiếu lương đã được tính lại thành công!'
                ]);
            }

            return back()->with('success', 'Phiếu lương đã được tính lại thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recalculating payslip: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tính lại phiếu lương: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi tính lại phiếu lương: ' . $e->getMessage());
        }
    }

    /**
     * Store company invoice from payslip (auto create)
     */
    public function storeCompanyInvoice(Request $request, PayrollPayslip $payrollPayslip)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.company_invoice.create', 'Bạn không có quyền tạo Company Invoices.');
        
        $this->checkOrganizationAccess(
            $payrollPayslip->payrollCycle->organization_id ?? null,
            'Unauthorized access to payslip.',
            'payroll_payslip',
            $payrollPayslip->id
        );

        $organizationId = $this->getCurrentOrganizationId();

        // Load relationships
        $payrollPayslip->load(['user.userProfile', 'payrollCycle']);

        // Check if company invoice already exists for this payslip
        $existingInvoice = CompanyInvoice::where('payroll_payslip_id', $payrollPayslip->id)->first();
        if ($existingInvoice) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn công ty đã được tạo cho phiếu lương này.',
                    'invoice_id' => $existingInvoice->id
                ], 400);
            }
            return redirect()->route('staff.company-invoices.show', $existingInvoice->id)
                ->with('info', 'Hóa đơn công ty đã được tạo cho phiếu lương này.');
        }

        try {
            DB::beginTransaction();

            // Validate net_amount
            if ($payrollPayslip->net_amount === null || $payrollPayslip->net_amount < 0) {
                throw new \Exception('Phiếu lương không có số tiền hợp lệ để tạo hóa đơn công ty.');
            }

            // Get user name
            $userName = $payrollPayslip->user->userProfile->full_name ?? $payrollPayslip->user->email ?? 'N/A';
            $periodMonth = Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->format('m/Y');

            // Calculate dates
            $issueDate = now()->toDateString();
            $dueDate = $payrollPayslip->paid_at 
                ? Carbon::parse($payrollPayslip->paid_at)->toDateString() 
                : now()->addDays(7)->toDateString();

            // Ensure due_date >= issue_date
            if ($dueDate < $issueDate) {
                $dueDate = $issueDate;
            }

            // Create company invoice with auto-filled data
            $invoice = new CompanyInvoice();
            $invoice->organization_id = $organizationId; // Set organization_id first for generateInvoiceNumber()
            $invoice->invoice_no = $invoice->generateInvoiceNumber();
            $invoice->fill([
                'organization_id' => $organizationId,
                'vendor_id' => null,
                'user_id' => $payrollPayslip->user_id,
                'invoice_type' => 'payroll_payslip',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => $request->input('status', 'pending'),
                'subtotal' => max(0, $payrollPayslip->net_amount),
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => max(0, $payrollPayslip->net_amount),
                'currency' => 'VND',
                'description' => "Lương nhân viên - {$userName} - Tháng {$periodMonth}",
                'note' => "Tự động tạo từ phiếu lương #{$payrollPayslip->id}",
                'payroll_payslip_id' => $payrollPayslip->id,
                'user_id' => $payrollPayslip->user_id,
                'created_by' => $user->id,
            ]);
            $invoice->save();

            // Create default item from payslip
            $itemAmount = max(0, $payrollPayslip->net_amount);
            CompanyInvoiceItem::create([
                'company_invoice_id' => $invoice->id,
                'item_type' => 'other',
                'description' => "Lương nhân viên - {$userName} - Tháng {$periodMonth}",
                'quantity' => 1,
                'unit_price' => $itemAmount,
                'amount' => $itemAmount,
            ]);

            DB::commit();

            Log::info('Company invoice created from payslip', [
                'invoice_id' => $invoice->id,
                'payslip_id' => $payrollPayslip->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hóa đơn công ty đã được tạo thành công!',
                    'invoice_id' => $invoice->id
                ]);
            }

            return redirect()->route('staff.company-invoices.show', $invoice->id)
                ->with('success', 'Hóa đơn công ty đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating company invoice from payslip: ' . $e->getMessage(), [
                'payslip_id' => $payrollPayslip->id,
                'error' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo hóa đơn công ty: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo hóa đơn công ty: ' . $e->getMessage());
        }
    }

    /**
     * Calculate basic salary with prorated logic if contract signed within the period
     * 
     * @param SalaryContract $contract
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return float
     */
    private function calculateBasicSalary(SalaryContract $contract, Carbon $periodStart, Carbon $periodEnd): float
    {
        $effectiveFrom = Carbon::parse($contract->effective_from);
        $baseSalary = (float) $contract->base_salary;

        // If contract was signed before the period start, use full salary
        if ($effectiveFrom->lt($periodStart)) {
            return $baseSalary;
        }

        // If contract was signed within the period, calculate prorated salary
        if ($effectiveFrom->gte($periodStart) && $effectiveFrom->lte($periodEnd)) {
            // Get total days in the cycle (month)
            $totalDaysInCycle = $periodStart->daysInMonth;
            
            // Get the day of month when contract was signed (1-31)
            $signingDay = $effectiveFrom->day;
            
            // Calculate prorated salary: (base_salary / total_days) * (total_days - signing_day)
            // Formula: lương cơ bản = (lương cơ bản / tổng số ngày chu kỳ) x (tổng ngày chu kỳ - ngày ký)
            $proratedSalary = ($baseSalary / $totalDaysInCycle) * ($totalDaysInCycle - $signingDay);
            
            Log::info('Calculating prorated basic salary (recalculate)', [
                'contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'base_salary' => $baseSalary,
                'total_days_in_cycle' => $totalDaysInCycle,
                'signing_day' => $signingDay,
                'effective_from' => $effectiveFrom->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'prorated_salary' => $proratedSalary,
                'days_worked' => $totalDaysInCycle - $signingDay,
            ]);
            
            return round($proratedSalary, 2);
        }

        // Default: return full salary (should not reach here normally)
        return $baseSalary;
    }
}
