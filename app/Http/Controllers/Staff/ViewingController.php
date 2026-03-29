<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\Viewing;
use App\Models\Unit;
use App\Models\Property;
use App\Models\User;
use App\Models\Lead;
use App\Services\ViewingNotificationService;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller: ViewingController
 * 
 * MỤC ĐÍCH:
 * Quản lý viewings (lịch hẹn xem phòng) trong module CRM - cho phép tạo, xem, sửa, xóa và quản lý lịch hẹn xem phòng, theo dõi trạng thái (requested, confirmed, done, no_show, cancelled), hỗ trợ cả lead và tenant
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. index(): Hiển thị danh sách viewings với filter, search, sort, pagination và statistics
 * 2. create(): Hiển thị form tạo viewing mới với pre-fill từ property_id/unit_id
 * 3. store(): Tạo viewing mới với validation, tự động tạo Lead nếu cần, kiểm tra unit status
 * 4. show(): Hiển thị chi tiết viewing kèm property, unit, agent, customer
 * 5. edit(): Hiển thị form chỉnh sửa viewing
 * 6. update(): Cập nhật thông tin viewing với validation và kiểm tra unit status
 * 7. destroy(): Xóa viewing (soft delete)
 * 8. calendar(): Hiển thị lịch viewings (calendar view)
 * 9. today(): Hiển thị viewings hôm nay
 * 10. statistics(): Hiển thị thống kê viewings theo status, agent, property
 * 11. confirm(): Xác nhận viewing (status = confirmed)
 * 12. cancel(): Hủy viewing (status = cancelled)
 * 13. markDone(): Đánh dấu viewing hoàn thành (status = done)
 * 
 * ENDPOINTS:
 * - GET /staff/viewings: Hiển thị danh sách viewings
 * - GET /staff/viewings/create: Hiển thị form tạo mới
 * - POST /staff/viewings: Tạo viewing mới
 * - GET /staff/viewings/{id}: Hiển thị chi tiết viewing
 * - GET /staff/viewings/{id}/edit: Hiển thị form chỉnh sửa
 * - PUT /staff/viewings/{id}: Cập nhật viewing
 * - DELETE /staff/viewings/{id}: Xóa viewing
 * - GET /staff/viewings/calendar: Hiển thị lịch viewings
 * - GET /staff/viewings/today: Hiển thị viewings hôm nay
 * - GET /staff/viewings/statistics: Hiển thị thống kê
 * - POST /staff/viewings/{id}/confirm: Xác nhận viewing
 * - POST /staff/viewings/{id}/cancel: Hủy viewing
 * - POST /staff/viewings/{id}/mark-done: Đánh dấu hoàn thành
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model Viewing (bảng viewings): Lấy danh sách và chi tiết viewings
 * - Model Property (bảng properties): Lấy thông tin properties (cho ownership filter)
 * - Model Unit (bảng units): Lấy thông tin units
 * - Model Lead (bảng leads): Lấy thông tin leads (khách hàng tiềm năng)
 * - Model User (bảng users): Lấy thông tin tenants và agents
 * - Trait ChecksCapabilities: Kiểm tra quyền truy cập
 * - Trait FiltersByOwnership: Lọc dữ liệu theo ownership (view_all/view_own)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng viewings: Tạo, cập nhật, xóa viewings
 * - Bảng leads: Tạo lead mới nếu customer_type = 'lead' và không có lead_id
 * - Logs: Ghi log lỗi khi có exception
 * 
 * LƯU Ý:
 * - Yêu cầu user phải đăng nhập (middleware auth)
 * - Yêu cầu organization phải có quyền crm.access
 * - Manager có quyền view_all (xem tất cả viewings)
 * - Agent có quyền view_own (chỉ xem viewings của assigned properties hoặc agent_id = user.id)
 * - Viewing được soft delete (ghi deleted_by và deleted_at)
 * - Chỉ cho phép tạo/cập nhật viewing cho unit có status = 'available'
 * - Agent tự động được gán agent_id (không cho phép sửa), Manager có thể gán cho agent khác
 * - Hỗ trợ HTMX cho filter, sort, pagination không reload trang
 * - Statistics (total, requested, confirmed, done, no_show, cancelled) không bị ảnh hưởng bởi filter status
 * - Customer có thể là Lead (khách hàng tiềm năng) hoặc Tenant (khách thuê)
 */
