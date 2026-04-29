<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Property;
use App\Models\CommissionPolicy;
use App\Models\Organization;
use App\Models\SalaryContract;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller quản lý Staff (Agent và Manager) trong tổ chức (Party module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách staff members (agent và manager) trong tổ chức
 * - Chỉ manager mới có quyền quản lý staff (agent không có quyền)
 * - Hiển thị workload và performance metrics cho từng staff member
 * - Quản lý roles, properties assignment, salary contracts, commission events
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách staff với workload và performance data
 *    - Filter theo organization_id, chỉ lấy agent và manager roles (exclude admin)
 *    - Tính workload: active leads, active viewings, pending bookings, active leases
 *    - Tính performance: leads/viewings/bookings/leases count (30 days), conversion rate, commission earned
 *    - Hỗ trợ HTMX/AJAX requests để update table
 *    - Sort theo các fields được phép (id, email, phone, status, created_at, full_name)
 * 2. create(): Hiển thị form tạo staff mới (chỉ manager)
 * 3. store(): Tạo staff mới với validation, assign role, assign properties
 *    - Validate email, phone uniqueness
 *    - Create user, userProfile, assign role trong organization
 *    - Assign properties nếu có
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết staff (roles, assigned properties, salary contracts, commission events)
 * 5. edit(): Hiển thị form edit staff với roles và assigned properties
 * 6. update(): Cập nhật staff (email, phone, password, status, roles, assigned properties)
 *    - Validate email/phone uniqueness (ignore current user)
 *    - Update user và userProfile
 *    - Sync roles và assigned properties
 * 7. destroy(): Xóa staff (soft delete)
 *    - Không cho phép xóa chính mình
 *    - Soft delete user và organization_users records
 * 8. getSalaryContracts(): API endpoint lấy salary contracts của staff (AJAX)
 * 9. getCommissionEvents(): API endpoint lấy commission events của staff (AJAX)
 * 10. assignProperties(): API endpoint assign/unassign properties cho staff (AJAX)
 * 11. toggleStatus(): API endpoint toggle status của staff (active/inactive) (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/staff: Danh sách staff (hỗ trợ HTMX/AJAX)
 * - GET /staff/staff/create: Form tạo staff
 * - POST /staff/staff: Tạo staff mới
 * - GET /staff/staff/{id}: Chi tiết staff
 * - GET /staff/staff/{id}/edit: Form edit staff
 * - PUT/PATCH /staff/staff/{id}: Cập nhật staff
 * - DELETE /staff/staff/{id}: Xóa staff
 * - GET /staff/staff/{id}/salary-contracts: Lấy salary contracts (AJAX)
 * - GET /staff/staff/{id}/commission-events: Lấy commission events (AJAX)
 * - POST /staff/staff/{id}/assign-properties: Assign properties (AJAX)
 * - POST /staff/staff/{id}/toggle-status: Toggle status (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: User, Role, Property, CommissionPolicy, Organization, SalaryContract
 * - Database tables: users, user_profiles, roles, properties, organization_users, salary_contracts, commission_events, viewings, booking_deposits, leases
 * - Request: search, role_id, status, date_from, date_to, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: users, user_profiles, organization_users, property_user (pivot table cho assigned properties)
 * - Không có thay đổi roles, properties, salary_contracts, commission_events tables (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (party.access, party.role.assign, etc.)
 * 
 * CAPABILITY CHECKING:
 * - party.access: Quyền truy cập module Party (required cho tất cả methods)
 * - party.role.assign: Quyền xem và quản lý staff (index, create, store, edit, update, destroy)
 * 
 * OWNERSHIP FILTERING:
 * - Không có ownership filtering (manager xem tất cả staff trong organization)
 * - Exclude admin users khỏi danh sách (admin không được quản lý bởi manager)
 * - Chỉ hiển thị agent và manager roles (exclude admin)
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng batch queries để tính workload và performance data (tránh N+1 queries)
 * - Sử dụng keyBy() để map data theo agent_id cho quick lookup
 * - Eager loading relationships (organizationRoles, salaryContracts, assignedProperties)
 * - Tính workload và performance trong một lần query thay vì per-staff queries
 * - Validate sort fields để prevent SQL injection
 * 
 * WORKLOAD METRICS:
 * - active_leads: Số lượng leads đang active (qua viewings với status requested/confirmed)
 * - active_viewings: Số lượng viewings đang active (status requested/confirmed)
 * - pending_bookings: Số lượng bookings đang pending payment
 * - active_leases: Số lượng leases đang active
 * 
 * PERFORMANCE METRICS (30 days):
 * - leads_count: Số lượng leads mới trong 30 ngày
 * - viewings_count: Số lượng viewings trong 30 ngày
 * - bookings_count: Số lượng bookings trong 30 ngày
 * - leases_count: Số lượng leases trong 30 ngày
 * - conversion_rate: Tỷ lệ chuyển đổi từ leads sang leases (leases_count / leads_count * 100)
 * - commission_earned: Tổng commission đã kiếm được trong 30 ngày (status = 'paid')
 * 
 * VALIDATION:
 * - Email: required, email, unique (excluding soft-deleted)
 * - Phone: nullable, unique (excluding soft-deleted)
 * - Password: nullable, min:8 (khi update)
 * - Role: required, exists:roles (key_code = 'agent' hoặc 'manager')
 * - Properties: array, exists:properties (khi assign)
 * 
 * SECURITY:
 * - Chỉ manager mới có quyền quản lý staff (agent không có quyền)
 * - Không cho phép xóa chính mình
 * - Exclude admin users khỏi danh sách và operations
 * - Validate sort fields để prevent SQL injection
 * - Email/phone uniqueness check (excluding soft-deleted users)
 * 
 * LƯU Ý:
 * - Staff chỉ bao gồm agent và manager roles (admin được exclude)
 * - Workload và performance data được tính bằng batch queries để tối ưu performance
 * - Conversion rate = (leases_count / leads_count) * 100 (trong 30 ngày)
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Properties assignment sử dụng sync() để update pivot table
 * - Salary contracts và commission events được load riêng qua AJAX để giảm initial load time
 */
class StaffController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Hiển thị danh sách staff members với workload và performance metrics
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: party.access và party.role.assign
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Build query với filters (search, role, status, date range)
     *    - Chỉ lấy users có role 'agent' hoặc 'manager'
     *    - Exclude users có role 'admin'
     *    - Filter theo organization_id và status = 'active'
     * 4. Apply sorting (validate sort fields)
     * 5. Paginate results (10 items per page)
     * 6. Tính workload data cho từng staff (batch queries):
     *    - Active leads (qua viewings)
     *    - Active viewings
     *    - Pending bookings
     *    - Active leases
     * 7. Tính performance data cho từng staff (30 days, batch queries):
     *    - Leads/viewings/bookings/leases count
     *    - Conversion rate (leases / leads * 100)
     *    - Commission earned
     * 8. Attach workload và performance data vào mỗi staff member
     * 9. Check request type (HTMX/AJAX):
     *    - HTMX: Return table partial HTML
     *    - AJAX: Return JSON với table_html
     *    - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: users, user_profiles, organization_users, roles, viewings, booking_deposits, leases, commission_events
     * - Request: search, role_id, status, date_from, date_to, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - party.access: Quyền truy cập module Party
     * - party.role.assign: Quyền xem danh sách staff
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng batch queries để tính workload và performance (tránh N+1 queries)
     * - Sử dụng keyBy() để map data theo agent_id cho quick lookup
     * - Eager loading relationships (organizationRoles, salaryContracts, assignedProperties)
     * - Tính tất cả metrics trong một lần query thay vì per-staff queries
     * 
     * WORKLOAD METRICS:
     * - active_leads: Leads đang active (viewings với status requested/confirmed)
     * - active_viewings: Viewings đang active
     * - pending_bookings: Bookings đang pending payment
     * - active_leases: Leases đang active
     * 
     * PERFORMANCE METRICS (30 days):
     * - leads_count, viewings_count, bookings_count, leases_count
     * - conversion_rate: (leases_count / leads_count) * 100
     * - commission_earned: Tổng commission đã kiếm (status = 'paid')
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check if user has party.access capability
        $hasPartyAccess = $this->checkCapability('party.access');
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.');
        }
        
        // Check capability - only manager can view staff list
        $this->requireCapability('party.role.assign', 'Bạn không có quyền xem danh sách nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return view('staff.party.staff.index', [
                'staff' => collect([]),
                'roles' => collect([])
            ])->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);

        // Lấy tất cả staff members (agent và manager roles) trong organization
        // Exclude admin users (admin không được quản lý bởi manager)
        // Eager load relationships để tối ưu performance
        $query = User::with(['organizationRoles', 'salaryContracts', 'assignedProperties'])
            // Chỉ lấy users có role 'agent' hoặc 'manager' trong organization này
            ->whereHas('organizationUsers', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->where('status', 'active') // Chỉ lấy organization_users với status = 'active'
                  ->whereHas('role', function($roleQuery) {
                      // Chỉ include agent và manager roles
                      $roleQuery->whereIn('key_code', ['agent', 'manager']);
                  });
            })
            // Exclude users có role 'admin' trong organization này
            // Sử dụng whereDoesntHave() để loại bỏ users có admin role
            ->whereDoesntHave('organizationUsers', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                  ->whereHas('role', function($roleQuery) {
                      $roleQuery->where('key_code', 'admin');
                  });
            });

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('userProfile', function($profileQuery) use ($search) {
                    $profileQuery->where('full_name', 'like', "%{$search}%");
                })
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role_id')) {
            $query->whereHas('organizationRoles', function($q) use ($request, $organizationId) {
                $q->where('organization_id', $organizationId)
                  ->where('role_id', $request->role_id);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'created_at', 'email', 'phone', 'status', 'full_name'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        // Apply sorting
        if ($sortBy === 'full_name') {
            // Sort by full_name from user_profiles table
            $query->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                  ->select('users.*')
                  ->orderBy('user_profiles.full_name', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate với 10 items per page và giữ query string cho filters
        $staff = $query->paginate(10)->withQueryString();
        
        // Tính workload và performance data cho từng staff member
        // Sử dụng batch queries để tránh N+1 queries (tối ưu performance)
        $thirtyDaysAgo = now()->subDays(30); // 30 ngày trước để tính performance metrics
        $staffIds = $staff->pluck('id')->toArray(); // Lấy danh sách staff IDs
        
        // Khởi tạo arrays để lưu workload và performance data
        $workloadData = [];
        $performanceData = [];
        
        // Chỉ tính workload và performance nếu có staff members
        if (!empty($staffIds)) {
            // WORKLOAD METRICS: Tính số lượng công việc đang active của mỗi staff
            
            // Workload: Active leads (qua viewings)
            // Đếm số lượng distinct leads đang active (viewings với status requested/confirmed)
            $activeLeadsData = DB::table('viewings')
                ->select('agent_id', DB::raw('COUNT(DISTINCT lead_id) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->whereIn('status', ['requested', 'confirmed'])
                ->whereNotNull('lead_id')
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id'); // Key by agent_id để quick lookup
            
            // Workload: Active viewings
            // Đếm số lượng viewings đang active (status requested/confirmed)
            $activeViewingsData = DB::table('viewings')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->whereIn('status', ['requested', 'confirmed'])
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Workload: Pending bookings
            // Đếm số lượng bookings đang pending payment
            $pendingBookingsData = DB::table('booking_deposits')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('payment_status', 'pending')
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Workload: Active leases
            // Đếm số lượng leases đang active
            $activeLeasesData = DB::table('leases')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // PERFORMANCE METRICS (30 days): Tính số liệu performance trong 30 ngày qua
            
            // Performance: Leads count (30 days)
            // Đếm số lượng distinct leads mới trong 30 ngày qua (qua viewings)
            $leadsCountData = DB::table('viewings')
                ->select('agent_id', DB::raw('COUNT(DISTINCT lead_id) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereNotNull('lead_id')
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Performance: Viewings count (30 days)
            // Đếm số lượng viewings trong 30 ngày qua
            $viewingsCountData = DB::table('viewings')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Performance: Bookings count (30 days)
            // Đếm số lượng bookings trong 30 ngày qua
            $bookingsCountData = DB::table('booking_deposits')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Performance: Leases count (30 days)
            // Đếm số lượng leases trong 30 ngày qua
            $leasesCountData = DB::table('leases')
                ->select('agent_id', DB::raw('COUNT(*) as count'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereNull('deleted_at')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Performance: Commission earned (30 days)
            // Tính tổng commission đã kiếm được trong 30 ngày qua (status = 'paid')
            $commissionData = DB::table('commission_events')
                ->select('agent_id', DB::raw('SUM(commission_total) as total'))
                ->whereIn('agent_id', $staffIds)
                ->where('organization_id', $organizationId)
                ->where('occurred_at', '>=', $thirtyDaysAgo)
                ->where('status', 'paid') // Chỉ tính commission đã được trả
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');
            
            // Build workload và performance arrays cho từng staff member
            // Sử dụng keyBy() để quick lookup thay vì loop qua từng query result
            foreach ($staffIds as $memberId) {
                // Build workload data: Số lượng công việc đang active
                $workloadData[$memberId] = [
                    'active_leads' => $activeLeadsData->get($memberId)?->count ?? 0,
                    'active_viewings' => $activeViewingsData->get($memberId)?->count ?? 0,
                    'pending_bookings' => $pendingBookingsData->get($memberId)?->count ?? 0,
                    'active_leases' => $activeLeasesData->get($memberId)?->count ?? 0,
                ];
                
                // Tính conversion rate: Tỷ lệ chuyển đổi từ leads sang leases
                $leadsCount = $leadsCountData->get($memberId)?->count ?? 0;
                $leasesCount = $leasesCountData->get($memberId)?->count ?? 0;
                // Conversion rate = (leases_count / leads_count) * 100
                // Nếu leadsCount = 0 thì trả về 0 để tránh chia cho 0
                $conversionRate = $leadsCount > 0 ? round(($leasesCount / $leadsCount) * 100, 1) : 0;
                
                // Build performance data: Số liệu performance trong 30 ngày
                $performanceData[$memberId] = [
                    'leads_count' => $leadsCount,
                    'viewings_count' => $viewingsCountData->get($memberId)?->count ?? 0,
                    'bookings_count' => $bookingsCountData->get($memberId)?->count ?? 0,
                    'leases_count' => $leasesCount,
                    'conversion_rate' => $conversionRate,
                    'commission_earned' => $commissionData->get($memberId)?->total ?? 0,
                ];
            }
        }
        
        // Attach workload và performance data vào mỗi staff member
        // Sử dụng setAttribute() để thêm dynamic attributes vào model
        foreach ($staff as $member) {
            $member->setAttribute('workload', $workloadData[$member->id] ?? []);
            $member->setAttribute('performance', $performanceData[$member->id] ?? []);
        }

        // Get roles for filter (agent and manager only)
        $roles = Role::whereIn('key_code', ['agent', 'manager'])->get();

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // If HTMX request, return table partial
        if ($isHtmx) {
            $tableHtml = view('staff.party.staff.partials.table', [
                'staff' => $staff,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            return response($tableHtml)
                ->header('HX-Push-Url', $request->fullUrl());
        }
        
        // Legacy AJAX support (for backward compatibility)
        if ($request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            $tableHtml = view('staff.party.staff.partials.table', [
                'staff' => $staff,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ])->render();
            
            return response()->json([
                'success' => true,
                'html' => $tableHtml,
                'table_html' => $tableHtml, // Also provide table_html for compatibility
                'pagination' => [
                    'current_page' => $staff->currentPage(),
                    'last_page' => $staff->lastPage(),
                    'per_page' => $staff->perPage(),
                    'total' => $staff->total(),
                ]
            ]);
        }

        return view('staff.party.staff.index', compact('staff', 'roles', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create()
    {
        $roles = Role::whereIn('key_code', ['agent', 'manager'])->get();
        
        // Check capability - only manager can create staff
        $this->requireCapability('party.role.assign', 'Bạn không có quyền tạo nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);
        
        // Chỉ lấy properties thuộc tổ chức của manager
        $properties = Property::where('status', 1)
            ->where('organization_id', $organizationId)
            ->get();
            
        $commissionPolicies = CommissionPolicy::where('active', 1)
            ->where('organization_id', $organizationId)
            ->get();

        return view('staff.party.staff.create', compact('roles', 'managerOrganization', 'properties', 'commissionPolicies'));
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request)
    {
        // Check capability - only manager can create staff
        $this->requireCapability('party.role.assign', 'Bạn không có quyền tạo nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gắn vào tổ chức nào!'
                ], 400);
            }
            return back()->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|integer|in:0,1',
            'properties' => 'nullable|array',
            'properties.*' => 'exists:properties,id',
        ]);

        // Validate role - cannot assign admin role
        $role = Role::find($request->role_id);
        if ($role && $role->key_code === 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo tài khoản với vai trò Quản trị hệ thống. Vui lòng chọn vai trò khác.'
                ], 403);
            }
            return back()->withInput()->with('error', 'Bạn không có quyền tạo tài khoản với vai trò Quản trị hệ thống. Vui lòng chọn vai trò khác.');
        }

        DB::beginTransaction();
        try {
            // Kiểm tra thủ công email/phone đã tồn tại và chưa bị soft delete
            if ($request->filled('email')) {
                $existingEmail = DB::table('users')
                    ->where('email', $request->email)
                    ->whereNull('deleted_at')
                    ->first();
                if ($existingEmail) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác.',
                            'errors' => ['email' => ['Email này đã được sử dụng.']]
                        ], 422);
                    }
                    return back()->withInput()->withErrors(['email' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác.']);
                }
            }
            
            if ($request->filled('phone')) {
                $existingPhone = DB::table('users')
                    ->where('phone', $request->phone)
                    ->whereNull('deleted_at')
                    ->first();
                if ($existingPhone) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác.',
                            'errors' => ['phone' => ['Số điện thoại này đã được sử dụng.']]
                        ], 422);
                    }
                    return back()->withInput()->withErrors(['phone' => 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác.']);
                }
            }
            
            // Create user
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'status' => (int)$request->status,
            ]);
            
            // Create user profile with full_name
            \App\Models\UserProfile::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
            ]);

            // Assign to organization (tự động gắn tổ chức của manager)
            // Kiểm tra xem user đã có trong organization chưa
            // Một user chỉ có thể có 1 role trong 1 organization
            $existingOrgUser = DB::table('organization_users')
                ->where('organization_id', $organizationId)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$existingOrgUser) {
                DB::table('organization_users')->insert([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'role_id' => $request->role_id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Nếu đã tồn tại, cập nhật role và status
                DB::table('organization_users')
                    ->where('id', $existingOrgUser->id)
                    ->update([
                        'role_id' => $request->role_id,
                        'status' => 'active',
                        'updated_at' => now()
                    ]);
            }


            // Assign properties
            if ($request->filled('properties')) {
                foreach ($request->properties as $propertyId) {
                    DB::table('properties_user')->insert([
                        'property_id' => $propertyId,
                        'user_id' => $user->id,
                        'role_key' => 'agent',
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nhân viên đã được tạo thành công!',
                    'redirect' => route('staff.staff.index')
                ]);
            }

            return redirect()->route('staff.staff.index')
                ->with('success', 'Nhân viên đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo nhân viên: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Không thể tạo nhân viên: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified staff member.
     */
    public function show($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check if user has party.access capability
        $hasPartyAccess = $this->checkCapability('party.access');
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.');
        }
        
        // Check capability - only manager can view staff details
        $this->requireCapability('party.role.assign', 'Bạn không có quyền xem chi tiết nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.staff.index')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);

        $staff = User::with([
            'organizationRoles',
            'salaryContracts.organization',
            'assignedProperties.propertyType',
            'assignedProperties.location',
            'commissionEvents',
            'organizationUsers.organization'
        ])->findOrFail($id);

        // Kiểm tra nhân viên có thuộc tổ chức không
        $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
        if (!$staffOrganization) {
            return redirect()->route('staff.staff.index')
                ->with('error', 'Bạn không có quyền xem thông tin nhân viên này!');
        }

        // Get commission statistics
        $commissionStats = DB::table('commission_events')
            ->where('agent_id', $id)
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(commission_total) as total_amount
            ')
            ->groupBy('status')
            ->get();

        // Get recent salary history
        $salaryHistory = DB::table('payroll_payslips')
            ->join('payroll_cycles', 'payroll_payslips.payroll_cycle_id', '=', 'payroll_cycles.id')
            ->where('payroll_payslips.user_id', $id)
            ->select('payroll_payslips.*', 'payroll_cycles.period_month')
            ->orderBy('payroll_cycles.period_month', 'desc')
            ->limit(12)
            ->get();

        // Get workload statistics
        // Leads: Get distinct leads that have viewings handled by this agent
        $activeLeads = DB::table('viewings')
            ->where('agent_id', $id)
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['requested', 'confirmed'])
            ->whereNotNull('lead_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->count('lead_id');
        
        $workload = [
            'active_leads' => $activeLeads,
            'active_viewings' => DB::table('viewings')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->whereIn('status', ['requested', 'confirmed'])
                ->whereNull('deleted_at')
                ->count(),
            'pending_bookings' => DB::table('booking_deposits')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->where('payment_status', 'pending')
                ->whereNull('deleted_at')
                ->count(),
            'active_leases' => DB::table('leases')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count(),
        ];

        // Get performance KPI - allow custom date range from request
        $request = request();
        $dateFrom = $request->filled('kpi_date_from') ? $request->kpi_date_from : now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->filled('kpi_date_to') ? $request->kpi_date_to : now()->format('Y-m-d');
        $kpiDateFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $kpiDateTo = \Carbon\Carbon::parse($dateTo)->endOfDay();
        
        // Leads: Count distinct leads that have viewings created by this agent in date range
        $leadsCount = DB::table('viewings')
            ->where('agent_id', $id)
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$kpiDateFrom, $kpiDateTo])
            ->whereNotNull('lead_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->count('lead_id');
        
        $performance = [
            'leads_count' => $leadsCount,
            'viewings_count' => DB::table('viewings')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->whereBetween('created_at', [$kpiDateFrom, $kpiDateTo])
                ->whereNull('deleted_at')
                ->count(),
            'bookings_count' => DB::table('booking_deposits')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->whereBetween('created_at', [$kpiDateFrom, $kpiDateTo])
                ->whereNull('deleted_at')
                ->count(),
            'leases_count' => DB::table('leases')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->whereBetween('created_at', [$kpiDateFrom, $kpiDateTo])
                ->whereNull('deleted_at')
                ->count(),
            'conversion_rate' => 0,
            'commission_earned' => DB::table('commission_events')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->whereBetween('occurred_at', [$kpiDateFrom, $kpiDateTo])
                ->where('status', 'paid')
                ->sum('commission_total'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        // Calculate conversion rate
        if ($performance['leads_count'] > 0) {
            $performance['conversion_rate'] = round(
                ($performance['leases_count'] / $performance['leads_count']) * 100,
                1
            );
        }

        // Get activity log (30 days) - only load if needed (not for AJAX requests initially)
        $recentActivities = collect();
        
        // Only load activity log if not AJAX request or tab is activity-log
        if (!$request->ajax() || ($request->has('tab') && $request->tab === 'activity-log')) {
            $activityDateFrom = now()->subDays(30)->startOfDay();
            // Get leads through viewings
            $leadIds = DB::table('viewings')
                ->where('agent_id', $id)
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $activityDateFrom)
                ->whereNotNull('lead_id')
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('lead_id');
            
            if ($leadIds->isNotEmpty()) {
                $recentActivities = DB::table('leads')
                    ->whereIn('id', $leadIds)
                    ->where('organization_id', $organizationId)
                    ->where('created_at', '>=', $activityDateFrom)
                    ->selectRaw("'lead' as type, id, name as title, created_at, status")
                    ->whereNull('deleted_at')
                    ->limit(10)
                    ->get();
            }
            
            $recentActivities = $recentActivities
                ->merge(
                    DB::table('viewings')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->where('created_at', '>=', $activityDateFrom)
                        ->selectRaw("'viewing' as type, id, CONCAT('Xem phòng: ', COALESCE(lead_name, 'N/A')) as title, created_at, status")
                        ->whereNull('deleted_at')
                        ->limit(10)
                        ->get()
                )
                ->merge(
                    DB::table('booking_deposits')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->where('created_at', '>=', $activityDateFrom)
                        ->selectRaw("'booking' as type, id, CONCAT('Đặt cọc: ', amount, ' VNĐ') as title, created_at, payment_status as status")
                        ->whereNull('deleted_at')
                        ->limit(10)
                        ->get()
                )
                ->sortByDesc('created_at')
                ->take(20);
        }

        // Get monthly performance trend (last 6 months) - always calculate for initial load
        $monthlyTrend = [];
        
        // Calculate monthly trend (skip only if it's a non-performance-trend AJAX request)
        if (!$request->ajax() || !$request->has('tab') || $request->tab === 'performance-trend') {
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd = now()->subMonths($i)->endOfMonth();
                
                $monthlyTrend[] = [
                    'month' => $monthStart->format('M Y'),
                    'leads' => DB::table('viewings')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->whereNotNull('lead_id')
                        ->whereNull('deleted_at')
                        ->distinct()
                        ->count('lead_id'),
                    'viewings' => DB::table('viewings')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->whereNull('deleted_at')
                        ->count(),
                    'leases' => DB::table('leases')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->whereNull('deleted_at')
                        ->count(),
                    'commission' => DB::table('commission_events')
                        ->where('agent_id', $id)
                        ->where('organization_id', $organizationId)
                        ->whereBetween('occurred_at', [$monthStart, $monthEnd])
                        ->where('status', 'paid')
                        ->sum('commission_total'),
                ];
            }
        }

        // If AJAX request for specific tabs
        if ($request->ajax() && $request->has('tab')) {
            if ($request->tab === 'activity-log') {
                $html = view('staff.party.staff.partials.activity-log', compact('recentActivities'))->render();
                return response()->json(['html' => $html]);
            }
            if ($request->tab === 'performance-trend') {
                $html = view('staff.party.staff.partials.performance-trend', compact('monthlyTrend'))->render();
                return response()->json(['html' => $html, 'data' => $monthlyTrend]);
            }
        }

        return view('staff.party.staff.show', compact(
            'staff', 
            'commissionStats', 
            'salaryHistory',
            'workload',
            'performance',
            'recentActivities',
            'monthlyTrend'
        ));
    }

    /**
     * Show the form for editing the specified staff member.
     */
    public function edit($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check capability - only manager can edit staff
        $this->requireCapability('party.role.assign', 'Bạn không có quyền chỉnh sửa nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.staff.index')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);

        $staff = User::with(['organizationRoles', 'salaryContracts', 'assignedProperties'])->findOrFail($id);

        // Kiểm tra nhân viên có thuộc tổ chức không
        $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
        if (!$staffOrganization) {
            return redirect()->route('staff.staff.index')
                ->with('error', 'Bạn không có quyền chỉnh sửa nhân viên này!');
        }
        $roles = Role::whereIn('key_code', ['agent', 'manager'])->get();
        
        // Chỉ lấy properties thuộc tổ chức
        $properties = Property::where('status', 1)
            ->where('organization_id', $organizationId)
            ->get();
            
        $commissionPolicies = CommissionPolicy::where('active', 1)
            ->where('organization_id', $organizationId)
            ->get();

        // Get assigned property IDs
        $assignedPropertyIds = $staff->assignedProperties->pluck('id')->toArray();

        return view('staff.party.staff.edit', compact('staff', 'roles', 'managerOrganization', 'properties', 'commissionPolicies', 'assignedPropertyIds'));
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, $id)
    {
        $staff = User::findOrFail($id);

        // Check capability - only manager can update staff
        $this->requireCapability('party.role.assign', 'Bạn không có quyền cập nhật nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gắn vào tổ chức nào!'
                ], 400);
            }
            return back()->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.staff.index')
                ->with('error', 'Bạn chưa được gắn vào tổ chức nào!');
        }
        
        $managerOrganization = Organization::find($organizationId);

        $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
        if (!$staffOrganization) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật nhân viên này!'
                ], 403);
            }
            return back()->with('error', 'Bạn không có quyền cập nhật nhân viên này!');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($id)
            ],
            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone')->whereNull('deleted_at')->ignore($id)
            ],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|boolean',
            'properties' => 'nullable|array',
            'properties.*' => 'exists:properties,id',
        ]);

        DB::beginTransaction();
        try {
            // Update user
            $staff->update([
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => $request->status,
            ]);
            
            // Update user profile with full_name
            $staff->userProfile()->updateOrCreate(
                ['user_id' => $staff->id],
                ['full_name' => $request->full_name]
            );

            // Update password if provided
            if ($request->filled('password')) {
                $staff->update(['password_hash' => Hash::make($request->password)]);
            }

            // Update role through organization_users (handled below)

            // Update organization role (chỉ cập nhật role, không xóa tất cả)
            DB::table('organization_users')
                ->where('user_id', $id)
                ->where('organization_id', $organizationId)
                ->update([
                    'role_id' => $request->role_id,
                    'updated_at' => now(),
                ]);


            // Update properties assignment
            // Xóa các properties cũ không còn được chọn
            $currentPropertyIds = $staff->assignedProperties->pluck('id')->toArray();
            $newPropertyIds = $request->properties ?? [];
            $propertiesToDelete = array_diff($currentPropertyIds, $newPropertyIds);
            $propertiesToAdd = array_diff($newPropertyIds, $currentPropertyIds);

            // Xóa properties không còn được chọn
            if (!empty($propertiesToDelete)) {
                DB::table('properties_user')
                    ->where('user_id', $id)
                    ->whereIn('property_id', $propertiesToDelete)
                    ->delete();
            }

            // Thêm properties mới
            if (!empty($propertiesToAdd)) {
                foreach ($propertiesToAdd as $propertyId) {
                    DB::table('properties_user')->insert([
                        'property_id' => $propertyId,
                        'user_id' => $staff->id,
                        'role_key' => 'agent',
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            // Check if it's an AJAX request
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thông tin nhân viên đã được cập nhật!',
                    'redirect' => route('staff.staff.show', $staff->id)
                ]);
            }

            return redirect()->route('staff.staff.show', $staff->id)
                ->with('success', 'Thông tin nhân viên đã được cập nhật!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error for debugging
            Log::error('Staff update error: ' . $e->getMessage(), [
                'user_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if it's an AJAX request
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể cập nhật nhân viên: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Không thể cập nhật nhân viên: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy($id)
    {
        try {
            $staff = User::findOrFail($id);

            /** @var User $currentUser */
            $currentUser = Auth::user();
            
            // Check capability - only manager can delete staff
            $this->requireCapability('party.role.assign', 'Bạn không có quyền xóa nhân viên.');
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gắn vào tổ chức nào!'
                ], 400);
            }
            
            $managerOrganization = Organization::find($organizationId);

            $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
            if (!$staffOrganization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa nhân viên này!'
                ], 403);
            }
            
            // Soft delete the organization_user record for this organization
            $staffOrganization->deleted_by = Auth::id();
            $staffOrganization->save();
            $staffOrganization->delete(); // Soft delete organization_user
            
            // Check if staff still belongs to other organizations
            $remainingOrgUsers = \App\Models\OrganizationUser::where('user_id', $staff->id)
                ->whereNull('deleted_at')
                ->count();
            
            // Only soft delete the user if they don't belong to any other organization
            if ($remainingOrgUsers === 0) {
                $staff->deleted_by = Auth::id();
                $staff->save();
                $staff->delete(); // Soft delete user only if no other organizations
            }

            return response()->json([
                'success' => true,
                'message' => 'Nhân viên đã được xóa thành công!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa nhân viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get staff salary contracts
     */
    public function getSalaryContracts($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check capability - only manager can view staff salary contracts
        $this->requireCapability('party.role.assign', 'Bạn không có quyền xem hợp đồng lương của nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn chưa được gắn vào tổ chức nào!'], 403);
        }
        
        // Verify staff belongs to organization
        $staff = User::findOrFail($id);
        $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
        if (!$staffOrganization) {
            return response()->json(['error' => 'Bạn không có quyền xem thông tin nhân viên này!'], 403);
        }
        
        $contracts = SalaryContract::where('user_id', $id)
            ->with('organization')
            ->orderBy('effective_from', 'desc')
            ->get();

        return response()->json($contracts);
    }

    /**
     * Get staff commission events
     */
    public function getCommissionEvents($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check capability - only manager can view staff commission events
        $this->requireCapability('party.role.assign', 'Bạn không có quyền xem hoa hồng của nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn chưa được gắn vào tổ chức nào!'], 403);
        }
        
        // Verify staff belongs to organization
        $staff = User::findOrFail($id);
        $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
        if (!$staffOrganization) {
            return response()->json(['error' => 'Bạn không có quyền xem thông tin nhân viên này!'], 403);
        }
        
        $events = DB::table('commission_events')
            ->join('commission_policies', 'commission_events.policy_id', '=', 'commission_policies.id')
            ->where('commission_events.agent_id', $id)
            ->where('commission_events.organization_id', $organizationId)
            ->select(
                'commission_events.*',
                'commission_events.occurred_at',
                'commission_events.amount_base',
                'commission_policies.title as policy_title'
            )
            ->orderBy('commission_events.occurred_at', 'desc')
            ->get();

        return response()->json($events);
    }

    /**
     * Assign properties to staff
     */
    public function assignProperties(Request $request, $id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // Check capability - only manager can assign properties to staff
        $this->requireCapability('party.role.assign', 'Bạn không có quyền gán bất động sản cho nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn chưa được gắn vào tổ chức nào!'], 403);
        }
        
        $request->validate([
            'properties' => 'required|array',
            'properties.*' => 'exists:properties,id',
        ]);

        try {
            $staff = User::findOrFail($id);
            
            // Verify staff belongs to organization
            $staffOrganization = $staff->organizationUsers()->where('organization_id', $organizationId)->whereNull('deleted_at')->first();
            if (!$staffOrganization) {
                return response()->json(['error' => 'Bạn không có quyền gán bất động sản cho nhân viên này!'], 403);
            }
            
            // Verify all properties belong to organization
            $propertyIds = $request->properties;
            $propertiesCount = Property::whereIn('id', $propertyIds)
                ->where('organization_id', $organizationId)
                ->count();
            
            if ($propertiesCount !== count($propertyIds)) {
                return response()->json(['error' => 'Một số bất động sản không thuộc tổ chức của bạn!'], 403);
            }

            // Remove existing assignments
            DB::table('properties_user')->where('user_id', $id)->delete();

            // Add new assignments
            foreach ($request->properties as $propertyId) {
                DB::table('properties_user')->insert([
                    'property_id' => $propertyId,
                    'user_id' => $staff->id,
                    'role_key' => 'agent',
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã gắn bất động sản cho nhân viên thành công!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gắn bất động sản: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('party.role.assign', 'Bạn không có quyền cập nhật trạng thái nhân viên.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tổ chức.'
            ], 403);
        }

        // Find staff member
        $staff = User::whereHas('organizations', function($q) use ($organizationId) {
            $q->where('organizations.id', $organizationId);
        })->findOrFail($id);

        // Don't allow updating the current user's status
        if ($staff->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể thay đổi trạng thái của chính mình.'
            ], 422);
        }

        $request->validate([
            'status' => 'required|boolean'
        ], [
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean' => 'Trạng thái không hợp lệ.'
        ]);

        try {
            DB::beginTransaction();

            $staff->update(['status' => $request->status]);
            
            $statusLabel = $staff->status ? 'kích hoạt' : 'tạm ngưng';

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Nhân viên đã được {$statusLabel} thành công!",
                'status' => $staff->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling staff status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái nhân viên.'
            ], 500);
        }
    }
}


