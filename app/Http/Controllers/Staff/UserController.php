<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Capability;
use App\Models\OrganizationUser;
use App\Models\CashOutflow;
use App\Services\CapabilityService;
use App\Services\Subscription\PlanLimitChecker;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Controller quản lý Users trong tổ chức (Party module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách users trong tổ chức (xem, tạo, sửa, xóa)
 * - Chỉ manager mới có quyền quản lý users (agent không có quyền)
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Kiểm tra subscription plan limits khi tạo user mới
 * - Quản lý roles và capabilities của users
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách users với filters (search, role, status, date range), stats, pagination
 *    - Filter theo organization_id, exclude admin users
 *    - Tính statistics (total, active, inactive) bằng aggregation
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép (id, email, phone, status, created_at, updated_at, full_name)
 * 2. create(): Hiển thị form tạo user mới (chỉ manager)
 * 3. store(): Tạo user mới với validation, check subscription limit, assign role
 *    - Validate email, phone uniqueness
 *    - Check subscription plan limit (max_users)
 *    - Create user, userProfile, assign role trong organization
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết user (roles, capabilities, assigned properties)
 * 5. edit(): Hiển thị form edit user với roles và capabilities
 * 6. update(): Cập nhật user (email, phone, password, status, roles, capabilities)
 *    - Validate email/phone uniqueness (ignore current user)
 *    - Update user và userProfile
 *    - Sync roles và capabilities
 * 7. destroy(): Xóa user (soft delete)
 *    - Không cho phép xóa chính mình
 *    - Soft delete user và organization_users records
 * 8. updateStatus(): Cập nhật status của user (active/inactive)
 * 
 * ENDPOINTS:
 * - GET /staff/users: Danh sách users (hỗ trợ HTMX/AJAX)
 * - GET /staff/users/create: Form tạo user
 * - POST /staff/users: Tạo user mới
 * - GET /staff/users/{id}: Chi tiết user
 * - GET /staff/users/{id}/edit: Form edit user
 * - PUT/PATCH /staff/users/{id}: Cập nhật user
 * - DELETE /staff/users/{id}: Xóa user
 * - POST /staff/users/{id}/status: Cập nhật status
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: User, Role, Capability, OrganizationUser, Organization
 * - Database tables: users, user_profiles, roles, capabilities, organization_users, organization_user_capabilities
 * - Services: PlanLimitChecker (để check subscription limits), CapabilityService
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: users, user_profiles, organization_users, organization_user_capabilities
 * - Không có thay đổi roles và capabilities tables (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (party.access, party.user.view, party.user.create, etc.)
 * 
 * SERVICES SỬ DỤNG:
 * - PlanLimitChecker: Kiểm tra subscription plan limits (max_users)
 * - CapabilityService: Quản lý capabilities của users
 * 
 * CAPABILITY CHECKING:
 * - party.access: Quyền truy cập module Party (required cho tất cả methods)
 * - party.user.view: Quyền xem danh sách users (index, show)
 * - party.user.create: Quyền tạo user (create, store)
 * - party.user.update: Quyền cập nhật user (edit, update)
 * - party.user.delete: Quyền xóa user (destroy)
 * 
 * OWNERSHIP FILTERING:
 * - Không có ownership filtering (manager xem tất cả users trong organization)
 * - Exclude admin users khỏi danh sách (admin không được quản lý bởi manager)
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng JOINs thay vì whereHas() để tối ưu performance
 * - Sử dụng DISTINCT để tránh duplicate khi user thuộc nhiều organizations
 * - Eager loading relationships (userProfile, userRoles) với organization context
 * - Tính statistics bằng aggregation (SUM, COUNT) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * - Sử dụng selectRaw() cho statistics aggregation
 * 
 * SUBSCRIPTION LIMITS:
 * - Kiểm tra max_users limit khi tạo user mới
 * - Sử dụng PlanLimitChecker để check limits và get current count
 * - Trả về error message với current/limit nếu vượt quá limit
 * 
 * VALIDATION:
 * - Email: required, email, unique (excluding soft-deleted)
 * - Phone: nullable, unique (excluding soft-deleted)
 * - Password: nullable, min:8 (khi update)
 * - Role: required, exists:roles (khi tạo/update)
 * 
 * SECURITY:
 * - Chỉ manager mới có quyền quản lý users (agent không có quyền)
 * - Không cho phép xóa chính mình
 * - Exclude admin users khỏi danh sách và operations
 * - Validate sort fields để prevent SQL injection
 * - Email/phone uniqueness check (excluding soft-deleted users)
 * 
 * LƯU Ý:
 * - Admin users được exclude khỏi tất cả operations (không được quản lý bởi manager)
 * - User có thể thuộc nhiều organizations, cần filter theo organization_id
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Subscription limits được check trước khi tạo user mới
 */
class UserController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * PlanLimitChecker instance để kiểm tra subscription plan limits
     * 
     * @var \App\Services\Subscription\PlanLimitChecker
     */
    protected $limitChecker;

    /**
     * Constructor: Inject PlanLimitChecker dependency
     * 
     * @param \App\Services\Subscription\PlanLimitChecker $limitChecker
     */
    public function __construct(PlanLimitChecker $limitChecker)
    {
        $this->limitChecker = $limitChecker;
    }

    /**
     * Hiển thị danh sách users trong tổ chức với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: party.access và party.user.view
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Lấy danh sách admin user IDs để exclude khỏi danh sách
     * 4. Tạo helper function applyFilters() để apply common filters (search, role, status, date range)
     * 5. Build base query với filters cho pagination
     * 6. Tạo stats query riêng để tính statistics bằng aggregation (total, active, inactive)
     * 7. Build query cho pagination với eager loading (userProfile, userRoles)
     * 8. Handle sorting: Validate sort fields, support full_name sorting với JOIN
     * 9. Paginate results (10 items per page)
     * 10. Check request type (HTMX/AJAX):
     *     - HTMX: Return HTML với hx-swap-oob cho stats update
     *     - AJAX: Return JSON với table_html và stats_html
     *     - Normal: Return view với full data (roles, geo data)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: users, user_profiles, organization_users, roles
     * - Request: search, role_id, status, date_from, date_to, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - party.access: Quyền truy cập module Party
     * - party.user.view: Quyền xem danh sách users
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng JOINs thay vì whereHas() để tối ưu performance
     * - Sử dụng DISTINCT để tránh duplicate khi user thuộc nhiều organizations
     * - Tính statistics bằng aggregation (SUM, COUNT) trong một query
     * - Eager loading relationships với organization context
     * - Validate sort fields để prevent SQL injection
     * 
     * FILTERS:
     * - search: Tìm kiếm theo email, phone, full_name (userProfile)
     * - role_id: Filter theo role
     * - status: Filter theo status (active/inactive)
     * - date_from/date_to: Filter theo created_at
     * 
     * SORTING:
     * - Supported fields: id, email, phone, status, created_at, updated_at, full_name
     * - full_name requires JOIN với user_profiles table
     * - Default: created_at DESC
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và lấy organization ID
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $this->requireCapability('party.user.view', 'Bạn không có quyền xem Users.'); // Kiểm tra quyền xem danh sách users → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter users theo organization
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'); // Dừng request và trả về lỗi 403
        }

        // Lấy danh sách admin user IDs một lần để tái sử dụng
        // Admin users được exclude khỏi danh sách (không được quản lý bởi manager)
        // JOIN với roles để filter theo key_code = 'admin'
        $adminUserIds = DB::table('organization_users') // Query từ bảng organization_users
            ->join('roles', 'organization_users.role_id', '=', 'roles.id') // JOIN với roles → Để filter theo key_code
            ->where('roles.key_code', 'admin') // Chỉ lấy users có role admin
            ->where('organization_users.organization_id', $organizationId) // Chỉ lấy của organization hiện tại
            ->whereNull('organization_users.deleted_at') // Chỉ lấy chưa bị xóa
            ->pluck('organization_users.user_id') // Lấy danh sách user_id → Dùng để exclude
            ->toArray(); // Convert sang array → Dùng để whereNotIn

        // Helper function để apply common filters cho query
        // Sử dụng closure để có thể reuse cho cả base query và stats query
        $applyFilters = function($query) use ($request, $organizationId, $adminUserIds) {
            // Sử dụng JOIN thay vì whereHas() để tối ưu performance
            // JOIN với organization_users để filter theo organization_id
            $query->join('organization_users', 'users.id', '=', 'organization_users.user_id') // JOIN với organization_users → Để filter theo organization
                  ->where('organization_users.organization_id', $organizationId) // Chỉ lấy users của organization hiện tại
                  ->whereNull('organization_users.deleted_at') // Chỉ lấy organization_users chưa bị xóa
                  ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa
                  ->distinct() // Tránh duplicate nếu user thuộc nhiều organizations → Đảm bảo mỗi user chỉ xuất hiện 1 lần
                  ->select('users.*'); // Chỉ select columns từ users table → Tránh conflicts khi có JOIN

            if (!empty($adminUserIds)) { // Nếu có admin users
                $query->whereNotIn('users.id', $adminUserIds); // Exclude admin users → Admin không được quản lý bởi manager
            }

            if ($request->filled('search')) { // Nếu có search
                $search = $request->search; // Lấy từ khóa tìm kiếm
                $query->where(function($q) use ($search) { // Tạo group where → Tìm kiếm trong nhiều fields
                    $q->where('users.email', 'like', "%{$search}%") // Tìm trong email
                      ->orWhere('users.phone', 'like', "%{$search}%") // Hoặc phone
                      ->orWhereHas('userProfile', function ($profileQuery) use ($search) { // Hoặc full_name từ userProfile
                          $profileQuery->where('full_name', 'like', "%{$search}%"); // Tìm trong full_name
                      });
                }); // Trả về query builder → Dùng để tìm kiếm users
            }

            if ($request->filled('role_id')) { // Nếu có filter role_id
                $query->where('organization_users.role_id', $request->role_id); // Lọc theo role_id → Dùng để filter users theo vai trò
            }

            if ($request->filled('status')) { // Nếu có filter status
                $query->where('users.status', $request->status); // Lọc theo status → Dùng để filter users theo trạng thái (1=active, 0=inactive)
            }

            if ($request->filled('date_from')) { // Nếu có filter date_from
                $query->whereDate('users.created_at', '>=', $request->date_from); // Lọc từ ngày → Dùng để filter users theo ngày tạo
            }
            if ($request->filled('date_to')) { // Nếu có filter date_to
                $query->whereDate('users.created_at', '<=', $request->date_to); // Lọc đến ngày → Dùng để filter users theo ngày tạo
            }
        };

        // Build base query với filters cho pagination
        // Sử dụng base query để clone sau này cho pagination query
        $baseQuery = User::query(); // Tạo query builder từ User model → Dùng để build query với filters
        $applyFilters($baseQuery); // Áp dụng filters → Lọc users theo organization, exclude admin, search, role, status, date range

        // Tính statistics hiệu quả bằng conditional aggregation
        // Tạo query riêng cho stats để tránh conflicts với withCount() và eager loading
        // Sử dụng aggregation (SUM, COUNT) thay vì multiple queries để tối ưu performance
        $statsQuery = User::query(); // Tạo query riêng cho stats → Tránh conflicts với pagination query
        
        // Apply filters but without select('users.*') to avoid GROUP BY issues
        $statsQuery->join('organization_users', 'users.id', '=', 'organization_users.user_id') // JOIN với organization_users → Để filter theo organization
                  ->where('organization_users.organization_id', $organizationId) // Chỉ lấy users của organization hiện tại
                  ->whereNull('organization_users.deleted_at') // Chỉ lấy organization_users chưa bị xóa
                  ->whereNull('users.deleted_at'); // Chỉ lấy users chưa bị xóa

        if (!empty($adminUserIds)) { // Nếu có admin users
            $statsQuery->whereNotIn('users.id', $adminUserIds); // Exclude admin users → Admin không được tính vào statistics
        }

        // Apply same filters for stats
        if ($request->filled('search')) { // Nếu có search
            $search = $request->search; // Lấy từ khóa tìm kiếm
            $statsQuery->where(function($q) use ($search) { // Tạo group where → Tìm kiếm trong nhiều fields
                $q->where('users.email', 'like', "%{$search}%") // Tìm trong email
                  ->orWhere('users.phone', 'like', "%{$search}%") // Hoặc phone
                  ->orWhereHas('userProfile', function ($profileQuery) use ($search) { // Hoặc full_name từ userProfile
                      $profileQuery->where('full_name', 'like', "%{$search}%"); // Tìm trong full_name
                  });
            }); // Trả về query builder → Dùng để tìm kiếm users
        }

        if ($request->filled('role_id')) { // Nếu có filter role_id
            $statsQuery->where('organization_users.role_id', $request->role_id); // Lọc theo role_id → Dùng để filter users theo vai trò
        }

        if ($request->filled('status')) { // Nếu có filter status
            $statsQuery->where('users.status', $request->status); // Lọc theo status → Dùng để filter users theo trạng thái
        }

        if ($request->filled('date_from')) { // Nếu có filter date_from
            $statsQuery->whereDate('users.created_at', '>=', $request->date_from); // Lọc từ ngày → Dùng để filter users theo ngày tạo
        }
        if ($request->filled('date_to')) { // Nếu có filter date_to
            $statsQuery->whereDate('users.created_at', '<=', $request->date_to); // Lọc đến ngày → Dùng để filter users theo ngày tạo
        }

        // Tính statistics bằng aggregation (chỉ select aggregation columns)
        // Sử dụng SUM với CASE WHEN để đếm active/inactive trong một query duy nhất
        // Điều này tối ưu hơn việc chạy 3 queries riêng biệt
        $statsResult = $statsQuery->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN users.status = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN users.status = 0 THEN 1 ELSE 0 END) as inactive
            ')
            ->first(); // Lấy kết quả đầu tiên → Trả về object với total, active, inactive
        
        // Format statistics với default values nếu null
        // Cast sang int để đảm bảo type consistency
        $stats = [
            'total' => (int) ($statsResult->total ?? 0), // Tổng số users → Hiển thị trong statistics card
            'active' => (int) ($statsResult->active ?? 0), // Số users active → Hiển thị trong statistics card
            'inactive' => (int) ($statsResult->inactive ?? 0), // Số users inactive → Hiển thị trong statistics card
        ];

        // Build query cho pagination với eager loading
        // Clone base query để không ảnh hưởng đến base query gốc
        $query = clone $baseQuery; // Clone base query → Dùng để paginate, không ảnh hưởng đến base query
        
        // Eager load relationships với organization context để tối ưu performance
        // Chỉ load các fields cần thiết để giảm memory usage và query time
        $query->with([
            // Chỉ load user_id và full_name từ userProfile
            // Lưu ý: user_id là primary key trong user_profiles table, không phải id
            'userProfile:user_id,full_name', // Eager load userProfile với chỉ các fields cần thiết → Tránh N+1 query và giảm memory
            // Load userRoles với filter theo organization_id và exclude soft-deleted
            // Chỉ select các fields cần thiết từ roles table
            'userRoles' => function($q) use ($organizationId) { // Eager load userRoles với filter
                $q->wherePivot('organization_id', $organizationId) // Chỉ lấy roles của organization hiện tại
                  ->wherePivotNull('deleted_at') // Chỉ lấy roles chưa bị xóa
                  ->select('roles.id', 'roles.name', 'roles.key_code'); // Chỉ select các fields cần thiết → Tối ưu performance
            },
        ]);

        // Xử lý sorting: Lấy sort_by và sort_order từ request
        $sortBy = $request->get('sort_by', 'created_at'); // Lấy field sắp xếp từ request → Mặc định là 'created_at'
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy thứ tự sắp xếp từ request → Mặc định là 'desc'
        
        // Validate sort_by field để prevent SQL injection
        // Chỉ cho phép các fields được định nghĩa trong allowedSortFields
        $allowedSortFields = ['id', 'email', 'phone', 'status', 'created_at', 'updated_at', 'full_name']; // Danh sách fields được phép sort → Tránh SQL injection
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu field không hợp lệ
            $sortBy = 'created_at'; // Đặt về 'created_at' → Tránh SQL injection
        }
        
        // Validate sort_order: Chỉ cho phép 'asc' hoặc 'desc'
        // Convert sang lowercase để so sánh, default là 'desc'
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc'; // Validate sort order → Tránh SQL injection
        
        // Apply sorting
        if ($sortBy === 'full_name') { // Nếu sort theo full_name
            // Sort theo full_name từ user_profiles table
            // Cần JOIN với user_profiles và add full_name vào SELECT list khi sử dụng DISTINCT
            // Điều này cần thiết vì MySQL yêu cầu các columns trong ORDER BY phải có trong SELECT khi dùng DISTINCT
            $query->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id') // JOIN với user_profiles → Để sort theo full_name
                  ->select('users.*', 'user_profiles.full_name') // Add full_name vào SELECT → Để ORDER BY hoạt động với DISTINCT
                  ->distinct() // Đảm bảo không có duplicate từ join → Mỗi user chỉ xuất hiện 1 lần
                  ->orderBy('user_profiles.full_name', $sortOrder); // Sắp xếp theo full_name → Hiển thị danh sách users
        } else {
            // Prefix với 'users.' để tránh ambiguous column errors khi có joins
            // Ví dụ: nếu có join với organization_users, cả 2 tables có thể có column 'id'
            $query->orderBy('users.' . $sortBy, $sortOrder); // Sắp xếp theo field → Hiển thị danh sách users
        }
        
        // Paginate với 10 items per page
        // Laravel sẽ tự động handle pagination links và metadata
        $users = $query->paginate(10); // Phân trang 10 items/trang → Dùng để hiển thị danh sách users
        
        // Check if HTMX request (preferred method - no JavaScript needed)
        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không → Dùng để trả về partial HTML
        $isAjax = $request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest'); // Kiểm tra có phải AJAX request không → Dùng để trả về JSON
        
        // Prepare table HTML for both HTMX and AJAX requests
        if ($isHtmx || $isAjax) {
            // For AJAX requests, skip loading unnecessary data
            // Define columns for index-table component
            $columns = [
                [
                    'name' => 'id',
                    'label' => 'ID',
                    'format' => function($user) {
                        return '<span class="badge bg-secondary">#' . $user->id . '</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'full_name',
                    'label' => 'Họ tên',
                    'format' => function($user) {
                        return '<h6 class="mb-0">' . ($user->userProfile->full_name ?? '-') . '</h6>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'format' => function($user) {
                        return '<small class="text-muted">' . htmlspecialchars($user->email) . '</small>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'phone',
                    'label' => 'Số điện thoại',
                    'format' => function($user) {
                        return $user->phone ? '<small class="text-muted">' . htmlspecialchars($user->phone) . '</small>' : '<span class="text-muted">-</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'roles',
                    'label' => 'Vai trò',
                    'format' => function($user) {
                        if ($user->userRoles->count() > 0) {
                            $badges = $user->userRoles->map(function($role) {
                                return '<span class="badge bg-info">' . $role->name . '</span>';
                            })->implode(' ');
                            return $badges;
                        }
                        return '<span class="text-muted">Chưa có vai trò</span>';
                    },
                    'sortable' => false,
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'format' => function($user) {
                        if ($user->status) {
                            return '<span class="badge bg-success">Hoạt động</span>';
                        }
                        return '<span class="badge bg-warning">Tạm ngưng</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'created_at',
                    'label' => 'Ngày tạo',
                    'format' => function($user) {
                        return '<small class="text-muted">' . $user->created_at->format('d/m/Y H:i') . '</small>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'updated_at',
                    'label' => 'Cập nhật',
                    'format' => function($user) {
                        return '<small class="text-muted">' . $user->updated_at->format('d/m/Y H:i') . '</small>';
                    },
                    'sortable' => true,
                ],
            ];
            
            // Define row actions
            $rowActions = function($user) {
                $actions = [
                    [
                        'variant' => 'outline-primary',
                        'icon' => 'fas fa-eye',
                        'iconPosition' => 'only',
                        'tooltip' => 'Xem chi tiết',
                        'url' => route('staff.users.show', $user->id)
                    ],
                    [
                        'variant' => 'outline-warning',
                        'icon' => 'fas fa-edit',
                        'iconPosition' => 'only',
                        'tooltip' => 'Sửa',
                        'url' => route('staff.users.edit', $user->id)
                    ],
                ];
                
                // Only show delete button if not current user
                if ($user->id !== Auth::id()) {
                    $userName = $user->userProfile->full_name ?? $user->email;
                    $actions[] = [
                        'variant' => 'outline-danger',
                        'icon' => 'fas fa-trash',
                        'iconPosition' => 'only',
                        'tooltip' => 'Xóa',
                        'onclick' => "deleteUser({$user->id}, '" . addslashes($userName) . "')",
                        'type' => 'button',
                    ];
                }
                
                return $actions;
            };
            
            // Render index-table component for AJAX/HTMX
            $tableHtml = view('staff.components.index-table', [
                'items' => $users,
                'tableContainerId' => 'users-table-container',
                'selectable' => false, // Disable bulk actions to avoid errors
                'columns' => $columns,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'rowActions' => $rowActions,
                'emptyMessage' => 'Chưa có người dùng nào',
                'emptyIcon' => 'fa-users',
                'emptyAction' => [
                    'variant' => 'primary',
                    'label' => 'Thêm người dùng mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.users.create')
                ]
            ])->render();
            
            // Format stats for response
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'active' => [
                    'value' => $stats['active'] ?? 0,
                    'label' => 'Hoạt động',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => '1',
                ],
                'inactive' => [
                    'value' => $stats['inactive'] ?? 0,
                    'label' => 'Tạm ngưng',
                    'icon' => 'fa-pause-circle',
                    'color' => 'warning',
                    'filter' => '0',
                ],
            ];
            
            $statsHtml = view('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter', // Use HTMX
                'onClearClick' => 'htmx-clear', // Use HTMX
                'tableContainerId' => 'users-table-container',
                'action' => route('staff.users.index'),
                'columns' => 3
            ])->render();
            
            // Handle HTMX request - return HTML directly
            if ($isHtmx) {
                // For innerHTML swap, we need to return only the inner content
                // Extract inner HTML from tableHtml (remove the outer wrapper div)
                // The tableHtml contains: <div class="row" id="users-table-container">...</div>
                // We need to return only the content inside (the col-12 div and everything inside)
                $innerTableHtml = $tableHtml;
                // Match the opening div with id="users-table-container" and extract everything inside until closing tag
                if (preg_match('/<div[^>]*id=["\']users-table-container["\'][^>]*>(.*?)<\/div>\s*$/s', $tableHtml, $matches)) {
                    $innerTableHtml = trim($matches[1]);
                }
                
                // Return inner HTML with stats update via hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            }
            
            // Handle AJAX request - return JSON
            return response()->json([
                'success' => true,
                'table_html' => $tableHtml,
                'stats_html' => $statsHtml,
                'has_more' => $users->hasMorePages(), // For infinite scroll
                'url' => $users->url($users->currentPage()), // For URL update
            ]);
        }

        // For non-AJAX requests, load additional data for filters
        $roles = Role::where('key_code', '!=', 'admin')
            ->whereHas('users', function($q) use ($organizationId) {
                $q->whereHas('organizations', function($orgQ) use ($organizationId) {
                    $orgQ->where('organizations.id', $organizationId);
                });
            })
            ->get();
        
        // Get all geo data for dropdowns (since users don't have location data)
        $provinces = \App\Models\GeoProvince::all();
        $districts = \App\Models\GeoDistrict::all();
        $provinces2025 = \App\Models\GeoProvince2025::all();
        
        // Get wards2025 based on selected province_2025
        $wards2025 = collect();
        if ($request->filled('province_2025')) {
            $wards2025 = \App\Models\GeoWard2025::where('province_code', $request->province_2025)->get();
        }

        return view('staff.party.users.index', compact('users', 'roles', 'stats', 'provinces', 'districts', 'provinces2025', 'wards2025'));
    }

    /**
     * Hiển thị form tạo user mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để manager tạo user mới trong organization
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * 
     * OUTPUT:
     * - View: staff.party.users.create
     * - Data: roles (danh sách roles, exclude admin)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.create
     * 2. Lấy danh sách roles (exclude admin)
     * 3. Trả về view với roles
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng roles: Lấy danh sách roles (exclude admin)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * CAPABILITY CHECKING:
     * - party.access: Quyền truy cập module Party
     * - party.user.create: Quyền tạo user
     * 
     * @return \Illuminate\View\View View form tạo user
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $this->requireCapability('party.user.create', 'Bạn không có quyền tạo Users.'); // Kiểm tra quyền tạo user → Dừng nếu không có quyền
        
        $roles = Role::where('key_code', '!=', 'admin')->get(); // Lấy danh sách roles (exclude admin) → Hiển thị trong form select
        return view('staff.party.users.create', compact('roles')); // Trả về view với roles → Hiển thị form tạo user
    }

    /**
     * Tạo user mới trong organization
     * 
     * MỤC ĐÍCH:
     * Tạo user mới với email, phone, password, role và gán vào organization hiện tại
     * 
     * INPUT:
     * - Request: full_name, email, phone, password, status, role_id
     * - Session: organization_id, user_id
     * - Database: organizations (để check subscription limit)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", user_id: ...}
     * - Database: Tạo bản ghi mới trong users, user_profiles, organization_users
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.create
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra subscription limit (max_users)
     * 4. Validate input (email, phone uniqueness, password, role_id)
     * 5. Kiểm tra không được assign admin role
     * 6. Transaction: Tạo User, UserProfile, OrganizationUser
     * 7. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Kiểm tra subscription limit
     * - Bảng roles: Validate role_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Tạo user mới
     * - Bảng user_profiles: Tạo profile với full_name
     * - Bảng organization_users: Gán user vào organization với role
     * 
     * LƯU Ý:
     * - Kiểm tra subscription limit trước khi tạo user
     * - Manager không thể tạo admin users
     * - Email và phone phải unique (excluding soft-deleted)
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin user
     * @return \Illuminate\Http\JsonResponse JSON response
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module Party.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        $this->requireCapability('party.user.create', 'Bạn không có quyền tạo Users.'); // Kiểm tra quyền tạo user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để gán user vào organization
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        try {
            $organization = \App\Models\Organization::find($organizationId); // Tìm organization theo ID → Dùng để check subscription limit
            
            if (!$organization) { // Nếu không tìm thấy organization
                return response()->json([
                    'success' => false,
                    'message' => 'Tổ chức không tồn tại.'
                ], 404); // Trả về JSON error → Frontend hiển thị thông báo
            }

            if (!$this->limitChecker->canAddUser($organization)) { // Kiểm tra subscription limit → Dừng nếu vượt quá limit
                $limit = $this->limitChecker->getLimit($organization, 'max_users'); // Lấy limit value → Hiển thị trong error message
                $current = $this->limitChecker->getUsersCount($organization); // Lấy số users hiện tại → Hiển thị trong error message
                
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã đạt giới hạn số lượng người dùng của gói dịch vụ. Hiện tại: {$current}/{$limit}",
                    'error_type' => 'subscription_limit',
                ], 403); // Trả về JSON error → Frontend hiển thị thông báo
            }

            $validated = $request->validate([
                'full_name' => 'required|string|max:255', // full_name: bắt buộc, string, tối đa 255 ký tự
                'email' => [
                    'required',
                    'email',
                    // Bỏ unique validation vì sẽ xử lý thủ công theo logic trùng email trong cùng/khác tổ chức
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    // Bỏ unique validation vì sẽ xử lý thủ công theo logic trùng phone trong cùng/khác tổ chức
                ],
                'password' => 'required|string|min:6', // password: bắt buộc, string, tối thiểu 6 ký tự
                'status' => 'nullable|integer|in:0,1', // status: nullable, integer, chỉ 0 hoặc 1
                'role_id' => 'required|exists:roles,id', // role_id: bắt buộc, phải tồn tại trong bảng roles
            ]);

            $role = Role::find($validated['role_id']); // Tìm role theo ID → Kiểm tra không được assign admin role
            if ($role && $role->key_code === 'admin') { // Nếu role là admin
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo tài khoản với vai trò Quản trị hệ thống. Vui lòng chọn vai trò khác.'
                ], 403); // Trả về JSON error → Frontend hiển thị thông báo
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            try {
                $user = null;
                $isNewUser = false;
                
                // BƯỚC 1: Kiểm tra email trước
                $existingUserByEmail = User::where('email', $validated['email'])
                    ->whereNull('deleted_at')
                    ->first();
                
                if ($existingUserByEmail) {
                    // Kiểm tra xem user này đã thuộc tổ chức hiện tại chưa
                    $existingOrgUser = DB::table('organization_users')
                        ->where('user_id', $existingUserByEmail->id)
                        ->where('organization_id', $organizationId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if ($existingOrgUser) {
                        // Email trùng trong cùng tổ chức -> báo lỗi
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Email này đã được sử dụng trong tổ chức này. Vui lòng sử dụng email khác.',
                            'errors' => ['email' => ['Email này đã được sử dụng trong tổ chức này.']]
                        ], 422);
                    } else {
                        // Email trùng nhưng khác tổ chức -> gắn thêm tổ chức và role cho user đó
                        $user = $existingUserByEmail;
                        
                        // Cập nhật full_name nếu có thay đổi
                        $profile = $user->userProfile;
                        if ($profile) {
                            $profile->update(['full_name' => $validated['full_name']]);
                        } else {
                            \App\Models\UserProfile::create([
                                'user_id' => $user->id,
                                'full_name' => $validated['full_name'],
                            ]);
                        }
                        
                        // Gắn user vào tổ chức hiện tại với role
                        $user->organizations()->attach($organizationId, [
                            'role_id' => $validated['role_id'],
                            'status' => 'active'
                        ]);
                    }
                } else {
                    // Email chưa tồn tại -> tạo user mới
                    $isNewUser = true;
                    $user = User::create([
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => Hash::make($validated['password']),
                        'status' => $validated['status'] ?? 1,
                    ]);

                    \App\Models\UserProfile::create([
                        'user_id' => $user->id,
                        'full_name' => $validated['full_name'],
                    ]);

                    $user->organizations()->attach($organizationId, [
                        'role_id' => $validated['role_id'],
                        'status' => 'active'
                    ]);
                }
                
                // BƯỚC 2: Kiểm tra phone trong tổ chức đã gắn thêm (hoặc tổ chức hiện tại nếu user mới)
                if ($request->filled('phone') && $validated['phone']) {
                    // Kiểm tra xem phone đã tồn tại trong tổ chức hiện tại chưa (trừ user hiện tại)
                    $existingUserByPhone = User::where('phone', $validated['phone'])
                        ->where('id', '!=', $user->id) // Loại trừ user hiện tại
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if ($existingUserByPhone) {
                        // Kiểm tra xem user này đã thuộc tổ chức hiện tại chưa
                        $existingOrgUserByPhone = DB::table('organization_users')
                            ->where('user_id', $existingUserByPhone->id)
                            ->where('organization_id', $organizationId)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if ($existingOrgUserByPhone) {
                            // Phone trùng trong cùng tổ chức -> báo lỗi
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Số điện thoại này đã được sử dụng trong tổ chức này. Vui lòng sử dụng số điện thoại khác.',
                                'errors' => ['phone' => ['Số điện thoại này đã được sử dụng trong tổ chức này.']]
                            ], 422);
                        } else {
                            // Phone trùng nhưng khác tổ chức -> gắn thêm tổ chức và role cho user đó
                            $existingUserByPhone->organizations()->attach($organizationId, [
                                'role_id' => $validated['role_id'],
                                'status' => 'active'
                            ]);
                        }
                    } else {
                        // Phone chưa tồn tại -> cập nhật phone cho user hiện tại (nếu user mới tạo)
                        if ($isNewUser) {
                            $user->update(['phone' => $validated['phone']]);
                        }
                    }
                }

                DB::commit(); // Commit transaction → Lưu tất cả thay đổi

                return response()->json([
                    'success' => true,
                    'message' => 'Người dùng đã được tạo thành công!',
                    'user_id' => $user->id // User ID → Frontend có thể redirect đến trang chi tiết
                ]);
            } catch (\Exception $e) {
                DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
                throw $e; // Throw lại exception → Để catch block bên ngoài xử lý
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin không hợp lệ: ' . implode(', ', $e->validator->errors()->all()) . '. Vui lòng kiểm tra lại và thử lại.'
            ], 422); // Trả về JSON error → Frontend hiển thị validation errors
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.'
            ], 500); // Trả về JSON error → Frontend hiển thị thông báo lỗi
        }
    }

    /**
     * Hiển thị chi tiết user
     * 
     * MỤC ĐÍCH:
     * Hiển thị thông tin chi tiết của một user bao gồm roles, capabilities, assigned properties, invoices, cash outflows
     * 
     * INPUT:
     * - Route parameter: id (ID của user cần xem)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, organization_users, roles, capabilities, company_invoices, cash_outflows
     * 
     * OUTPUT:
     * - View: staff.party.users.show
     * - Data: targetUser, orgUser, userCapabilities, allCapabilities
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.view
     * 2. Lấy organization ID từ session
     * 3. Tìm target user với eager loading relationships
     * 4. Load cash outflows qua company_invoices
     * 5. Kiểm tra không phải admin user
     * 6. Lấy OrganizationUser với capabilities và overrides
     * 7. Lấy user current capabilities và all capabilities
     * 8. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user
     * - Bảng user_profiles: Lấy profile (full_name)
     * - Bảng organization_users: Lấy OrganizationUser với capabilities
     * - Bảng roles: Lấy roles của user
     * - Bảng capabilities: Lấy tất cả capabilities
     * - Bảng company_invoices: Lấy invoices của user
     * - Bảng cash_outflows: Lấy cash outflows qua company_invoices
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Manager không thể xem admin users
     * - Cash outflows được load riêng vì relationship là query builder
     * 
     * @param int $id ID của user cần xem
     * @return \Illuminate\View\View View chi tiết user
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $this->requireCapability('party.user.view', 'Bạn không có quyền xem Users.'); // Kiểm tra quyền xem user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'); // Dừng request và trả về lỗi 403
        }

        $targetUser = User::with(['userRoles', 'userProfile', 'sepayBank', 'companyInvoices']) // Eager load relationships → Tránh N+1 query
            ->whereHas('organizations', function($q) use ($organizationId) { // Tìm user thuộc organization hiện tại
                $q->where('organizations.id', $organizationId); // Chỉ lấy users của organization hiện tại
            })
            ->findOrFail($id); // Tìm user theo ID → Dừng nếu không tìm thấy
        
        // Load cash outflows separately since relationship is now a query builder
        // Get cash outflows through company_invoices where this user is the recipient (user_id)
        $targetUser->setRelation('cashOutflows', CashOutflow::whereHas('companyInvoice', function($query) use ($targetUser) { // Tìm cash outflows qua company_invoices
            $query->where('user_id', $targetUser->id); // Chỉ lấy cash outflows của user này
        })->get()); // Lấy tất cả cash outflows → Dùng để hiển thị
        
        if ($targetUser->userRoles->where('key_code', 'admin')->count() > 0) { // Nếu user là admin
            abort(403, 'Bạn không có quyền xem thông tin tài khoản Quản trị hệ thống. Vui lòng liên hệ Admin để được hỗ trợ.'); // Dừng request và trả về lỗi 403
        }
        
        $orgUser = OrganizationUser::where('user_id', $targetUser->id) // Tìm OrganizationUser theo user_id
            ->where('organization_id', $organizationId) // Chỉ lấy của organization hiện tại
            ->with(['capabilities', 'capabilityOverrides']) // Eager load capabilities và overrides → Tránh N+1 query
            ->first(); // Lấy bản ghi đầu tiên → Có thể null nếu user chưa được gán vào organization
        
        $userCapabilities = CapabilityService::getUserCapabilities($targetUser->id, $organizationId); // Lấy user current capabilities (role defaults + overrides) → Dùng để hiển thị
        $allCapabilities = Capability::ordered()->get()->groupBy('category'); // Lấy tất cả capabilities grouped by category → Dùng để hiển thị
        
        return view('staff.party.users.show', compact('targetUser', 'orgUser', 'userCapabilities', 'allCapabilities')); // Trả về view với data → Hiển thị chi tiết user
    }

    /**
     * Hiển thị form chỉnh sửa user
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để manager chỉnh sửa thông tin user (email, phone, password, status, role)
     * 
     * INPUT:
     * - Route parameter: id (ID của user cần chỉnh sửa)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, organization_users, roles
     * 
     * OUTPUT:
     * - View: staff.party.users.edit
     * - Data: targetUser, roles (danh sách roles, exclude admin)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.update
     * 2. Lấy organization ID từ session
     * 3. Tìm target user với eager loading relationships
     * 4. Kiểm tra không phải admin user
     * 5. Lấy danh sách roles (exclude admin)
     * 6. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user
     * - Bảng user_profiles: Lấy profile (full_name)
     * - Bảng organization_users: Lấy role của user trong organization
     * - Bảng roles: Lấy danh sách roles (exclude admin)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Manager không thể chỉnh sửa admin users
     * 
     * @param int $id ID của user cần chỉnh sửa
     * @return \Illuminate\View\View View form chỉnh sửa user
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $this->requireCapability('party.user.update', 'Bạn không có quyền chỉnh sửa Users.'); // Kiểm tra quyền chỉnh sửa user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'); // Dừng request và trả về lỗi 403
        }

        $targetUser = User::with(['userRoles', 'userProfile']) // Eager load relationships → Tránh N+1 query
            ->whereHas('organizations', function($q) use ($organizationId) { // Tìm user thuộc organization hiện tại
                $q->where('organizations.id', $organizationId); // Chỉ lấy users của organization hiện tại
            })
            ->findOrFail($id); // Tìm user theo ID → Dừng nếu không tìm thấy
        
        if ($targetUser->userRoles->where('key_code', 'admin')->count() > 0) { // Nếu user là admin
            abort(403, 'Bạn không có quyền chỉnh sửa tài khoản Quản trị hệ thống. Vui lòng liên hệ Admin để được hỗ trợ.'); // Dừng request và trả về lỗi 403
        }

        $roles = Role::where('key_code', '!=', 'admin')->get(); // Lấy danh sách roles (exclude admin) → Hiển thị trong form select
        return view('staff.party.users.edit', compact('targetUser', 'roles')); // Trả về view với data → Hiển thị form chỉnh sửa user
    }

    /**
     * Cập nhật thông tin user
     * 
     * MỤC ĐÍCH:
     * Cập nhật thông tin user (email, phone, password, status, role) trong organization hiện tại
     * 
     * INPUT:
     * - Request: full_name, email, phone, password (optional), status, role_id
     * - Route parameter: id (ID của user cần cập nhật)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, organization_users, roles
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."}
     * - Database: Cập nhật bản ghi trong users, user_profiles, organization_users
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.update
     * 2. Lấy organization ID từ session
     * 3. Tìm target user và kiểm tra không phải admin
     * 4. Validate input (email, phone uniqueness với ignore current user, password optional)
     * 5. Kiểm tra không được assign admin role
     * 6. Transaction: Cập nhật User, UserProfile, OrganizationUser (role)
     * 7. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user hiện tại
     * - Bảng user_profiles: Lấy profile hiện tại
     * - Bảng organization_users: Lấy OrganizationUser hiện tại
     * - Bảng roles: Validate role_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật email, phone, password, status
     * - Bảng user_profiles: Cập nhật hoặc tạo full_name
     * - Bảng organization_users: Cập nhật role_id (chỉ cho organization hiện tại)
     * 
     * LƯU Ý:
     * - Manager không thể cập nhật admin users
     * - Manager không thể assign admin role
     * - Password là optional (chỉ cập nhật nếu có)
     * - Role chỉ cập nhật cho organization hiện tại, không đồng bộ với các organization khác
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin cập nhật
     * @param int $id ID của user cần cập nhật
     * @return \Illuminate\Http\JsonResponse JSON response
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module Party.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        $this->requireCapability('party.user.update', 'Bạn không có quyền cập nhật Users.'); // Kiểm tra quyền cập nhật user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để cập nhật role
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }

        $targetUser = User::with(['userRoles']) // Eager load userRoles → Kiểm tra không phải admin
            ->whereHas('organizations', function($q) use ($organizationId) { // Tìm user thuộc organization hiện tại
                $q->where('organizations.id', $organizationId); // Chỉ lấy users của organization hiện tại
            })
            ->findOrFail($id); // Tìm user theo ID → Dừng nếu không tìm thấy
        
        if ($targetUser->userRoles->where('key_code', 'admin')->count() > 0) { // Nếu user là admin
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật tài khoản Quản trị hệ thống. Vui lòng liên hệ Admin để được hỗ trợ.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }

        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255', // full_name: bắt buộc, string, tối đa 255 ký tự
                'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')->ignore($targetUser->id)], // email: bắt buộc, email format, unique (excluding soft-deleted và current user)
                'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->whereNull('deleted_at')->ignore($targetUser->id)], // phone: nullable, string, tối đa 20 ký tự, unique (excluding soft-deleted và current user)
                'password' => 'nullable|string|min:6', // password: nullable, string, tối thiểu 6 ký tự
                'status' => 'nullable|integer|in:0,1', // status: nullable, integer, chỉ 0 hoặc 1
                'role_id' => 'required|exists:roles,id', // role_id: bắt buộc, phải tồn tại trong bảng roles
            ]);

            $role = Role::find($validated['role_id']); // Tìm role theo ID → Kiểm tra không được assign admin role
            if ($role && $role->key_code === 'admin') { // Nếu role là admin
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền gán vai trò Quản trị hệ thống. Vui lòng chọn vai trò khác.'
                ], 403); // Trả về JSON error → Frontend hiển thị thông báo
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            try {
                $updateData = [
                    'email' => $validated['email'], // Email mới
                    'phone' => $validated['phone'], // Phone mới
                    'status' => $validated['status'] ?? 1, // Status mặc định là 1 (active)
                ];

                if (!empty($validated['password'])) { // Nếu có password mới
                    $updateData['password_hash'] = Hash::make($validated['password']); // Hash password → Lưu vào database
                }

                $targetUser->update($updateData); // Cập nhật user → Lưu email, phone, password, status

                $profile = $targetUser->userProfile; // Lấy user profile hiện tại → Kiểm tra có tồn tại không
                if (!$profile) { // Nếu chưa có profile
                    $profile = \App\Models\UserProfile::create([
                        'user_id' => $targetUser->id, // User ID → Liên kết với user
                        'full_name' => $validated['full_name'] // Full name → Hiển thị trong UI
                    ]); // Tạo profile mới → Lưu full_name
                } else {
                    $profile->update([
                        'full_name' => $validated['full_name'], // Full name mới → Hiển thị trong UI
                    ]); // Cập nhật profile → Lưu full_name
                }

                // Update organization role (CHỈ cho organization hiện tại, KHÔNG đồng bộ với các organization khác)
                // Sử dụng DB::table() để đảm bảo chỉ cập nhật role cho organization hiện tại
                $pivotUpdated = DB::table('organization_users') // Query từ bảng organization_users
                    ->where('user_id', $targetUser->id) // Chỉ cập nhật của user này
                    ->where('organization_id', $organizationId) // Chỉ cập nhật của organization hiện tại
                    ->whereNull('deleted_at') // Chỉ cập nhật chưa bị xóa
                    ->update([
                        'role_id' => $validated['role_id'], // Role ID mới → Gán role cho user trong organization
                        'updated_at' => now(), // Cập nhật thời gian → Track thay đổi
                    ]); // Cập nhật role → Chỉ ảnh hưởng đến organization hiện tại

                if ($pivotUpdated === 0) { // Nếu không cập nhật được (không tìm thấy pivot record)
                    $exists = DB::table('organization_users') // Kiểm tra user có thuộc organization không
                        ->where('user_id', $targetUser->id) // Chỉ kiểm tra của user này
                        ->where('organization_id', $organizationId) // Chỉ kiểm tra của organization hiện tại
                        ->exists(); // Kiểm tra tồn tại → Trả về true/false
                    
                    if (!$exists) { // Nếu user không thuộc organization
                        throw new \Exception('Người dùng chưa được gán vào tổ chức này. Vui lòng liên hệ Admin để được hỗ trợ.'); // Throw exception → Catch block xử lý
                    } else {
                        throw new \Exception('Không thể cập nhật vai trò. Vui lòng liên hệ Admin để được hỗ trợ.'); // Throw exception → Catch block xử lý
                    }
                }

                DB::commit(); // Commit transaction → Lưu tất cả thay đổi

                return response()->json([
                    'success' => true,
                    'message' => 'Người dùng đã được cập nhật thành công!',
                    'redirect' => route('staff.users.show', $targetUser->id) // URL chuyển đến trang chi tiết → Frontend redirect
                ]); // Trả về JSON success → Frontend hiển thị thông báo và redirect
            } catch (\Exception $e) {
                DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
                throw $e; // Throw lại exception → Để catch block bên ngoài xử lý
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin không hợp lệ: ' . implode(', ', $e->validator->errors()->all()) . '. Vui lòng kiểm tra lại và thử lại.'
            ], 422); // Trả về JSON error → Frontend hiển thị validation errors
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.'
            ], 500); // Trả về JSON error → Frontend hiển thị thông báo lỗi
        }
    }

    /**
     * Xóa user (soft delete)
     * 
     * MỤC ĐÍCH:
     * Xóa mềm user khỏi organization hiện tại. Nếu user không thuộc organization nào khác, sẽ xóa mềm user luôn
     * 
     * INPUT:
     * - Route parameter: id (ID của user cần xóa)
     * - Session: organization_id, user_id
     * - Database: users, organization_users
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."}
     * - Database: Soft delete bản ghi trong organization_users (và users nếu không thuộc organization nào khác)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.delete
     * 2. Lấy organization ID từ session
     * 3. Tìm target user và kiểm tra không phải admin
     * 4. Kiểm tra không được xóa chính mình
     * 5. Transaction: Soft delete OrganizationUser
     * 6. Kiểm tra user còn thuộc organization nào khác không
     * 7. Nếu không còn organization nào: Soft delete User
     * 8. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user
     * - Bảng organization_users: Lấy OrganizationUser và kiểm tra còn organization nào khác
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng organization_users: Soft delete (ghi deleted_by và deleted_at)
     * - Bảng users: Soft delete (nếu không còn thuộc organization nào khác)
     * 
     * LƯU Ý:
     * - Manager không thể xóa admin users
     * - Manager không thể xóa chính mình
     * - Chỉ xóa mềm (soft delete), dữ liệu vẫn được lưu trữ
     * - Nếu user thuộc nhiều organizations, chỉ xóa khỏi organization hiện tại
     * 
     * @param int $id ID của user cần xóa
     * @return \Illuminate\Http\JsonResponse JSON response
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và kiểm tra không xóa chính mình
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module Party.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        $this->requireCapability('party.user.delete', 'Bạn không có quyền xóa Users.'); // Kiểm tra quyền xóa user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để xóa user khỏi organization
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gán vào tổ chức nào. Vui lòng liên hệ Admin để được hỗ trợ.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
        try {
            $targetUser = User::with(['userRoles']) // Eager load userRoles → Kiểm tra không phải admin
                ->whereHas('organizations', function($q) use ($organizationId) { // Tìm user thuộc organization hiện tại
                    $q->where('organizations.id', $organizationId); // Chỉ lấy users của organization hiện tại
                })
                ->findOrFail($id); // Tìm user theo ID → Dừng nếu không tìm thấy
            
            if ($targetUser->userRoles->where('key_code', 'admin')->count() > 0) { // Nếu user là admin
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa tài khoản Quản trị hệ thống. Vui lòng liên hệ Admin để được hỗ trợ.'
                ], 403); // Trả về JSON error → Frontend hiển thị thông báo
            }
            
            if ($targetUser->id === $user->id) { // Nếu đang cố xóa chính mình
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thể xóa tài khoản của chính mình. Vui lòng liên hệ Admin để được hỗ trợ.'
                ], 422); // Trả về JSON error → Frontend hiển thị thông báo
            }
            
            $targetOrgUser = OrganizationUser::where('user_id', $targetUser->id) // Tìm OrganizationUser theo user_id
                ->where('organization_id', $organizationId) // Chỉ lấy của organization hiện tại
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->first(); // Lấy bản ghi đầu tiên → Có thể null
            
            if (!$targetOrgUser) { // Nếu không tìm thấy OrganizationUser
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không thuộc tổ chức này.'
                ], 404); // Trả về JSON error → Frontend hiển thị thông báo
            }
            
            // Soft delete the organization_user record for this organization
            // deleted_by will be set automatically by HasSoftDeletesWithUser trait
            $targetOrgUser->delete(); // Soft delete organization_user → Ghi deleted_by và deleted_at
            
            $remainingOrgUsers = OrganizationUser::where('user_id', $targetUser->id) // Kiểm tra user còn thuộc organization nào khác không
                ->whereNull('deleted_at') // Chỉ đếm chưa bị xóa
                ->count(); // Đếm số lượng → Nếu = 0 thì xóa user luôn
            
            if ($remainingOrgUsers === 0) { // Nếu user không còn thuộc organization nào
                // deleted_by will be set automatically by HasSoftDeletesWithUser trait
                $targetUser->delete(); // Soft delete user → Ghi deleted_by và deleted_at
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([
                'success' => true,
                'message' => 'Người dùng đã được xóa mềm thành công! Dữ liệu vẫn được lưu trữ và có thể khôi phục lại.'
            ]); // Trả về JSON success → Frontend hiển thị thông báo
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi khi có lỗi
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500); // Trả về JSON error → Frontend hiển thị thông báo lỗi
        }
    }

    /**
     * Cập nhật trạng thái user (active/inactive)
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái user (active = 1, inactive = 0) để kích hoạt hoặc tạm dừng tài khoản
     * 
     * INPUT:
     * - Request: status (boolean: true = active, false = inactive)
     * - Route parameter: id (ID của user cần cập nhật status)
     * - Session: organization_id, user_id
     * - Database: users
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."}
     * - Database: Cập nhật status trong bảng users
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access và party.user.update
     * 2. Lấy organization ID từ session
     * 3. Tìm target user và kiểm tra không phải admin
     * 4. Kiểm tra không được cập nhật status của chính mình
     * 5. Validate: status phải là boolean
     * 6. Transaction: Cập nhật status
     * 7. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user hiện tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật status (1 = active, 0 = inactive)
     * 
     * LƯU Ý:
     * - Manager không thể cập nhật status của admin users
     * - Manager không thể cập nhật status của chính mình
     * 
     * @param \Illuminate\Http\Request $request Request chứa status
     * @param int $id ID của user cần cập nhật status
     * @return \Illuminate\Http\JsonResponse JSON response
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và kiểm tra không cập nhật chính mình
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module Party.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }
        
        $this->requireCapability('party.user.update', 'Bạn không có quyền cập nhật Users.'); // Kiểm tra quyền cập nhật user → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa được gán vào tổ chức nào.'
            ], 403); // Trả về JSON error → Frontend hiển thị thông báo
        }

        try {
            $targetUser = User::with('userRoles') // Eager load userRoles → Kiểm tra không phải admin
                ->whereHas('organizations', function($q) use ($organizationId) { // Tìm user thuộc organization hiện tại
                    $q->where('organizations.id', $organizationId); // Chỉ lấy users của organization hiện tại
                })
                ->findOrFail($id); // Tìm user theo ID → Dừng nếu không tìm thấy
            
            if ($targetUser->userRoles->where('key_code', 'admin')->count() > 0) { // Nếu user là admin
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật tài khoản Quản trị hệ thống.'
                ], 403); // Trả về JSON error → Frontend hiển thị thông báo
            }
            
            if ($targetUser->id === $user->id) { // Nếu đang cố cập nhật status của chính mình
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thể thay đổi trạng thái của chính mình.'
                ], 422); // Trả về JSON error → Frontend hiển thị thông báo
            }

            $request->validate([
                'status' => 'required|boolean' // status: bắt buộc, phải là boolean (true/false)
            ], [
                'status.required' => 'Trạng thái là bắt buộc.',
                'status.boolean' => 'Trạng thái không hợp lệ.'
            ]);

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            $targetUser->update(['status' => $request->status]); // Cập nhật status → Lưu vào database
            
            $statusLabel = $targetUser->status ? 'kích hoạt' : 'tạm dừng'; // Lấy label status → Dùng để hiển thị trong message

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([
                'success' => true,
                'message' => "Người dùng đã được {$statusLabel} thành công!",
                'status' => $targetUser->status // Status mới → Frontend có thể cập nhật UI
            ]); // Trả về JSON success → Frontend hiển thị thông báo

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi khi có lỗi validation
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors() // Validation errors → Frontend hiển thị chi tiết lỗi
            ], 422); // Trả về JSON error → Frontend hiển thị validation errors
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi khi có lỗi
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage()
            ], 500); // Trả về JSON error → Frontend hiển thị thông báo lỗi
        }
    }

}

