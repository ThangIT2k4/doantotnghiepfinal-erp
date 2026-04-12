<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SalaryContract;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryContractController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has party.access capability
        $hasPartyAccess = $this->checkCapability('party.access');
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.');
        }
        
        // Check capability - manager can view all, agent can only view their own
        $this->requireCapability('party.salary_contract.view', 'Bạn không có quyền xem Salary Contracts.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all salary contracts or only own contracts
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('party.salary_contract');
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        $shouldFilter = $this->shouldFilterByOwnership('party.salary_contract');
        
        // Base query for statistics (NOT filtered - always show all)
        if ($shouldFilter) {
            $statsQuery = SalaryContract::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->where('user_id', $user->id);
        } else {
            $statsQuery = SalaryContract::where('organization_id', $organizationId)
                ->whereNull('deleted_at');
        }
        
        // Calculate statistics from base query (not filtered)
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'active' => (int) (clone $statsQuery)->where('status', 'active')->count(),
            'inactive' => (int) (clone $statsQuery)->where('status', 'inactive')->count(),
            'terminated' => (int) (clone $statsQuery)->where('status', 'terminated')->count(),
        ];
        
        // Main query for listing
        // Tự động filter theo ownership nếu agent chỉ có view_own
        $query = SalaryContract::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->with(['user.userProfile']);
        
        if ($shouldFilter) {
            // Agent sees only their own contracts
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
            $query->where('effective_from', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('effective_from', '<=', $request->date_to);
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
        $allowedSortFields = ['created_at', 'status', 'effective_from', 'effective_to', 'base_salary'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $salaryContracts = $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString();

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
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm dừng',
            'terminated' => 'Đã chấm dứt'
        ];

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng cộng',
                'icon' => 'fa-file-contract',
                'color' => 'primary',
                'filter' => '',
            ],
            'active' => [
                'value' => $stats['active'] ?? 0,
                'label' => 'Đang hoạt động',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'active',
            ],
            'inactive' => [
                'value' => $stats['inactive'] ?? 0,
                'label' => 'Tạm dừng',
                'icon' => 'fa-pause-circle',
                'color' => 'warning',
                'filter' => 'inactive',
            ],
            'terminated' => [
                'value' => $stats['terminated'] ?? 0,
                'label' => 'Đã chấm dứt',
                'icon' => 'fa-times-circle',
                'color' => 'danger',
                'filter' => 'terminated',
            ],
        ];
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.finance.salary-contracts.partials.table', [
                'salaryContracts' => $salaryContracts,
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
                'tableContainerId' => 'salary-contracts-table-container',
                'action' => route('staff.salary-contracts.index'),
                'columns' => 4
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.finance.salary-contracts.partials.table', [
                'salaryContracts' => $salaryContracts,
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
                'columns' => 4
            ])->render();
            
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
            ]);
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.finance.salary-contracts.index', compact('salaryContracts', 'users', 'statuses', 'isManager', 'stats', 'sortBy', 'sortOrder', 'statsFormatted'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create contracts
        $this->requireCapability('party.salary_contract.create', 'Bạn không có quyền tạo Salary Contracts.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get users with manager and agent roles
        // Exclude users who already have active contracts
        $today = now()->toDateString();
        
        $usersWithActiveContracts = SalaryContract::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $today)
            ->where(function($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->pluck('user_id')
            ->toArray();
        
        $users = User::with('userProfile')
            ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
            ->join('roles', 'organization_users.role_id', '=', 'roles.id')
            ->where('organization_users.organization_id', $organizationId)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->whereIn('roles.key_code', ['manager', 'agent']) // Include both manager and agent
            ->whereNull('users.deleted_at')
            ->whereNotIn('users.id', $usersWithActiveContracts) // Exclude users with active contracts
            ->select('users.*')
            ->distinct()
            ->get()
            ->sortBy(function($user) {
                return $user->userProfile->full_name ?? $user->full_name ?? '';
            })
            ->values();

        $payCycles = [
            'monthly' => 'Hàng tháng',
            'weekly' => 'Hàng tuần',
            'daily' => 'Hàng ngày'
        ];

        $statuses = [
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm dừng'
        ];

        return view('staff.finance.salary-contracts.create', compact('users', 'payCycles', 'statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'pay_cycle' => 'required|in:monthly,weekly,daily',
            'pay_day' => 'required|integer|min:1|max:31',
            'allowances_json' => 'nullable|string',
            'kpi_target_json' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check capability - only manager can create contracts
            $this->requireCapability('party.salary_contract.create', 'Bạn không có quyền tạo Salary Contracts.');
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                abort(403, 'Bạn không thuộc tổ chức nào.');
            }

            // Check if user already has an active contract
            $existingContract = SalaryContract::where('user_id', $request->user_id)
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where(function($q) use ($request) {
                    $q->where('effective_from', '<=', $request->effective_from)
                      ->where(function($q2) use ($request) {
                          $q2->whereNull('effective_to')
                             ->orWhere('effective_to', '>=', $request->effective_from);
                      });
                })
                ->first();

            if ($existingContract) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nhân viên này đã có hợp đồng lương đang hoạt động trong khoảng thời gian này'
                    ], 400);
                }
                return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng lương đang hoạt động trong khoảng thời gian này');
            }

            // Parse JSON data
            $allowances = [];
            if ($request->allowances_json) {
                $allowances = json_decode($request->allowances_json, true) ?: [];
            }
            
            $kpiTargets = [];
            if ($request->kpi_target_json) {
                $kpiTargets = json_decode($request->kpi_target_json, true) ?: [];
            }

            $salaryContract = SalaryContract::create([
                'organization_id' => $organizationId,
                'user_id' => $request->user_id,
                'base_salary' => $request->base_salary,
                'currency' => $request->currency,
                'pay_cycle' => $request->pay_cycle,
                'pay_day' => $request->pay_day,
                'allowances_json' => $allowances,
                'kpi_target_json' => $kpiTargets,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'status' => $request->status,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng lương đã được tạo thành công!',
                    'data' => $salaryContract
                ]);
            }

            return redirect()->route('staff.salary-contracts.show', $salaryContract)
                ->with('success', 'Hợp đồng lương đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating salary contract: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo hợp đồng lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo hợp đồng lương');
        }
    }

    public function show(SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has party.access capability
        $hasPartyAccess = $this->checkCapability('party.access');
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.');
        }
        
        // Check capability
        $this->requireCapability('party.salary_contract.view', 'Bạn không có quyền xem Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );
        
        // Check if user can view all salary contracts or only own contracts
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('party.salary_contract');
        
        // For agent, only allow viewing their own contracts
        if (!$canViewAll && $salaryContract->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xem hợp đồng lương của người khác.');
        }

        $salaryContract->load(['user.userProfile']);

        return view('staff.finance.salary-contracts.show', compact('salaryContract'));
    }

    public function edit(SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit contracts
        $this->requireCapability('party.salary_contract.update', 'Bạn không có quyền chỉnh sửa Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        $organizationId = $this->getCurrentOrganizationId();

        // Only allow editing if status is 'active' or 'inactive'
        if ($salaryContract->status === 'terminated') {
            return back()->with('error', 'Không thể chỉnh sửa hợp đồng lương đã chấm dứt.');
        }

        // Get users with manager and agent roles only
        $users = User::with('userProfile')
            ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
            ->join('roles', 'organization_users.role_id', '=', 'roles.id')
            ->where('organization_users.organization_id', $organizationId)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->whereIn('roles.key_code', ['manager', 'agent'])
            ->whereNull('users.deleted_at')
            ->select('users.*')
            ->distinct()
            ->get()
            ->sortBy(function($user) {
                return $user->userProfile->full_name ?? $user->full_name ?? '';
            })
            ->values();

        $payCycles = [
            'monthly' => 'Hàng tháng',
            'weekly' => 'Hàng tuần',
            'daily' => 'Hàng ngày'
        ];

        $statuses = [
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm dừng'
        ];

        return view('staff.finance.salary-contracts.edit', compact('salaryContract', 'users', 'payCycles', 'statuses'));
    }

    public function update(Request $request, SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update contracts
        $this->requireCapability('party.salary_contract.update', 'Bạn không có quyền cập nhật Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        // Only allow editing if status is 'active' or 'inactive'
        if ($salaryContract->status === 'terminated') {
            return back()->with('error', 'Không thể chỉnh sửa hợp đồng lương đã chấm dứt.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'pay_cycle' => 'required|in:monthly,weekly,daily',
            'pay_day' => 'required|integer|min:1|max:31',
            'allowances_json' => 'nullable|string',
            'kpi_target_json' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            // Check if user already has an active contract (excluding current one)
            $existingContract = SalaryContract::where('user_id', $request->user_id)
                ->where('organization_id', $salaryContract->organization_id)
                ->where('id', '!=', $salaryContract->id)
                ->where('status', 'active')
                ->where(function($q) use ($request) {
                    $q->where('effective_from', '<=', $request->effective_from)
                      ->where(function($q2) use ($request) {
                          $q2->whereNull('effective_to')
                             ->orWhere('effective_to', '>=', $request->effective_from);
                      });
                })
                ->first();

            if ($existingContract) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nhân viên này đã có hợp đồng lương đang hoạt động trong khoảng thời gian này'
                    ], 400);
                }
                return back()->withInput()->with('error', 'Nhân viên này đã có hợp đồng lương đang hoạt động trong khoảng thời gian này');
            }

            // Parse JSON data
            $allowances = [];
            if ($request->allowances_json) {
                $allowances = json_decode($request->allowances_json, true) ?: [];
            }
            
            $kpiTargets = [];
            if ($request->kpi_target_json) {
                $kpiTargets = json_decode($request->kpi_target_json, true) ?: [];
            }

            $salaryContract->update([
                'user_id' => $request->user_id,
                'base_salary' => $request->base_salary,
                'currency' => $request->currency,
                'pay_cycle' => $request->pay_cycle,
                'pay_day' => $request->pay_day,
                'allowances_json' => $allowances,
                'kpi_target_json' => $kpiTargets,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'status' => $request->status,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng lương đã được cập nhật thành công!',
                    'redirect' => route('staff.salary-contracts.show', $salaryContract->id),
                    'data' => $salaryContract
                ]);
            }

            return redirect()->route('staff.salary-contracts.show', $salaryContract->id)
                ->with('success', 'Hợp đồng lương đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating salary contract: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật hợp đồng lương'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật hợp đồng lương');
        }
    }

    public function destroy(SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete contracts
        $this->requireCapability('party.salary_contract.delete', 'Bạn không có quyền xóa Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        // Only allow deleting if status is 'inactive' or 'terminated'
        // Block deletion if status is 'active'
        if ($salaryContract->status === 'active') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa hợp đồng lương đang hoạt động. Hãy tạm dừng hoặc chấm dứt trước.'
                ], 400);
            }
            return back()->with('error', 'Không thể xóa hợp đồng lương đang hoạt động. Hãy tạm dừng hoặc chấm dứt trước.');
        }

        try {
            $salaryContract->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng lương đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.salary-contracts.index')
                ->with('success', 'Hợp đồng lương đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting salary contract: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa hợp đồng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa hợp đồng lương');
        }
    }

    public function terminate(SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can terminate contracts
        $this->requireCapability('party.salary_contract.terminate', 'Bạn không có quyền chấm dứt Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        if ($salaryContract->status === 'terminated') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hợp đồng lương đã được chấm dứt trước đó'
                ], 400);
            }
            return back()->with('error', 'Hợp đồng lương đã được chấm dứt trước đó');
        }

        try {
            $salaryContract->update([
                'status' => 'terminated',
                'effective_to' => now()->toDateString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng lương đã được chấm dứt thành công!'
                ]);
            }

            return back()->with('success', 'Hợp đồng lương đã được chấm dứt thành công!');

        } catch (\Exception $e) {
            Log::error('Error terminating salary contract: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi chấm dứt hợp đồng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi chấm dứt hợp đồng lương');
        }
    }

    public function activate(SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can activate contracts
        $this->requireCapability('party.salary_contract.update', 'Bạn không có quyền kích hoạt Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        if ($salaryContract->status === 'active') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hợp đồng lương đã đang hoạt động'
                ], 400);
            }
            return back()->with('error', 'Hợp đồng lương đã đang hoạt động');
        }

        if ($salaryContract->status === 'terminated') {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể kích hoạt hợp đồng lương đã chấm dứt'
                ], 400);
            }
            return back()->with('error', 'Không thể kích hoạt hợp đồng lương đã chấm dứt');
        }

        try {
            $salaryContract->update([
                'status' => 'active'
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng lương đã được kích hoạt thành công!'
                ]);
            }

            return back()->with('success', 'Hợp đồng lương đã được kích hoạt thành công!');

        } catch (\Exception $e) {
            Log::error('Error activating salary contract: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi kích hoạt hợp đồng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi kích hoạt hợp đồng lương');
        }
    }

    public function updateStatus(Request $request, SalaryContract $salaryContract)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update status
        $this->requireCapability('party.salary_contract.update', 'Bạn không có quyền cập nhật trạng thái Salary Contracts.');
        
        $this->checkOrganizationAccess(
            $salaryContract->organization_id,
            'Unauthorized access to salary contract.',
            'salary_contract',
            $salaryContract->id
        );

        $request->validate([
            'status' => 'required|in:active,inactive,terminated'
        ]);

        $newStatus = $request->status;

        // Validate status transitions
        if ($salaryContract->status === 'terminated' && $newStatus !== 'terminated') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể thay đổi trạng thái hợp đồng lương đã chấm dứt'
                ], 400);
            }
            return back()->with('error', 'Không thể thay đổi trạng thái hợp đồng lương đã chấm dứt');
        }

        try {
            $updateData = ['status' => $newStatus];
            
            // If terminating, set effective_to to today
            if ($newStatus === 'terminated') {
                $updateData['effective_to'] = now()->toDateString();
            }

            $salaryContract->update($updateData);

            $statusLabels = [
                'active' => 'Đang hoạt động',
                'inactive' => 'Tạm dừng',
                'terminated' => 'Đã chấm dứt'
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trạng thái hợp đồng lương đã được cập nhật thành công!',
                    'status_label' => $statusLabels[$newStatus] ?? $newStatus
                ]);
            }

            return back()->with('success', 'Trạng thái hợp đồng lương đã được cập nhật thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating salary contract status: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái hợp đồng lương'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái hợp đồng lương');
        }
    }
}