class ViewingController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership; // Trait kiểm tra quyền và filter theo ownership → Dùng để kiểm tra capabilities và lọc data
    
    /**
     * Hiển thị danh sách viewings
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách viewings với filter, search, sort, pagination và statistics, hỗ trợ HTMX/AJAX cho dynamic updates
     * 
     * INPUT:
     * - Request: search, status, property_id, agent_id, lead_id, sort_by, sort_order (query parameters)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, user_profiles
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.index (với viewings, properties, agents, stats, sortBy, sortOrder)
     * - HTML/JSON: Table HTML và stats HTML (cho HTMX/AJAX requests)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo base query với JOINs và ownership filter (nếu agent: filter theo assigned properties hoặc agent_id)
     * 5. Tính statistics từ base query (trước khi apply filters)
     * 6. Áp dụng filters: status, property_id, agent_id, lead_id, search
     * 7. Sort và paginate
     * 8. Eager load relationships
     * 9. Lấy properties và agents cho filter dropdown
     * 10. Xử lý HTMX/AJAX request hoặc trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy danh sách viewings với filters
     * - Bảng properties: Lấy assigned properties (cho ownership filter) và properties cho filter dropdown
     * - Bảng units: JOIN để lấy unit_code
     * - Bảng users: JOIN để lấy agent_name
     * - Bảng user_profiles: JOIN để lấy agent full_name
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Statistics được tính từ base query (trước filters) để hiển thị tổng số chính xác
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem viewings của assigned properties hoặc agent_id = user.id
     * - Hỗ trợ HTMX (preferred) và AJAX (backward compatibility)
     * - Statistics update via hx-swap-oob cho HTMX requests
     * - Sử dụng JOINs để tối ưu query performance
     * 
     * @param \Illuminate\Http\Request $request Request chứa query parameters (filters, search, sort, pagination)
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response View hoặc JSON/HTML response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check if user has crm.access capability
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data theo organization
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Optimized query with JOINs and proper index order
        $query = Viewing::select([
            'viewings.*',
            'properties.name as property_name', // Tên property → Dùng để hiển thị trong table
            'units.code as unit_code', // Mã phòng → Dùng để hiển thị trong table
            'agent_profiles.full_name as agent_name' // Tên agent → Dùng để hiển thị trong table
        ])
        ->join('properties', 'viewings.property_id', '=', 'properties.id') // JOIN properties → Lấy tên property và filter theo organization
        ->leftJoin('units', 'viewings.unit_id', '=', 'units.id') // LEFT JOIN units → Lấy mã phòng (có thể null)
        ->leftJoin('users as agent_users', 'viewings.agent_id', '=', 'agent_users.id') // LEFT JOIN users (agent) → Lấy thông tin agent
        ->leftJoin('user_profiles as agent_profiles', 'agent_users.id', '=', 'agent_profiles.user_id') // LEFT JOIN user_profiles → Lấy full_name của agent
        ->where('properties.organization_id', $organizationId); // Filter theo organization → Sử dụng index idx_properties_deleted_at_org nếu có
        
        // Tự động filter theo ownership nếu agent chỉ có view_own
        // Viewing filter theo property (qua assigned properties) hoặc agent_id
        if ($this->shouldFilterByOwnership('crm.appointment')) { // Nếu cần filter theo ownership (Agent)
            // Filter theo agent_id trực tiếp hoặc qua assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có assigned properties
                // Nếu không có property được gán, chỉ xem viewings của chính mình
                $query->where('viewings.agent_id', $user->id); // Filter: agent_id = user.id → Agent chỉ xem viewings của chính mình
            } else { // Nếu có assigned properties
                // Xem viewings của properties được gán hoặc của chính mình
                $query->where(function($q) use ($assignedPropertyIds, $user) { // Tạo group where → Filter theo property hoặc agent
                    $q->whereIn('viewings.property_id', $assignedPropertyIds) // Filter: property_id trong assigned properties → Agent xem viewings của assigned properties
                      ->orWhere('viewings.agent_id', $user->id); // Hoặc agent_id = user.id → Agent xem viewings của chính mình
                });
            }
        }
        
        // Apply filters in optimal order: organization_id -> deleted_at -> status
        $query->whereNull('viewings.deleted_at') // Chỉ lấy viewings chưa bị xóa → Sử dụng index idx_viewings_deleted_at_status_schedule nếu có
              ->whereNull('properties.deleted_at'); // Chỉ lấy properties chưa bị xóa → Sử dụng index idx_properties_deleted_at_org nếu có

        // Calculate statistics FIRST from base query (before any filters)
        // Query directly from Viewing model to ensure accurate statistics
        $statsQuery = Viewing::whereHas('property', function($q) use ($organizationId) { // Tạo query riêng cho statistics → Tính từ base query trước khi apply filters
            $q->where('organization_id', $organizationId) // Filter property theo organization → Đảm bảo statistics chính xác
              ->whereNull('deleted_at'); // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
        })
        ->whereNull('deleted_at'); // Chỉ lấy viewings chưa bị xóa → Exclude soft-deleted viewings
        
        // For agent, only count viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter statistics
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có assigned properties
                $statsQuery->whereRaw('1 = 0'); // No results → Không trả về kết quả nào (Agent không có assigned properties thì không có statistics)
            } else { // Nếu có assigned properties
                $statsQuery->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ đếm viewings của assigned properties
            }
        }
        
        // Count by status using database aggregation for accuracy
        $stats = [
            'total' => (int) (clone $statsQuery)->count(), // Đếm tổng số viewings → Hiển thị trong statistics card
            'requested' => (int) (clone $statsQuery)->where('status', 'requested')->count(), // Đếm viewings chờ xác nhận → Hiển thị trong statistics card
            'confirmed' => (int) (clone $statsQuery)->where('status', 'confirmed')->count(), // Đếm viewings đã xác nhận → Hiển thị trong statistics card
            'done' => (int) (clone $statsQuery)->where('status', 'done')->count(), // Đếm viewings hoàn thành → Hiển thị trong statistics card
            'no_show' => (int) (clone $statsQuery)->where('status', 'no_show')->count(), // Đếm viewings không đến → Hiển thị trong statistics card
            'cancelled' => (int) (clone $statsQuery)->where('status', 'cancelled')->count(), // Đếm viewings đã hủy → Hiển thị trong statistics card
        ];

        // Filter by status - uses idx_viewings_deleted_at_status_schedule
        if ($request->filled('status')) { // Nếu có filter status
            $query->where('viewings.status', $request->status); // Filter theo status → Chỉ lấy viewings có status này
        }

        // Filter by property
        if ($request->filled('property_id')) { // Nếu có filter property_id
            $query->where('viewings.property_id', $request->property_id); // Filter theo property → Chỉ lấy viewings của property này
        }

        // Filter by agent
        if ($request->filled('agent_id')) { // Nếu có filter agent_id
            $query->where('viewings.agent_id', $request->agent_id); // Filter theo agent → Chỉ lấy viewings của agent này
        }

        // Filter by lead
        if ($request->filled('lead_id')) { // Nếu có filter lead_id
            $query->where('viewings.lead_id', $request->lead_id); // Filter theo lead → Chỉ lấy viewings của lead này
        }

        // Search - optimized with JOIN
        if ($request->filled('search')) { // Nếu có search query
            $search = $request->search; // Lấy search term → Dùng để tìm kiếm
            $query->where(function($q) use ($search) { // Tạo group where → Tìm trong nhiều fields
                $q->where('viewings.note', 'like', "%{$search}%") // Tìm trong ghi chú → Tìm viewing theo ghi chú
                  ->orWhere('viewings.lead_name', 'like', "%{$search}%") // Hoặc tìm trong tên lead → Tìm viewing theo tên lead
                  ->orWhere('viewings.lead_phone', 'like', "%{$search}%") // Hoặc tìm trong SĐT lead → Tìm viewing theo SĐT lead
                  ->orWhere('viewings.lead_email', 'like', "%{$search}%") // Hoặc tìm trong email lead → Tìm viewing theo email lead
                  ->orWhere('properties.name', 'like', "%{$search}%") // Hoặc tìm trong tên property → Tìm viewing theo tên property
                  ->orWhere('agent_profiles.full_name', 'like', "%{$search}%"); // Hoặc tìm trong tên agent → Tìm viewing theo tên agent
            });
        }

        // Get viewings with sorting
        $sortBy = $request->get('sort_by', 'schedule_at'); // Lấy sort_by từ request → Mặc định là 'schedule_at'
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy sort_order từ request → Mặc định là 'desc'
        
        // Validate sort fields
        $allowedSortFields = ['id', 'created_at', 'schedule_at', 'status']; // Danh sách fields được phép sort → Ngăn chặn SQL injection
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu sort_by không hợp lệ
            $sortBy = 'schedule_at'; // Set mặc định là 'schedule_at' → Đảm bảo sort field hợp lệ
        }
        
        $allowedSortOrders = ['asc', 'desc']; // Danh sách sort orders được phép → Ngăn chặn SQL injection
        if (!in_array($sortOrder, $allowedSortOrders)) { // Nếu sort_order không hợp lệ
            $sortOrder = 'desc'; // Set mặc định là 'desc' → Đảm bảo sort order hợp lệ
        }
        
        $viewings = $query->orderBy("viewings.{$sortBy}", $sortOrder)->paginate(20); // Sort, paginate 20 items/trang → Hiển thị danh sách viewings
        
        // Eager load relationships for display
        $viewings->load(['property.location', 'property.location2025', 'unit', 'agent', 'organization', 'tenant', 'lead']); // Eager load relationships → Tránh N+1 query

        // Get properties for filter dropdown - optimized
        if ($canViewAll) { // Nếu có quyền view_all (Manager)
            $properties = Property::where('organization_id', $organizationId) // Query từ bảng properties → Sử dụng index idx_properties_deleted_at_org nếu có
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Sử dụng index idx_properties_deleted_at_org nếu có
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo filter dropdown
        } else { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter properties
            $properties = Property::whereIn('id', $assignedPropertyIds) // Query từ bảng properties → Chỉ lấy assigned properties
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo filter dropdown
        }

        // Get agents for filter dropdown - use capability-based check
        $agents = \App\Services\CapabilityService::getUsersWithModuleAccess('crm', $organizationId) // Lấy users có quyền truy cập module CRM → Dùng để tạo filter dropdown
            ->sortBy(function($user) { // Sắp xếp theo full_name → Hiển thị trong dropdown
                return $user->userProfile->full_name ?? ''; // Lấy full_name từ userProfile → Sắp xếp theo tên
            })
            ->values(); // Reset keys → Dùng để hiển thị trong dropdown

        // Check if HTMX request (preferred method - no JavaScript needed)
        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra có phải HTMX request không → Xử lý HTMX khác với AJAX
        $isAjax = $request->ajax() || ($request->has('ajax') && $request->header('X-Requested-With') === 'XMLHttpRequest'); // Kiểm tra có phải AJAX request không → Backward compatibility
        
        // Prepare table HTML for both HTMX and AJAX requests
        if ($isHtmx || $isAjax) { // Nếu là HTMX hoặc AJAX request
            try {
                // Render only table content for AJAX/HTMX
                $tableHtml = view('staff.crm.viewings.partials.table', [ // Render table partial → Chỉ render table content, không render layout
                    'viewings' => $viewings, // Danh sách viewings đã paginate → Hiển thị trong table
                    'sortBy' => $sortBy, // Sort field hiện tại → Dùng để highlight sort column
                    'sortOrder' => $sortOrder, // Sort order hiện tại → Dùng để hiển thị sort icon
                ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                
                // Format stats for response
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0, // Tổng số viewings → Hiển thị trong statistics card
                        'label' => 'Tổng cộng', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-list', // Icon → Hiển thị trong statistics card
                        'color' => 'primary', // Màu → Hiển thị trong statistics card
                        'filter' => '', // Filter value → Không filter khi click
                    ],
                    'requested' => [
                        'value' => $stats['requested'] ?? 0, // Số viewings chờ xác nhận → Hiển thị trong statistics card
                        'label' => 'Chờ xác nhận', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-clock', // Icon → Hiển thị trong statistics card
                        'color' => 'warning', // Màu → Hiển thị trong statistics card
                        'filter' => 'requested', // Filter value → Filter theo status=requested khi click
                    ],
                    'confirmed' => [
                        'value' => $stats['confirmed'] ?? 0, // Số viewings đã xác nhận → Hiển thị trong statistics card
                        'label' => 'Đã xác nhận', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-check', // Icon → Hiển thị trong statistics card
                        'color' => 'info', // Màu → Hiển thị trong statistics card
                        'filter' => 'confirmed', // Filter value → Filter theo status=confirmed khi click
                    ],
                    'done' => [
                        'value' => $stats['done'] ?? 0, // Số viewings hoàn thành → Hiển thị trong statistics card
                        'label' => 'Hoàn thành', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-check-circle', // Icon → Hiển thị trong statistics card
                        'color' => 'success', // Màu → Hiển thị trong statistics card
                        'filter' => 'done', // Filter value → Filter theo status=done khi click
                    ],
                    'no_show' => [
                        'value' => $stats['no_show'] ?? 0, // Số viewings không đến → Hiển thị trong statistics card
                        'label' => 'Không đến', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-user-times', // Icon → Hiển thị trong statistics card
                        'color' => 'danger', // Màu → Hiển thị trong statistics card
                        'filter' => 'no_show', // Filter value → Filter theo status=no_show khi click
                    ],
                    'cancelled' => [
                        'value' => $stats['cancelled'] ?? 0, // Số viewings đã hủy → Hiển thị trong statistics card
                        'label' => 'Đã hủy', // Label hiển thị → Hiển thị trong statistics card
                        'icon' => 'fa-times', // Icon → Hiển thị trong statistics card
                        'color' => 'secondary', // Màu → Hiển thị trong statistics card
                        'filter' => 'cancelled', // Filter value → Filter theo status=cancelled khi click
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards component → Hiển thị statistics với HTMX filter
                    'stats' => $statsFormatted, // Statistics đã format → Hiển thị trong cards
                    'currentFilter' => request('status', ''), // Filter hiện tại → Highlight card đang được filter
                    'filterKey' => 'status', // Filter key → Dùng để tạo filter query parameter
                    'onFilterClick' => 'htmx-filter', // HTMX filter handler → Filter bằng HTMX khi click card
                    'onClearClick' => 'htmx-clear', // HTMX clear handler → Clear filter bằng HTMX
                    'tableContainerId' => 'viewings-table-container', // Table container ID → Dùng để update table khi filter
                    'action' => route('staff.viewings.index'), // Action URL → Dùng để gửi HTMX request
                    'columns' => 6 // Số cột → Hiển thị 6 statistics cards
                ])->render(); // Render thành HTML string → Trả về cho HTMX/AJAX
                
                // Handle HTMX request - return HTML directly
                if ($isHtmx) { // Nếu là HTMX request
                    // Extract inner HTML from tableHtml (remove the outer wrapper div if exists)
                    $innerTableHtml = $tableHtml; // Khởi tạo inner HTML → Dùng để extract inner content
                    
                    // Try to extract using DOMDocument for better HTML parsing
                    if (class_exists('DOMDocument')) { // Nếu có DOMDocument class
                        libxml_use_internal_errors(true); // Bật internal errors → Tránh warning khi parse HTML
                        $dom = new \DOMDocument(); // Tạo DOMDocument → Dùng để parse HTML
                        $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML → Parse table HTML
                        $xpath = new \DOMXPath($dom); // Tạo XPath → Dùng để query DOM
                        $container = $xpath->query('//div[@id="viewings-table-container"]')->item(0); // Tìm container div → Extract inner content
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
                        // Match the opening div with id="viewings-table-container" and extract everything inside
                        // Use greedy match to get the last closing div (the container's closing tag)
                        if (preg_match('/<div[^>]*id=["\']viewings-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) { // Regex match container div → Extract inner content
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
            } catch (\Exception $e) { // Nếu có lỗi
                Log::error('ViewingController AJAX/HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
                $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                if ($isHtmx) { // Nếu là HTMX request
                    return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500); // Trả về HTML error → HTMX sẽ hiển thị error
                }
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage,
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error
            }
        }

        return view('staff.crm.viewings.index', compact('viewings', 'properties', 'agents', 'stats', 'sortBy', 'sortOrder')); // Trả về view → Hiển thị trang danh sách viewings
    }

    /**
     * Hiển thị form tạo viewing mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để tạo viewing mới với các trường: customer_type (lead/tenant), property_id, unit_id, schedule_at, status, note, agent_id, hỗ trợ pre-fill từ property_id/unit_id trong request
     * 
     * INPUT:
     * - Request: property_id, unit_id (query parameters - optional, để pre-fill form)
     * - Session: organization_id, user_id
     * - Database: properties, leads, tenants, agents
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.create
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.appointment.create
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Lấy properties (theo ownership: Manager xem tất cả, Agent chỉ xem assigned properties)
     * 5. Lấy leads (status = 'new', từ organization và default organization)
     * 6. Lấy tenants (users với role 'tenant', từ organization và default organization)
     * 7. Lấy agents (users không phải tenant/landlord, từ organization)
     * 8. Kiểm tra current user có trong agents list không (để auto-fill agent_id)
     * 9. Nếu có property_id/unit_id trong request: Validate và pre-fill form, kiểm tra unit status
     * 10. Trả về view form
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy danh sách properties (theo ownership)
     * - Bảng leads: Lấy danh sách leads (status = 'new')
     * - Bảng users: Lấy danh sách tenants và agents
     * - Bảng user_profiles: Lấy full_name của users
     * - Bảng organization_users: Lấy users thuộc organization
     * - Bảng roles: Lấy role 'tenant'
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.appointment.create
     * - Ownership filter: Manager xem tất cả properties, Agent chỉ xem assigned properties
     * - Hỗ trợ pre-fill từ property_id/unit_id (khi tạo viewing từ unit show page)
     * - Kiểm tra unit status: Không cho tạo viewing cho unit đã thuê (occupied hoặc is_rented)
     * - Leads và tenants bao gồm cả default organization (id = 3) để tăng tính linh hoạt
     * 
     * @param \Illuminate\Http\Request $request Request có thể chứa property_id, unit_id (query parameters)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check capability
        $this->requireCapability('crm.appointment.create', 'Bạn không có quyền tạo lịch hẹn.'); // Kiểm tra quyền tạo viewing → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get properties based on capability
        if ($canViewAll) { // Nếu có quyền view_all (Manager)
            $properties = Property::where('organization_id', $organizationId) // Query từ bảng properties → Lấy tất cả properties của organization
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown
        } else { // Nếu không có quyền view_all (Agent)
            // Agent only sees assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter properties
            $properties = Property::whereIn('id', $assignedPropertyIds) // Query từ bảng properties → Chỉ lấy assigned properties
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown
        }

        // Get leads for dropdown (include both user's org and default org)
        $leads = Lead::whereIn('organization_id', [$organizationId, 3]) // Query từ bảng leads → Bao gồm cả default organization (id = 3) để tăng tính linh hoạt
            ->where('status', 'new') // Chỉ lấy leads mới → Hiển thị leads chưa được xử lý
            ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
            ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown

        // Get tenants (users with tenant role) for dropdown (include both user's org and default org)
        $tenants = User::select('users.*') // Select users → Lấy thông tin users
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id') // JOIN user_profiles → Lấy full_name
            ->whereHas('organizationUsers', function($query) use ($organizationId) { // Filter: users phải thuộc organization → Chỉ lấy tenants của organization
                $query->whereIn('organization_id', [$organizationId, 3]) // Bao gồm cả default organization (id = 3) → Tăng tính linh hoạt
                      ->whereHas('role', function($roleQuery) { // Filter: role phải là 'tenant' → Chỉ lấy users có role tenant
                          $roleQuery->where('key_code', 'tenant'); // Filter role theo key_code → Lấy role tenant
                      });
            })
            ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->with('userProfile') // Eager load userProfile → Tránh N+1 query
            ->orderBy('user_profiles.full_name') // Sắp xếp theo full_name → Hiển thị trong dropdown
            ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown

        // Get all users for assignment - include managers and agents, exclude tenant and landlord only
        $allUsers = User::with('userProfile') // Eager load userProfile → Tránh N+1 query
            ->whereHas('organizationUsers', function($q) use ($organizationId) { // Filter: users phải thuộc organization → Chỉ lấy users của organization
                $q->where('organization_id', $organizationId) // Filter theo organization → Đảm bảo users thuộc organization
                  ->where('status', 'active'); // Chỉ lấy users active → Hiển thị users đang hoạt động
            })
            ->whereDoesntHave('userRoles', function($q) { // Filter: loại trừ users có role tenant/landlord → Chỉ lấy managers và agents
                $q->whereIn('key_code', ['tenant', 'landlord']); // Loại trừ tenant và landlord → Chỉ lấy managers và agents
            })
            ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown
        
        // Get manager role IDs
        $managerIds = DB::table('organization_users') // Query từ bảng organization_users → Lấy manager IDs
            ->join('roles', 'organization_users.role_id', '=', 'roles.id') // JOIN roles → Lấy role key_code
            ->where('organization_users.organization_id', $organizationId) // Filter theo organization → Chỉ lấy managers của organization
            ->where('organization_users.status', 'active') // Chỉ lấy users active → Hiển thị managers đang hoạt động
            ->where('roles.key_code', 'manager') // Filter role theo key_code → Chỉ lấy role manager
            ->pluck('organization_users.user_id') // Lấy user_id → Dùng để identify managers
            ->toArray(); // Convert sang array → Dùng để filter
        
        // Combine managers and agents (both excluding tenants and landlords)
        $agents = $allUsers->sortBy(function($user) { // Sắp xếp theo full_name → Hiển thị trong dropdown
                return $user->userProfile->full_name ?? $user->full_name ?? ''; // Lấy full_name từ userProfile → Sắp xếp theo tên
            })
            ->values(); // Reset keys → Dùng để hiển thị trong dropdown

        // Check if current user is in the list (for auto-fill)
        $isCurrentUserInList = $agents->contains('id', $user->id); // Kiểm tra current user có trong agents list không → Dùng để auto-fill agent_id
        $defaultAgentId = $isCurrentUserInList ? $user->id : null; // Set default agent_id → Auto-fill agent_id nếu current user là agent

        // Get property_id and unit_id from request (if coming from unit show page)
        $propertyId = $request->input('property_id'); // Lấy property_id từ request → Dùng để pre-fill form
        $unitId = $request->input('unit_id'); // Lấy unit_id từ request → Dùng để pre-fill form
        $selectedProperty = null; // Khởi tạo selectedProperty → Dùng để pre-fill form
        $selectedUnit = null; // Khởi tạo selectedUnit → Dùng để pre-fill form

        // If property_id provided, get property and validate
        if ($propertyId) { // Nếu có property_id trong request
            $selectedProperty = Property::where('organization_id', $organizationId) // Query từ bảng properties → Validate property thuộc organization
                ->where('id', $propertyId) // Filter theo property_id → Lấy property cụ thể
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->first(); // Lấy property đầu tiên → Dùng để pre-fill form
            
            // If unit_id provided, get unit and validate
            if ($unitId && $selectedProperty) { // Nếu có unit_id và property hợp lệ
                $selectedUnit = Unit::where('property_id', $propertyId) // Query từ bảng units → Validate unit thuộc property
                    ->where('id', $unitId) // Filter theo unit_id → Lấy unit cụ thể
                    ->whereNull('deleted_at') // Chỉ lấy units chưa bị xóa → Exclude soft-deleted units
                    ->first(); // Lấy unit đầu tiên → Dùng để pre-fill form
                
                // Check if unit is rented - show warning
                if ($selectedUnit && ($selectedUnit->status === 'occupied' || $selectedUnit->is_rented)) { // Nếu unit đã thuê
                    return redirect()->route('staff.units.show', $unitId) // Redirect về trang chi tiết unit → Hiển thị warning
                        ->with('warning', 'Không thể tạo lịch hẹn cho phòng đang ở trạng thái đã thuê.'); // Thêm warning message → Thông báo không thể tạo viewing
                }
            }
        }

        return view('staff.crm.viewings.create', compact('properties', 'leads', 'tenants', 'agents', 'user', 'defaultAgentId', 'propertyId', 'unitId', 'selectedProperty', 'selectedUnit')); // Trả về view form → Hiển thị form với data đã load
    }

    /**
     * Tạo viewing mới
     * 
     * MỤC ĐÍCH:
     * Tạo viewing mới với validation, tự động tạo Lead nếu cần (khi customer_type = 'lead' và không có lead_id), kiểm tra unit status (chỉ cho phép available), transaction để đảm bảo data consistency
     * 
     * INPUT:
     * - Request: customer_type, property_id, unit_id, schedule_at, status, note, agent_id, lead_id (hoặc lead_name, lead_phone, lead_email), tenant_id
     * - Session: organization_id, user_id
     * - Database: properties, units, leads, users
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Tạo bản ghi mới trong bảng viewings (và bảng leads nếu cần)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.appointment.create
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Kiểm tra Agent chỉ tạo cho assigned properties (nếu không có quyền view_all)
     * 5. Validate input: customer_type, property_id, unit_id, schedule_at, status, note, agent_id
     * 6. Validate lead-specific fields (nếu customer_type = 'lead'): lead_id hoặc lead_name, lead_phone, lead_email
     * 7. Validate tenant-specific fields (nếu customer_type = 'tenant'): tenant_id
     * 8. Transaction:
     *    - Validate property thuộc organization
     *    - Validate unit thuộc property
     *    - Kiểm tra unit status (chỉ cho phép available)
     *    - Tự động gán agent_id cho Agent
     *    - Tạo Lead mới nếu cần (customer_type = 'lead' và không có lead_id)
     *    - Tạo Viewing với customer data (lead hoặc tenant)
     *    - Cập nhật status timestamps nếu status = 'confirmed'
     * 9. Commit transaction
     * 10. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Kiểm tra property thuộc organization và assigned properties
     * - Bảng units: Kiểm tra unit thuộc property và status = 'available'
     * - Bảng leads: Lấy thông tin lead nếu có lead_id
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Tạo bản ghi mới
     * - Bảng leads: Tạo lead mới nếu customer_type = 'lead' và không có lead_id
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.appointment.create
     * - Ownership check: Agent chỉ tạo cho assigned properties
     * - Chỉ cho phép tạo viewing cho unit có status = 'available'
     * - Agent tự động được gán agent_id (không cho phép sửa), Manager có thể gán cho agent khác
     * - Tự động tạo Lead nếu customer_type = 'lead' và không có lead_id (source = 'viewing_booking')
     * - Sử dụng transaction để đảm bảo data consistency
     * - schedule_at phải sau thời điểm hiện tại
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin viewing (customer_type, property_id, unit_id, schedule_at, status, note, agent_id, lead_id/lead_name/lead_phone/lead_email, tenant_id)
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu created_by và check quyền
        
        // Check capability
        $this->requireCapability('crm.appointment.create', 'Bạn không có quyền tạo lịch hẹn.'); // Kiểm tra quyền tạo viewing → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // For agent, check if property belongs to assigned properties
        if (!$canViewAll && $request->filled('property_id')) { // Nếu không có quyền view_all (Agent) và có property_id
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để validate
            $property = Property::where('id', $request->property_id) // Query từ bảng properties → Kiểm tra property thuộc assigned properties
                ->whereIn('id', $assignedPropertyIds) // Filter: property_id trong assigned properties → Agent chỉ tạo cho assigned properties
                ->first(); // Lấy property đầu tiên → Nếu không có thì Agent không có quyền
            
            if (!$property) { // Nếu không tìm thấy property
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo lịch hẹn cho bất động sản này.'
                ], 403); // Trả về JSON error → Agent không có quyền tạo cho property này
            }
        }

        $validated = $request->validate([
            'customer_type' => 'required|in:lead,tenant', // customer_type: bắt buộc, phải là 'lead' hoặc 'tenant'
            'property_id' => 'required|exists:properties,id', // property_id: bắt buộc, phải tồn tại trong bảng properties
            'unit_id' => 'required|exists:units,id', // unit_id: bắt buộc, phải tồn tại trong bảng units
            'schedule_at' => 'required|date|after:now', // schedule_at: bắt buộc, phải là date, phải sau thời điểm hiện tại
            'status' => 'required|in:requested,confirmed', // status: bắt buộc, phải là 'requested' hoặc 'confirmed'
            'note' => 'nullable|string|max:1000', // note: không bắt buộc, string, tối đa 1000 ký tự
            'agent_id' => 'nullable|exists:users,id', // agent_id: không bắt buộc, phải tồn tại trong bảng users
        ], [
            'customer_type.required' => 'Vui lòng chọn loại khách hàng.',
            'customer_type.in' => 'Loại khách hàng không hợp lệ.',
            'property_id.required' => 'Vui lòng chọn bất động sản.',
            'property_id.exists' => 'Bất động sản không tồn tại.',
            'unit_id.required' => 'Vui lòng chọn phòng.',
            'unit_id.exists' => 'Phòng không tồn tại.',
            'schedule_at.required' => 'Vui lòng chọn thời gian hẹn.',
            'schedule_at.date' => 'Thời gian hẹn không hợp lệ.',
            'schedule_at.after' => 'Thời gian hẹn phải sau thời điểm hiện tại.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'agent_id.exists' => 'Agent không tồn tại.',
        ]);

        // Validate lead-specific fields
        if ($request->customer_type === 'lead') { // Nếu customer_type = 'lead'
            // If lead_id is provided, only validate lead_id exists
            // Otherwise, validate lead_name and lead_phone are required
            if ($request->filled('lead_id')) { // Nếu có lead_id
                $request->validate([
                    'lead_id' => 'required|exists:leads,id', // lead_id: bắt buộc, phải tồn tại trong bảng leads
                ], [
                    'lead_id.required' => 'Vui lòng chọn lead.',
                    'lead_id.exists' => 'Lead không tồn tại.',
                ]);
            } else { // Nếu không có lead_id
                $request->validate([
                    'lead_name' => 'required|string|max:255', // lead_name: bắt buộc, string, tối đa 255 ký tự
                    'lead_phone' => 'required|string|max:20', // lead_phone: bắt buộc, string, tối đa 20 ký tự
                    'lead_email' => 'nullable|email|max:255', // lead_email: không bắt buộc, phải là email hợp lệ, tối đa 255 ký tự
                ], [
                    'lead_name.required' => 'Vui lòng nhập tên khách hàng.',
                    'lead_name.max' => 'Tên khách hàng không được vượt quá 255 ký tự.',
                    'lead_phone.required' => 'Vui lòng nhập số điện thoại.',
                    'lead_phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
                    'lead_email.email' => 'Email không hợp lệ.',
                    'lead_email.max' => 'Email không được vượt quá 255 ký tự.',
                ]);
            }
        }

        // Validate tenant-specific fields
        if ($request->customer_type === 'tenant') { // Nếu customer_type = 'tenant'
            $request->validate([
                'tenant_id' => 'required|exists:users,id', // tenant_id: bắt buộc, phải tồn tại trong bảng users
            ], [
                'tenant_id.required' => 'Vui lòng chọn khách thuê.',
                'tenant_id.exists' => 'Khách thuê không tồn tại.',
            ]);
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Get property to verify organization
            $property = Property::where('id', $request->property_id) // Query từ bảng properties → Validate property thuộc organization
                ->where('organization_id', $organizationId) // Filter theo organization → Đảm bảo property thuộc organization
                ->firstOrFail(); // Lấy property hoặc throw 404 → Validate property tồn tại và thuộc organization

            // Get unit to verify it belongs to property
            $unit = Unit::where('id', $request->unit_id) // Query từ bảng units → Validate unit thuộc property
                ->where('property_id', $request->property_id) // Filter theo property_id → Đảm bảo unit thuộc property
                ->firstOrFail(); // Lấy unit hoặc throw 404 → Validate unit tồn tại và thuộc property

            // Validate unit status - only allow available units
            if ($unit->status !== 'available') { // Nếu unit không có status = 'available'
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể tạo lịch hẹn cho phòng có trạng thái "Có sẵn" (available). Phòng này hiện đang ở trạng thái: ' . $this->getUnitStatusLabel($unit->status) . '.'
                ], 422); // Trả về lỗi validation → Không cho tạo viewing cho unit không available
            }

            // Tự động gán agent_id cho agent (không cho phép sửa)
            // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
            $this->enforceAgentId($validated, 'agent_id'); // Tự động gán agent_id → Agent tự động gán cho chính mình, Manager có thể gán cho agent khác

            // Prepare viewing data
            $viewingData = [
                'organization_id' => $organizationId, // Organization ID → Gán viewing vào organization
                'property_id' => $validated['property_id'], // Property ID → Gán viewing vào property
                'unit_id' => $validated['unit_id'], // Unit ID → Gán viewing vào unit
                'schedule_at' => $validated['schedule_at'], // Thời gian hẹn → Lưu thời gian hẹn xem phòng
                'status' => $validated['status'], // Trạng thái → Lưu trạng thái viewing (requested hoặc confirmed)
                'note' => $validated['note'], // Ghi chú → Lưu ghi chú về viewing
                'agent_id' => $validated['agent_id'], // Agent ID → Gán viewing cho agent
            ];

            // Handle customer data based on type
            if ($validated['customer_type'] === 'lead') { // Nếu customer_type = 'lead'
                // Create new lead if not exists
                $lead = null; // Khởi tạo lead → Dùng để lưu lead object
                if ($request->filled('lead_id')) { // Nếu có lead_id
                    // Use existing lead - get info from lead
                    $lead = Lead::where('id', $request->lead_id) // Query từ bảng leads → Lấy lead hiện có
                        ->where('organization_id', $organizationId) // Filter theo organization → Đảm bảo lead thuộc organization
                        ->firstOrFail(); // Lấy lead hoặc throw 404 → Validate lead tồn tại và thuộc organization
                    
                    // Use lead information
                    $viewingData['lead_name'] = $lead->name; // Tên lead → Lưu tên lead vào viewing
                    $viewingData['lead_phone'] = $lead->phone; // SĐT lead → Lưu SĐT lead vào viewing
                    $viewingData['lead_email'] = $lead->email; // Email lead → Lưu email lead vào viewing
                    $viewingData['lead_id'] = $lead->id; // Lead ID → Liên kết viewing với lead
                } else { // Nếu không có lead_id
                    // Create new lead
                    $lead = Lead::create([
                        'name' => $request->lead_name, // Tên lead → Lưu tên lead
                        'phone' => $request->lead_phone, // SĐT lead → Lưu SĐT lead
                        'email' => $request->lead_email, // Email lead → Lưu email lead
                        'organization_id' => $organizationId, // Organization ID → Gán lead vào organization
                        'status' => 'new', // Trạng thái mặc định → Lead mới có status = 'new'
                        'source' => 'viewing_booking', // Nguồn lead → Đánh dấu lead được tạo từ viewing booking
                    ]); // Tạo lead mới → Lưu vào database
                    
                    $viewingData['lead_name'] = $request->lead_name; // Tên lead → Lưu tên lead vào viewing
                    $viewingData['lead_phone'] = $request->lead_phone; // SĐT lead → Lưu SĐT lead vào viewing
                    $viewingData['lead_email'] = $request->lead_email; // Email lead → Lưu email lead vào viewing
                    $viewingData['lead_id'] = $lead->id; // Lead ID → Liên kết viewing với lead vừa tạo
                }
            } else { // Nếu customer_type = 'tenant'
                $viewingData['tenant_id'] = $request->tenant_id; // Tenant ID → Liên kết viewing với tenant
            }

            // Create viewing
            $viewing = Viewing::create($viewingData); // Tạo viewing mới → Lưu vào database

            // Update status timestamps
            if ($request->status === 'confirmed') { // Nếu status = 'confirmed'
                $viewing->update([
                    'confirmed_at' => now(), // Thời gian xác nhận → Lưu thời gian xác nhận viewing
                    'confirmed_by' => $user->id // User xác nhận → Lưu user đã xác nhận viewing
                ]); // Cập nhật timestamps → Track thời gian và user xác nhận
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            // Gửi email thông báo cho lead khi có lịch hẹn mới (chỉ khi customer_type = 'lead')
            if ($validated['customer_type'] === 'lead') {
                try {
                    $viewingNotificationService = app(ViewingNotificationService::class);
                    $emailResult = $viewingNotificationService->sendViewingCreatedEmail($viewing);
                    
                    if (!$emailResult['success']) {
                        // Log warning nhưng không fail request
                        Log::warning('Failed to send viewing created email', [
                            'viewing_id' => $viewing->id,
                            'error' => $emailResult['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error nhưng không fail request
                    Log::error('Error sending viewing created email', [
                        'viewing_id' => $viewing->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Lịch hẹn đã được tạo thành công!',
                    'redirect' => route('staff.viewings.show', $viewing->id) // URL chuyển đến trang chi tiết → Hiển thị viewing vừa tạo
            ]); // Trả về JSON success → Frontend sẽ redirect

        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error creating viewing: ' . $e->getMessage(), [
                'exception' => $e, // Exception object → Dùng để debug
                'request_data' => $request->all(), // Request data → Dùng để debug
                'trace' => $e->getTraceAsString() // Stack trace → Dùng để debug
            ]); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'tạo lịch hẹn');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Hiển thị chi tiết viewing
     * 
     * MỤC ĐÍCH:
     * Hiển thị chi tiết viewing kèm property, unit, agent, customer (lead hoặc tenant), hỗ trợ ownership filter (Manager xem tất cả, Agent chỉ xem của assigned properties)
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, leads
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.show (với viewing)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 5. Load viewing với eager load relationships
     * 6. Trả về view
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Bảng properties: Eager load property với location
     * - Bảng units: Eager load unit
     * - Bảng users: Eager load agent, tenant
     * - Bảng leads: Eager load lead
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem viewings của assigned properties
     * - Eager load relationships để tránh N+1 query
     * 
     * @param int $id Viewing ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View hoặc redirect nếu có lỗi
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check if user has crm.access capability
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem viewings của assigned properties
        }
        
        $viewing = $query->with(['property.location', 'property.location2025', 'unit', 'agent', 'organization', 'tenant', 'lead']) // Eager load relationships → Tránh N+1 query
            ->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        return view('staff.crm.viewings.show', compact('viewing')); // Trả về view → Hiển thị trang chi tiết viewing
    }

    /**
     * Hiển thị form chỉnh sửa viewing
     * 
     * MỤC ĐÍCH:
     * Hiển thị form để chỉnh sửa viewing với các trường: customer_type (lead/tenant), property_id, unit_id, schedule_at, status, note, agent_id, hỗ trợ ownership filter (Manager xem tất cả, Agent chỉ xem/sửa của assigned properties)
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, leads, tenants, agents
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.edit (với viewing, properties, leads, tenants, agents)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.appointment.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 5. Load viewing với eager load relationships
     * 6. Lấy properties (theo ownership)
     * 7. Lấy leads, tenants, agents
     * 8. Trả về view form
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing để hiển thị trong form
     * - Bảng properties: Lấy danh sách properties (theo ownership)
     * - Bảng leads: Lấy danh sách leads (status = 'new')
     * - Bảng users: Lấy danh sách tenants và agents
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ hiển thị form)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.appointment.update
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem/sửa viewings của assigned properties
     * - Leads và tenants bao gồm cả default organization (id = 3) để tăng tính linh hoạt
     * 
     * @param int $id Viewing ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form hoặc redirect nếu có lỗi
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check capability
        $this->requireCapability('crm.appointment.update', 'Bạn không có quyền cập nhật lịch hẹn.'); // Kiểm tra quyền cập nhật viewing → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem/sửa viewings của assigned properties
        }
        
        $viewing = $query->with(['property', 'unit', 'agent', 'organization', 'tenant', 'lead']) // Eager load relationships → Tránh N+1 query
            ->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        // Get properties based on capability
        if ($canViewAll) { // Nếu có quyền view_all (Manager)
            $properties = Property::where('organization_id', $organizationId) // Query từ bảng properties → Lấy tất cả properties của organization
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown
        } else { // Nếu không có quyền view_all (Agent)
            // Agent only sees assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter properties
            $properties = Property::whereIn('id', $assignedPropertyIds) // Query từ bảng properties → Chỉ lấy assigned properties
                ->where('status', 1) // Chỉ lấy properties active → Hiển thị properties đang hoạt động
                ->whereNull('deleted_at') // Chỉ lấy properties chưa bị xóa → Exclude soft-deleted properties
                ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
                ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown
        }

        // Get leads for dropdown (include both user's org and default org)
        $leads = Lead::whereIn('organization_id', [$organizationId, 3]) // Query từ bảng leads → Bao gồm cả default organization (id = 3) để tăng tính linh hoạt
            ->where('status', 'new') // Chỉ lấy leads mới → Hiển thị leads chưa được xử lý
            ->orderBy('name') // Sắp xếp theo tên → Hiển thị trong dropdown
            ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown

        // Get tenants (users with tenant role) for dropdown (include both user's org and default org)
        $tenants = User::select('users.*') // Select users → Lấy thông tin users
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id') // JOIN user_profiles → Lấy full_name
            ->whereHas('organizationUsers', function($query) use ($organizationId) { // Filter: users phải thuộc organization → Chỉ lấy tenants của organization
                $query->whereIn('organization_id', [$organizationId, 3]) // Bao gồm cả default organization (id = 3) → Tăng tính linh hoạt
                      ->whereHas('role', function($roleQuery) { // Filter: role phải là 'tenant' → Chỉ lấy users có role tenant
                          $roleQuery->where('key_code', 'tenant'); // Filter role theo key_code → Lấy role tenant
                      });
            })
            ->whereNull('users.deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->with('userProfile') // Eager load userProfile → Tránh N+1 query
            ->orderBy('user_profiles.full_name') // Sắp xếp theo full_name → Hiển thị trong dropdown
            ->get(); // Lấy tất cả kết quả → Dùng để tạo dropdown

        // Get all users for assignment - exclude tenant and landlord only
        $agents = User::with('userProfile') // Eager load userProfile → Tránh N+1 query
            ->whereHas('organizationUsers', function($q) use ($organizationId) { // Filter: users phải thuộc organization → Chỉ lấy users của organization
                $q->where('organization_id', $organizationId) // Filter theo organization → Đảm bảo users thuộc organization
                  ->where('status', 'active'); // Chỉ lấy users active → Hiển thị users đang hoạt động
            })
            ->whereDoesntHave('userRoles', function($q) { // Filter: loại trừ users có role tenant/landlord → Chỉ lấy managers và agents
                $q->whereIn('key_code', ['tenant', 'landlord']); // Loại trừ tenant và landlord → Chỉ lấy managers và agents
            })
            ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->get() // Lấy tất cả kết quả → Dùng để tạo dropdown
            ->sortBy(function($user) { // Sắp xếp theo full_name → Hiển thị trong dropdown
                return $user->userProfile->full_name ?? $user->full_name ?? ''; // Lấy full_name từ userProfile → Sắp xếp theo tên
            })
            ->values(); // Reset keys → Dùng để hiển thị trong dropdown

        return view('staff.crm.viewings.edit', compact('viewing', 'properties', 'leads', 'tenants', 'agents')); // Trả về view form → Hiển thị form với data hiện tại
    }

    /**
     * Cập nhật viewing
     * 
     * MỤC ĐÍCH:
     * Cập nhật thông tin viewing với validation, kiểm tra unit status (chỉ cho phép available nếu unit đang được thay đổi), transaction để đảm bảo data consistency, cập nhật status timestamps (confirmed_at, cancelled_at, done_at)
     * 
     * INPUT:
     * - Request: customer_type, property_id, unit_id, schedule_at, status, note, agent_id, lead_name/lead_phone/lead_email/lead_id, tenant_id, result_note
     * - Route parameter: id (viewing ID)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, leads
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "...", redirect: "..."} hoặc {success: false, message: "...", errors: {...}}
     * - Database: Cập nhật bản ghi trong bảng viewings
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.appointment.update
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 5. Load viewing
     * 6. Kiểm tra Agent chỉ cập nhật cho assigned properties (nếu property_id đang được thay đổi)
     * 7. Validate input: customer_type, property_id, unit_id, schedule_at, status, note, agent_id
     * 8. Validate lead-specific fields (nếu customer_type = 'lead'): lead_name, lead_phone, lead_email, lead_id
     * 9. Validate tenant-specific fields (nếu customer_type = 'tenant'): tenant_id
     * 10. Transaction:
     *     - Validate property thuộc organization
     *     - Validate unit thuộc property
     *     - Kiểm tra unit status (chỉ cho phép available nếu unit đang được thay đổi)
     *     - Tự động gán agent_id cho Agent
     *     - Cập nhật Viewing với customer data (lead hoặc tenant)
     *     - Cập nhật status timestamps nếu status thay đổi (confirmed_at, cancelled_at, done_at)
     * 11. Commit transaction
     * 12. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Bảng properties: Kiểm tra property thuộc organization và assigned properties
     * - Bảng units: Kiểm tra unit thuộc property và status = 'available' (nếu unit đang được thay đổi)
     * - Bảng users: Lấy thông tin tenant (nếu customer_type = 'tenant')
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Cập nhật bản ghi
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.appointment.update
     * - Ownership check: Agent chỉ cập nhật của assigned properties
     * - Chỉ kiểm tra unit status = 'available' nếu unit đang được thay đổi (unit_id mới khác unit_id cũ)
     * - Agent tự động được gán agent_id (không cho phép sửa), Manager có thể gán cho agent khác
     * - Cập nhật status timestamps chỉ khi status thay đổi (confirmed_at, cancelled_at, done_at)
     * - Sử dụng transaction để đảm bảo data consistency
     * 
     * @param \Illuminate\Http\Request $request Request chứa thông tin viewing (customer_type, property_id, unit_id, schedule_at, status, note, agent_id, lead_name/lead_phone/lead_email/lead_id, tenant_id, result_note)
     * @param int $id Viewing ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu updated_by và check quyền
        
        // Check capability
        $this->requireCapability('crm.appointment.update', 'Bạn không có quyền cập nhật lịch hẹn.'); // Kiểm tra quyền cập nhật viewing → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter và validate
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem/sửa viewings của assigned properties
        }
        
        $viewing = $query->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy
        
        // For agent, check if new property belongs to assigned properties
        if (!$canViewAll && $request->filled('property_id')) { // Nếu không có quyền view_all (Agent) và có property_id trong request
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để validate
            $property = Property::where('id', $request->property_id) // Query từ bảng properties → Kiểm tra property thuộc assigned properties
                ->whereIn('id', $assignedPropertyIds) // Filter: property_id trong assigned properties → Agent chỉ cập nhật cho assigned properties
                ->first(); // Lấy property đầu tiên → Nếu không có thì Agent không có quyền
            
            if (!$property) { // Nếu không tìm thấy property
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật lịch hẹn cho bất động sản này.'
                ], 403); // Trả về JSON error → Agent không có quyền cập nhật cho property này
            }
        }

        $validated = $request->validate([
            'customer_type' => 'required|in:lead,tenant', // customer_type: bắt buộc, phải là 'lead' hoặc 'tenant'
            'property_id' => 'required|exists:properties,id', // property_id: bắt buộc, phải tồn tại trong bảng properties
            'unit_id' => 'required|exists:units,id', // unit_id: bắt buộc, phải tồn tại trong bảng units
            'schedule_at' => 'required|date', // schedule_at: bắt buộc, phải là date
            'status' => 'required|in:requested,confirmed,done,no_show,cancelled', // status: bắt buộc, phải là một trong các giá trị cho phép
            'note' => 'nullable|string|max:1000', // note: không bắt buộc, string, tối đa 1000 ký tự
            'agent_id' => 'nullable|exists:users,id', // agent_id: không bắt buộc, phải tồn tại trong bảng users
        ], [
            'customer_type.required' => 'Vui lòng chọn loại khách hàng.',
            'customer_type.in' => 'Loại khách hàng không hợp lệ.',
            'property_id.required' => 'Vui lòng chọn bất động sản.',
            'property_id.exists' => 'Bất động sản không tồn tại.',
            'unit_id.required' => 'Vui lòng chọn phòng.',
            'unit_id.exists' => 'Phòng không tồn tại.',
            'schedule_at.required' => 'Vui lòng chọn thời gian hẹn.',
            'schedule_at.date' => 'Thời gian hẹn không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'agent_id.exists' => 'Agent không tồn tại.',
        ]);

        // Validate lead-specific fields
        if ($request->customer_type === 'lead') { // Nếu customer_type = 'lead'
            $request->validate([
                'lead_name' => 'required|string|max:255', // lead_name: bắt buộc, string, tối đa 255 ký tự
                'lead_phone' => 'required|string|max:20', // lead_phone: bắt buộc, string, tối đa 20 ký tự
                'lead_email' => 'nullable|email|max:255', // lead_email: không bắt buộc, phải là email hợp lệ, tối đa 255 ký tự
                'lead_id' => 'nullable|exists:leads,id', // lead_id: không bắt buộc, phải tồn tại trong bảng leads
            ], [
                'lead_name.required' => 'Vui lòng nhập tên khách hàng.',
                'lead_name.max' => 'Tên khách hàng không được vượt quá 255 ký tự.',
                'lead_phone.required' => 'Vui lòng nhập số điện thoại.',
                'lead_phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
                'lead_email.email' => 'Email không hợp lệ.',
                'lead_email.max' => 'Email không được vượt quá 255 ký tự.',
                'lead_id.exists' => 'Lead không tồn tại.',
            ]);
        }

        // Validate tenant-specific fields
        if ($request->customer_type === 'tenant') { // Nếu customer_type = 'tenant'
            $request->validate([
                'tenant_id' => 'required|exists:users,id', // tenant_id: bắt buộc, phải tồn tại trong bảng users
            ], [
                'tenant_id.required' => 'Vui lòng chọn khách thuê.',
                'tenant_id.exists' => 'Khách thuê không tồn tại.',
            ]);
        }

        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency

            // Get property to verify organization
            $property = Property::where('id', $request->property_id) // Query từ bảng properties → Validate property thuộc organization
                ->where('organization_id', $organizationId) // Filter theo organization → Đảm bảo property thuộc organization
                ->firstOrFail(); // Lấy property hoặc throw 404 → Validate property tồn tại và thuộc organization

            // Get unit to verify it belongs to property
            $unit = Unit::where('id', $request->unit_id) // Query từ bảng units → Validate unit thuộc property
                ->where('property_id', $request->property_id) // Filter theo property_id → Đảm bảo unit thuộc property
                ->firstOrFail(); // Lấy unit hoặc throw 404 → Validate unit tồn tại và thuộc property

            // Validate unit status - only allow available units (if unit is being changed)
            if ($viewing->unit_id != $request->unit_id && $unit->status !== 'available') { // Nếu unit đang được thay đổi và unit mới không có status = 'available'
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể cập nhật lịch hẹn cho phòng có trạng thái "Có sẵn" (available). Phòng này hiện đang ở trạng thái: ' . $this->getUnitStatusLabel($unit->status) . '.'
                ], 422); // Trả về lỗi validation → Không cho cập nhật viewing cho unit không available
            }

            // Tự động gán agent_id cho agent (không cho phép sửa)
            // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
            $this->enforceAgentId($validated, 'agent_id'); // Tự động gán agent_id → Agent tự động gán cho chính mình, Manager có thể gán cho agent khác

            // Lưu original values để so sánh và gửi email
            $originalStatus = $viewing->status;
            $originalScheduleAt = $viewing->schedule_at;
            $originalPropertyId = $viewing->property_id;
            $originalUnitId = $viewing->unit_id;
            $originalAgentId = $viewing->agent_id;
            $originalNote = $viewing->note;

            // Prepare viewing data
            $viewingData = [
                'property_id' => $validated['property_id'], // Property ID → Cập nhật property
                'unit_id' => $validated['unit_id'], // Unit ID → Cập nhật unit
                'schedule_at' => $validated['schedule_at'], // Thời gian hẹn → Cập nhật thời gian hẹn
                'status' => $validated['status'], // Trạng thái → Cập nhật trạng thái viewing
                'note' => $validated['note'], // Ghi chú → Cập nhật ghi chú
                'agent_id' => $validated['agent_id'], // Agent ID → Cập nhật agent
            ];

            // Handle customer data based on type
            if ($validated['customer_type'] === 'lead') { // Nếu customer_type = 'lead'
                $viewingData['customer_type'] = 'lead'; // Customer type → Đánh dấu customer là lead
                $viewingData['customer_name'] = $request->lead_name; // Tên lead → Cập nhật tên lead
                $viewingData['lead_phone'] = $request->lead_phone; // SĐT lead → Cập nhật SĐT lead
                $viewingData['lead_email'] = $request->lead_email; // Email lead → Cập nhật email lead
                $viewingData['lead_id'] = $request->lead_id; // Lead ID → Liên kết viewing với lead
                $viewingData['tenant_id'] = null; // Tenant ID → Clear tenant_id nếu chuyển từ tenant sang lead
            } else { // Nếu customer_type = 'tenant'
                $viewingData['customer_type'] = 'tenant'; // Customer type → Đánh dấu customer là tenant
                $viewingData['tenant_id'] = $request->tenant_id; // Tenant ID → Liên kết viewing với tenant
                $viewingData['lead_id'] = null; // Lead ID → Clear lead_id nếu chuyển từ lead sang tenant
                $viewingData['lead_phone'] = null; // Lead phone → Clear lead_phone
                $viewingData['lead_email'] = null; // Lead email → Clear lead_email
                
                // Get tenant info
                $tenant = User::with('userProfile')->findOrFail($request->tenant_id); // Query từ bảng users → Lấy thông tin tenant
                $viewingData['customer_name'] = $tenant->userProfile->full_name ?? ''; // Tên tenant → Lấy full_name từ userProfile
            }

            // Update viewing
            $viewing->update($viewingData); // Cập nhật viewing → Lưu thay đổi vào database

            // Update status timestamps
            if ($request->status === 'confirmed' && $viewing->status !== 'confirmed') { // Nếu status mới = 'confirmed' và status cũ khác 'confirmed'
                $viewing->update([
                    'confirmed_at' => now(), // Thời gian xác nhận → Lưu thời gian xác nhận viewing
                    'confirmed_by' => $user->id // User xác nhận → Lưu user đã xác nhận viewing
                ]); // Cập nhật timestamps → Track thời gian và user xác nhận
            } elseif ($request->status === 'cancelled' && $viewing->status !== 'cancelled') { // Nếu status mới = 'cancelled' và status cũ khác 'cancelled'
                $viewing->update([
                    'cancelled_at' => now(), // Thời gian hủy → Lưu thời gian hủy viewing
                    'cancelled_by' => $user->id // User hủy → Lưu user đã hủy viewing
                ]); // Cập nhật timestamps → Track thời gian và user hủy
            } elseif ($request->status === 'done' && $viewing->status !== 'done') { // Nếu status mới = 'done' và status cũ khác 'done'
                $viewing->update([
                    'done_at' => now(), // Thời gian hoàn thành → Lưu thời gian hoàn thành viewing
                    'done_by' => $user->id // User hoàn thành → Lưu user đã hoàn thành viewing
                ]); // Cập nhật timestamps → Track thời gian và user hoàn thành
            }

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            // Gửi email thông báo cho lead khi có thay đổi (chỉ khi customer_type = 'lead')
            if ($validated['customer_type'] === 'lead') {
                try {
                    // Refresh viewing để lấy dữ liệu mới nhất
                    $viewing->refresh();
                    
                    // Tạo mảng changes để gửi email
                    $changes = [];
                    
                    if ($originalStatus !== $viewing->status) {
                        $changes['status'] = [
                            'old' => $originalStatus,
                            'new' => $viewing->status
                        ];
                    }
                    
                    if ($originalScheduleAt != $viewing->schedule_at) {
                        $changes['schedule_at'] = [
                            'old' => $originalScheduleAt,
                            'new' => $viewing->schedule_at
                        ];
                    }
                    
                    if ($originalPropertyId != $viewing->property_id) {
                        $changes['property_id'] = [
                            'old' => $originalPropertyId,
                            'new' => $viewing->property_id
                        ];
                    }
                    
                    if ($originalUnitId != $viewing->unit_id) {
                        $changes['unit_id'] = [
                            'old' => $originalUnitId,
                            'new' => $viewing->unit_id
                        ];
                    }
                    
                    if ($originalAgentId != $viewing->agent_id) {
                        $changes['agent_id'] = [
                            'old' => $originalAgentId,
                            'new' => $viewing->agent_id
                        ];
                    }
                    
                    if ($originalNote != $viewing->note) {
                        $changes['note'] = [
                            'old' => $originalNote,
                            'new' => $viewing->note
                        ];
                    }
                    
                    // Chỉ gửi email nếu có thay đổi
                    if (!empty($changes)) {
                        $viewingNotificationService = app(ViewingNotificationService::class);
                        $updateType = isset($changes['status']) ? 'status' : 'info';
                        $emailResult = $viewingNotificationService->sendViewingUpdatedEmail($viewing, $changes, $updateType);
                        
                        if (!$emailResult['success']) {
                            // Log warning nhưng không fail request
                            Log::warning('Failed to send viewing updated email', [
                                'viewing_id' => $viewing->id,
                                'error' => $emailResult['message']
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // Log error nhưng không fail request
                    Log::error('Error sending viewing updated email', [
                        'viewing_id' => $viewing->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Lịch hẹn đã được cập nhật thành công!',
                    'redirect' => route('staff.viewings.show', $viewing->id) // URL chuyển đến trang chi tiết → Hiển thị viewing vừa cập nhật
            ]); // Trả về JSON success → Frontend sẽ redirect

        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error updating viewing: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'cập nhật lịch hẹn');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Xóa viewing
     * 
     * MỤC ĐÍCH:
     * Xóa viewing (soft delete) với kiểm tra quyền và ownership filter (Manager xóa tất cả, Agent chỉ xóa của assigned properties)
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Session: organization_id, user_id
     * - Database: viewings
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."}
     * - Database: Soft delete viewing (ghi deleted_at và deleted_by)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.appointment.delete
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 5. Load viewing
     * 6. Soft delete viewing
     * 7. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Soft delete (ghi deleted_at và deleted_by)
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.appointment.delete
     * - Ownership check: Agent chỉ xóa của assigned properties
     * - Sử dụng soft delete (không xóa vĩnh viễn, có thể restore)
     * - Ghi deleted_by và deleted_at khi xóa
     * 
     * @param int $id Viewing ID
     * @return \Illuminate\Http\JsonResponse JSON response với success/error
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu deleted_by và check quyền
        
        // Check capability
        $this->requireCapability('crm.appointment.delete', 'Bạn không có quyền xóa lịch hẹn.'); // Kiểm tra quyền xóa viewing → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xóa viewings của assigned properties
        }
        
        $viewing = $query->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        try {
            // Áp dụng xóa mềm (soft delete) cho tất cả trạng thái
            $viewing->delete(); // Soft delete viewing → Ghi deleted_at và deleted_by (nếu có)

            return response()->json([
                'success' => true,
                'message' => 'Lịch hẹn đã được xóa thành công!'
            ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo

        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error deleting viewing: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'xóa lịch hẹn');
            return response()->json([
                'success' => false,
                'message' => $safeMessage
            ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
        }
    }

    /**
     * Hiển thị lịch viewings (calendar view)
     * 
     * MỤC ĐÍCH:
     * Hiển thị tất cả viewings dưới dạng lịch (calendar view) với ownership filter (Manager xem tất cả, Agent chỉ xem của assigned properties)
     * 
     * INPUT:
     * - Request: (không có query parameters)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, leads
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.calendar (với viewings)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 5. Load viewings với eager load relationships
     * 6. Sort theo schedule_at
     * 7. Trả về view calendar
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy tất cả viewings (theo ownership)
     * - Bảng properties: Eager load property với location
     * - Bảng units: Eager load unit
     * - Bảng users: Eager load agent, tenant với userProfile
     * - Bảng leads: Eager load lead
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem viewings của assigned properties
     * - Eager load relationships để tránh N+1 query
     * - Sort theo schedule_at để hiển thị theo thời gian
     * 
     * @param \Illuminate\Http\Request $request Request (không có query parameters)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View calendar hoặc redirect nếu có lỗi
     */
    public function calendar(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check if user has crm.access capability
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewings for calendar
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem viewings của assigned properties
        }
        
        $viewings = $query->with([
                'property.location', // Eager load property location → Tránh N+1 query
                'property.location2025', // Eager load property location2025 → Tránh N+1 query
                'unit', // Eager load unit → Tránh N+1 query
                'agent.userProfile', // Eager load agent với userProfile → Tránh N+1 query
                'organization', // Eager load organization → Tránh N+1 query
                'tenant.userProfile', // Eager load tenant với userProfile → Tránh N+1 query
                'lead' // Eager load lead → Tránh N+1 query
            ])
            ->orderBy('schedule_at') // Sort theo schedule_at → Hiển thị theo thời gian
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị trong calendar


        return view('staff.crm.viewings.calendar', compact('viewings')); // Trả về view calendar → Hiển thị lịch viewings
    }

    /**
     * Hiển thị viewings hôm nay
     * 
     * MỤC ĐÍCH:
     * Hiển thị danh sách viewings có schedule_at = hôm nay với ownership filter (Manager xem tất cả, Agent chỉ xem của assigned properties)
     * 
     * INPUT:
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, leads
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.today (với viewings)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Tạo query với ownership filter và filter theo schedule_at = hôm nay (nếu agent: filter theo assigned properties)
     * 5. Load viewings với eager load relationships
     * 6. Sort theo schedule_at
     * 7. Trả về view today
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy viewings có schedule_at = hôm nay (theo ownership)
     * - Bảng properties: Eager load property với location
     * - Bảng units: Eager load unit
     * - Bảng users: Eager load agent, tenant
     * - Bảng leads: Eager load lead
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem viewings của assigned properties
     * - Filter theo schedule_at = hôm nay (whereDate)
     * - Eager load relationships để tránh N+1 query
     * - Sort theo schedule_at để hiển thị theo thời gian
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View today hoặc redirect nếu có lỗi
     */
    public function today()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check if user has crm.access capability
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get today's viewings
        $today = now()->format('Y-m-d'); // Lấy ngày hôm nay → Dùng để filter viewings
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            })
            ->whereDate('schedule_at', $today); // Filter theo schedule_at = hôm nay → Chỉ lấy viewings hôm nay
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem viewings của assigned properties
        }
        
        $viewings = $query->with(['property.location', 'property.location2025', 'unit', 'agent', 'organization', 'tenant', 'lead']) // Eager load relationships → Tránh N+1 query
            ->orderBy('schedule_at') // Sort theo schedule_at → Hiển thị theo thời gian
            ->get(); // Lấy tất cả kết quả → Dùng để hiển thị

        return view('staff.crm.viewings.today', compact('viewings')); // Trả về view today → Hiển thị viewings hôm nay
    }

    /**
     * Hiển thị thống kê viewings
     * 
     * MỤC ĐÍCH:
     * Hiển thị thống kê viewings theo status, agent, property trong khoảng thời gian (start_date, end_date) với ownership filter (Manager xem tất cả, Agent chỉ xem của assigned properties)
     * 
     * INPUT:
     * - Request: start_date, end_date (query parameters - optional, mặc định là tháng hiện tại)
     * - Session: organization_id, user_id
     * - Database: viewings, properties, units, users, leads
     * 
     * OUTPUT:
     * - View: staff.crm.viewings.statistics (với viewings, totalViewings, statusCounts, agentCounts, propertyCounts, agents, properties, startDate, endDate)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền: crm.access
     * 2. Lấy organization ID từ session
     * 3. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 4. Lấy date range từ request (mặc định là tháng hiện tại)
     * 5. Tạo query với ownership filter và filter theo date range (nếu agent: filter theo assigned properties)
     * 6. Load viewings với eager load relationships
     * 7. Tính statistics: totalViewings, statusCounts, agentCounts, propertyCounts
     * 8. Lấy agents và properties cho labels
     * 9. Trả về view statistics
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy viewings trong date range (theo ownership)
     * - Bảng properties: Eager load property với location, lấy properties cho labels
     * - Bảng units: Eager load unit
     * - Bảng users: Eager load agent, tenant, lấy agents cho labels
     * - Bảng leads: Eager load lead
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log statistics (debug)
     * 
     * LƯU Ý:
     * - Yêu cầu quyền crm.access
     * - Ownership filter: Manager xem tất cả, Agent chỉ xem viewings của assigned properties
     * - Filter theo date range (whereBetween schedule_at)
     * - Tính statistics: totalViewings, statusCounts (groupBy status), agentCounts (groupBy agent_id), propertyCounts (groupBy property_id)
     * - Loại trừ null agent_id và property_id khi tính statistics
     * - Eager load relationships để tránh N+1 query
     * - Ghi log để debug
     * 
     * @param \Illuminate\Http\Request $request Request có thể chứa start_date, end_date (query parameters)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View statistics hoặc redirect nếu có lỗi
     */
    public function statistics(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter data
        
        // Check if user has crm.access capability
        $hasCrmAccess = $this->checkCapability('crm.access'); // Kiểm tra quyền truy cập module CRM → Dừng nếu không có quyền
        if (!$hasCrmAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module CRM.'); // Dừng request và trả về lỗi 403
        }
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get date range
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d')); // Lấy start_date từ request → Mặc định là đầu tháng hiện tại
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d')); // Lấy end_date từ request → Mặc định là cuối tháng hiện tại
        
        // Get viewings in date range
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            })
            ->whereBetween('schedule_at', [$startDate, $endDate]); // Filter theo date range → Chỉ lấy viewings trong khoảng thời gian
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xem viewings của assigned properties
        }
        
        $viewings = $query->with(['property.location', 'property.location2025', 'unit', 'agent', 'organization', 'tenant', 'lead']) // Eager load relationships → Tránh N+1 query
            ->get(); // Lấy tất cả kết quả → Dùng để tính statistics

        // Calculate statistics
        $totalViewings = $viewings->count(); // Đếm tổng số viewings → Hiển thị trong statistics
        $statusCounts = $viewings->groupBy('status')->map->count(); // Đếm viewings theo status → Hiển thị trong statistics
        
        // Filter out null agent_id and property_id
        $agentCounts = $viewings->whereNotNull('agent_id')->groupBy('agent_id')->map->count(); // Đếm viewings theo agent_id (loại trừ null) → Hiển thị trong statistics
        $propertyCounts = $viewings->whereNotNull('property_id')->groupBy('property_id')->map->count(); // Đếm viewings theo property_id (loại trừ null) → Hiển thị trong statistics
        
        // Debug: Log the data
        Log::info('Total viewings: ' . $totalViewings); // Ghi log tổng số viewings → Dùng để debug
        Log::info('Agent counts:', $agentCounts->toArray()); // Ghi log số viewings theo agent → Dùng để debug
        Log::info('Property counts:', $propertyCounts->toArray()); // Ghi log số viewings theo property → Dùng để debug
        Log::info('Viewings with agent_id: ' . $viewings->whereNotNull('agent_id')->count()); // Ghi log số viewings có agent_id → Dùng để debug
        Log::info('Viewings with property_id: ' . $viewings->whereNotNull('property_id')->count()); // Ghi log số viewings có property_id → Dùng để debug
        
        // Get agents and properties for labels - only if we have data
        $agents = $agentCounts->isNotEmpty() ? User::whereIn('id', $agentCounts->keys())->get() : collect(); // Lấy agents cho labels → Chỉ lấy nếu có data
        $properties = $propertyCounts->isNotEmpty() ? Property::whereIn('id', $propertyCounts->keys())->get() : collect(); // Lấy properties cho labels → Chỉ lấy nếu có data
        
        Log::info('Agents found: ' . $agents->count()); // Ghi log số agents tìm thấy → Dùng để debug
        Log::info('Properties found: ' . $properties->count()); // Ghi log số properties tìm thấy → Dùng để debug

        return view('staff.crm.viewings.statistics', compact(
            'viewings', 'totalViewings', 'statusCounts', 'agentCounts', 'propertyCounts',
            'agents', 'properties', 'startDate', 'endDate'
        )); // Trả về view statistics → Hiển thị thống kê viewings
    }

    /**
     * Xác nhận viewing
     * 
     * MỤC ĐÍCH:
     * Xác nhận viewing (status = 'confirmed') với kiểm tra quyền và ownership filter (Manager xác nhận tất cả, Agent chỉ xác nhận của assigned properties), cập nhật confirmed_at và confirmed_by
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Request: expectsJson hoặc ajax (optional)
     * - Session: organization_id, user_id
     * - Database: viewings
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."} (nếu AJAX/JSON request)
     * - Redirect: redirect()->back()->with('success', '...') (nếu normal request)
     * - Database: Cập nhật status = 'confirmed', confirmed_at, confirmed_by trong bảng viewings
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization ID từ session
     * 2. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 3. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 4. Load viewing
     * 5. Cập nhật viewing: status = 'confirmed', confirmed_at = now(), confirmed_by = user.id
     * 6. Trả về JSON hoặc redirect tùy theo request type
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Cập nhật status, confirmed_at, confirmed_by
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Ownership check: Agent chỉ xác nhận của assigned properties
     * - Cập nhật confirmed_at và confirmed_by khi xác nhận
     * - Hỗ trợ cả JSON/AJAX request và normal request
     * 
     * @param \Illuminate\Http\Request $request Request (có thể là AJAX/JSON hoặc normal request)
     * @param int $id Viewing ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON hoặc redirect response
     */
    public function confirm(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu confirmed_by và check quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ xác nhận viewings của assigned properties
        }
        
        $viewing = $query->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        try {
            $originalStatus = $viewing->status;
            
            $viewing->update([
                'status' => 'confirmed', // Cập nhật status → Đánh dấu viewing đã được xác nhận
                'confirmed_at' => now(), // Thời gian xác nhận → Lưu thời gian xác nhận viewing
                'confirmed_by' => $user->id // User xác nhận → Lưu user đã xác nhận viewing
            ]); // Cập nhật viewing → Lưu thay đổi vào database

            // Gửi email thông báo cho lead khi xác nhận (chỉ khi là lead)
            if (!$viewing->tenant_id && $originalStatus !== 'confirmed') {
                try {
                    $viewing->refresh();
                    $viewingNotificationService = app(ViewingNotificationService::class);
                    $changes = [
                        'status' => [
                            'old' => $originalStatus,
                            'new' => 'confirmed'
                        ]
                    ];
                    $emailResult = $viewingNotificationService->sendViewingUpdatedEmail($viewing, $changes, 'confirm');
                    
                    if (!$emailResult['success']) {
                        Log::warning('Failed to send viewing confirmed email', [
                            'viewing_id' => $viewing->id,
                            'error' => $emailResult['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending viewing confirmed email', [
                        'viewing_id' => $viewing->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => true,
                    'message' => 'Lịch hẹn đã được xác nhận thành công!'
                ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo
            }

            return redirect()->back()->with('success', 'Lịch hẹn đã được xác nhận thành công!'); // Redirect về trang trước → Hiển thị thông báo success
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error confirming viewing: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'xác nhận lịch hẹn');
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
            }
            
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước → Hiển thị thông báo error
        }
    }

    /**
     * Hủy viewing
     * 
     * MỤC ĐÍCH:
     * Hủy viewing (status = 'cancelled') với kiểm tra quyền và ownership filter (Manager hủy tất cả, Agent chỉ hủy của assigned properties), cập nhật cancelled_at và cancelled_by
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Request: expectsJson hoặc ajax (optional)
     * - Session: organization_id, user_id
     * - Database: viewings
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."} (nếu AJAX/JSON request)
     * - Redirect: redirect()->back()->with('success', '...') (nếu normal request)
     * - Database: Cập nhật status = 'cancelled', cancelled_at, cancelled_by trong bảng viewings
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization ID từ session
     * 2. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 3. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 4. Load viewing
     * 5. Cập nhật viewing: status = 'cancelled', cancelled_at = now(), cancelled_by = user.id
     * 6. Trả về JSON hoặc redirect tùy theo request type
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Cập nhật status, cancelled_at, cancelled_by
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Ownership check: Agent chỉ hủy của assigned properties
     * - Cập nhật cancelled_at và cancelled_by khi hủy
     * - Hỗ trợ cả JSON/AJAX request và normal request
     * 
     * @param \Illuminate\Http\Request $request Request (có thể là AJAX/JSON hoặc normal request)
     * @param int $id Viewing ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON hoặc redirect response
     */
    public function cancel(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu cancelled_by và check quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ hủy viewings của assigned properties
        }
        
        $viewing = $query->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        try {
            $originalStatus = $viewing->status;
            
            $viewing->update([
                'status' => 'cancelled', // Cập nhật status → Đánh dấu viewing đã được hủy
                'cancelled_at' => now(), // Thời gian hủy → Lưu thời gian hủy viewing
                'cancelled_by' => $user->id // User hủy → Lưu user đã hủy viewing
            ]); // Cập nhật viewing → Lưu thay đổi vào database

            // Gửi email thông báo cho lead khi hủy (chỉ khi là lead)
            if (!$viewing->tenant_id && $originalStatus !== 'cancelled') {
                try {
                    $viewing->refresh();
                    $viewingNotificationService = app(ViewingNotificationService::class);
                    $changes = [
                        'status' => [
                            'old' => $originalStatus,
                            'new' => 'cancelled'
                        ]
                    ];
                    $emailResult = $viewingNotificationService->sendViewingUpdatedEmail($viewing, $changes, 'cancel');
                    
                    if (!$emailResult['success']) {
                        Log::warning('Failed to send viewing cancelled email', [
                            'viewing_id' => $viewing->id,
                            'error' => $emailResult['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending viewing cancelled email', [
                        'viewing_id' => $viewing->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => true,
                    'message' => 'Lịch hẹn đã được hủy thành công!'
                ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo
            }

            return redirect()->back()->with('success', 'Lịch hẹn đã được hủy thành công!'); // Redirect về trang trước → Hiển thị thông báo success
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error cancelling viewing: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'hủy lịch hẹn');
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
            }
            
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước → Hiển thị thông báo error
        }
    }

    /**
     * Đánh dấu viewing hoàn thành
     * 
     * MỤC ĐÍCH:
     * Đánh dấu viewing hoàn thành (status = 'done') với kiểm tra quyền và ownership filter (Manager đánh dấu tất cả, Agent chỉ đánh dấu của assigned properties), cập nhật done_at, done_by và result_note
     * 
     * INPUT:
     * - Route parameter: id (viewing ID)
     * - Request: result_note (optional), expectsJson hoặc ajax (optional)
     * - Session: organization_id, user_id
     * - Database: viewings
     * 
     * OUTPUT:
     * - JSON: {success: true, message: "..."} hoặc {success: false, message: "..."} (nếu AJAX/JSON request)
     * - Redirect: redirect()->back()->with('success', '...') (nếu normal request)
     * - Database: Cập nhật status = 'done', done_at, done_by, result_note trong bảng viewings
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization ID từ session
     * 2. Kiểm tra ownership: canViewAll (view_all hoặc view_own)
     * 3. Tạo query với ownership filter (nếu agent: filter theo assigned properties)
     * 4. Load viewing
     * 5. Cập nhật viewing: status = 'done', done_at = now(), done_by = user.id, result_note = request.result_note
     * 6. Trả về JSON hoặc redirect tùy theo request type
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng viewings: Lấy chi tiết viewing
     * - Request: result_note (ghi chú kết quả)
     * - Session: organization_id, user_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng viewings: Cập nhật status, done_at, done_by, result_note
     * - Logs: Ghi log nếu có lỗi
     * 
     * LƯU Ý:
     * - Ownership check: Agent chỉ đánh dấu của assigned properties
     * - Cập nhật done_at, done_by và result_note khi đánh dấu hoàn thành
     * - Hỗ trợ cả JSON/AJAX request và normal request
     * - result_note là optional (có thể null)
     * 
     * @param \Illuminate\Http\Request $request Request chứa result_note (optional), có thể là AJAX/JSON hoặc normal request
     * @param int $id Viewing ID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse JSON hoặc redirect response
     */
    public function markDone(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để lưu done_by và check quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }
        
        // Check if user can view all appointments or only own appointments
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('crm.appointment'); // Kiểm tra user có thể xem tất cả viewings không → Manager có view_all, Agent có view_own
        
        // Get viewing
        $query = Viewing::whereHas('property', function($propertyQuery) use ($organizationId) { // Tạo base query filter theo property → Sử dụng relationship để filter
                $propertyQuery->where('organization_id', $organizationId); // Filter property theo organization → Đảm bảo viewing thuộc organization
            });
        
        // For agent, only show viewings of assigned properties
        if (!$canViewAll) { // Nếu không có quyền view_all (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign → Dùng để filter viewings
            $query->whereIn('property_id', $assignedPropertyIds); // Filter: property_id trong assigned properties → Agent chỉ đánh dấu viewings của assigned properties
        }
        
        $viewing = $query->findOrFail($id); // Tìm viewing theo ID → Throw 404 nếu không tìm thấy

        try {
            $originalStatus = $viewing->status;
            
            $viewing->update([
                'status' => 'done', // Cập nhật status → Đánh dấu viewing đã hoàn thành
                'done_at' => now(), // Thời gian hoàn thành → Lưu thời gian hoàn thành viewing
                'done_by' => $user->id, // User hoàn thành → Lưu user đã hoàn thành viewing
                'result_note' => $request->result_note // Ghi chú kết quả → Lưu ghi chú về kết quả viewing
            ]); // Cập nhật viewing → Lưu thay đổi vào database

            // Gửi email thông báo cho lead khi đánh dấu hoàn thành (chỉ khi là lead)
            if (!$viewing->tenant_id && $originalStatus !== 'done') {
                try {
                    $viewing->refresh();
                    $viewingNotificationService = app(ViewingNotificationService::class);
                    $changes = [
                        'status' => [
                            'old' => $originalStatus,
                            'new' => 'done'
                        ]
                    ];
                    $emailResult = $viewingNotificationService->sendViewingUpdatedEmail($viewing, $changes, 'done');
                    
                    if (!$emailResult['success']) {
                        Log::warning('Failed to send viewing done email', [
                            'viewing_id' => $viewing->id,
                            'error' => $emailResult['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending viewing done email', [
                        'viewing_id' => $viewing->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => true,
                    'message' => 'Lịch hẹn đã được đánh dấu hoàn thành!'
                ]); // Trả về JSON success → Frontend sẽ hiển thị thông báo
            }

            return redirect()->back()->with('success', 'Lịch hẹn đã được đánh dấu hoàn thành!'); // Redirect về trang trước → Hiển thị thông báo success
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error marking viewing as done: ' . $e->getMessage()); // Ghi log lỗi → Dùng để debug
            $safeMessage = ErrorHelper::getSafeErrorMessageWithContext($e, 'đánh dấu hoàn thành');
            
            if ($request->expectsJson() || $request->ajax()) { // Nếu là AJAX/JSON request
                return response()->json([
                    'success' => false,
                    'message' => $safeMessage
                ], 500); // Trả về JSON error → Frontend sẽ hiển thị error message
            }
            
            return redirect()->back()->with('error', $safeMessage); // Redirect về trang trước → Hiển thị thông báo error
        }
    }

    /**
     * Lấy label trạng thái unit bằng tiếng Việt
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi unit status code (available, reserved, occupied, maintenance) sang label tiếng Việt để hiển thị cho user
     * 
     * INPUT:
     * - Parameter: $status (string) - Unit status code
     * 
     * OUTPUT:
     * - String: Label tiếng Việt của status hoặc status code nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo mảng mapping status code -> label tiếng Việt
     * 2. Kiểm tra status có trong mảng không
     * 3. Trả về label tương ứng hoặc status code nếu không tìm thấy
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ xử lý parameter)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     * 
     * LƯU Ý:
     * - Helper method để format unit status label
     * - Trả về status code nếu không tìm thấy trong mapping
     * - Dùng trong validation error messages
     * 
     * @param string $status Unit status code (available, reserved, occupied, maintenance)
     * @return string Label tiếng Việt hoặc status code nếu không tìm thấy
     */
    private function getUnitStatusLabel($status)
    {
        $labels = [
            'available' => 'Có sẵn', // available → Label tiếng Việt
            'reserved' => 'Đã đặt cọc', // reserved → Label tiếng Việt
            'occupied' => 'Đã cho thuê', // occupied → Label tiếng Việt
            'maintenance' => 'Bảo trì', // maintenance → Label tiếng Việt
        ];

        return $labels[$status] ?? $status; // Trả về label tương ứng hoặc status code nếu không tìm thấy → Dùng để hiển thị cho user
    }
}
