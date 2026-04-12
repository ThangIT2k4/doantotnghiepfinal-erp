<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvance;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryAdvanceController extends Controller
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
        $this->requireCapability('finance.salary_advance.view', 'Bạn không có quyền xem Salary Advances.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        $shouldFilter = $this->shouldFilterByOwnership('finance.salary_advance');
        
        // Base query for statistics (NOT filtered - always show all)
        if ($shouldFilter) {
            $statsQuery = SalaryAdvance::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->where('user_id', $user->id);
        } else {
            $statsQuery = SalaryAdvance::where('organization_id', $organizationId)
                ->whereNull('deleted_at');
        }
        
        // Calculate statistics from base query (not filtered)
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (int) (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected' => (int) (clone $statsQuery)->where('status', 'rejected')->count(),
            'repaid' => (int) (clone $statsQuery)->where('status', 'repaid')->count(),
            'partially_repaid' => (int) (clone $statsQuery)->where('status', 'partially_repaid')->count(),
        ];
        
        // Main query for listing
        // Tự động filter theo ownership nếu agent chỉ có view_own
        $query = SalaryAdvance::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->with(['user.userProfile', 'approver', 'rejector']);
        
        if ($shouldFilter) {
            // Agent sees only their own advances
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user (only for manager)
        if ($canViewAll && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('advance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('advance_date', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->whereHas('userProfile', function($profileQuery) use ($search) {
                    $profileQuery->where('full_name', 'like', "%{$search}%");
                })->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['created_at', 'status', 'advance_date', 'expected_repayment_date', 'amount', 'remaining_amount'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $salaryAdvances = $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString();

        // Get users for filter dropdown (only for manager)
        $users = collect([]);
        if ($canViewAll) {
            $users = User::with('userProfile')
                ->whereHas('organizations', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
        }

        $statuses = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Đã từ chối',
            'repaid' => 'Đã hoàn trả',
            'partially_repaid' => 'Hoàn trả một phần'
        ];

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng cộng',
                'icon' => 'fa-money-bill-wave',
                'color' => 'primary',
                'filter' => '',
            ],
            'pending' => [
                'value' => $stats['pending'] ?? 0,
                'label' => 'Chờ duyệt',
                'icon' => 'fa-clock',
                'color' => 'warning',
                'filter' => 'pending',
            ],
            'approved' => [
                'value' => $stats['approved'] ?? 0,
                'label' => 'Đã duyệt',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'approved',
            ],
            'rejected' => [
                'value' => $stats['rejected'] ?? 0,
                'label' => 'Đã từ chối',
                'icon' => 'fa-times-circle',
                'color' => 'danger',
                'filter' => 'rejected',
            ],
            'repaid' => [
                'value' => $stats['repaid'] ?? 0,
                'label' => 'Đã hoàn trả',
                'icon' => 'fa-check-double',
                'color' => 'info',
                'filter' => 'repaid',
            ],
            'partially_repaid' => [
                'value' => $stats['partially_repaid'] ?? 0,
                'label' => 'Hoàn trả một phần',
                'icon' => 'fa-percent',
                'color' => 'secondary',
                'filter' => 'partially_repaid',
            ],
        ];
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.finance.salary-advances.partials.table', [
                'salaryAdvances' => $salaryAdvances,
                'isManager' => $canViewAll,
                'statuses' => $statuses,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'salary-advances-table-container',
                'action' => route('staff.salary-advances.index'),
                'columns' => 6
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.finance.salary-advances.partials.table', [
                'salaryAdvances' => $salaryAdvances,
                'isManager' => $canViewAll,
                'statuses' => $statuses,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'filterByStatus',
                'onClearClick' => 'clearAllFilters',
                'columns' => 6
            ])->render();
            
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
            ]);
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.salary-advances.index', compact('salaryAdvances', 'users', 'statuses', 'stats', 'isManager', 'sortBy', 'sortOrder', 'statsFormatted'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.salary_advance.create', 'Bạn không có quyền tạo Salary Advances.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // Manager can create for any user (manager/agent only), agent can only create for themselves
        $users = collect([]);
        if ($canViewAll) {
            // Get manager and agent role IDs
            $managerUserIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'manager')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            $agentUserIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'agent')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            // Combine manager and agent user IDs
            $allowedUserIds = array_unique(array_merge($managerUserIds, $agentUserIds));
            
            // Get users with manager or agent role only
            $users = User::with('userProfile')
                ->whereIn('id', $allowedUserIds)
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->full_name ?? '';
                })->values();
        }

        $repaymentMethods = [
            'payroll_deduction' => 'Trừ lương',
            'direct_payment' => 'Thanh toán trực tiếp',
            'installment' => 'Trả góp'
        ];

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.salary-advances.create', compact('users', 'repaymentMethods', 'isManager'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.salary_advance.create', 'Bạn không có quyền tạo Salary Advances.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // Validation rules differ for manager vs agent
        $validationRules = [
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'advance_date' => 'required|date',
            'expected_repayment_date' => 'required|date|after:advance_date',
            'reason' => 'required|string|max:1000',
            'repayment_method' => 'required|in:payroll_deduction,direct_payment,installment',
            'installment_months' => 'nullable|integer|min:1|max:12',
            'monthly_deduction' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000'
        ];
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:VND,USD',
            'advance_date' => 'required|date',
            'expected_repayment_date' => 'required|date|after:advance_date',
            'reason' => 'required|string|max:1000',
            'repayment_method' => 'required|in:payroll_deduction,direct_payment,installment',
            'installment_months' => 'nullable|integer|min:1|max:12',
            'monthly_deduction' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'user_id' => 'required|exists:users,id',
        ]);
        
        // Agent có giới hạn amount và advance_date
        if (!$canViewAll) {
            $validated['amount'] = max(100000, min(50000000, $validated['amount']));
            if ($validated['advance_date'] > now()) {
                $validated['advance_date'] = now()->toDateString();
            }
        }

        try {
            DB::beginTransaction();
            
            // Tự động gán user_id cho agent (không cho phép sửa)
            // Manager có thể gán cho user khác, Agent phải gán cho chính mình
            $this->enforceUserId($validated, 'user_id');

            $salaryAdvance = SalaryAdvance::create([
                'organization_id' => $organizationId,
                'user_id' => $validated['user_id'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'advance_date' => $validated['advance_date'],
                'expected_repayment_date' => $validated['expected_repayment_date'],
                'reason' => $validated['reason'],
                'status' => 'pending',
                'repaid_amount' => 0,
                'remaining_amount' => $validated['amount'],
                'repayment_method' => $validated['repayment_method'],
                'installment_months' => $validated['installment_months'] ?? null,
                'monthly_deduction' => $validated['monthly_deduction'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn ứng lương đã được tạo thành công!',
                    'data' => $salaryAdvance
                ]);
            }

            return redirect()->route('staff.salary-advances.index')
                ->with('success', 'Đơn ứng lương đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating salary advance: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo đơn ứng lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo đơn ứng lương');
        }
    }

    public function show(SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability
        $this->requireCapability('finance.salary_advance.view', 'Bạn không có quyền xem Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // For agent, only allow viewing their own advances
        if (!$canViewAll && $salaryAdvance->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xem đơn ứng lương của người khác.');
        }

        $salaryAdvance->load(['user.userProfile', 'approver', 'rejector']);

        return view('staff.finance.salary-advances.show', compact('salaryAdvance'));
    }

    public function edit(SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.salary_advance.update', 'Bạn không có quyền chỉnh sửa Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );

        $organizationId = $this->getCurrentOrganizationId();

        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // For agent, only allow editing their own advances
        if (!$canViewAll && $salaryAdvance->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền chỉnh sửa đơn ứng lương của người khác.');
        }

        // Only allow editing pending advances
        if (!$salaryAdvance->canBeDeleted()) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa đơn ứng lương đang chờ duyệt hoặc đã từ chối.');
        }
        
        // Manager can edit for any user (manager/agent only), agent can only edit for themselves
        $users = collect([]);
        if ($canViewAll) {
            // Get manager and agent role IDs
            $managerUserIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'manager')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            $agentUserIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'agent')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            // Combine manager and agent user IDs
            $allowedUserIds = array_unique(array_merge($managerUserIds, $agentUserIds));
            
            // Get users with manager or agent role only
            $users = User::with('userProfile')
                ->whereIn('id', $allowedUserIds)
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->full_name ?? '';
                })->values();
        }

        $repaymentMethods = [
            'payroll_deduction' => 'Trừ lương',
            'direct_payment' => 'Thanh toán trực tiếp',
            'installment' => 'Trả góp'
        ];

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.salary-advances.edit', compact('salaryAdvance', 'users', 'repaymentMethods', 'isManager'));
    }

    public function update(Request $request, SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.salary_advance.update', 'Bạn không có quyền cập nhật Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // For agent, only allow editing their own advances
        if (!$canViewAll && $salaryAdvance->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền cập nhật đơn ứng lương của người khác.');
        }

        // Only allow editing pending advances
        if (!$salaryAdvance->canBeDeleted()) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa đơn ứng lương đang chờ duyệt hoặc đã từ chối.');
        }

        // Validation rules differ for manager vs agent
        $validationRules = [
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'advance_date' => 'required|date',
            'expected_repayment_date' => 'required|date|after:advance_date',
            'reason' => 'required|string|max:1000',
            'repayment_method' => 'required|in:payroll_deduction,direct_payment,installment',
            'installment_months' => 'nullable|integer|min:1|max:12',
            'monthly_deduction' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000'
        ];
        
        $canViewAll = $this->canViewAll('finance.salary_advance');
        if ($canViewAll) {
            $validationRules['user_id'] = 'required|exists:users,id';
        } else {
            // Agent can only update for themselves
            $request->merge(['user_id' => $user->id]);
            $validationRules['amount'] = 'required|numeric|min:100000|max:50000000';
            $validationRules['advance_date'] = 'required|date|before_or_equal:today';
        }
        
        $request->validate($validationRules);
        
        // Ensure agent can only update for themselves
        if (!$canViewAll && $request->user_id !== $user->id) {
            abort(403, 'Bạn chỉ có thể cập nhật đơn ứng lương của chính mình.');
        }

        try {
            DB::beginTransaction();

            $salaryAdvance->update([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'advance_date' => $request->advance_date,
                'expected_repayment_date' => $request->expected_repayment_date,
                'reason' => $request->reason,
                'remaining_amount' => $request->amount, // Reset remaining amount
                'repayment_method' => $request->repayment_method,
                'installment_months' => $request->installment_months,
                'monthly_deduction' => $request->monthly_deduction,
                'note' => $request->note,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn ứng lương đã được cập nhật thành công!',
                    'redirect' => route('staff.salary-advances.show', $salaryAdvance->id),
                    'data' => $salaryAdvance
                ]);
            }

            return redirect()->route('staff.salary-advances.show', $salaryAdvance->id)
                ->with('success', 'Đơn ứng lương đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating salary advance: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật đơn ứng lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật đơn ứng lương');
        }
    }

    public function destroy(SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('finance.salary_advance.delete', 'Bạn không có quyền xóa Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );
        
        // Check if user has finance.salary_advance.view capability (manager has all permissions)
        $canViewAll = $this->canViewAll('finance.salary_advance');
        
        // For agent, only allow deleting their own advances
        if (!$canViewAll && $salaryAdvance->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xóa đơn ứng lương của người khác.');
        }

        // Only allow deleting pending or rejected advances
        if (!$salaryAdvance->canBeDeleted()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể xóa đơn ứng lương đang chờ duyệt hoặc đã từ chối'
                ], 400);
            }
            return back()->with('error', 'Chỉ có thể xóa đơn ứng lương đang chờ duyệt hoặc đã từ chối');
        }

        try {
            $salaryAdvance->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn ứng lương đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.salary-advances.index')
                ->with('success', 'Đơn ứng lương đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting salary advance: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa đơn ứng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa đơn ứng lương');
        }
    }

    public function approve(SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can approve
        $this->requireCapability('finance.salary_advance.approve', 'Bạn không có quyền duyệt Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );

        if (!$salaryAdvance->canBeApproved()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể duyệt đơn ứng lương này'
                ], 400);
            }
            return back()->with('error', 'Không thể duyệt đơn ứng lương này');
        }

        try {
            $salaryAdvance->approve(Auth::id());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn ứng lương đã được duyệt thành công!'
                ]);
            }

            return back()->with('success', 'Đơn ứng lương đã được duyệt thành công!');

        } catch (\Exception $e) {
            Log::error('Error approving salary advance: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi duyệt đơn ứng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi duyệt đơn ứng lương');
        }
    }

    public function reject(Request $request, SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can reject
        $this->requireCapability('finance.salary_advance.reject', 'Bạn không có quyền từ chối Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );

        if (!$salaryAdvance->canBeRejected()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể từ chối đơn ứng lương này'
                ], 400);
            }
            return back()->with('error', 'Không thể từ chối đơn ứng lương này');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        try {
            $salaryAdvance->reject(Auth::id(), $request->rejection_reason);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn ứng lương đã được từ chối!'
                ]);
            }

            return back()->with('success', 'Đơn ứng lương đã được từ chối!');

        } catch (\Exception $e) {
            Log::error('Error rejecting salary advance: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi từ chối đơn ứng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi từ chối đơn ứng lương');
        }
    }

    public function addRepayment(Request $request, SalaryAdvance $salaryAdvance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can add repayment
        $this->requireCapability('finance.salary_advance.update', 'Bạn không có quyền thêm thanh toán cho Salary Advances.');
        
        $this->checkOrganizationAccess(
            $salaryAdvance->organization_id,
            'Unauthorized access to salary advance.',
            'salary_advance',
            $salaryAdvance->id
        );

        if (!$salaryAdvance->canBeRepaid()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể thêm thanh toán cho đơn ứng lương này'
                ], 400);
            }
            return back()->with('error', 'Không thể thêm thanh toán cho đơn ứng lương này');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $salaryAdvance->remaining_amount
        ]);

        try {
            DB::beginTransaction();

            $salaryAdvance->addRepayment($request->amount);

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán đã được thêm thành công!'
                ]);
            }

            return back()->with('success', 'Thanh toán đã được thêm thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding repayment: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi thêm thanh toán'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi thêm thanh toán');
        }
    }
}