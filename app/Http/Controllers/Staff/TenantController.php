<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lease;
use App\Models\LeaseResident;
use App\Models\Lead;
use App\Models\BookingDeposit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Organization;
use App\Models\Role;
use App\Models\OrganizationUser;
use App\Services\Subscription\PlanLimitChecker;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * Controller: TenantController
 * 
 * MỤC ĐÍCH:
 * Quản lý tenants (khách hàng thuê) trong module Party - cho phép tạo, xem, sửa, xóa và quản lý thông tin tenants, theo dõi leases, booking deposits, invoices, payments, tickets
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách tenants với filter, search, sort, pagination và statistics
 * 2. create(): Hiển thị form tạo tenant mới
 * 3. store(): Tạo tenant mới với validation, duplicate check, subscription limit check
 * 4. show(): Hiển thị chi tiết tenant kèm leases, bookingDeposits, invoices, tickets
 * 5. edit(): Hiển thị form chỉnh sửa tenant
 * 6. update(): Cập nhật thông tin tenant với validation và duplicate check
 * 7. destroy(): Xóa tenant (soft delete) với kiểm tra không cho xóa chính mình
 * 8. statistics(): Hiển thị trang thống kê tenants theo status, month, source, lease status
 * 9. updateStatus(): Cập nhật trạng thái tenant (active/inactive)
 * 10. addResident(): Thêm resident vào lease
 * 11. searchUsers(): API endpoint tìm kiếm users (cho autocomplete)
 * 
 * ENDPOINTS:
 * - GET /staff/tenants: Hiển thị danh sách tenants
 * - GET /staff/tenants/create: Hiển thị form tạo mới
 * - POST /staff/tenants: Tạo tenant mới
 * - GET /staff/tenants/{id}: Hiển thị chi tiết tenant
 * - GET /staff/tenants/{id}/edit: Hiển thị form chỉnh sửa
 * - PUT /staff/tenants/{id}: Cập nhật tenant
 * - DELETE /staff/tenants/{id}: Xóa tenant
 * - GET /staff/tenants/statistics: Hiển thị thống kê
 * - POST /staff/tenants/{id}/update-status: Cập nhật trạng thái
 * - POST /staff/tenants/add-resident/{leaseId}: Thêm resident vào lease
 * - GET /staff/api/users/search: Tìm kiếm users (API)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model User (bảng users): Lấy danh sách và chi tiết tenants
 * - Model UserProfile (bảng user_profiles): Lấy thông tin profile của tenants
 * - Model Lease (bảng leases): Lấy hợp đồng thuê của tenants
 * - Model BookingDeposit (bảng booking_deposits): Lấy đặt cọc của tenants
 * - Model Invoice (bảng invoices): Lấy hóa đơn của tenants
 * - Model Payment (bảng payments): Lấy thanh toán của tenants
 * - Model Ticket (bảng tickets): Lấy tickets của tenants
 * - Model OrganizationUser (bảng organization_users): Lấy relationship giữa user và organization
 * - Model Role (bảng roles): Lấy role "tenant"
 * - Service PlanLimitChecker: Kiểm tra subscription limits (max_users)
 * - Trait ChecksCapabilities: Kiểm tra quyền truy cập
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng users: Tạo, cập nhật, xóa tenants
 * - Bảng user_profiles: Tạo, cập nhật profile của tenants
 * - Bảng organization_users: Assign role "tenant" vào organization
 * - Bảng lease_residents: Tạo resident trong lease
 * - Logs: Ghi log lỗi khi có exception
 * 
 * LƯU Ý:
 * - Yêu cầu user phải đăng nhập (middleware auth)
 * - Yêu cầu organization phải có quyền party.access
 * - Manager có quyền view (xem tất cả tenants)
 * - Agent chỉ xem tenants có leases của assigned properties
 * - Tenant được soft delete (ghi deleted_by và deleted_at)
 * - Không cho xóa chính mình
 * - Kiểm tra subscription limit (max_users) khi tạo tenant mới
 * - Hỗ trợ HTMX cho filter, sort, pagination không reload trang
 * - Statistics (total, active, inactive, has_lease) không bị ảnh hưởng bởi filter status
 */
class TenantController extends Controller
{
    use ChecksCapabilities; // Trait kiểm tra quyền → Dùng để kiểm tra capabilities
    
    protected $limitChecker; // PlanLimitChecker service → Dùng để kiểm tra subscription limits
    
    /**
     * Constructor - Khởi tạo controller
     * 
     * MỤC ĐÍCH:
     * Inject PlanLimitChecker service để kiểm tra subscription limits khi tạo tenant mới
     * 
     * INPUT:
     * - PlanLimitChecker $limitChecker: Service kiểm tra subscription limits
     * 
     * OUTPUT:
     * - Không có (chỉ inject dependency)
     * 
     * @param \App\Services\Subscription\PlanLimitChecker $limitChecker Service kiểm tra subscription limits
     */
    public function __construct(PlanLimitChecker $limitChecker)
    {
        $this->limitChecker = $limitChecker; // Lưu PlanLimitChecker service → Dùng để kiểm tra max_users khi tạo tenant
    }
    
