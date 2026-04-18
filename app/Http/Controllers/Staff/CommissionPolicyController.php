<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CommissionPolicy;
use App\Models\CommissionEvent;
use App\Models\Organization;
use App\Models\User;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionPolicyController extends Controller
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
        
        // Check capability - manager can view all, agent can only view
        $this->requireCapability('finance.commission.view', 'Bạn không có quyền xem Commission Policies.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all commission policies or only own policies
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.commission');
        
        $query = CommissionPolicy::with(['organization'])
            ->withCount('events')
            ->where('organization_id', $organizationId);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('trigger_event')) {
            $query->where('trigger_event', $request->trigger_event);
        }

        if ($request->filled('calc_type')) {
            $query->where('calc_type', $request->calc_type);
        }

        if ($request->has('active') && $request->active !== '') {
            $query->where('active', $request->active);
        }

        $query->whereNull('deleted_at');

        // Get policies with sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'code', 'title', 'trigger_event', 'calc_type', 'active', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $policies = $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString();
        
        // Get filter options
        $triggerEvents = [
            'deposit_paid' => 'Thanh toán cọc',
            'lease_signed' => 'Ký hợp đồng',
            'invoice_paid' => 'Thanh toán hóa đơn',
            'viewing_done' => 'Hoàn thành xem phòng',
            'listing_published' => 'Đăng tin'
        ];
        
        $calcTypes = [
            'percent' => 'Phần trăm',
            'flat' => 'Số tiền cố định',
            'tiered' => 'Bậc thang'
        ];
        
        // Calculate statistics FIRST from base query (before any filters)
        // Query directly from CommissionPolicy model to ensure accurate statistics
        $statsQuery = CommissionPolicy::where('organization_id', $organizationId)->whereNull('deleted_at');
        
        // Calculate statistics (NOT filtered - always show all)
        $stats = [
            'total_policies' => (clone $statsQuery)->count(),
            'active_policies' => (clone $statsQuery)->where('active', true)->count(),
            'inactive_policies' => (clone $statsQuery)->where('active', false)->count(),
        ];
        
        // Calculate events statistics (NOT filtered - always show all)
        $eventsQuery = CommissionEvent::where('organization_id', $organizationId)->whereNull('deleted_at');
        
        if (!$canViewAll) {
            $eventsQuery->where('agent_id', $user->id);
        }
        
        $stats['total_events'] = (clone $eventsQuery)->count();
        $stats['total_commission'] = (clone $eventsQuery)->sum('commission_total');
        
        $statusStats = [
            'active' => $stats['active_policies'] ?? 0,
            'inactive' => $stats['inactive_policies'] ?? 0,
        ];

        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total_policies'] ?? 0,
                'label' => 'Tổng chính sách',
                'icon' => 'fa-file-contract',
                'color' => 'primary',
                'filter' => '',
            ],
            'active' => [
                'value' => $statusStats['active'] ?? 0,
                'label' => 'Đang hoạt động',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => '1',
            ],
            'inactive' => [
                'value' => $statusStats['inactive'] ?? 0,
                'label' => 'Không hoạt động',
                'icon' => 'fa-pause-circle',
                'color' => 'secondary',
                'filter' => '0',
            ],
            'total_events' => [
                'value' => $stats['total_events'] ?? 0,
                'label' => 'Tổng sự kiện',
                'icon' => 'fa-chart-line',
                'color' => 'info',
                'filter' => '',
            ],
            'total_commission' => [
                'value' => $stats['total_commission'] ?? 0,
                'label' => 'Tổng hoa hồng (VNĐ)',
                'icon' => 'fa-coins',
                'color' => 'success',
                'filter' => '',
                'format' => 'currency',
            ],
        ];
        
        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial with statistics cards update via hx-swap-oob
        if ($isHtmx) {
            $tableHtml = view('staff.finance.commission-policies.partials.table', [
                'policies' => $policies,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('active', ''),
                'filterKey' => 'active',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'commission-policies-table-container',
                'action' => route('staff.commission-policies.index'),
                'columns' => 5
            ])->render();
            
            // Return table HTML with statistics cards update via hx-swap-oob
            $html = $tableHtml . "\n<div id='statistics-cards-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
            
            return response($html)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.finance.commission-policies.partials.table', [
                'policies' => $policies,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('active', ''),
                'filterKey' => 'active',
                'onFilterClick' => 'filterByStatus',
                'onClearClick' => 'clearAllFilters',
                'columns' => 5
            ])->render();
            
            return response()->json([
                'success' => true,
                'html' => $tableHtml,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
                'pagination' => [
                    'current_page' => $policies->currentPage(),
                    'last_page' => $policies->lastPage(),
                    'per_page' => $policies->perPage(),
                    'total' => $policies->total(),
                ]
            ]);
        }

        return view('staff.finance.commission-policies.index', compact('policies', 'stats', 'statusStats', 'triggerEvents', 'calcTypes', 'sortBy', 'sortOrder', 'statsFormatted'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create commission policies
        $this->requireCapability('finance.commission.create', 'Bạn không có quyền tạo Commission Policies.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $triggerEvents = [
            'deposit_paid' => 'Thanh toán cọc',
            'lease_signed' => 'Ký hợp đồng',
            'invoice_paid' => 'Thanh toán hóa đơn',
            'viewing_done' => 'Hoàn thành xem phòng',
            'listing_published' => 'Đăng tin'
        ];

        $calcTypes = [
            'percent' => 'Phần trăm',
            'flat' => 'Số tiền cố định',
            'tiered' => 'Bậc thang'
        ];

        $basisTypes = [
            'cash' => 'Tiền mặt',
            'accrual' => 'Dồn tích'
        ];

        return view('staff.finance.commission-policies.create', compact('triggerEvents', 'calcTypes', 'basisTypes'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create commission policies
        $this->requireCapability('finance.commission.create', 'Bạn không có quyền tạo Commission Policies.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $request->validate([
            'code' => 'required|string|max:50|unique:commission_policies,code',
            'title' => 'required|string|max:150',
            'trigger_event' => 'required|in:deposit_paid,lease_signed,invoice_paid,viewing_done,listing_published',
            'basis' => 'required|in:cash,accrual',
            'calc_type' => 'required|in:percent,flat,tiered',
            'percent_value' => 'nullable|numeric|min:0|max:100',
            'flat_amount' => 'nullable|numeric|min:0',
            'apply_limit_months' => 'nullable|integer|min:1|max:12',
            'min_amount' => 'nullable|numeric|min:0',
            'cap_amount' => 'nullable|numeric|min:0',
            'active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $policy = CommissionPolicy::create([
                'organization_id' => $organizationId,
                'code' => $request->code,
                'title' => $request->title,
                'trigger_event' => $request->trigger_event,
                'basis' => $request->basis,
                'calc_type' => $request->calc_type,
                'percent_value' => $request->percent_value,
                'flat_amount' => $request->flat_amount,
                'apply_limit_months' => $request->apply_limit_months,
                'min_amount' => $request->min_amount,
                'cap_amount' => $request->cap_amount,
                'active' => $request->boolean('active', true),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chính sách hoa hồng đã được tạo thành công!',
                    'data' => $policy
                ]);
            }

            return redirect()->route('staff.commission-policies.index')
                ->with('success', 'Chính sách hoa hồng đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating commission policy: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo chính sách hoa hồng'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo chính sách hoa hồng');
        }
    }

    public function show(CommissionPolicy $commissionPolicy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has finance.access capability
        $hasFinanceAccess = $this->checkCapability('finance.access');
        if (!$hasFinanceAccess) {
            abort(403, 'Bạn không có quyền truy cập module Finance.');
        }
        
        // Check capability
        $this->requireCapability('finance.commission.view', 'Bạn không có quyền xem Commission Policies.');
        
        $this->checkOrganizationAccess(
            $commissionPolicy->organization_id,
            'Unauthorized access to commission policy.',
            'commission_policy',
            $commissionPolicy->id
        );
        
        // Check if user can view all commission policies or only own policies
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('finance.commission');
        
        $commissionPolicy->load(['organization', 'events.agent', 'events.lease', 'events.unit']);
        
        // For agent, only show their own events
        if (!$canViewAll) {
            $events = CommissionEvent::where('policy_id', $commissionPolicy->id)
                ->where('agent_id', $user->id)
                ->whereNull('deleted_at')
                ->with(['lease.unit.property', 'lease.tenant', 'agent'])
                ->orderBy('occurred_at', 'desc')
                ->paginate(20);
            
            // Thống kê cho chính sách này (chỉ cho agent)
            $policyStats = [
                'total_events' => CommissionEvent::where('policy_id', $commissionPolicy->id)
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->count(),
                'total_commission' => CommissionEvent::where('policy_id', $commissionPolicy->id)
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->sum('commission_total'),
                'paid_commission' => CommissionEvent::where('policy_id', $commissionPolicy->id)
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->where('status', 'paid')
                    ->sum('commission_total'),
                'pending_commission' => CommissionEvent::where('policy_id', $commissionPolicy->id)
                    ->where('agent_id', $user->id)
                    ->whereNull('deleted_at')
                    ->where('status', 'pending')
                    ->sum('commission_total'),
            ];
            
            return view('staff.finance.commission-policies.show', compact('commissionPolicy', 'events', 'policyStats'));
        }
        
        // Manager sees all events
        $events = CommissionEvent::where('policy_id', $commissionPolicy->id)
            ->whereNull('deleted_at')
            ->with(['lease.unit.property', 'lease.tenant', 'agent'])
            ->orderBy('occurred_at', 'desc')
            ->paginate(20);

        return view('staff.finance.commission-policies.show', compact('commissionPolicy', 'events'));
    }

    public function edit(CommissionPolicy $commissionPolicy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit commission policies
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền chỉnh sửa Commission Policies.');
        
        $this->checkOrganizationAccess(
            $commissionPolicy->organization_id,
            'Unauthorized access to commission policy.',
            'commission_policy',
            $commissionPolicy->id
        );

        $triggerEvents = [
            'deposit_paid' => 'Thanh toán cọc',
            'lease_signed' => 'Ký hợp đồng',
            'invoice_paid' => 'Thanh toán hóa đơn',
            'viewing_done' => 'Hoàn thành xem phòng',
            'listing_published' => 'Đăng tin'
        ];

        $calcTypes = [
            'percent' => 'Phần trăm',
            'flat' => 'Số tiền cố định',
            'tiered' => 'Bậc thang'
        ];

        $basisTypes = [
            'cash' => 'Tiền mặt',
            'accrual' => 'Dồn tích'
        ];

        return view('staff.finance.commission-policies.edit', compact('commissionPolicy', 'triggerEvents', 'calcTypes', 'basisTypes'));
    }

    public function update(Request $request, CommissionPolicy $commissionPolicy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update commission policies
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền cập nhật Commission Policies.');
        
        $this->checkOrganizationAccess(
            $commissionPolicy->organization_id,
            'Unauthorized access to commission policy.',
            'commission_policy',
            $commissionPolicy->id
        );

        $request->validate([
            'code' => 'required|string|max:50|unique:commission_policies,code,' . $commissionPolicy->id,
            'title' => 'required|string|max:150',
            'trigger_event' => 'required|in:deposit_paid,lease_signed,invoice_paid,viewing_done,listing_published',
            'basis' => 'required|in:cash,accrual',
            'calc_type' => 'required|in:percent,flat,tiered',
            'percent_value' => 'nullable|numeric|min:0|max:100',
            'flat_amount' => 'nullable|numeric|min:0',
            'apply_limit_months' => 'nullable|integer|min:1|max:12',
            'min_amount' => 'nullable|numeric|min:0',
            'cap_amount' => 'nullable|numeric|min:0',
            'active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $commissionPolicy->update([
                'code' => $request->code,
                'title' => $request->title,
                'trigger_event' => $request->trigger_event,
                'basis' => $request->basis,
                'calc_type' => $request->calc_type,
                'percent_value' => $request->percent_value,
                'flat_amount' => $request->flat_amount,
                'apply_limit_months' => $request->apply_limit_months,
                'min_amount' => $request->min_amount,
                'cap_amount' => $request->cap_amount,
                'active' => $request->boolean('active', true),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chính sách hoa hồng đã được cập nhật thành công!',
                    'data' => $commissionPolicy,
                    'redirect' => route('staff.commission-policies.show', $commissionPolicy->id)
                ]);
            }

            return redirect()->route('staff.commission-policies.show', $commissionPolicy->id)
                ->with('success', 'Chính sách hoa hồng đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating commission policy: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật chính sách hoa hồng'
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật chính sách hoa hồng');
        }
    }

    public function destroy(CommissionPolicy $commissionPolicy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete commission policies
        $this->requireCapability('finance.commission.delete', 'Bạn không có quyền xóa Commission Policies.');
        
        $this->checkOrganizationAccess(
            $commissionPolicy->organization_id,
            'Unauthorized access to commission policy.',
            'commission_policy',
            $commissionPolicy->id
        );

        try {
            $commissionPolicy->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chính sách hoa hồng đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.commission-policies.index')
                ->with('success', 'Chính sách hoa hồng đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting commission policy: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa chính sách hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa chính sách hoa hồng');
        }
    }

    public function toggleStatus(Request $request, CommissionPolicy $commissionPolicy)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can toggle policy status
        $this->requireCapability('finance.commission.update', 'Bạn không có quyền cập nhật trạng thái Commission Policies.');
        
        $this->checkOrganizationAccess(
            $commissionPolicy->organization_id,
            'Unauthorized access to commission policy.',
            'commission_policy',
            $commissionPolicy->id
        );

        $request->validate([
            'active' => 'required|boolean'
        ]);

        try {
            $commissionPolicy->update(['active' => $request->active]);

            $statusLabel = $commissionPolicy->active ? 'kích hoạt' : 'tạm ngưng';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Chính sách hoa hồng đã được {$statusLabel} thành công!",
                    'active' => $commissionPolicy->active
                ]);
            }

            return back()->with('success', "Chính sách hoa hồng đã được {$statusLabel} thành công!");

        } catch (\Exception $e) {
            Log::error('Error toggling commission policy status: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái chính sách hoa hồng'
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái chính sách hoa hồng');
        }
    }
}
