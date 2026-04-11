<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\PayrollCycle;
use App\Models\PayrollPayslip;
use App\Models\PayrollPayslipItem;
use App\Models\SalaryContract;
use App\Models\CommissionEvent;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollCycleController extends Controller
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
        
        // Check capability - manager can view all, agent can only view cycles with their payslips
        $this->requireCapability('finance.payroll.view', 'Bạn không có quyền xem Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all payroll cycles or only own cycles
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.payroll');
        
        // Base query for statistics (NOT filtered - always show all)
        if ($canViewAll) {
            $statsQuery = PayrollCycle::where('organization_id', $organizationId)
                ->whereNull('deleted_at');
        } else {
            $statsQuery = PayrollCycle::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->whereHas('payslips', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        }
        
        // Calculate statistics from base query (not filtered)
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'open' => (int) (clone $statsQuery)->where('status', 'open')->count(),
            'locked' => (int) (clone $statsQuery)->where('status', 'locked')->count(),
            'paid' => (int) (clone $statsQuery)->where('status', 'paid')->count(),
        ];
        
        // Main query for listing
        if ($canViewAll) {
            // Manager sees all cycles in organization
            $query = PayrollCycle::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->with(['payslips'])
                ->withCount(['payslips']);
        } else {
            // Agent sees only cycles with their payslips
            $query = PayrollCycle::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->whereHas('payslips', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['payslips' => function($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                ->withCount(['payslips' => function($q) use ($user) {
                    $q->where('user_id', $user->id);
                }]);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('period_month', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('period_month', 'like', $request->year . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'period_month');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['period_month', 'status', 'created_at', 'locked_at', 'paid_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'period_month';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $cycles = $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString();

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            // Format stats for statistics-cards component
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-calendar-alt',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'open' => [
                    'value' => $stats['open'] ?? 0,
                    'label' => 'Mở',
                    'icon' => 'fa-unlock',
                    'color' => 'success',
                    'filter' => 'open',
                ],
                'locked' => [
                    'value' => $stats['locked'] ?? 0,
                    'label' => 'Đã khóa',
                    'icon' => 'fa-lock',
                    'color' => 'warning',
                    'filter' => 'locked',
                ],
                'paid' => [
                    'value' => $stats['paid'] ?? 0,
                    'label' => 'Đã thanh toán',
                    'icon' => 'fa-check-circle',
                    'color' => 'info',
                    'filter' => 'paid',
                ],
            ];
            
            $tableHtml = view('staff.finance.payroll-cycles.partials.table', [
                'cycles' => $cycles,
                'isManager' => $canViewAll,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])->render();
            
            // Wrap table in card structure
            $tableContainerHtml = '<div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Danh sách Kỳ Lương
                    </h6>
                </div>
                <div class="card-body">
                    ' . $tableHtml . '
                </div>
            </div>';
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'payroll-cycles-table-container',
                'action' => route('staff.payroll-cycles.index'),
                'columns' => 4
            ])->render();
            
            // Return HTML with hx-swap-oob for stats container
            return response($tableContainerHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>");
        }

        // Handle legacy AJAX request (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            // Format stats for statistics-cards component
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-calendar-alt',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'open' => [
                    'value' => $stats['open'] ?? 0,
                    'label' => 'Mở',
                    'icon' => 'fa-unlock',
                    'color' => 'success',
                    'filter' => 'open',
                ],
                'locked' => [
                    'value' => $stats['locked'] ?? 0,
                    'label' => 'Đã khóa',
                    'icon' => 'fa-lock',
                    'color' => 'warning',
                    'filter' => 'locked',
                ],
                'paid' => [
                    'value' => $stats['paid'] ?? 0,
                    'label' => 'Đã thanh toán',
                    'icon' => 'fa-check-circle',
                    'color' => 'info',
                    'filter' => 'paid',
                ],
            ];
            
            $tableHtml = view('staff.finance.payroll-cycles.partials.table', [
                'cycles' => $cycles,
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
                'tableContainerId' => 'payroll-cycles-table-container',
                'action' => route('staff.payroll-cycles.index'),
                'columns' => 4
            ])->render();
            
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
            ]);
        }

        return view('staff.finance.payroll-cycles.index', compact('cycles', 'isManager', 'stats', 'sortBy', 'sortOrder'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create cycles
        $this->requireCapability('finance.payroll.create', 'Bạn không có quyền tạo Payroll Cycles.');
        
        // Get current month and include past and future months for selection
        $currentMonth = Carbon::now()->format('Y-m');
        $availableMonths = [];
        
        // Include 12 months in the past and 12 months in the future (total 25 months)
        // Loop from past to future for chronological order
        for ($i = -12; $i <= 12; $i++) {
            $monthDate = Carbon::now()->addMonths($i);
            $month = $monthDate->format('Y-m');
            $monthLabel = $monthDate->format('m/Y');
            $availableMonths[$month] = $monthLabel;
        }
        
        // Sort by month value (ascending: past to future)
        ksort($availableMonths);

        return view('staff.finance.payroll-cycles.create', compact('availableMonths', 'currentMonth'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create cycles
        $this->requireCapability('finance.payroll.create', 'Bạn không có quyền tạo Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $request->validate([
            'period_month' => 'required|date_format:Y-m|unique:payroll_cycles,period_month,NULL,id,organization_id,' . $organizationId,
            'note' => 'nullable|string|max:255'
        ]);

        try {
            $cycle = PayrollCycle::create([
                'organization_id' => $organizationId,
                'period_month' => $request->period_month,
                'status' => 'open', // Ensure status is always a string
                'note' => $request->note,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kỳ lương đã được tạo thành công!',
                    'data' => $cycle
                ]);
            }

            return redirect()->route('staff.payroll-cycles.index')
                ->with('success', 'Kỳ lương đã được tạo thành công!');

        } catch (\Exception $e) {
            Log::error('Error creating payroll cycle: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo kỳ lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo kỳ lương');
        }
    }

    public function show(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability
        $this->requireCapability('finance.payroll.view', 'Bạn không có quyền xem Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );
        
        // Check if user can view all payroll cycles or only own cycles
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.payroll');
        
        // For agent, only allow viewing cycles with their payslips
        if (!$canViewAll) {
            $hasPayslip = $payrollCycle->payslips()->where('user_id', $user->id)->exists();
            if (!$hasPayslip) {
                abort(403, 'Bạn không có quyền xem kỳ lương này.');
            }
            // Load only their payslips
            $payrollCycle->load(['payslips' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }, 'payslips.user.userProfile']);
        } else {
            // Manager sees all payslips
            $payrollCycle->load(['payslips.user.userProfile']);
        }
        
        // Get summary statistics
        $payslips = $payrollCycle->payslips;
        $totalGross = $payslips->sum('gross_amount');
        $totalDeductions = $payslips->sum('deduction_amount');
        $totalNet = $payslips->sum('net_amount');
        $totalEmployees = $payslips->count();

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.payroll-cycles.show', compact('payrollCycle', 'totalGross', 'totalDeductions', 'totalNet', 'totalEmployees', 'isManager'));
    }

    public function edit(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit cycles
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền chỉnh sửa Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        // Only allow editing if status is 'open'
        if ($payrollCycle->status !== 'open') {
            return back()->with('error', 'Chỉ có thể chỉnh sửa kỳ lương đang mở');
        }

        // Load payslips with items and user info
        $payrollCycle->load([
            'payslips.items',
            'payslips.user.userProfile'
        ]);

        return view('staff.finance.payroll-cycles.edit', compact('payrollCycle'));
    }

    public function update(Request $request, PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update cycles
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền cập nhật Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        // Only allow editing if status is 'open'
        if ($payrollCycle->status !== 'open') {
            return back()->with('error', 'Chỉ có thể chỉnh sửa kỳ lương đang mở');
        }

        $request->validate([
            'note' => 'nullable|string|max:255',
            'payslips' => 'nullable|array',
            'payslips.*.id' => 'required|exists:payroll_payslips,id',
            'payslips.*.items' => 'nullable|array',
            'payslips.*.items.*.id' => 'nullable|exists:payroll_payslip_items,id',
            'payslips.*.items.*.item_type' => 'required|string',
            'payslips.*.items.*.item_name' => 'required|string|max:255',
            'payslips.*.items.*.sign' => 'required|in:1,-1',
            'payslips.*.items.*.amount' => 'required|numeric|min:0',
            'payslips.*.items.*.note' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update cycle note
            $payrollCycle->update([
                'note' => $request->note,
            ]);

            // Update payslips and items if provided
            if ($request->has('payslips')) {
                foreach ($request->payslips as $payslipData) {
                    $payslip = \App\Models\PayrollPayslip::findOrFail($payslipData['id']);
                    
                    // Verify payslip belongs to this cycle
                    if ($payslip->payroll_cycle_id !== $payrollCycle->id) {
                        throw new \Exception('Payslip không thuộc kỳ lương này');
                    }

                    // Calculate totals from items
                    $grossAmount = 0;
                    $deductionAmount = 0;
                    
                    if (isset($payslipData['items']) && is_array($payslipData['items'])) {
                        foreach ($payslipData['items'] as $itemData) {
                            $amount = floatval($itemData['amount']);
                            $sign = intval($itemData['sign']);
                            
                            // Dựa trên sign: 1 = thu nhập, -1 = khấu trừ
                            if ($sign === 1) {
                                $grossAmount += $amount;
                            } else {
                                $deductionAmount += $amount;
                            }
                        }
                    }
                    
                    $netAmount = $grossAmount - $deductionAmount;
                    
                    // Update payslip amounts
                    $payslip->update([
                        'gross_amount' => $grossAmount,
                        'deduction_amount' => $deductionAmount,
                        'net_amount' => $netAmount,
                    ]);

                    // Delete existing items
                    \App\Models\PayrollPayslipItem::where('payroll_payslip_id', $payslip->id)->delete();

                    // Create new items
                    if (isset($payslipData['items']) && is_array($payslipData['items'])) {
                        foreach ($payslipData['items'] as $itemData) {
                            // Lưu amount với sign: nếu sign = -1 thì lưu amount âm, nếu sign = 1 thì lưu amount dương
                            $amount = floatval($itemData['amount']);
                            $sign = intval($itemData['sign']);
                            $finalAmount = $sign === -1 ? -abs($amount) : abs($amount);
                            
                            \App\Models\PayrollPayslipItem::create([
                                'payroll_payslip_id' => $payslip->id,
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
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kỳ lương đã được cập nhật thành công!',
                    'redirect' => route('staff.payroll-cycles.show', $payrollCycle->id),
                    'data' => $payrollCycle->fresh(['payslips.items'])
                ]);
            }

            return redirect()->route('staff.payroll-cycles.show', $payrollCycle->id)
                ->with('success', 'Kỳ lương đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating payroll cycle: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật kỳ lương: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật kỳ lương: ' . $e->getMessage());
        }
    }

    public function destroy(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete cycles
        $this->requireCapability('finance.payroll.delete', 'Bạn không có quyền xóa Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        // Only allow deletion if status is 'open' and no payslips exist
        if ($payrollCycle->status !== 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể xóa kỳ lương đang mở'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể xóa kỳ lương đang mở');
        }

        if ($payrollCycle->payslips()->count() > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa kỳ lương đã có phiếu lương'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa kỳ lương đã có phiếu lương');
        }

        try {
            $payrollCycle->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kỳ lương đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.payroll-cycles.index')
                ->with('success', 'Kỳ lương đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting payroll cycle: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa kỳ lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa kỳ lương');
        }
    }

    public function lock(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can lock cycles
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền khóa Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        if ($payrollCycle->status !== 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể khóa kỳ lương đang mở'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể khóa kỳ lương đang mở');
        }

        try {
            $payrollCycle->update([
                'status' => 'locked', // Ensure status is always a string
                'locked_at' => now()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kỳ lương đã được khóa thành công!'
                ]);
            }

            return back()->with('success', 'Kỳ lương đã được khóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error locking payroll cycle: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi khóa kỳ lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi khóa kỳ lương');
        }
    }

    public function updateStatus(Request $request, PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update status
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền cập nhật trạng thái Payroll Cycles.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        $request->validate([
            'status' => 'required|in:open,locked,paid'
        ]);

        $newStatus = $request->status;
        $currentStatus = $payrollCycle->status;

        // Validate status transitions
        if ($currentStatus === 'paid' && $newStatus !== 'paid') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể thay đổi trạng thái của kỳ lương đã thanh toán'
                ], 400);
            }
            return back()->with('error', 'Không thể thay đổi trạng thái của kỳ lương đã thanh toán');
        }

        if ($currentStatus === 'locked' && $newStatus === 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể mở lại kỳ lương đã khóa'
                ], 400);
            }
            return back()->with('error', 'Không thể mở lại kỳ lương đã khóa');
        }

        try {
            $updateData = ['status' => $newStatus];
            
            if ($newStatus === 'locked' && $currentStatus === 'open') {
                $updateData['locked_at'] = now();
            }
            
            if ($newStatus === 'paid' && $currentStatus === 'locked') {
                $updateData['paid_at'] = now();
            }

            $payrollCycle->update($updateData);

            $statusLabels = [
                'open' => 'Mở',
                'locked' => 'Đã khóa',
                'paid' => 'Đã thanh toán'
            ];

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Kỳ lương đã được chuyển sang trạng thái \"{$statusLabels[$newStatus]}\" thành công!"
                ]);
            }

            return back()->with('success', "Kỳ lương đã được chuyển sang trạng thái \"{$statusLabels[$newStatus]}\" thành công!");

        } catch (\Exception $e) {
            Log::error('Error updating payroll cycle status: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái kỳ lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái kỳ lương');
        }
    }

    public function previewPayslips(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can preview payslips
        $this->requireCapability('finance.payroll.create', 'Bạn không có quyền xem preview phiếu lương.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        if ($payrollCycle->status !== 'open') {
            return back()->with('error', 'Chỉ có thể xem preview cho kỳ lương đang mở');
        }

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();

            // Get all active salary contracts for the organization
            // Include contracts that are effective within the period (not just before period start)
            $salaryContracts = SalaryContract::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where('effective_from', '<=', $periodEnd) // Changed: allow contracts signed within the period
                ->where(function($q) use ($periodStart) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $periodStart);
                })
                ->with('user')
                ->get();

            $previewPayslips = [];
            
            try {
                $commissionService = app(\App\Services\CommissionEventService::class);
            } catch (\Exception $serviceError) {
                Log::error('previewPayslips: Failed to instantiate CommissionEventService', [
                    'error' => $serviceError->getMessage(),
                    'trace' => $serviceError->getTraceAsString()
                ]);
                return back()->with('error', 'Lỗi hệ thống: Không thể khởi tạo dịch vụ hoa hồng. Vui lòng liên hệ quản trị viên.');
            }

            foreach ($salaryContracts as $contract) {
                // Skip if payslip already exists
                $existingPayslip = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                    ->where('user_id', $contract->user_id)
                    ->first();

                if ($existingPayslip) {
                    continue;
                }

                // Calculate basic salary with prorated logic if contract signed within the period
                $basicSalary = $this->calculateBasicSalary($contract, $periodStart, $periodEnd);

                // Calculate allowances
                $allowances = 0;
                $allowanceItems = [];
                if ($contract->allowances_json) {
                    foreach ($contract->allowances_json as $allowanceName => $allowanceAmount) {
                        $allowances += $allowanceAmount;
                        if ($allowanceAmount > 0) {
                            $allowanceItems[] = [
                                'item_type' => PayrollPayslipItem::TYPE_ALLOWANCE,
                                'item_name' => $allowanceName,
                                'sign' => 1,
                                'amount' => $allowanceAmount,
                                'ref_type' => SalaryContract::class,
                                'ref_id' => $contract->id,
                                'note' => $allowanceName,
                            ];
                        }
                    }
                }

                // Calculate commission
                $commission = $commissionService->calculateCommissionForPayroll(
                    $contract->user_id,
                    $periodStart,
                    $periodEnd,
                    $organizationId
                );

                // Calculate salary advances deduction
                $salaryAdvanceDeduction = 0;
                $salaryAdvanceItems = [];
                $salaryAdvances = \App\Models\SalaryAdvance::where('user_id', $contract->user_id)
                    ->where('organization_id', $organizationId)
                    ->where('repayment_method', 'payroll_deduction')
                    ->whereIn('status', ['approved', 'partially_repaid'])
                    ->where('remaining_amount', '>', 0)
                    ->get();

                foreach ($salaryAdvances as $advance) {
                    try {
                        $monthlyDeduction = $advance->calculateMonthlyDeduction();
                        if ($monthlyDeduction > 0) {
                            $salaryAdvanceDeduction += $monthlyDeduction;
                            $salaryAdvanceItems[] = [
                                'item_type' => PayrollPayslipItem::TYPE_SALARY_ADVANCE_DEDUCTION,
                                'item_name' => 'Trừ tạm ứng lương',
                                'sign' => -1,
                                'amount' => $monthlyDeduction,
                                'ref_type' => \App\Models\SalaryAdvance::class,
                                'ref_id' => $advance->id,
                                'note' => 'Trừ tạm ứng lương',
                            ];
                        }
                    } catch (\Exception $advanceError) {
                        Log::error('previewPayslips: Error calculating salary advance deduction', [
                            'user_id' => $contract->user_id,
                            'salary_advance_id' => $advance->id,
                            'error' => $advanceError->getMessage()
                        ]);
                        // Continue without this deduction
                    }
                }

                // Calculate amounts
                $grossAmount = $basicSalary + $allowances + $commission;
                $netAmount = $grossAmount - $salaryAdvanceDeduction;

                // Build items array
                $items = [];
                
                // Basic salary item
                if ($basicSalary > 0) {
                    $items[] = [
                        'item_type' => PayrollPayslipItem::TYPE_BASIC_SALARY,
                        'item_name' => 'Lương cơ bản',
                        'sign' => 1,
                        'amount' => $basicSalary,
                        'ref_type' => SalaryContract::class,
                        'ref_id' => $contract->id,
                        'note' => 'Lương cơ bản',
                    ];
                }

                // Allowance items
                $items = array_merge($items, $allowanceItems);

                // Commission item
                if ($commission > 0) {
                    // Lấy danh sách commission events để hiển thị trong preview
                    $commissionEvents = $commissionService->getCommissionEventsForPayroll(
                        $contract->user_id,
                        $periodStart,
                        $periodEnd,
                        $organizationId
                    );

                    // Tạo item tổng cho commission
                    $items[] = [
                        'item_type' => PayrollPayslipItem::TYPE_COMMISSION,
                        'item_name' => 'Hoa hồng',
                        'sign' => 1,
                        'amount' => $commission,
                        'ref_type' => null,
                        'ref_id' => null,
                        'note' => 'Hoa hồng (' . count($commissionEvents) . ' sự kiện)',
                    ];
                }

                // Salary advance deduction items
                $items = array_merge($items, $salaryAdvanceItems);

                $previewPayslips[] = [
                    'user_id' => $contract->user_id,
                    'user_name' => $contract->user->full_name ?? $contract->user->email,
                    'user_email' => $contract->user->email,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'commission' => $commission,
                    'gross_amount' => $grossAmount,
                    'deduction_amount' => $salaryAdvanceDeduction,
                    'net_amount' => $netAmount,
                    'items' => $items,
                ];
            }

            return view('staff.finance.payroll-cycles.preview', compact('payrollCycle', 'previewPayslips'));

        } catch (\Exception $e) {
            Log::error('Error previewing payslips: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xem preview phiếu lương: ' . $e->getMessage());
        }
    }

    public function generatePayslips(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Log initial request info
        Log::info('generatePayslips called', [
            'user_id' => $user->id ?? null,
            'payroll_cycle_id' => $payrollCycle->id,
            'cycle_status' => $payrollCycle->status,
        ]);
        
        // Check capability - only manager can generate payslips
        try {
            $this->requireCapability('finance.payroll.create', 'Bạn không có quyền tạo phiếu lương.');
        } catch (\Exception $e) {
            Log::error('generatePayslips: Capability check failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            Log::error('generatePayslips: No organization ID', [
                'user_id' => $user->id ?? null,
                'session_id' => session()->getId(),
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: Không xác định được tổ chức. Vui lòng đăng xuất và đăng nhập lại.'
                ], 403);
            }
            abort(403, 'Lỗi: Không xác định được tổ chức. Vui lòng đăng xuất và đăng nhập lại.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        if ($payrollCycle->status !== 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể tạo phiếu lương cho kỳ lương đang mở'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể tạo phiếu lương cho kỳ lương đang mở');
        }

        try {
            DB::beginTransaction();

            $periodStart = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();

            // Get all active salary contracts for the organization
            // Include contracts that are effective within the period (not just before period start)
            $salaryContracts = SalaryContract::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where('effective_from', '<=', $periodEnd) // Changed: allow contracts signed within the period
                ->where(function($q) use ($periodStart) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $periodStart);
                })
                ->with('user')
                ->get();

            $createdCount = 0;
            foreach ($salaryContracts as $contract) {
                // Check if payslip already exists
                $existingPayslip = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                    ->where('user_id', $contract->user_id)
                    ->first();

                if ($existingPayslip) {
                    continue; // Skip if payslip already exists
                }

                // Calculate basic salary with prorated logic if contract signed within the period
                $basicSalary = $this->calculateBasicSalary($contract, $periodStart, $periodEnd);

                // Calculate allowances
                $allowances = 0;
                if ($contract->allowances_json) {
                    foreach ($contract->allowances_json as $allowance) {
                        $allowances += $allowance;
                    }
                }

                // Calculate commission for the period (with apply_limit_months check)
                try {
                    $commissionService = app(\App\Services\CommissionEventService::class);
                    $commission = $commissionService->calculateCommissionForPayroll(
                        $contract->user_id,
                        $periodStart,
                        $periodEnd,
                        $organizationId
                    );
                } catch (\Exception $commissionError) {
                    Log::error('generatePayslips: Error calculating commission', [
                        'user_id' => $contract->user_id,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'error' => $commissionError->getMessage(),
                        'trace' => $commissionError->getTraceAsString()
                    ]);
                    // Set commission to 0 if error occurs, don't stop payslip generation
                    $commission = 0;
                }

                // Calculate salary advances deduction for the period
                $salaryAdvanceDeduction = 0;
                $salaryAdvances = \App\Models\SalaryAdvance::where('user_id', $contract->user_id)
                    ->where('organization_id', $organizationId)
                    ->where('repayment_method', 'payroll_deduction')
                    ->whereIn('status', ['approved', 'partially_repaid'])
                    ->where('remaining_amount', '>', 0)
                    ->get();

                foreach ($salaryAdvances as $advance) {
                    try {
                        $monthlyDeduction = $advance->calculateMonthlyDeduction();
                        if ($monthlyDeduction > 0) {
                            $salaryAdvanceDeduction += $monthlyDeduction;
                        }
                    } catch (\Exception $advanceError) {
                        Log::error('generatePayslips: Error calculating salary advance deduction', [
                            'user_id' => $contract->user_id,
                            'salary_advance_id' => $advance->id,
                            'error' => $advanceError->getMessage()
                        ]);
                        // Continue without this deduction
                    }
                }

                // Calculate gross amount
                $grossAmount = $basicSalary + $allowances + $commission;
                
                // Calculate net amount (after salary advance deductions)
                $netAmount = $grossAmount - $salaryAdvanceDeduction;

                // Tính paid_at dựa trên pay_day của salary contract
                $paidAt = null;
                if ($contract->pay_day) {
                    try {
                        // Tạo ngày thanh toán: pay_day của tháng payroll cycle
                        $periodDate = Carbon::createFromFormat('Y-m', $payrollCycle->period_month);
                        // CRITICAL FIX: Cast to int before min() and day()
                        $payDay = (int) min((int) $contract->pay_day, $periodDate->daysInMonth);
                        // Validate pay_day is between 1-31
                        if ($payDay < 1 || $payDay > 31) {
                            Log::warning('generatePayslips: Invalid pay_day value', [
                                'pay_day' => $contract->pay_day,
                                'calculated_pay_day' => $payDay,
                                'user_id' => $contract->user_id
                            ]);
                            $payDay = 1; // Default to 1st of month
                        }
                        $paidAt = $periodDate->copy()->day($payDay)->startOfDay();
                    } catch (\Exception $e) {
                        Log::error('generatePayslips: Error calculating paid_at from pay_day', [
                            'pay_day' => $contract->pay_day,
                            'pay_day_type' => gettype($contract->pay_day),
                            'period_month' => $payrollCycle->period_month,
                            'user_id' => $contract->user_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Set paid_at to null if error
                        $paidAt = null;
                    }
                }

                // Create payslip
                $payslip = PayrollPayslip::create([
                    'payroll_cycle_id' => $payrollCycle->id,
                    'user_id' => $contract->user_id,
                    'gross_amount' => $grossAmount,
                    'deduction_amount' => $salaryAdvanceDeduction, // Salary advance deductions
                    'net_amount' => $netAmount, // Net amount after salary advance deductions
                    'status' => 'pending', // Ensure status is always a string
                    'paid_at' => $paidAt,
                ]);

                // Create payroll payslip items for detailed breakdown
                // Basic salary item (positive)
                if ($basicSalary > 0) {
                    PayrollPayslipItem::create([
                        'payroll_payslip_id' => $payslip->id,
                        'item_type' => PayrollPayslipItem::TYPE_BASIC_SALARY,
                        'item_name' => 'Lương cơ bản',
                        'sign' => 1, // Positive (income)
                        'amount' => $basicSalary,
                        'ref_type' => SalaryContract::class,
                        'ref_id' => $contract->id,
                        'note' => 'Lương cơ bản',
                    ]);
                }

                // Allowances items (positive)
                if ($contract->allowances_json && count($contract->allowances_json) > 0) {
                    foreach ($contract->allowances_json as $allowanceName => $allowanceAmount) {
                        if ($allowanceAmount > 0) {
                            PayrollPayslipItem::create([
                                'payroll_payslip_id' => $payslip->id,
                                'item_type' => PayrollPayslipItem::TYPE_ALLOWANCE,
                                'item_name' => $allowanceName,
                                'sign' => 1, // Positive (income)
                                'amount' => $allowanceAmount,
                                'ref_type' => SalaryContract::class,
                                'ref_id' => $contract->id,
                                'note' => $allowanceName,
                            ]);
                        }
                    }
                }

                // Commission item (positive)
                if ($commission > 0) {
                    // Lấy danh sách commission events đã được tính
                    try {
                        if (!isset($commissionService)) {
                            $commissionService = app(\App\Services\CommissionEventService::class);
                        }
                        $commissionEvents = $commissionService->getCommissionEventsForPayroll(
                            $contract->user_id,
                            $periodStart,
                            $periodEnd,
                            $organizationId
                        );

                        // Tạo item tổng cho commission
                        PayrollPayslipItem::create([
                            'payroll_payslip_id' => $payslip->id,
                            'item_type' => PayrollPayslipItem::TYPE_COMMISSION,
                            'item_name' => 'Hoa hồng',
                            'sign' => 1, // Positive (income)
                            'amount' => $commission,
                            'ref_type' => null,
                            'ref_id' => null,
                            'note' => 'Hoa hồng',
                        ]);

                        // Cập nhật status của các commission events thành 'paid'
                        // getCommissionEventsForPayroll đã chỉ trả về approved events
                        foreach ($commissionEvents as $event) {
                            $event->update(['status' => 'paid']);
                            Log::info('Updated commission event status to paid', [
                                'event_id' => $event->id,
                                'payslip_id' => $payslip->id,
                                'user_id' => $contract->user_id,
                                'occurred_at' => $event->occurred_at
                            ]);
                        }
                    } catch (\Exception $commissionEventError) {
                        Log::error('generatePayslips: Error getting/updating commission events', [
                            'user_id' => $contract->user_id,
                            'payslip_id' => $payslip->id,
                            'error' => $commissionEventError->getMessage(),
                            'trace' => $commissionEventError->getTraceAsString()
                        ]);
                        // Still create the commission item even if events can't be updated
                        PayrollPayslipItem::create([
                            'payroll_payslip_id' => $payslip->id,
                            'item_type' => PayrollPayslipItem::TYPE_COMMISSION,
                            'item_name' => 'Hoa hồng',
                            'sign' => 1,
                            'amount' => $commission,
                            'ref_type' => null,
                            'ref_id' => null,
                            'note' => 'Hoa hồng (lỗi cập nhật events)',
                        ]);
                    }
                }

                // Salary advance deduction items (negative)
                foreach ($salaryAdvances as $advance) {
                    try {
                        $monthlyDeduction = $advance->calculateMonthlyDeduction();
                        if ($monthlyDeduction > 0) {
                            PayrollPayslipItem::create([
                                'payroll_payslip_id' => $payslip->id,
                                'item_type' => PayrollPayslipItem::TYPE_SALARY_ADVANCE_DEDUCTION,
                                'item_name' => 'Trừ tạm ứng lương',
                                'sign' => -1, // Negative (deduction)
                                'amount' => $monthlyDeduction,
                                'ref_type' => \App\Models\SalaryAdvance::class,
                                'ref_id' => $advance->id,
                                'note' => 'Trừ tạm ứng lương',
                            ]);
                        }
                    } catch (\Exception $itemError) {
                        Log::error('generatePayslips: Error creating salary advance item', [
                            'user_id' => $contract->user_id,
                            'payslip_id' => $payslip->id,
                            'salary_advance_id' => $advance->id,
                            'error' => $itemError->getMessage()
                        ]);
                        // Continue without this item
                    }
                }

                // Update salary advances with actual deductions
                foreach ($salaryAdvances as $advance) {
                    try {
                        $monthlyDeduction = $advance->calculateMonthlyDeduction();
                        if ($monthlyDeduction > 0) {
                            $advance->addRepayment($monthlyDeduction);
                        }
                    } catch (\Exception $repaymentError) {
                        Log::error('generatePayslips: Error updating salary advance repayment', [
                            'user_id' => $contract->user_id,
                            'salary_advance_id' => $advance->id,
                            'error' => $repaymentError->getMessage()
                        ]);
                        // Continue without updating this advance
                    }
                }

                $createdCount++;
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã tạo thành công {$createdCount} phiếu lương!",
                    'created_count' => $createdCount
                ]);
            }

            return back()->with('success', "Đã tạo thành công {$createdCount} phiếu lương!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating payslips', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $organizationId ?? null,
                'user_id' => $user->id ?? null,
            ]);

            $errorMessage = \App\Helpers\ErrorHelper::getSafeErrorMessage(
                $e,
                'Có lỗi xảy ra khi tạo phiếu lương. Chi tiết: ' . $e->getMessage()
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    public function createFromPreview(Request $request, PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Log request info for debugging
        Log::info('createFromPreview called', [
            'user_id' => $user->id ?? null,
            'payroll_cycle_id' => $payrollCycle->id,
            'cycle_status' => $payrollCycle->status,
            'session_id' => session()->getId(),
            'has_session' => session()->isStarted(),
        ]);
        
        // Check capability - only manager can create payslips from preview
        try {
            $this->requireCapability('finance.payroll.create', 'Bạn không có quyền tạo phiếu lương.');
        } catch (\Exception $e) {
            Log::error('createFromPreview: Capability check failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            Log::error('createFromPreview: No organization ID', [
                'user_id' => $user->id ?? null,
                'session_id' => session()->getId(),
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: Không xác định được tổ chức. Vui lòng đăng xuất và đăng nhập lại.'
                ], 403);
            }
            abort(403, 'Lỗi: Không xác định được tổ chức. Vui lòng đăng xuất và đăng nhập lại.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        if ($payrollCycle->status !== 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể tạo phiếu lương cho kỳ lương đang mở'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể tạo phiếu lương cho kỳ lương đang mở');
        }

        try {
            DB::beginTransaction();

            $payslipsData = $request->input('payslips', []);
            
            if (empty($payslipsData)) {
                throw new \Exception('Không có dữ liệu phiếu lương để tạo.');
            }

            Log::info('Creating payslips from preview', [
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $organizationId,
                'user_id' => $user->id ?? null,
                'payslips_count' => count($payslipsData)
            ]);

            $createdCount = 0;

            foreach ($payslipsData as $payslipData) {
                try {
                    $userId = $payslipData['user_id'] ?? null;
                    
                    if (!$userId) {
                        Log::warning('Skipping payslip: missing user_id', ['payslip_data' => $payslipData]);
                        continue;
                    }
                    
                    // Check if payslip already exists
                    $existingPayslip = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                        ->where('user_id', $userId)
                        ->first();

                    if ($existingPayslip) {
                        Log::info('Skipping payslip: already exists', [
                            'payslip_id' => $existingPayslip->id,
                            'user_id' => $userId
                        ]);
                        continue; // Skip if payslip already exists
                    }

                    // Calculate totals from items
                    $grossAmount = 0;
                    $deductionAmount = 0;
                    $items = $payslipData['items'] ?? [];

                    if (!is_array($items)) {
                        Log::error('createFromPreview: Items is not array', [
                            'user_id' => $userId,
                            'items_type' => gettype($items)
                        ]);
                        continue;
                    }

                    foreach ($items as $item) {
                        if (!is_array($item)) {
                            Log::warning('createFromPreview: Item is not array', [
                                'user_id' => $userId,
                                'item' => $item
                            ]);
                            continue;
                        }
                        
                        $amount = floatval($item['amount'] ?? 0);
                        if ($amount < 0) {
                            Log::warning('createFromPreview: Negative amount detected', [
                                'user_id' => $userId,
                                'amount' => $amount,
                                'item' => $item
                            ]);
                            $amount = abs($amount);
                        }
                        
                        if (($item['sign'] ?? 1) == 1) {
                            $grossAmount += $amount;
                        } else {
                            $deductionAmount += $amount;
                        }
                    }

                    $netAmount = $grossAmount - $deductionAmount;
                    
                    // Validate calculated amounts
                    if ($grossAmount < 0 || $deductionAmount < 0 || $netAmount < 0) {
                        Log::error('createFromPreview: Invalid calculated amounts', [
                            'user_id' => $userId,
                            'gross' => $grossAmount,
                            'deduction' => $deductionAmount,
                            'net' => $netAmount
                        ]);
                        // Fix negative values
                        $grossAmount = max(0, $grossAmount);
                        $deductionAmount = max(0, $deductionAmount);
                        $netAmount = max(0, $netAmount);
                    }

                    // Tính paid_at dựa trên pay_day của salary contract
                    $paidAt = null;
                    try {
                        $contract = SalaryContract::where('user_id', $userId)
                            ->where('organization_id', $organizationId)
                            ->where('status', 'active')
                            ->where(function($query) use ($payrollCycle) {
                                try {
                                    $periodStart = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
                                    $periodEnd = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();
                                    $query->where('effective_from', '<=', $periodEnd)
                                        ->where(function($q) use ($periodStart) {
                                            $q->whereNull('effective_to')
                                              ->orWhere('effective_to', '>=', $periodStart);
                                        });
                                } catch (\Exception $e) {
                                    Log::error('createFromPreview: Error parsing period dates', [
                                        'period_month' => $payrollCycle->period_month,
                                        'error' => $e->getMessage()
                                    ]);
                                    // Skip contract filtering if date parsing fails
                                }
                            })
                            ->orderBy('effective_from', 'desc')
                            ->first();

                        if ($contract && $contract->pay_day) {
                            try {
                                // Tạo ngày thanh toán: pay_day của tháng payroll cycle
                                $periodDate = Carbon::createFromFormat('Y-m', $payrollCycle->period_month);
                                // CRITICAL FIX: Cast to int before min() and day()
                                $payDay = (int) min((int) $contract->pay_day, $periodDate->daysInMonth);
                                // Validate pay_day is between 1-31
                                if ($payDay < 1 || $payDay > 31) {
                                    Log::warning('createFromPreview: Invalid pay_day value', [
                                        'pay_day' => $contract->pay_day,
                                        'calculated_pay_day' => $payDay,
                                        'user_id' => $userId
                                    ]);
                                    $payDay = 1; // Default to 1st of month
                                }
                                $paidAt = $periodDate->copy()->day($payDay)->startOfDay();
                            } catch (\Exception $e) {
                                Log::error('createFromPreview: Error calculating paid_at from pay_day', [
                                    'pay_day' => $contract->pay_day,
                                    'pay_day_type' => gettype($contract->pay_day),
                                    'period_month' => $payrollCycle->period_month,
                                    'user_id' => $userId,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                // Set paid_at to null if error
                                $paidAt = null;
                            }
                        }
                    } catch (\Exception $contractError) {
                        Log::error('createFromPreview: Error fetching salary contract', [
                            'user_id' => $userId,
                            'organization_id' => $organizationId,
                            'error' => $contractError->getMessage()
                        ]);
                        // Continue without paid_at
                    }

                    // Create payslip
                    Log::info('Creating payslip', [
                        'payroll_cycle_id' => $payrollCycle->id,
                        'user_id' => $userId,
                        'gross_amount' => $grossAmount,
                        'deduction_amount' => $deductionAmount,
                        'net_amount' => $netAmount,
                        'paid_at' => $paidAt
                    ]);

                    try {
                        $payslip = PayrollPayslip::create([
                            'payroll_cycle_id' => $payrollCycle->id,
                            'user_id' => $userId,
                            'gross_amount' => $grossAmount,
                            'deduction_amount' => $deductionAmount,
                            'net_amount' => $netAmount,
                            'status' => 'pending',
                            'paid_at' => $paidAt,
                        ]);
                    } catch (\Exception $payslipCreateError) {
                        Log::error('createFromPreview: Error creating payslip record', [
                            'user_id' => $userId,
                            'payroll_cycle_id' => $payrollCycle->id,
                            'error' => $payslipCreateError->getMessage(),
                            'trace' => $payslipCreateError->getTraceAsString()
                        ]);
                        // Skip this payslip and continue
                        continue;
                    }

                    // Create items
                    $hasCommission = false;
                    $commissionAmount = 0;
                    foreach ($items as $item) {
                        try {
                            if (!is_array($item)) {
                                Log::warning('createFromPreview: Skipping non-array item', [
                                    'payslip_id' => $payslip->id,
                                    'item' => $item
                                ]);
                                continue;
                            }
                            
                            $itemType = $item['item_type'] ?? PayrollPayslipItem::TYPE_OTHER;
                            if ($itemType === PayrollPayslipItem::TYPE_COMMISSION) {
                                $hasCommission = true;
                                $commissionAmount = floatval($item['amount'] ?? 0);
                            }
                            
                            $itemData = [
                                'payroll_payslip_id' => $payslip->id,
                                'item_type' => $itemType,
                                'item_name' => $item['item_name'] ?? 'Không xác định',
                                'sign' => intval($item['sign'] ?? 1),
                                'amount' => floatval($item['amount'] ?? 0),
                                'ref_type' => $item['ref_type'] ?? null,
                                'ref_id' => $item['ref_id'] ?? null,
                                'note' => $item['note'] ?? '',
                            ];
                            
                            // Validate item_name
                            if (empty($itemData['item_name'])) {
                                $itemData['item_name'] = 'Không xác định';
                            }
                            
                            PayrollPayslipItem::create($itemData);
                        } catch (\Exception $itemError) {
                            Log::error('createFromPreview: Error creating payslip item', [
                                'payslip_id' => $payslip->id,
                                'user_id' => $userId,
                                'item' => $item ?? null,
                                'error' => $itemError->getMessage()
                            ]);
                            // Continue with next item
                        }
                    }

                    // Nếu có commission, cập nhật status của commission events thành 'paid'
                    if ($hasCommission && $commissionAmount > 0) {
                        try {
                            $periodStart = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
                            $periodEnd = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();
                            
                            $commissionService = app(\App\Services\CommissionEventService::class);
                            $commissionEvents = $commissionService->getCommissionEventsForPayroll(
                                $userId,
                                $periodStart,
                                $periodEnd,
                                $organizationId
                            );

                            // Cập nhật status của các commission events thành 'paid'
                            // getCommissionEventsForPayroll đã chỉ trả về approved events có occurred_at trong tháng
                            foreach ($commissionEvents as $event) {
                                $event->update(['status' => 'paid']);
                                Log::info('Updated commission event status to paid from preview', [
                                    'event_id' => $event->id,
                                    'payslip_id' => $payslip->id,
                                    'user_id' => $userId,
                                    'occurred_at' => $event->occurred_at
                                ]);
                            }
                        } catch (\Exception $commissionError) {
                            Log::error('createFromPreview: Error updating commission events', [
                                'user_id' => $userId,
                                'payslip_id' => $payslip->id ?? null,
                                'commission_amount' => $commissionAmount,
                                'error' => $commissionError->getMessage(),
                                'trace' => $commissionError->getTraceAsString()
                            ]);
                            // Don't throw - continue with payslip creation even if commission events can't be updated
                        }
                    }

                    // Update salary advances if there are deductions
                    if ($deductionAmount > 0) {
                        $salaryAdvanceItems = array_filter($items, function($item) {
                            return ($item['item_type'] ?? '') == PayrollPayslipItem::TYPE_SALARY_ADVANCE_DEDUCTION 
                                && isset($item['ref_id']);
                        });

                        foreach ($salaryAdvanceItems as $item) {
                            try {
                                $advance = \App\Models\SalaryAdvance::find($item['ref_id'] ?? null);
                                if ($advance) {
                                    $amount = floatval($item['amount'] ?? 0);
                                    if ($amount > 0) {
                                        $advance->addRepayment($amount);
                                    }
                                }
                            } catch (\Exception $repaymentError) {
                                Log::error('createFromPreview: Error updating salary advance repayment', [
                                    'user_id' => $userId,
                                    'salary_advance_id' => $item['ref_id'] ?? null,
                                    'error' => $repaymentError->getMessage()
                                ]);
                                // Continue without updating this advance
                            }
                        }
                    }

                    $createdCount++;
                    Log::info('Successfully created payslip', [
                        'payslip_id' => $payslip->id,
                        'user_id' => $userId
                    ]);
                    
                } catch (\Exception $payslipException) {
                    Log::error('Error creating individual payslip', [
                        'exception_type' => get_class($payslipException),
                        'message' => $payslipException->getMessage(),
                        'file' => $payslipException->getFile(),
                        'line' => $payslipException->getLine(),
                        'user_id' => $userId ?? null,
                        'payslip_data' => $payslipData ?? null
                    ]);
                    // Continue với payslip tiếp theo thay vì dừng toàn bộ
                    continue;
                }
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã tạo thành công {$createdCount} phiếu lương!",
                    'created_count' => $createdCount,
                    'redirect' => route('staff.payroll-cycles.show', $payrollCycle->id)
                ]);
            }

            return redirect()->route('staff.payroll-cycles.show', $payrollCycle->id)
                ->with('success', "Đã tạo thành công {$createdCount} phiếu lương!");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating payslips from preview', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $organizationId,
                'user_id' => $user->id ?? null,
                'request_data' => $request->except(['_token', 'password', 'password_confirmation'])
            ]);

            $errorMessage = \App\Helpers\ErrorHelper::getSafeErrorMessage(
                $e,
                'Có lỗi xảy ra khi tạo phiếu lương. Vui lòng thử lại sau hoặc liên hệ quản trị viên.'
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Sync commission events approved vào payslips đã tồn tại
     * Kiểm tra và thêm commission events approved vào payslips nếu chưa có
     */
    public function syncCommissionEvents(PayrollCycle $payrollCycle)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can sync commission events
        $this->requireCapability('finance.payroll.update', 'Bạn không có quyền sync commission events.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user belongs to the same organization
        $this->checkOrganizationAccess(
            $payrollCycle->organization_id,
            'Unauthorized access to payroll cycle.',
            'payroll_cycle',
            $payrollCycle->id
        );

        if ($payrollCycle->status !== 'open') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể sync commission events cho kỳ lương đang mở'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể sync commission events cho kỳ lương đang mở');
        }

        try {
            DB::beginTransaction();

            $periodStart = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
            $periodEnd = Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();

            // Lấy tất cả payslips của cycle này
            $payslips = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                ->with('user')
                ->get();

            $commissionService = app(\App\Services\CommissionEventService::class);
            $updatedCount = 0;

            foreach ($payslips as $payslip) {
                // Lấy danh sách commission events approved có occurred_at trong tháng payroll
                // getCommissionEventsForPayroll đã chỉ trả về approved events có occurred_at trong tháng
                $commissionEvents = $commissionService->getCommissionEventsForPayroll(
                    $payslip->user_id,
                    $periodStart,
                    $periodEnd,
                    $organizationId
                );

                if (count($commissionEvents) > 0) {
                    // Tính tổng commission từ các approved events
                    $newCommission = 0;
                    foreach ($commissionEvents as $event) {
                        $newCommission += $event->commission_total;
                    }

                    // Kiểm tra xem payslip đã có commission item chưa
                    $existingCommissionItem = PayrollPayslipItem::where('payroll_payslip_id', $payslip->id)
                        ->where('item_type', PayrollPayslipItem::TYPE_COMMISSION)
                        ->first();

                    if ($existingCommissionItem) {
                        // Cập nhật amount của commission item
                        $existingCommissionItem->update([
                            'amount' => $existingCommissionItem->amount + $newCommission
                        ]);
                    } else {
                        // Tạo commission item mới
                        PayrollPayslipItem::create([
                            'payroll_payslip_id' => $payslip->id,
                            'item_type' => PayrollPayslipItem::TYPE_COMMISSION,
                            'item_name' => 'Hoa hồng',
                            'sign' => 1,
                            'amount' => $newCommission,
                            'ref_type' => null,
                            'ref_id' => null,
                            'note' => 'Hoa hồng',
                        ]);
                    }

                    // Cập nhật payslip totals
                    $payslip->gross_amount += $newCommission;
                    $payslip->net_amount += $newCommission;
                    $payslip->save();

                    // Cập nhật status của commission events thành 'paid'
                    // getCommissionEventsForPayroll đã chỉ trả về approved events có occurred_at trong tháng
                    foreach ($commissionEvents as $event) {
                        $event->update(['status' => 'paid']);
                        Log::info('Updated commission event status to paid (sync)', [
                            'event_id' => $event->id,
                            'payslip_id' => $payslip->id,
                            'user_id' => $payslip->user_id,
                            'occurred_at' => $event->occurred_at
                        ]);
                    }

                    $updatedCount++;
                }
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã sync {$updatedCount} phiếu lương với commission events!",
                    'updated_count' => $updatedCount
                ]);
            }

            return back()->with('success', "Đã sync {$updatedCount} phiếu lương với commission events!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing commission events: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi sync commission events: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi sync commission events: ' . $e->getMessage());
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
            
            Log::info('Calculating prorated basic salary', [
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