    /**
     * Hiển thị danh sách tenants
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách tenants với filter, search, sort, pagination và statistics, hỗ trợ HTMX/AJAX cho dynamic updates
     * 
     * INPUT:
     * - Request: search, status, has_lease, date_from, date_to, sort_by, sort_order (query parameters)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, organization_users, leases, properties
     * 
     * OUTPUT:
     * - View: staff.party.tenants.index (với tenants, stats, sortBy, sortOrder)
     * - HTML/JSON: Table HTML và stats HTML (cho HTMX/AJAX requests)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo base query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Tính statistics từ base query (trước khi apply filters)
     * 6. Áp dụng filters: search, status, has_lease, date range
     * 7. Sort và paginate
     * 8. Eager load relationships (userProfile, organizations, leasesAsTenant)
     * 9. Thêm data cho mỗi tenant: current lease, active lease status
     * 10. Xử lý HTMX/AJAX request hoặc trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy danh sách tenants với filters
     * - Bảng user_profiles: Eager load profile của tenants
     * - Bảng organization_users: Lấy relationship giữa user và organization
     * - Bảng leases: Lấy leases của tenants (cho ownership filter và statistics)
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics được tính từ base query (trước filters) để hiển thị tổng số chính xác
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - Hỗ trợ HTMX (preferred) và AJAX (backward compatibility)
     * - Statistics update via hx-swap-oob cho HTMX requests
     * - Pagination: 20 items per page
     * 
     * @param \Illuminate\Http\Request $request Request chứa query parameters (filters, search, sort, pagination)
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response View hoặc JSON/HTML response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data theo organization
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có

        // Build query for tenants
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            })
            ->with(['userProfile', 'organizations']); // Eager load userProfile và organizations → Tránh N+1 query
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                // If no assigned properties, return empty result
                $tenants = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20); // Tạo empty paginator → Agent không có assigned properties thì không xem được tenants
                $stats = [
                    'total' => 0, // Tổng số tenants → 0 vì không có assigned properties
                    'active' => 0, // Số tenants active → 0
                    'inactive' => 0, // Số tenants inactive → 0
                    'has_lease' => 0, // Số tenants có lease → 0
                ];
                return view('staff.party.tenants.index', compact('tenants', 'stats')); // Trả về view với empty data → Hiển thị trang trống
            }
        }

        // Apply filters
        if ($request->filled('search')) { // Nếu có search query
            $search = $request->get('search'); // Lấy search term → Dùng để tìm kiếm
            $query->where(function($q) use ($search) { // Tạo group where → Tìm trong nhiều fields
                $q->whereHas('userProfile', function($profileQuery) use ($search) { // Tìm trong userProfile → Tìm tenant theo tên
                    $profileQuery->where('full_name', 'like', "%{$search}%"); // Tìm trong full_name → Tìm tenant theo tên đầy đủ
                })
                  ->orWhere('email', 'like', "%{$search}%") // Hoặc tìm trong email → Tìm tenant theo email
                  ->orWhere('phone', 'like', "%{$search}%"); // Hoặc tìm trong phone → Tìm tenant theo số điện thoại
            });
        }

        if ($request->filled('status')) { // Nếu có filter status
            if ($request->get('status') === 'active') { // Nếu status = "active"
                $query->whereNull('deleted_at'); // Filter: deleted_at = null → Chỉ lấy tenants chưa bị xóa
            } elseif ($request->get('status') === 'inactive') { // Nếu status = "inactive"
                $query->whereNotNull('deleted_at'); // Filter: deleted_at != null → Chỉ lấy tenants đã bị xóa (soft deleted)
            }
        } else { // Nếu không có filter status
            // By default, only show non-deleted tenants
            $query->whereNull('deleted_at'); // Mặc định chỉ hiển thị tenants chưa bị xóa → Exclude soft-deleted tenants
        }

        if ($request->filled('has_lease')) { // Nếu có filter has_lease
            $hasLease = $request->get('has_lease') === '1'; // Lấy giá trị has_lease → true nếu = "1"
            if ($hasLease) { // Nếu has_lease = true
                $query->whereHas('leasesAsTenant', function($q) { // Filter: tenant phải có leases → Chỉ lấy tenants có active lease
                    $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants có active lease
                });
            } else { // Nếu has_lease = false
                $query->whereDoesntHave('leasesAsTenant', function($q) { // Filter: tenant không có leases → Chỉ lấy tenants không có active lease
                    $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants không có active lease
                });
            }
        }

        if ($request->filled('date_from')) { // Nếu có date_from
            $query->whereDate('created_at', '>=', $request->get('date_from')); // Filter: created_at >= date_from → Chỉ lấy tenants tạo từ ngày này trở đi
        }

        if ($request->filled('date_to')) { // Nếu có date_to
            $query->whereDate('created_at', '<=', $request->get('date_to')); // Filter: created_at <= date_to → Chỉ lấy tenants tạo đến ngày này
        }

        // Use default pagination per page (20 items per page)
        $perPage = 20; // Số items mỗi trang → Dùng để paginate

        // Calculate statistics before pagination
        // Build stats query similar to main query
        $statsQuery = User::whereHas('userRoles', function($q) { // Tạo query riêng cho statistics → Tính từ base query trước khi apply filters
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy tenants
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            })
            ->with(['userProfile', 'organizations']); // Eager load userProfile và organizations → Tránh N+1 query
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $statsQuery->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                // If no assigned properties, return empty stats
                $stats = [
                    'total' => 0, // Tổng số tenants → 0 vì không có assigned properties
                    'active' => 0, // Số tenants active → 0
                    'inactive' => 0, // Số tenants inactive → 0
                    'has_lease' => 0, // Số tenants có lease → 0
                ];
            }
        }
        
        // Apply same filters for stats (except status filter)
        if ($request->filled('search')) { // Nếu có search query
            $search = $request->get('search'); // Lấy search term → Dùng để tìm kiếm
            $statsQuery->where(function($q) use ($search) { // Tạo group where → Tìm trong nhiều fields
                $q->whereHas('userProfile', function($profileQuery) use ($search) { // Tìm trong userProfile → Tìm tenant theo tên
                    $profileQuery->where('full_name', 'like', "%{$search}%"); // Tìm trong full_name → Tìm tenant theo tên đầy đủ
                })
                  ->orWhere('email', 'like', "%{$search}%") // Hoặc tìm trong email → Tìm tenant theo email
                  ->orWhere('phone', 'like', "%{$search}%"); // Hoặc tìm trong phone → Tìm tenant theo số điện thoại
            });
        }
        
        if ($request->filled('has_lease')) { // Nếu có filter has_lease
            $hasLease = $request->get('has_lease') === '1'; // Lấy giá trị has_lease → true nếu = "1"
            if ($hasLease) { // Nếu has_lease = true
                $statsQuery->whereHas('leasesAsTenant', function($q) { // Filter: tenant phải có leases → Chỉ lấy tenants có active lease
                    $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants có active lease
                });
            } else { // Nếu has_lease = false
                $statsQuery->whereDoesntHave('leasesAsTenant', function($q) { // Filter: tenant không có leases → Chỉ lấy tenants không có active lease
                    $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants không có active lease
                });
            }
        }
        
        if ($request->filled('date_from')) { // Nếu có date_from
            $statsQuery->whereDate('created_at', '>=', $request->get('date_from')); // Filter: created_at >= date_from → Chỉ lấy tenants tạo từ ngày này trở đi
        }
        
        if ($request->filled('date_to')) { // Nếu có date_to
            $statsQuery->whereDate('created_at', '<=', $request->get('date_to')); // Filter: created_at <= date_to → Chỉ lấy tenants tạo đến ngày này
        }
        
        // Calculate stats
        if (!isset($stats)) { // Nếu chưa có stats (chưa được set ở trên)
            $stats = [
                'total' => (clone $statsQuery)->whereNull('deleted_at')->count(), // Đếm tổng số tenants chưa bị xóa → Hiển thị trong statistics card
                'active' => (clone $statsQuery)->whereNull('deleted_at')->count(), // Đếm số tenants active → Hiển thị trong statistics card (giống total vì mặc định chỉ hiển thị active)
                'inactive' => (clone $statsQuery)->whereNotNull('deleted_at')->count(), // Đếm số tenants inactive → Hiển thị trong statistics card
                'has_lease' => (clone $statsQuery)->whereNull('deleted_at') // Đếm số tenants có active lease → Hiển thị trong statistics card
                    ->whereHas('leasesAsTenant', function($q) { // Filter: tenant phải có active lease
                        $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants có active lease
                    })->count(), // Đếm số lượng → Hiển thị trong statistics card
            ];
        }

        // Sorting logic
        $allowedSortFields = ['id', 'created_at', 'full_name', 'email', 'phone']; // Danh sách fields được phép sort → Ngăn chặn SQL injection
        $sortBy = $request->get('sort_by', 'id'); // Lấy sort_by từ request → Mặc định là 'id'
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy sort_order từ request → Mặc định là 'desc'
        
        // Validate sort parameters
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu sort_by không hợp lệ
            $sortBy = 'id'; // Set mặc định là 'id' → Đảm bảo sort field hợp lệ
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) { // Nếu sort_order không hợp lệ
            $sortOrder = 'desc'; // Set mặc định là 'desc' → Đảm bảo sort order hợp lệ
        }
        
        // Apply sorting
        if ($sortBy === 'full_name') { // Nếu sort theo full_name
            // Sort by full_name from user_profiles
            $query->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id') // Join với user_profiles → Để sort theo full_name
                ->select('users.*') // Chọn tất cả columns từ users → Tránh duplicate columns
                ->orderBy('user_profiles.full_name', $sortOrder); // Sort theo full_name → Hiển thị tenants theo tên
        } else { // Nếu sort theo field khác
            $query->orderBy('users.' . $sortBy, $sortOrder); // Sort theo field trong bảng users → Hiển thị tenants theo field được chọn
        }

        // Get tenants with their related data (excluding soft deleted by default)
        $tenants = $query->paginate($perPage); // Paginate với 20 items/trang → Hiển thị danh sách tenants

        // Add additional data for each tenant
        foreach ($tenants as $tenant) { // Loop qua từng tenant → Thêm data bổ sung
            // Get current lease
            $currentLease = Lease::where('tenant_id', $tenant->id) // Tìm lease của tenant → Lấy lease hiện tại
                ->where('status', 'active') // Chỉ lấy lease active → Lấy lease đang có hiệu lực
                ->with(['unit.property']) // Eager load unit.property → Tránh N+1 query
                ->first(); // Lấy lease đầu tiên → Lấy lease hiện tại
            
            $tenant->current_lease = $currentLease; // Gán current_lease → Dùng để hiển thị trong view
            
            // Get lease history count
            $tenant->total_leases = Lease::where('tenant_id', $tenant->id)->count(); // Đếm tổng số leases → Hiển thị số lượng hợp đồng
            
            // Get total payments
            $tenant->total_payments = Payment::whereHas('invoice', function($q) use ($tenant) { // Tìm payments của tenant → Tính tổng thanh toán
                $q->whereHas('lease', function($leaseQuery) use ($tenant) { // Filter invoices theo lease của tenant → Chỉ lấy payments của tenant này
                    $leaseQuery->where('tenant_id', $tenant->id); // Filter lease theo tenant_id → Đảm bảo chỉ lấy payments của tenant này
                });
            })->where('status', 'success')->sum('amount'); // Chỉ lấy payments thành công và tính tổng → Hiển thị tổng thanh toán
            
            // Get outstanding amount
            $tenant->outstanding_amount = Invoice::whereHas('lease', function($q) use ($tenant) { // Tìm invoices của tenant → Tính số tiền còn nợ
                $q->where('tenant_id', $tenant->id); // Filter lease theo tenant_id → Chỉ lấy invoices của tenant này
            })->where('status', '!=', 'paid')->sum('total_amount'); // Chỉ lấy invoices chưa thanh toán và tính tổng → Hiển thị số tiền còn nợ
            
            // Get active tickets
            $tenant->active_tickets = Ticket::whereHas('lease', function($q) use ($tenant) { // Tìm tickets của tenant → Đếm tickets đang active
                $q->where('tenant_id', $tenant->id); // Filter lease theo tenant_id → Chỉ lấy tickets của tenant này
            })->whereIn('status', ['open', 'in_progress'])->count(); // Chỉ lấy tickets đang mở hoặc đang xử lý và đếm → Hiển thị số tickets active
        }

        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không → Xử lý HTMX khác với AJAX
        $isAjax = $request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest'); // Kiểm tra có phải AJAX request không → Backward compatibility
        
        // Prepare table HTML for both HTMX and AJAX requests
        if ($isHtmx || $isAjax) { // Nếu là HTMX hoặc AJAX request
            try {
                $tableHtml = view('staff.party.tenants.partials.table', [ // Render table partial → Chỉ render table content, không render layout
                    'tenants' => $tenants, // Danh sách tenants đã paginate → Hiển thị trong table
                    'sortBy' => $sortBy, // Sort field hiện tại → Dùng để highlight sort column
                    'sortOrder' => $sortOrder, // Sort order hiện tại → Dùng để hiển thị sort icon
                ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                
                // Format stats for response
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0, // Tổng số tenants → Hiển thị trong statistics card
                        'label' => 'Tổng cộng', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-list', // Icon → Hiển thị trong statistics card
                        'color' => 'primary', // Màu → Hiển thị trong statistics card
                        'filter' => '', // Filter value → Không filter khi click
                    ],
                    'active' => [
                        'value' => $stats['active'] ?? 0, // Số tenants active → Hiển thị trong statistics card
                        'label' => 'Hoạt động', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-check-circle', // Icon → Hiển thị trong statistics card
                        'color' => 'success', // Màu → Hiển thị trong statistics card
                        'filter' => 'active', // Filter value → Filter theo status=active khi click
                    ],
                    'has_lease' => [
                        'value' => $stats['has_lease'] ?? 0, // Số tenants có lease → Hiển thị trong statistics card
                        'label' => 'Có hợp đồng', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-file-contract', // Icon → Hiển thị trong statistics card
                        'color' => 'info', // Màu → Hiển thị trong statistics card
                        'filter' => '1', // Filter value → Filter theo has_lease=1 khi click
                        'filterKey' => 'has_lease', // Filter key → Dùng has_lease thay vì status
                    ],
                    'inactive' => [
                        'value' => $stats['inactive'] ?? 0, // Số tenants inactive → Hiển thị trong statistics card
                        'label' => 'Đã xóa', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-times-circle', // Icon → Hiển thị trong statistics card
                        'color' => 'danger', // Màu → Hiển thị trong statistics card
                        'filter' => 'inactive', // Filter value → Filter theo status=inactive khi click
                    ],
                ];
                
                // Determine current filter - check both status and has_lease
                $currentFilter = ''; // Khởi tạo current filter → Dùng để highlight card đang được filter
                if (request('has_lease') === '1') { // Nếu có filter has_lease=1
                    $currentFilter = '1'; // Set current filter = '1' → Highlight card "Có hợp đồng"
                } elseif (request('status')) { // Nếu có filter status
                    $currentFilter = request('status'); // Set current filter = status → Highlight card tương ứng
                }
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards component → Hiển thị statistics với HTMX filter
                    'stats' => $statsFormatted, // Statistics đã format → Hiển thị trong cards
                    'currentFilter' => $currentFilter, // Filter hiện tại → Highlight card đang được filter
                    'filterKey' => 'status', // Default filter key → Dùng để tạo filter query parameter
                    'onFilterClick' => 'htmx-filter', // HTMX filter handler → Filter bằng HTMX khi click card
                    'onClearClick' => 'htmx-clear', // HTMX clear handler → Clear filter bằng HTMX
                    'tableContainerId' => 'tenants-table-container', // Table container ID → Dùng để update table khi filter
                    'action' => route('staff.tenants.index'), // Action URL → Dùng để gửi HTMX request
                    'columns' => 4 // Số cột → Hiển thị 4 statistics cards
                ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                
                // Handle HTMX request - return HTML directly
                if ($isHtmx) { // Nếu là HTMX request
                    $innerTableHtml = $tableHtml; // Khởi tạo inner HTML → Dùng để extract inner content
                    
                    // Try to extract using DOMDocument for better HTML parsing
                    if (class_exists('DOMDocument')) { // Nếu có DOMDocument class
                        libxml_use_internal_errors(true); // Bật internal errors → Tránh warning khi parse HTML
                        $dom = new \DOMDocument(); // Tạo DOMDocument → Dùng để parse HTML
                        $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML → Parse table HTML
                        $xpath = new \DOMXPath($dom); // Tạo XPath → Dùng để query DOM
                        $container = $xpath->query('//div[@id="tenants-table-container"]')->item(0); // Tìm container div → Extract inner content
                        if ($container) { // Nếu tìm thấy container
                            $innerHtml = ''; // Khởi tạo inner HTML string → Dùng để lưu inner content
                            foreach ($container->childNodes as $child) { // Loop qua child nodes → Extract tất cả children
                                $innerHtml .= $dom->saveHTML($child); // Lưu HTML của child → Build inner HTML
                            }
                            $innerTableHtml = trim($innerHtml); // Trim whitespace → Clean inner HTML
                        }
                        libxml_clear_errors(); // Clear errors → Clean up
                    }
                    
                    // Fallback to regex if DOMDocument didn't work
                    if ($innerTableHtml === $tableHtml) { // Nếu DOMDocument không extract được
                        // Match the opening div with id="tenants-table-container" and extract everything inside
                        // Use greedy match to get the last closing div (the container's closing tag)
                        if (preg_match('/<div[^>]*id=["\']tenants-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) { // Regex match container div → Extract inner content
                            $innerTableHtml = trim($matches[1]); // Lấy inner content → Clean inner HTML
                        }
                    }
                    
                    // Return inner HTML with stats update via hx-swap-oob
                    $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>"; // Kết hợp inner table HTML và stats HTML với hx-swap-oob → Update cả table và stats trong một response
                    
                    return response($html) // Trả về HTML response → HTMX sẽ swap vào target
                        ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Update URL khi filter
                }
                
                // Handle AJAX request - return JSON (backward compatibility)
                return response()->json([
                    'success' => true,
                    'table_html' => $tableHtml, // Table HTML → Frontend sẽ insert vào DOM
                    'stats_html' => $statsHtml, // Stats HTML → Frontend sẽ insert vào DOM
                ]); // Trả về JSON response → Backward compatibility với AJAX
            } catch (\Exception $e) {
                Log::error('TenantController AJAX/HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
                if ($isHtmx) { // Nếu là HTMX request
                    return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500); // Trả về HTML error → HTMX sẽ hiển thị error
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.',
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error
            }
        }

        return view('staff.party.tenants.index', compact('tenants', 'stats', 'sortBy', 'sortOrder')); // Trả về view → Hiển thị trang danh sách tenants
    }

    /**
     * Hiển thị form tạo tenant mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để tạo tenant mới với các trường: full_name, phone, email, password, dob, gender, id_number, address, note
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * 
     * OUTPUT:
     * - View: staff.party.tenants.create
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.create
     * 2. Lấy organization ID từ session
     * 3. Trả về view form tạo mới
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: organization_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.create
     * - User phải thuộc một organization
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền
        
        $this->requireCapability('party.tenant.create', 'Bạn không có quyền tạo tenant.'); // Kiểm tra quyền tạo tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để validate
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        return view('staff.party.tenants.create'); // Trả về view form tạo mới → Hiển thị form
    }

    /**
     * Tạo tenant mới
     * 
     * MỤC ĐÍCH:
     * Tạo tenant mới với validation, duplicate check (phone/email loại trừ soft-deleted), subscription limit check (max_users), transaction để đảm bảo data consistency
     * 
     * INPUT:
     * - Request: full_name, phone, email, password, dob, gender, id_number, address, note
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, organization_users, roles (để check duplicate và subscription limit)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Tạo bản ghi mới trong bảng users, user_profiles, organization_users
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.create
     * 2. Lấy organization ID từ session
     * 3. Validate input (full_name, phone, email, password, dob, gender, id_number, address, note)
     * 4. Kiểm tra subscription limit (max_users)
     * 5. Transaction:
     *    - Kiểm tra duplicate (phone hoặc email, loại trừ soft-deleted)
     *    - Nếu duplicate: Rollback và trả về lỗi
     *    - Nếu không: Tạo User, UserProfile, assign role "tenant" trong organization
     * 6. Commit transaction
     * 7. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Kiểm tra duplicate (phone/email, loại trừ soft-deleted)
     * - Service PlanLimitChecker: Kiểm tra subscription limit (max_users)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Tạo bản ghi mới
     * - Bảng user_profiles: Tạo profile cho tenant
     * - Bảng organization_users: Assign role "tenant" vào organization
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.create
     * - Không cho tạo tenant trùng phone hoặc email (loại trừ soft-deleted)
     * - Kiểm tra subscription limit (max_users) trước khi tạo
     * - Sử dụng transaction để đảm bảo data consistency
     * - Password được hash trước khi lưu
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin tenant (full_name, phone, email, password, dob, gender, id_number, address, note)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON response hoặc redirect với success/error
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và lưu created_by
        
        $this->requireCapability('party.tenant.create', 'Bạn không có quyền tạo tenant.'); // Kiểm tra quyền tạo tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        
        if (!$organizationId) { // Nếu không có organization ID
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403); // Trả về JSON error → User phải thuộc organization
            }
            return redirect()->back() // Redirect về trang trước
                ->with('error', 'Bạn không thuộc tổ chức nào.'); // Với error message → Hiển thị thông báo lỗi
        }

        // Validation - cho phép trùng với soft deleted users
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255', // full_name: bắt buộc, string, tối đa 255 ký tự
            'phone' => [
                'required', // phone: bắt buộc
                'string', // string
                'max:30', // tối đa 30 ký tự
                Rule::unique('users', 'phone')->whereNull('deleted_at') // unique trong bảng users, loại trừ soft-deleted
            ],
            'email' => [
                'nullable', // email: không bắt buộc
                'email', // phải là email hợp lệ
                'max:255', // tối đa 255 ký tự
                Rule::unique('users', 'email')->whereNull('deleted_at') // unique trong bảng users, loại trừ soft-deleted
            ],
            'password' => 'required|string|min:6', // password: bắt buộc, string, tối thiểu 6 ký tự
            'dob' => 'nullable|date', // dob: không bắt buộc, phải là date hợp lệ
            'gender' => 'nullable|in:male,female,other', // gender: không bắt buộc, phải là một trong các giá trị cho phép
            'id_number' => 'nullable|string|max:50', // id_number: không bắt buộc, string, tối đa 50 ký tự
            'address' => 'nullable|string|max:255', // address: không bắt buộc, string, tối đa 255 ký tự
            'note' => 'nullable|string' // note: không bắt buộc, string
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi validation. Vui lòng kiểm tra lại thông tin.',
                    'errors' => $validator->errors() // Validation errors → Hiển thị lỗi validation
                ], 422); // Trả về JSON error → Frontend sẽ hiển thị validation errors
            }
            return redirect()->back() // Redirect về trang trước
                ->withErrors($validator) // Với validation errors → Hiển thị lỗi validation
                ->withInput(); // Giữ lại input → User không phải nhập lại
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Kiểm tra thủ công email/phone đã tồn tại và chưa bị soft delete
            if ($request->filled('email')) { // Nếu có email trong request
                $existingEmail = DB::table('users') // Query từ bảng users → Kiểm tra duplicate email
                    ->where('email', $request->email) // Filter theo email → Tìm user trùng email
                    ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa → Loại trừ soft-deleted
                    ->first(); // Lấy user đầu tiên → Nếu có thì là duplicate
                if ($existingEmail) { // Nếu có duplicate email
                    DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
                    if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                        return response()->json([
                            'success' => false,
                            'message' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác.',
                            'errors' => ['email' => ['Email này đã được sử dụng.']] // Email error → Hiển thị lỗi duplicate email
                        ], 422); // Trả về JSON error → Không cho tạo tenant duplicate
                    }
                    return redirect()->back() // Redirect về trang trước
                        ->withErrors(['email' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác.']) // Với email error → Hiển thị lỗi duplicate email
                        ->withInput(); // Giữ lại input → User không phải nhập lại
                }
            }
            
            if ($request->filled('phone')) { // Nếu có phone trong request
                $existingPhone = DB::table('users') // Query từ bảng users → Kiểm tra duplicate phone
                    ->where('phone', $request->phone) // Filter theo phone → Tìm user trùng phone
                    ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa → Loại trừ soft-deleted
                    ->first(); // Lấy user đầu tiên → Nếu có thì là duplicate
                if ($existingPhone) { // Nếu có duplicate phone
                    DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
                    if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                        return response()->json([
                            'success' => false,
                            'message' => 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác.',
                            'errors' => ['phone' => ['Số điện thoại này đã được sử dụng.']] // Phone error → Hiển thị lỗi duplicate phone
                        ], 422); // Trả về JSON error → Không cho tạo tenant duplicate
                    }
                    return redirect()->back() // Redirect về trang trước
                        ->withErrors(['phone' => 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác.']) // Với phone error → Hiển thị lỗi duplicate phone
                        ->withInput(); // Giữ lại input → User không phải nhập lại
                }
            }

            // Check subscription limit before creating
            $currentUserCount = User::whereHas('organizations', function($q) use ($organizationId) { // Đếm số users hiện tại trong organization → Dùng để kiểm tra subscription limit
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Chỉ đếm users của organization hiện tại
            })->whereNull('deleted_at')->count(); // Chỉ đếm users chưa bị xóa → Exclude soft-deleted users
            
            $organization = Organization::findOrFail($organizationId); // Lấy organization object → Dùng để kiểm tra subscription limit
            if (!$this->limitChecker->checkLimit($organization, 'max_users', $currentUserCount)) { // Kiểm tra subscription limit → Nếu đã đạt giới hạn max_users
                DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
                if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                    return response()->json([
                        'success' => false,
                        'message' => 'Đã đạt giới hạn số lượng người dùng. Vui lòng nâng cấp gói để thêm người dùng.'
                    ], 403); // Trả về JSON error → Không cho tạo tenant vì đã đạt giới hạn
                }
                return redirect()->back() // Redirect về trang trước
                    ->with('error', 'Đã đạt giới hạn số lượng người dùng. Vui lòng nâng cấp gói để thêm người dùng.') // Với error message → Hiển thị thông báo lỗi
                    ->withInput(); // Giữ lại input → User không phải nhập lại
            }

            // Create user
            $tenant = User::create([
                'phone' => $request->phone, // Số điện thoại → Lưu SĐT tenant
                'email' => $request->email, // Email → Lưu email tenant
                'password_hash' => Hash::make($request->password), // Hash password → Bảo mật password
                'status' => 1 // Status = 1 (active) → Tenant mặc định là active
            ]); // Tạo user mới → Lưu vào database

            // Create user profile with full_name
            $tenant->userProfile()->create([
                'full_name' => $request->full_name, // Tên đầy đủ → Lưu tên tenant
                'dob' => $request->dob, // Ngày sinh → Lưu ngày sinh tenant
                'gender' => $request->gender, // Giới tính → Lưu giới tính tenant
                'id_number' => $request->id_number, // Số CMND/CCCD → Lưu số CMND/CCCD tenant
                'address' => $request->address, // Địa chỉ → Lưu địa chỉ tenant
                'note' => $request->note // Ghi chú → Lưu ghi chú về tenant
            ]); // Tạo user profile → Lưu thông tin profile của tenant

            // Add tenant to organization with tenant role
            $tenantRole = \App\Models\Role::where('key_code', 'tenant')->first(); // Tìm role "tenant" → Dùng để assign role cho tenant
            if ($tenantRole) { // Nếu tìm thấy role
                $tenant->organizations()->attach($organizationId, [ // Attach tenant vào organization → Gán tenant vào organization với role
                    'role_id' => $tenantRole->id, // Role ID → Gán role "tenant"
                    'status' => 'active', // Status = "active" → Tenant mặc định là active
                    'created_at' => now(), // Created at → Lưu thời gian tạo
                    'updated_at' => now() // Updated at → Lưu thời gian cập nhật
                ]); // Attach tenant vào organization → Tạo record trong organization_users
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo khách hàng thành công!',
                    'redirect' => route('staff.tenants.show', $tenant->id) // URL chuyển đến trang chi tiết → Hiển thị tenant vừa tạo
                ]); // Trả về JSON success → Frontend sẽ redirect
            }

            return redirect()->route('staff.tenants.show', $tenant->id) // Redirect đến trang chi tiết tenant
                ->with('success', 'Tạo khách hàng thành công.'); // Với success message → Hiển thị thông báo thành công

        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error creating tenant: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo khách hàng. Vui lòng kiểm tra lại thông tin và thử lại.'
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
            }
            
            return redirect()->back() // Redirect về trang trước
                ->with('error', 'Có lỗi xảy ra khi tạo khách hàng. Vui lòng kiểm tra lại thông tin và thử lại.') // Với error message → Hiển thị thông báo lỗi
                ->withInput(); // Giữ lại input → User không phải nhập lại
        }
    }

    /**
     * Hiển thị chi tiết tenant
     * 
     * MỤC ĐÍCH:
     * Hiển thị chi tiết tenant kèm leases, bookingDeposits, invoices, tickets, và statistics, với ownership filter (Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties)
     * 
     * INPUT:
     * - Route parameter: id (tenant ID)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, leases, booking_deposits, invoices, payments, tickets, properties
     * 
     * OUTPUT:
     * - View: staff.party.tenants.show (với tenant, leases, bookingDeposits, invoices, tickets, stats)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Load tenant
     * 6. Đảm bảo userProfile tồn tại (tạo nếu chưa có)
     * 7. Load leases (với ownership filter cho agent)
     * 8. Load bookingDeposits
     * 9. Load invoices (qua leases)
     * 10. Load tickets (created_by = tenant_id)
     * 11. Tính statistics: total_leases, active_leases, total_payments, outstanding_amount, active_tickets
     * 12. Trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy chi tiết tenant
     * - Bảng user_profiles: Lấy profile của tenant (tạo nếu chưa có)
     * - Bảng leases: Lấy hợp đồng thuê của tenant
     * - Bảng booking_deposits: Lấy đặt cọc của tenant
     * - Bảng invoices: Lấy hóa đơn của tenant (qua leases)
     * - Bảng payments: Lấy thanh toán của tenant (qua invoices)
     * - Bảng tickets: Lấy tickets của tenant
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng user_profiles: Tạo profile nếu chưa có
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - UserProfile được tạo tự động nếu chưa có
     * - Leases, bookingDeposits, invoices, tickets được eager load để tránh N+1 query
     * 
     * @param int $id Tenant ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View hoặc redirect nếu có lỗi
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Get tenant
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            })
            ->with(['userProfile', 'organizations']); // Eager load userProfile và organizations → Tránh N+1 query
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                abort(403, 'Bạn không có quyền xem tenant này.'); // Dừng request và trả về lỗi 403 → Agent không có assigned properties thì không xem được
            }
        }
        
        $tenant = $query->findOrFail($id); // Tìm tenant theo ID → Throw 404 nếu không tìm thấy

        // Ensure userProfile exists
        if (!$tenant->userProfile) { // Nếu tenant chưa có userProfile
            $tenant->userProfile()->create([]); // Tạo userProfile rỗng → Đảm bảo tenant có profile
            $tenant->load('userProfile'); // Reload userProfile → Load profile vừa tạo
        }

        // Get tenant's leases - filter by assigned properties for agent
        $leaseQuery = Lease::where('tenant_id', $tenant->id); // Tạo query lấy leases của tenant → Hiển thị hợp đồng thuê
        
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter leases
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $leaseQuery->whereHas('unit', function($q) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                    $q->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem leases của properties được assign
                });
            } else { // Nếu không có assigned properties
                $leases = collect(); // Tạo empty collection → Agent không có assigned properties thì không xem được leases
            }
        }
        
        $leases = $leaseQuery->with(['unit.property', 'agent']) // Eager load unit.property và agent → Tránh N+1 query
            ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Hiển thị leases mới nhất trước
            ->get(); // Lấy tất cả leases → Hiển thị trong view

        // Get tenant's booking deposits
        $bookingDeposits = BookingDeposit::where('tenant_user_id', $tenant->id) // Tìm booking deposits của tenant → Hiển thị đặt cọc
            ->with(['unit.property', 'agent']) // Eager load unit.property và agent → Tránh N+1 query
            ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Hiển thị deposits mới nhất trước
            ->get(); // Lấy tất cả booking deposits → Hiển thị trong view

        // Get tenant's invoices
        $invoices = Invoice::whereHas('lease', function($q) use ($tenant) { // Tìm invoices của tenant → Hiển thị hóa đơn
            $q->where('tenant_id', $tenant->id); // Filter lease theo tenant_id → Chỉ lấy invoices của tenant này
        })
        ->with(['lease.unit.property']) // Eager load lease.unit.property → Tránh N+1 query
        ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Hiển thị invoices mới nhất trước
        ->get(); // Lấy tất cả invoices → Hiển thị trong view

        // Get tenant's tickets (tickets created by this tenant)
        $tickets = Ticket::where('created_by', $tenant->id) // Tìm tickets của tenant → Hiển thị tickets
            ->with(['lease.unit.property', 'assignedTo']) // Eager load lease.unit.property và assignedTo → Tránh N+1 query
            ->orderBy('created_at', 'desc') // Sắp xếp theo created_at giảm dần → Hiển thị tickets mới nhất trước
            ->get(); // Lấy tất cả tickets → Hiển thị trong view

        // Calculate statistics
        $stats = [
            'total_leases' => $leases->count(), // Tổng số leases → Hiển thị trong statistics
            'active_leases' => $leases->where('status', 'active')->count(), // Số leases active → Hiển thị trong statistics
            'total_payments' => Payment::whereHas('invoice', function($q) use ($tenant) { // Tìm payments của tenant → Tính tổng thanh toán
                $q->whereHas('lease', function($leaseQuery) use ($tenant) { // Filter invoices theo lease của tenant → Chỉ lấy payments của tenant này
                    $leaseQuery->where('tenant_id', $tenant->id); // Filter lease theo tenant_id → Đảm bảo chỉ lấy payments của tenant này
                });
            })->where('status', 'success')->sum('amount'), // Chỉ lấy payments thành công và tính tổng → Hiển thị tổng thanh toán
            'outstanding_amount' => $invoices->where('status', '!=', 'paid')->sum('total_amount'), // Tính số tiền còn nợ → Hiển thị số tiền còn nợ (từ invoices chưa thanh toán)
            'active_tickets' => $tickets->whereIn('status', ['open', 'in_progress'])->count() // Đếm tickets đang active → Hiển thị số tickets đang mở hoặc đang xử lý
        ];

        return view('staff.party.tenants.show', compact('tenant', 'leases', 'bookingDeposits', 'invoices', 'tickets', 'stats')); // Trả về view → Hiển thị trang chi tiết tenant
    }

    /**
     * Hiển thị form chỉnh sửa tenant
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để chỉnh sửa tenant với các trường: full_name, phone, email, password, dob, gender, id_number, address, note
     * 
     * INPUT:
     * - Route parameter: id (tenant ID)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles, leases, properties
     * 
     * OUTPUT:
     * - View: staff.party.tenants.edit (với tenant)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Load tenant
     * 6. Trả về view form chỉnh sửa
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy chi tiết tenant để hiển thị trong form
     * - Bảng user_profiles: Lấy profile của tenant
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * 
     * @param int $id Tenant ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $this->requireCapability('party.tenant.update', 'Bạn không có quyền cập nhật tenant.'); // Kiểm tra quyền cập nhật tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Get tenant
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            })
            ->with(['userProfile']); // Eager load userProfile → Tránh N+1 query
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                abort(403, 'Bạn không có quyền chỉnh sửa tenant này.'); // Dừng request và trả về lỗi 403 → Agent không có assigned properties thì không chỉnh sửa được
            }
        }
        
        $tenant = $query->findOrFail($id); // Tìm tenant theo ID → Throw 404 nếu không tìm thấy

        // Ensure userProfile exists
        if (!$tenant->userProfile) { // Nếu tenant chưa có userProfile
            $tenant->userProfile()->create([]); // Tạo userProfile rỗng → Đảm bảo tenant có profile
            $tenant->load('userProfile'); // Reload userProfile → Load profile vừa tạo
        }

        return view('staff.party.tenants.edit', compact('tenant')); // Trả về view form chỉnh sửa → Hiển thị form với data hiện tại
    }

    /**
     * Cập nhật tenant
     * 
     * MỤC ĐÍCH:
     * Cập nhật thông tin tenant với validation và duplicate check (phone/email loại trừ tenant hiện tại và soft-deleted), transaction để đảm bảo data consistency
     * 
     * INPUT:
     * - Request: full_name, phone, email, password (optional), dob, gender, id_number, address, note
     * - Route parameter: id (tenant ID)
     * - Session: organization_id, user_id
     * - Database: users, user_profiles (để check duplicate)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Cập nhật bản ghi trong bảng users và user_profiles
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Load tenant
     * 6. Validate input (full_name, phone, email, password, dob, gender, id_number, address, note)
     * 7. Transaction:
     *    - Kiểm tra duplicate (phone hoặc email, loại trừ tenant hiện tại và soft-deleted)
     *    - Nếu duplicate: Rollback và trả về lỗi
     *    - Nếu không: Cập nhật User và UserProfile
     * 8. Commit transaction
     * 9. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Kiểm tra duplicate (phone/email, loại trừ tenant hiện tại và soft-deleted)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật bản ghi
     * - Bảng user_profiles: Cập nhật profile
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - Không cho cập nhật tenant trùng phone hoặc email (loại trừ tenant hiện tại và soft-deleted)
     * - Password chỉ được cập nhật nếu có trong request
     * - Sử dụng transaction để đảm bảo data consistency
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin tenant (full_name, phone, email, password, dob, gender, id_number, address, note)
     * @param int $id Tenant ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON response hoặc redirect với success/error
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $this->requireCapability('party.tenant.update', 'Bạn không có quyền cập nhật tenant.'); // Kiểm tra quyền cập nhật tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        
        if (!$organizationId) { // Nếu không có organization ID
            return redirect()->back() // Redirect về trang trước
                ->with('error', 'Bạn không thuộc tổ chức nào.'); // Với error message → Hiển thị thông báo lỗi
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Get tenant
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            });
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                return redirect()->back() // Redirect về trang trước
                    ->with('error', 'Bạn không có quyền cập nhật tenant này.'); // Với error message → Agent không có assigned properties thì không cập nhật được
            }
        }
        
        $tenant = $query->findOrFail($id); // Tìm tenant theo ID → Throw 404 nếu không tìm thấy

        // Validation - cho phép trùng với soft deleted users
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255', // full_name: bắt buộc, string, tối đa 255 ký tự
            'phone' => [
                'required', // phone: bắt buộc
                'string', // string
                'max:30', // tối đa 30 ký tự
                Rule::unique('users', 'phone')->whereNull('deleted_at')->ignore($tenant->id) // unique trong bảng users, loại trừ soft-deleted và tenant hiện tại
            ],
            'email' => [
                'nullable', // email: không bắt buộc
                'email', // phải là email hợp lệ
                'max:255', // tối đa 255 ký tự
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($tenant->id) // unique trong bảng users, loại trừ soft-deleted và tenant hiện tại
            ],
            'password' => 'nullable|string|min:6', // password: không bắt buộc, string, tối thiểu 6 ký tự (chỉ update nếu có)
            'dob' => 'nullable|date', // dob: không bắt buộc, phải là date hợp lệ
            'gender' => 'nullable|in:male,female,other', // gender: không bắt buộc, phải là một trong các giá trị cho phép
            'id_number' => 'nullable|string|max:50', // id_number: không bắt buộc, string, tối đa 50 ký tự
            'address' => 'nullable|string|max:255', // address: không bắt buộc, string, tối đa 255 ký tự
            'note' => 'nullable|string' // note: không bắt buộc, string
        ]);

        if ($validator->fails()) { // Nếu validation thất bại
            return redirect()->back() // Redirect về trang trước
                ->withErrors($validator) // Với validation errors → Hiển thị lỗi validation
                ->withInput(); // Giữ lại input → User không phải nhập lại
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Update user
            $updateData = [
                'phone' => $request->phone, // Số điện thoại → Cập nhật SĐT tenant
                'email' => $request->email, // Email → Cập nhật email tenant
            ];

            if ($request->password) { // Nếu có password trong request
                $updateData['password_hash'] = Hash::make($request->password); // Hash password → Bảo mật password
            }

            $tenant->update($updateData); // Cập nhật user → Lưu thay đổi vào database

            // Update user profile with full_name
            $tenant->userProfile()->updateOrCreate(
                ['user_id' => $tenant->id], // Tìm userProfile theo user_id → Tạo nếu chưa có, update nếu đã có
                [
                    'full_name' => $request->full_name, // Tên đầy đủ → Cập nhật tên tenant
                    'dob' => $request->dob, // Ngày sinh → Cập nhật ngày sinh tenant
                    'gender' => $request->gender, // Giới tính → Cập nhật giới tính tenant
                    'id_number' => $request->id_number, // Số CMND/CCCD → Cập nhật số CMND/CCCD tenant
                    'address' => $request->address, // Địa chỉ → Cập nhật địa chỉ tenant
                    'note' => $request->note // Ghi chú → Cập nhật ghi chú về tenant
                ]
            ); // Update hoặc create user profile → Đảm bảo profile luôn tồn tại

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật khách hàng thành công!',
                    'redirect' => route('staff.tenants.show', $tenant->id) // URL chuyển đến trang chi tiết → Hiển thị tenant vừa cập nhật
                ]); // Trả về JSON success → Frontend sẽ redirect
            }

            return redirect()->route('staff.tenants.show', $tenant->id) // Redirect đến trang chi tiết tenant
                ->with('success', 'Cập nhật khách hàng thành công.'); // Với success message → Hiển thị thông báo thành công

        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error updating tenant: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            
            return redirect()->back() // Redirect về trang trước
                ->with('error', 'Có lỗi xảy ra khi cập nhật khách hàng. Vui lòng kiểm tra lại thông tin và thử lại.') // Với error message → Hiển thị thông báo lỗi
                ->withInput(); // Giữ lại input → User không phải nhập lại
        }
    }

    /**
     * Xóa tenant (soft delete)
     * 
     * MỤC ĐÍCH:
     * Xóa tenant (soft delete) với kiểm tra không cho xóa chính mình và không cho xóa tenant có active leases
     * 
     * INPUT:
     * - Route parameter: id (tenant ID)
     * - Session: organization_id, user_id
     * - Database: users, leases (để kiểm tra active leases)
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Soft delete bản ghi trong bảng users và organization_users (ghi deleted_by và deleted_at)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.delete
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Load tenant
     * 6. Kiểm tra không cho xóa chính mình
     * 7. Kiểm tra tenant có active leases không (không cho xóa nếu có)
     * 8. Soft delete tenant (trait tự động set deleted_by)
     * 9. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy chi tiết tenant
     * - Bảng leases: Kiểm tra active leases
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Soft delete (ghi deleted_by và deleted_at)
     * - Bảng organization_users: Soft delete (ghi deleted_by và deleted_at)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.delete
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - Không cho xóa chính mình
     * - Không cho xóa tenant có active leases
     * - Sử dụng soft delete (ghi deleted_by và deleted_at)
     * 
     * @param int $id Tenant ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $this->requireCapability('party.tenant.delete', 'Bạn không có quyền xóa tenant.'); // Kiểm tra quyền xóa tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403); // Trả về JSON error → User phải thuộc organization
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Get tenant
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            });
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa tenant này.'
                ], 403); // Trả về JSON error → Agent không có assigned properties thì không xóa được
            }
        }
        
        $tenant = $query->findOrFail($id); // Tìm tenant theo ID → Throw 404 nếu không tìm thấy

        try {
            // Check if trying to delete self
            if ($tenant->id === $user->id) { // Nếu đang cố xóa chính mình
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thể xóa chính mình.'
                ], 400); // Trả về JSON error → Không cho xóa chính mình
            }

            // Check if tenant has active leases
            $activeLeases = Lease::where('tenant_id', $tenant->id) // Tìm leases của tenant → Kiểm tra active leases
                ->where('status', 'active') // Chỉ lấy leases active → Kiểm tra tenant có hợp đồng đang hoạt động không
                ->count(); // Đếm số lượng → Nếu > 0 thì không cho xóa

            if ($activeLeases > 0) { // Nếu có active leases
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa khách hàng đang có hợp đồng hoạt động.'
                ], 400); // Trả về JSON error → Không cho xóa tenant có active leases
            }

            // Soft delete tenant - trait will automatically set deleted_by
            $tenant->delete(); // Soft delete tenant → Trait tự động set deleted_by và deleted_at

            return response()->json([
                'success' => true,
                'message' => 'Xóa khách hàng thành công.'
            ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo thành công

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error deleting tenant: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa khách hàng. Vui lòng thử lại sau.'
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Hiển thị trang thống kê tenants
     * 
     * MỤC ĐÍCH:
     * Hiển thị trang thống kê tenants với các metrics: total_tenants, active_tenants, tenants_with_leases, new_tenants_this_month, tenants_by_month (12 tháng gần nhất), tenants_with_active_leases, tenants_without_leases
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * - Database: users, leases, properties
     * 
     * OUTPUT:
     * - View: staff.party.tenants.statistics (với stats, tenantsByMonth, tenantsWithLeases, tenantsWithoutLeases)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo base query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Tính statistics: total_tenants, active_tenants, tenants_with_leases, new_tenants_this_month
     * 6. Tính tenants_by_month (12 tháng gần nhất) với ownership filter
     * 7. Tính tenants_with_active_leases và tenants_without_leases
     * 8. Trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy danh sách tenants để tính statistics
     * - Bảng leases: Lấy leases của tenants (cho statistics)
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - Statistics được tính từ base query (trước filters) để hiển thị tổng số chính xác
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View hoặc redirect nếu có lỗi
     */
    public function statistics()
    {
        try {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            abort(403, 'Bạn không có quyền truy cập module Party.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
                return redirect()->route('login')->with('error', 'Bạn chưa được gán vào tổ chức nào.'); // Redirect đến login → User phải thuộc organization
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Build base query
        $baseQuery = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
                })
                ->whereNull('deleted_at'); // Chỉ lấy tenants chưa bị xóa → Exclude soft-deleted tenants
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $baseQuery->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                // Return empty stats if no assigned properties
                    $stats = [
                    'total_tenants' => 0, // Tổng số tenants → 0 vì không có assigned properties
                    'active_tenants' => 0, // Số tenants active → 0
                    'tenants_with_leases' => 0, // Số tenants có leases → 0
                    'new_tenants_this_month' => 0 // Số tenants mới trong tháng → 0
                    ];
                    return view('staff.party.tenants.statistics', compact('stats')); // Trả về view với empty stats → Hiển thị trang thống kê trống
            }
        }

            // Calculate statistics
        $stats = [
            'total_tenants' => (clone $baseQuery)->count(), // Đếm tổng số tenants → Hiển thị trong statistics
            'active_tenants' => (clone $baseQuery)->where('status', 1)->count(), // Đếm số tenants active (status = 1) → Hiển thị trong statistics
            'tenants_with_leases' => (clone $baseQuery)->whereHas('leasesAsTenant')->count(), // Đếm số tenants có leases → Hiển thị trong statistics
            'new_tenants_this_month' => (clone $baseQuery) // Đếm số tenants mới trong tháng → Hiển thị trong statistics
                ->whereMonth('created_at', now()->month) // Filter theo tháng hiện tại → Chỉ lấy tenants tạo trong tháng này
                ->whereYear('created_at', now()->year) // Filter theo năm hiện tại → Chỉ lấy tenants tạo trong năm này
                ->count() // Đếm số lượng → Hiển thị trong statistics
        ];

            // Get tenants by month (last 12 months)
            $tenantsByMonth = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                    $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
                })
                ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                    $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
                })
                ->whereNull('deleted_at') // Chỉ lấy tenants chưa bị xóa → Exclude soft-deleted tenants
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count') // Format created_at thành YYYY-MM và đếm → Nhóm tenants theo tháng
                ->where('created_at', '>=', now()->subMonths(12)) // Filter: created_at >= 12 tháng trước → Chỉ lấy tenants tạo trong 12 tháng gần nhất
                ->groupBy('month') // Nhóm theo tháng → Tính số lượng tenants mỗi tháng
                ->orderBy('month') // Sắp xếp theo tháng tăng dần → Hiển thị từ tháng cũ đến mới
                ->get(); // Lấy tất cả kết quả → Dùng để vẽ chart

            // For agent, filter by assigned properties
            if (!$isManager) { // Nếu không phải manager (Agent)
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                    $tenantsByMonth = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                            $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
                        })
                        ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                            $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
                        })
                        ->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                            $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                              ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                                  $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                              });
                        })
                        ->whereNull('deleted_at') // Chỉ lấy tenants chưa bị xóa → Exclude soft-deleted tenants
                        ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count') // Format created_at thành YYYY-MM và đếm → Nhóm tenants theo tháng
                        ->where('created_at', '>=', now()->subMonths(12)) // Filter: created_at >= 12 tháng trước → Chỉ lấy tenants tạo trong 12 tháng gần nhất
                        ->groupBy('month') // Nhóm theo tháng → Tính số lượng tenants mỗi tháng
                        ->orderBy('month') // Sắp xếp theo tháng tăng dần → Hiển thị từ tháng cũ đến mới
                        ->get(); // Lấy tất cả kết quả → Dùng để vẽ chart
                } else { // Nếu không có assigned properties
                    $tenantsByMonth = collect(); // Tạo empty collection → Agent không có assigned properties thì không có data
                }
            }

            // Get tenants with active leases count
            $tenantsWithLeases = (clone $baseQuery) // Clone base query → Dùng để tính statistics riêng
                ->whereHas('leasesAsTenant', function($q) { // Filter: tenant phải có active leases → Chỉ lấy tenants có active lease
                    $q->where('status', 'active'); // Filter leases có status = "active" → Chỉ lấy tenants có active lease
                })
                ->count(); // Đếm số lượng → Hiển thị trong statistics

            // Get tenants without leases
            $tenantsWithoutLeases = (clone $baseQuery) // Clone base query → Dùng để tính statistics riêng
                ->whereDoesntHave('leasesAsTenant') // Filter: tenant không có leases → Chỉ lấy tenants không có lease
                ->count(); // Đếm số lượng → Hiển thị trong statistics

            return view('staff.party.tenants.statistics', compact(
                'stats', // Statistics → Hiển thị trong view
                'tenantsByMonth', // Tenants by month → Dùng để vẽ chart
                'tenantsWithLeases', // Tenants with active leases → Hiển thị trong statistics
                'tenantsWithoutLeases' // Tenants without leases → Hiển thị trong statistics
            )); // Trả về view → Hiển thị trang thống kê

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error in TenantController@statistics: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải thống kê. Vui lòng thử lại sau.'); // Redirect về trang trước với error message → Hiển thị thông báo lỗi
        }
    }

    /**
     * Cập nhật trạng thái tenant (active/inactive)
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái tenant (active = 1 hoặc inactive = 0) với ownership filter (Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties)
     * 
     * INPUT:
     * - Request: status (0 hoặc 1)
     * - Route parameter: id (tenant ID)
     * - Session: organization_id, user_id
     * - Database: users, leases, properties
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Cập nhật status trong bảng users
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có party.tenant.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem tenants có leases của assigned properties)
     * 5. Load tenant
     * 6. Validate input: status (0 hoặc 1)
     * 7. Cập nhật status
     * 8. Trả về JSON success với status label
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy chi tiết tenant
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật status
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants có leases của assigned properties
     * - Status: 1 = active (Hoạt động), 0 = inactive (Vô hiệu hóa)
     * 
     * @param \Illuminate\Http\Request $request Request chứa status (0 hoặc 1)
     * @param int $id Tenant ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $this->requireCapability('party.tenant.update', 'Bạn không có quyền cập nhật tenant.'); // Kiểm tra quyền cập nhật tenant → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403); // Trả về JSON error → User phải thuộc organization
        }
        
        // Check if user has party.tenant.view capability (manager has all permissions)
        $isManager = $this->checkCapability('party.tenant.view'); // Kiểm tra user có quyền view tất cả tenants không → Manager có quyền này, Agent không có
        
        // Get tenant
        $query = User::whereHas('userRoles', function($q) { // Tìm users có role "tenant" → Chỉ lấy tenants
                $q->where('key_code', 'tenant'); // Filter role có key_code = "tenant" → Chỉ lấy users có role tenant
            })
            ->whereHas('organizations', function($q) use ($organizationId) { // Filter users thuộc organization → Chỉ lấy tenants của organization hiện tại
                $q->where('organization_id', $organizationId); // Filter theo organization_id → Đảm bảo data isolation
            });
        
        // For agent, filter tenants by their leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter tenants
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('leasesAsTenant', function($q) use ($assignedPropertyIds, $organizationId) { // Filter: tenant phải có leases → Chỉ lấy tenants có leases của assigned properties
                    $q->where('organization_id', $organizationId) // Filter leases theo organization → Đảm bảo data isolation
                      ->whereHas('unit', function($unitQuery) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                          $unitQuery->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem tenants có leases của properties được assign
                      });
                });
            } else { // Nếu không có assigned properties
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật tenant này.'
                ], 403); // Trả về JSON error → Agent không có assigned properties thì không cập nhật được
            }
        }
        
        $tenant = $query->findOrFail($id); // Tìm tenant theo ID → Throw 404 nếu không tìm thấy
        
        $request->validate([
            'status' => 'required|in:0,1' // status: bắt buộc, phải là 0 hoặc 1 → 0 = inactive, 1 = active
        ]);
        
        try {
            $tenant->update([
                'status' => $request->status // Cập nhật status → Lưu trạng thái mới
            ]);
            
            $statusLabel = $request->status == 1 ? 'Hoạt động' : 'Vô hiệu hóa'; // Tạo status label → Dùng để hiển thị trong message
            
            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật trạng thái thành \"{$statusLabel}\" thành công!" // Message với status label → Hiển thị thông báo thành công
            ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo thành công
            
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error updating tenant status: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái. Vui lòng thử lại sau.'
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Thêm resident vào lease
     * 
     * MỤC ĐÍCH:
     * Thêm resident (người ở) vào lease với validation, transaction để đảm bảo data consistency. Có thể thêm user mới hoặc user đã tồn tại
     * 
     * INPUT:
     * - Request: user_type (new hoặc existing), existing_user_id (nếu existing), full_name, phone, email, dob, gender, id_number, address, note
     * - Route parameter: leaseId (lease ID)
     * - Session: organization_id, user_id
     * - Database: leases, users, user_profiles, lease_residents, properties
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Tạo bản ghi mới trong bảng users, user_profiles, lease_residents (nếu user mới) hoặc chỉ lease_residents (nếu user đã tồn tại)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.tenant.create
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: Manager (có contract.lease.view) hoặc Agent (không có)
     * 4. Tạo query với ownership filter (Agent chỉ xem leases của assigned properties)
     * 5. Load lease
     * 6. Validate input dựa trên user_type (new hoặc existing)
     * 7. Transaction:
     *    - Nếu user_type = "new": Tạo User và UserProfile mới
     *    - Nếu user_type = "existing": Lấy user đã tồn tại
     *    - Tạo LeaseResident record
     * 8. Commit transaction
     * 9. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leases: Lấy chi tiết lease
     * - Bảng users: Kiểm tra user đã tồn tại (nếu user_type = "existing")
     * - Bảng properties: Lấy assigned properties (cho ownership filter)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Tạo user mới (nếu user_type = "new")
     * - Bảng user_profiles: Tạo profile mới (nếu user_type = "new")
     * - Bảng lease_residents: Tạo resident record
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.tenant.create
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem leases của assigned properties
     * - Có thể thêm user mới hoặc user đã tồn tại
     * - Sử dụng transaction để đảm bảo data consistency
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin resident (user_type, existing_user_id, full_name, phone, email, dob, gender, id_number, address, note)
     * @param int $leaseId Lease ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON response hoặc redirect với success/error
     */
    public function addResident(Request $request, $leaseId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $this->requireCapability('party.tenant.create', 'Bạn không có quyền thêm người ở.'); // Kiểm tra quyền thêm resident → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return redirect()->back()->with('error', 'Bạn không thuộc tổ chức nào.'); // Redirect về trang trước với error message → User phải thuộc organization
        }
        
        // Check if user has contract.lease.view capability (manager has all permissions)
        $isManager = $this->checkCapability('contract.lease.view'); // Kiểm tra user có quyền view tất cả leases không → Manager có quyền này, Agent không có
        
        // Get lease
        $query = Lease::where('organization_id', $organizationId); // Tìm leases của organization → Chỉ lấy leases của organization hiện tại
        
        // For agent, only show leases of assigned properties
        if (!$isManager) { // Nếu không phải manager (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter leases
            
            if ($assignedPropertyIds->isNotEmpty()) { // Nếu có assigned properties
                $query->whereHas('unit', function($q) use ($assignedPropertyIds) { // Filter leases theo unit → Chỉ lấy leases của units thuộc assigned properties
                    $q->whereIn('property_id', $assignedPropertyIds); // Filter units theo assigned properties → Agent chỉ xem leases của properties được assign
                });
            } else { // Nếu không có assigned properties
                return redirect()->back()->with('error', 'Bạn không có quyền thêm người ở cho hợp đồng này.'); // Redirect về trang trước với error message → Agent không có assigned properties thì không thêm được
            }
        }
        
        $lease = $query->findOrFail($leaseId); // Tìm lease theo ID → Throw 404 nếu không tìm thấy

        // Validate based on user_type
        $userType = $request->input('user_type', 'new'); // Lấy user_type từ request → Mặc định là "new" (tạo user mới)
        
        if ($userType === 'existing') { // Nếu user_type = "existing" (dùng user đã tồn tại)
            $request->validate([
                'existing_user_id' => 'required|exists:users,id', // existing_user_id: bắt buộc, phải tồn tại trong bảng users
                'id_number' => 'nullable|string|max:20', // id_number: không bắt buộc, string, tối đa 20 ký tự
                'note' => 'nullable|string', // note: không bắt buộc, string
            ]);
        } else { // Nếu user_type = "new" (tạo user mới)
            $rules = [
                'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
                'phone' => 'required|string|max:20', // phone: bắt buộc, string, tối đa 20 ký tự
                'id_number' => 'nullable|string|max:20', // id_number: không bắt buộc, string, tối đa 20 ký tự
                'note' => 'nullable|string', // note: không bắt buộc, string
                'create_user_account' => 'boolean', // create_user_account: boolean (có tạo user account không)
            ];
            
            // Email is required only if creating user account
            if ($request->create_user_account) { // Nếu có tạo user account
                $rules['email'] = 'required|email|max:255|unique:users,email'; // email: bắt buộc, email hợp lệ, unique trong bảng users
            }
            
            $request->validate($rules); // Validate rules → Kiểm tra input
        }

        DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
        try {
            if ($userType === 'existing') { // Nếu user_type = "existing"
                // Use existing user
                $existingUser = User::findOrFail($request->existing_user_id); // Tìm user đã tồn tại → Lấy user để thêm vào lease
                
                // Check if user already exists in this lease as resident
                $existingResident = \App\Models\LeaseResident::where('lease_id', $lease->id) // Tìm resident trong lease → Kiểm tra user đã được thêm chưa
                    ->where('user_id', $existingUser->id) // Filter theo user_id → Chỉ lấy resident của user này
                    ->first(); // Lấy resident đầu tiên → Nếu có thì là duplicate
                
                if ($existingResident) { // Nếu user đã được thêm vào lease
                    throw new \Exception('Người dùng này đã được thêm vào hợp đồng này rồi.'); // Throw exception → Không cho thêm duplicate
                }
                
                $residentData = [
                    'lease_id' => $lease->id, // Lease ID → Gán resident vào lease này
                    'user_id' => $existingUser->id, // User ID → Gán user đã tồn tại
                    'name' => $existingUser->full_name ?? $existingUser->email ?? $existingUser->phone, // Tên → Lấy từ user profile hoặc email hoặc phone
                    'phone' => $existingUser->phone, // Số điện thoại → Lấy từ user
                    'id_number' => $request->id_number, // Số CMND/CCCD → Lấy từ request
                    'note' => $request->note, // Ghi chú → Lấy từ request
                ];
            } else { // Nếu user_type = "new"
                // Create new resident
                $residentData = [
                    'lease_id' => $lease->id, // Lease ID → Gán resident vào lease này
                    'name' => $request->name, // Tên → Lấy từ request
                    'phone' => $request->phone, // Số điện thoại → Lấy từ request
                    'id_number' => $request->id_number, // Số CMND/CCCD → Lấy từ request
                    'note' => $request->note, // Ghi chú → Lấy từ request
                ];

                // Create user account if requested
                if ($request->create_user_account) { // Nếu có tạo user account
                    // Check if email already exists
                    if (User::where('email', $request->email)->exists()) { // Kiểm tra email đã tồn tại chưa → Không cho tạo user trùng email
                        throw new \Exception('Email này đã được sử dụng. Vui lòng chọn email khác hoặc chọn từ người dùng có sẵn.'); // Throw exception → Không cho tạo user duplicate
                    }
                    
                    $newUser = User::create([
                        'phone' => $request->phone, // Số điện thoại → Lưu SĐT user mới
                        'email' => $request->email, // Email → Lưu email user mới
                        'password_hash' => Hash::make('12345678'), // Hash password mặc định → Bảo mật password (user sẽ đổi sau)
                        'status' => 1, // Status = 1 (active) → User mặc định là active
                    ]); // Tạo user mới → Lưu vào database
                    
                    // Create user profile with full_name
                    \App\Models\UserProfile::create([
                        'user_id' => $newUser->id, // User ID → Gán profile cho user mới
                        'full_name' => $request->name, // Tên đầy đủ → Lưu tên user mới
                    ]); // Tạo user profile → Lưu thông tin profile

                    // Add to organization with tenant role
                    $tenantRole = Role::where('key_code', 'tenant')->first(); // Tìm role "tenant" → Dùng để assign role cho user
                    if ($tenantRole) { // Nếu tìm thấy role
                        // Check if user already exists in this organization
                        // Một user chỉ có thể có 1 role trong 1 organization
                        $existingOrgUser = OrganizationUser::where('organization_id', $organizationId)
                            ->where('user_id', $newUser->id)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if (!$existingOrgUser) {
                            OrganizationUser::create([
                                'organization_id' => $organizationId,
                                'user_id' => $newUser->id,
                                'role_id' => $tenantRole->id,
                                'status' => 'active',
                            ]);
                        } else {
                            // Nếu đã tồn tại, cập nhật role và status
                            $existingOrgUser->update([
                                'role_id' => $tenantRole->id,
                                'status' => 'active',
                            ]);
                        }
                    }

                    $residentData['user_id'] = $newUser->id; // Gán user_id vào residentData → Liên kết resident với user mới
                }
            }

            LeaseResident::create($residentData); // Tạo resident record → Lưu resident vào lease

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi
            
            // Support JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => true,
                    'message' => 'Thêm người ở thành công.'
                ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo thành công
            }
            
            return redirect()->back() // Redirect về trang trước
                ->with('success', 'Thêm người ở thành công.'); // Với success message → Hiển thị thông báo thành công
                
        } catch (\Illuminate\Validation\ValidationException $e) { // Nếu có validation error
            DB::rollback(); // Rollback transaction → Hủy bỏ thay đổi
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors() // Validation errors → Hiển thị lỗi validation
                ], 422); // Trả về JSON error → Frontend sẽ hiển thị validation errors
            }
            
            return redirect()->back() // Redirect về trang trước
                ->withErrors($e->errors()) // Với validation errors → Hiển thị lỗi validation
                ->withInput(); // Giữ lại input → User không phải nhập lại
        } catch (\Exception $e) { // Nếu có lỗi khác
            DB::rollback(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error adding resident: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(), // Stack trace → Dùng để debug
                'lease_id' => $leaseId, // Lease ID → Dùng để debug
                'request_data' => $request->all() // Request data → Dùng để debug
            ]); // Ghi log lỗi → Dùng để debug
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là JSON/AJAX request
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Có lỗi xảy ra khi thêm người ở. Vui lòng kiểm tra lại thông tin và thử lại.'
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
            }
            
            return redirect()->back() // Redirect về trang trước
                ->with('error', 'Có lỗi xảy ra khi thêm người ở. Vui lòng kiểm tra lại thông tin và thử lại.'); // Với error message → Hiển thị thông báo lỗi
        }
    }

    /**
     * Tìm kiếm users (API endpoint cho autocomplete)
     * 
     * MỤC ĐÍCH:
     * API endpoint tìm kiếm users trong organization để thêm vào lease như resident, hỗ trợ autocomplete với search (name, email, phone), ownership filter (Manager xem tất cả, Agent chỉ xem tenants từ leases của mình)
     * 
     * INPUT:
     * - Request: search (optional, query parameter)
     * - Session: organization_id, user_id
     * - Database: users, organization_users, leases, user_profiles
     * 
     * OUTPUT:
     * - JSON: Array of users [{id, full_name, email, phone}, ...] hoặc empty array
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: party.access
     * 2. Lấy organization ID từ session
     * 3. Validate input: search (optional, string, max:255)
     * 4. Kiểm tra ownership: Manager (có party.user.view) hoặc Agent (không có)
     * 5. Tạo query với ownership filter (Agent chỉ xem tenants từ leases của mình)
     * 6. Áp dụng search filter (nếu có): tìm trong full_name, email, phone
     * 7. Limit 20 users và eager load userProfile
     * 8. Map users thành array với id, full_name, email, phone
     * 9. Trả về JSON
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy danh sách users để tìm kiếm
     * - Bảng organization_users: Filter users thuộc organization
     * - Bảng leases: Lấy tenant_ids từ leases của agent (cho ownership filter)
     * - Bảng user_profiles: Eager load profile để lấy full_name
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền party.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem tenants từ leases của mình
     * - Search là optional, nếu empty thì trả về 20 users đầu tiên
     * - Limit 20 users để tránh response quá lớn
     * - Dùng cho autocomplete khi thêm resident vào lease
     * 
     * @param \Illuminate\Http\Request $request Request chứa search (optional)
     * @return \Illuminate\Http\JsonResponse JSON response với array of users
     */
    public function searchUsers(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        $hasPartyAccess = $this->checkCapability('party.access'); // Kiểm tra quyền truy cập module Party → Dừng nếu không có quyền
        if (!$hasPartyAccess) {
            return response()->json([], 403); // Trả về empty array → Không có quyền truy cập
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([], 403); // Trả về empty array → User phải thuộc organization
        }
        
        // Search is optional - if empty, return first 20 users
        $request->validate([
            'search' => 'nullable|string|max:255', // search: không bắt buộc, string, tối đa 255 ký tự
        ]);
        
        $search = $request->input('search', ''); // Lấy search term từ request → Mặc định là empty string
        $isManager = $this->checkCapability('party.user.view'); // Kiểm tra user có quyền view tất cả users không → Manager có quyền này, Agent không có
        
        // Build query for users in organization
        $query = User::join('organization_users', 'users.id', '=', 'organization_users.user_id') // Join với organization_users → Lấy users thuộc organization
            ->where('organization_users.organization_id', $organizationId) // Filter theo organization_id → Chỉ lấy users của organization hiện tại
            ->whereNull('organization_users.deleted_at') // Chỉ lấy organization_users chưa bị xóa → Exclude soft-deleted
            ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->select('users.*') // Chọn tất cả columns từ users → Tránh duplicate columns
            ->distinct(); // Distinct để tránh duplicate → Một user có thể có nhiều organization_users records
        
        // For agent, only show tenants from their leases
        if (!$isManager) { // Nếu không phải manager (Agent)
            $tenantIds = \App\Models\Lease::where('agent_id', $user->id) // Tìm leases của agent → Lấy tenants từ leases của agent
                ->where('organization_id', $organizationId) // Filter theo organization → Chỉ lấy leases của organization hiện tại
                ->pluck('tenant_id') // Lấy danh sách tenant_ids → Dùng để filter users
                ->unique() // Loại bỏ duplicate → Một tenant có thể có nhiều leases
                ->toArray(); // Convert thành array → Dùng để filter users
            
            if (!empty($tenantIds)) { // Nếu có tenant IDs
                $query->whereIn('users.id', $tenantIds); // Filter users theo tenant IDs → Agent chỉ xem tenants từ leases của mình
            } else { // Nếu không có tenant IDs
                return response()->json([]); // Trả về empty array → Agent không có leases thì không có tenants
            }
        }
        
        // Search by name, email, or phone (if search term provided)
        if (!empty($search)) { // Nếu có search term
            $query->where(function($q) use ($search) { // Tạo group where → Tìm trong nhiều fields
                $q->whereHas('userProfile', function($profileQuery) use ($search) { // Tìm trong userProfile → Tìm user theo tên
                    $profileQuery->where('full_name', 'like', "%{$search}%"); // Tìm trong full_name → Tìm user theo tên đầy đủ
                })
                ->orWhere('users.email', 'like', "%{$search}%") // Hoặc tìm trong email → Tìm user theo email
                ->orWhere('users.phone', 'like', "%{$search}%"); // Hoặc tìm trong phone → Tìm user theo số điện thoại
            });
        }
        
        $users = $query->with('userProfile') // Eager load userProfile → Tránh N+1 query
            ->limit(20) // Limit 20 users → Tránh response quá lớn
            ->get() // Lấy tất cả kết quả → Dùng để map
            ->map(function($user) { // Map users thành array → Format data cho frontend
                return [
                    'id' => $user->id, // User ID → Dùng để select user
                    'full_name' => $user->full_name, // Tên đầy đủ → Hiển thị trong autocomplete
                    'email' => $user->email, // Email → Hiển thị trong autocomplete
                    'phone' => $user->phone, // Số điện thoại → Hiển thị trong autocomplete
                ];
            }); // Map users → Format data cho JSON response
        
        return response()->json($users); // Trả về JSON → Frontend sẽ hiển thị trong autocomplete
    }
}
