<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Location;
use App\Models\Location2025;
use App\Models\User;
use App\Models\GeoProvince;
use App\Models\GeoDistrict;
use App\Models\GeoProvince2025;
use App\Models\GeoWard2025;
use App\Models\PaymentCycle;
use App\Models\LeaseServiceSet;
use App\Models\Document;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;
use App\Services\Subscription\PlanLimitChecker;

/**
 * Controller quản lý Properties (Bất động sản) trong tổ chức (Asset module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách properties trong tổ chức (xem, tạo, sửa, xóa)
 * - Manager: Xem tất cả properties trong organization
 * - Agent: Chỉ xem assigned properties
 * - Quản lý thông tin property: name, description, property_type, location (old và new system 2025)
 * - Quản lý images, documents, assigned staff, payment cycles, lease service sets
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Kiểm tra subscription plan limits khi tạo property mới
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách properties với filters (search, type, status, location, date range)
 *    - Filter theo organization_id, ownership (assigned properties cho agent)
 *    - Tính statistics (total, active, inactive) bằng aggregation
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép (id, created_at, name, status)
 *    - Eager load relationships (propertyType, location, location2025, masterLeases, units)
 * 2. create(): Hiển thị form tạo property mới
 *    - Load propertyTypes, geo data (old và new system 2025)
 *    - Load payment cycles, lease service sets của organization
 *    - Load staff users (managers và agents, exclude tenants/admin/landlord)
 * 3. store(): Tạo property mới với validation, check subscription limit
 *    - Validate tất cả fields (name, property_type_id, location, images, etc.)
 *    - Check subscription plan limit (max_properties)
 *    - Create property, location (old và new system 2025), images, documents
 *    - Assign staff (managers và agents) nếu có
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết property (units, leases, assigned staff, images, documents)
 * 5. edit(): Hiển thị form edit property với tất cả data
 * 6. update(): Cập nhật property (name, description, location, images, assigned staff, etc.)
 *    - Validate và update property, location, images, documents
 *    - Sync assigned staff (managers và agents)
 *    - Handle image upload/delete
 * 7. destroy(): Xóa property (soft delete)
 *    - Soft delete property và related records
 * 8. getDistricts(): API endpoint lấy districts theo province code (old system) (AJAX)
 * 9. getWards(): API endpoint lấy wards theo district code (old system) (AJAX)
 * 10. getWards2025(): API endpoint lấy wards theo province code (new system 2025) (AJAX)
 * 11. updateStatus(): API endpoint cập nhật status của property (active/inactive) (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/properties: Danh sách properties (hỗ trợ HTMX/AJAX)
 * - GET /staff/properties/create: Form tạo property
 * - POST /staff/properties: Tạo property mới
 * - GET /staff/properties/{id}: Chi tiết property
 * - GET /staff/properties/{id}/edit: Form edit property
 * - PUT/PATCH /staff/properties/{id}: Cập nhật property
 * - DELETE /staff/properties/{id}: Xóa property
 * - GET /staff/properties/districts/{provinceCode}: Lấy districts (AJAX)
 * - GET /staff/properties/wards/{districtCode}: Lấy wards (AJAX)
 * - GET /staff/properties/wards2025/{provinceCode}: Lấy wards 2025 (AJAX)
 * - POST /staff/properties/{id}/status: Cập nhật status (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: Property, PropertyType, Location, Location2025, User, GeoProvince, GeoDistrict, GeoProvince2025, GeoWard2025, PaymentCycle, LeaseServiceSet, Document
 * - Database tables: properties, property_types, locations, locations_2025, users, geo_provinces, geo_districts, geo_provinces_2025, geo_wards_2025, payment_cycles, lease_service_sets, documents, property_user (pivot)
 * - Request: search, type, status, date_from, date_to, province, district, province_2025, ward_2025, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: properties, locations, locations_2025, documents, property_user (pivot table cho assigned staff)
 * - Storage: Images được upload qua ImageService
 * - Không có thay đổi property_types, geo data, payment_cycles, lease_service_sets (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (asset.access, asset.property.view, asset.property.create, etc.)
 * - FiltersByOwnership: Filter theo ownership (view_all vs view_own cho assigned properties)
 * 
 * SERVICES SỬ DỤNG:
 * - ImageService: Upload, delete, get URL cho property images
 * - PlanLimitChecker: Kiểm tra subscription plan limits (max_properties)
 * 
 * CAPABILITY CHECKING:
 * - asset.access: Quyền truy cập module Asset (required cho tất cả methods)
 * - asset.property.view: Quyền xem danh sách properties (index, show)
 * - asset.property.create: Quyền tạo property (create, store)
 * - asset.property.update: Quyền cập nhật property (edit, update)
 * - asset.property.delete: Quyền xóa property (destroy)
 * 
 * OWNERSHIP FILTERING:
 * - Manager: Xem tất cả properties trong organization (canViewAll = true)
 * - Agent: Chỉ xem assigned properties (canViewAll = false, filter theo assignedProperties)
 * - Sử dụng FiltersByOwnership trait để handle logic
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng JOINs thay vì whereHas() khi có thể để tối ưu performance
 * - Eager loading relationships (propertyType, location, location2025, masterLeases, units)
 * - Tính statistics bằng aggregation (COUNT) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * - Sử dụng whereIn() với assignedPropertyIds để filter hiệu quả
 * 
 * SUBSCRIPTION LIMITS:
 * - Kiểm tra max_properties limit khi tạo property mới
 * - Sử dụng PlanLimitChecker để check limits và get current count
 * - Trả về error message với current/limit nếu vượt quá limit
 * 
 * LOCATION HANDLING:
 * - Hỗ trợ cả old location system (Location model) và new system 2025 (Location2025 model)
 * - Old system: province_code, district_code
 * - New system 2025: province_code, ward_code (không có district)
 * - Có thể có cả 2 location records cho một property (migration period)
 * 
 * IMAGE HANDLING:
 * - Sử dụng ImageService để upload, delete, get URLs
 * - Hỗ trợ multiple images cho một property
 * - Images được lưu trong public storage
 * 
 * VALIDATION:
 * - name: required, string, max:255
 * - property_type_id: required, exists:property_types
 * - location: required (old hoặc new system)
 * - images: array, image files, max size
 * - assigned_staff: array, exists:users (optional)
 * 
 * SECURITY:
 * - Manager có quyền quản lý tất cả properties
 * - Agent chỉ có quyền xem assigned properties
 * - Validate sort fields để prevent SQL injection
 * - Image upload validation (file type, size)
 * 
 * LƯU Ý:
 * - Property có thể có cả Location và Location2025 (hỗ trợ migration)
 * - Assigned staff bao gồm managers và agents (exclude tenants, admin, landlord)
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Subscription limits được check trước khi tạo property mới
 * - Images được quản lý qua ImageService với public storage
 */
class PropertyController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;

    /**
     * ImageService instance để upload, delete, get URLs cho images
     * 
     * @var \App\Services\ImageService
     */
    protected $imageService;
    
    /**
     * PlanLimitChecker instance để kiểm tra subscription plan limits
     * 
     * @var \App\Services\Subscription\PlanLimitChecker
     */
    protected $limitChecker;

    /**
     * Constructor: Inject ImageService và PlanLimitChecker dependencies
     * 
     * @param \App\Services\ImageService $imageService
     * @param \App\Services\Subscription\PlanLimitChecker $limitChecker
     */
    public function __construct(ImageService $imageService, PlanLimitChecker $limitChecker)
    {
        $this->imageService = $imageService;
        $this->limitChecker = $limitChecker;
    }

    /**
     * Hiển thị danh sách properties với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: asset.access
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Kiểm tra ownership: canViewAll (manager xem tất cả, agent chỉ xem assigned)
     * 4. Build query với JOIN property_types
     * 5. Apply ownership filter: Nếu agent, chỉ lấy assigned properties
     * 6. Tính statistics (total, active, inactive) bằng aggregation
     * 7. Apply filters: search, type, status, date range, location (old và new system)
     * 8. Apply sorting (validate sort fields)
     * 9. Paginate results (15 items per page)
     * 10. Eager load relationships (propertyType, location, location2025, masterLeases, units)
     * 11. Load geo data cho filters (provinces, districts, provinces2025, wards2025)
     * 12. Check request type (HTMX/AJAX):
     *     - HTMX: Return table partial HTML với stats update
     *     - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: properties, property_types, locations, locations_2025, geo data
     * - Request: search, type, status, date_from, date_to, province, district, province_2025, ward_2025, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - asset.access: Quyền truy cập module Asset
     * 
     * OWNERSHIP FILTERING:
     * - Manager: Xem tất cả properties (canViewAll = true)
     * - Agent: Chỉ xem assigned properties (canViewAll = false, filter theo assignedProperties)
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng JOINs thay vì whereHas() để tối ưu performance
     * - Eager loading relationships để tránh N+1 queries
     * - Tính statistics bằng aggregation trong một query
     * - Validate sort fields để prevent SQL injection
     * 
     * FILTERS:
     * - search: Tìm kiếm theo name, description, location (street, district, ward, city), landlord name
     * - type: Filter theo property_type_id
     * - status: Filter theo status (active/inactive)
     * - date_from/date_to: Filter theo created_at
     * - province/district: Filter theo location (old system)
     * - province_2025/ward_2025: Filter theo location2025 (new system)
     * 
     * SORTING:
     * - Supported fields: id, created_at, name, status
     * - Default: id DESC
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và filter assigned properties
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter properties theo organization
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn không thuộc tổ chức nào.'); // Dừng request và trả về lỗi 403
        }

        $hasAssetAccess = $this->checkCapability('asset.access'); // Kiểm tra quyền truy cập module Asset → Dừng nếu không có quyền
        if (!$hasAssetAccess) { // Nếu không có quyền
            abort(403, 'Bạn không có quyền truy cập module Tài sản.'); // Dừng request và trả về lỗi 403
        }

        // Kiểm tra user có thể xem tất cả properties hay chỉ assigned properties
        // Sử dụng FiltersByOwnership trait method: view_all > view_own > view (backward compatibility)
        // Manager: canViewAll = true (xem tất cả)
        // Agent: canViewAll = false (chỉ xem assigned)
        $canViewAll = $this->canViewAll('asset.property'); // Kiểm tra user có quyền xem tất cả properties không → Manager: true, Agent: false
        
        // Build query với JOIN property_types để lấy property_type_name
        // Sử dụng LEFT JOIN để vẫn lấy được properties không có property_type
        $query = Property::select([ // Select các trường cần thiết → Tối ưu query, chỉ lấy dữ liệu cần dùng
            'properties.*', // Tất cả trường của properties
            'property_types.name as property_type_name' // Tên loại bất động sản từ bảng property_types
        ])
        ->leftJoin('property_types', 'properties.property_type_id', '=', 'property_types.id') // LEFT JOIN với property_types → Lấy tên loại BĐS, vẫn lấy được properties không có type
        ->where('properties.organization_id', $organizationId) // Filter theo organization ID → Chỉ lấy properties của organization hiện tại
        ->whereNull('properties.deleted_at'); // Chỉ lấy properties chưa bị xóa → Loại bỏ soft deleted records

        if (!$canViewAll) { // Nếu user không thể xem tất cả (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được gán cho agent → Dùng để filter chỉ hiển thị assigned properties
            
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có assigned properties
                return view('staff.asset.properties.index', [ // Trả về view rỗng → Agent không có properties nào được gán
                    'properties' => collect(), // Danh sách properties rỗng
                    'propertyTypes' => collect(), // Danh sách property types rỗng
                    'provinces' => collect(), // Danh sách provinces rỗng
                    'districts' => collect(), // Danh sách districts rỗng
                    'provinces2025' => collect(), // Danh sách provinces 2025 rỗng
                    'wards2025' => collect(), // Danh sách wards 2025 rỗng
                    'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0], // Statistics = 0
                    'sortBy' => 'id', // Sort mặc định theo ID
                    'sortOrder' => 'desc' // Sort order mặc định: giảm dần
                ]);
            }
            $query->whereIn('properties.id', $assignedPropertyIds); // Filter query chỉ lấy assigned properties → Agent chỉ xem properties được gán
        }

        $query->whereNull('properties.deleted_at'); // Đảm bảo chỉ lấy properties chưa bị xóa → Loại bỏ soft deleted records (đã filter ở trên nhưng để consistency)

        // Tính statistics TRƯỚC KHI apply các filters khác (search, type, etc.)
        // Query trực tiếp từ Property model để đảm bảo statistics chính xác
        // Statistics phải tính trên toàn bộ data, không bị ảnh hưởng bởi filters
        $statsQuery = Property::where('organization_id', $organizationId) // Query riêng để tính statistics → Đảm bảo statistics chính xác, không bị ảnh hưởng bởi filters
            ->whereNull('deleted_at'); // Chỉ đếm properties chưa bị xóa
        
        if (!$canViewAll) { // Nếu user không thể xem tất cả (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách assigned property IDs
            if ($assignedPropertyIds->isEmpty()) { // Nếu không có assigned properties
                $statsQuery->whereRaw('1 = 0'); // Set điều kiện không bao giờ true → Statistics = 0
            } else {
                $statsQuery->whereIn('id', $assignedPropertyIds); // Chỉ đếm assigned properties → Statistics chỉ tính assigned properties
            }
        }
        
        // Đếm theo status bằng database aggregation để đảm bảo chính xác
        // Sử dụng clone để không ảnh hưởng đến statsQuery gốc
        $stats = [
            'total' => (int) (clone $statsQuery)->count(), // Đếm tổng số properties → Hiển thị tổng số
            'active' => (int) (clone $statsQuery)->where('status', 1)->count(), // Đếm properties đang hoạt động → Hiển thị số active
            'inactive' => (int) (clone $statsQuery)->where('status', 0)->count(), // Đếm properties tạm ngưng → Hiển thị số inactive
        ];

        if ($request->filled('search')) { // Nếu có search keyword
            $search = $request->search; // Lấy search keyword từ request
            $query->where(function($q) use ($search) { // Group các điều kiện search → Đảm bảo OR logic đúng
                $q->where('properties.name', 'like', "%{$search}%") // Tìm kiếm trong tên property → Tìm properties có tên chứa keyword
                  ->orWhere('properties.description', 'like', "%{$search}%") // Tìm kiếm trong mô tả → Tìm properties có mô tả chứa keyword
                  ->orWhereHas('location', function($locationQuery) use ($search) { // Tìm kiếm trong location (old system) → Tìm properties có địa chỉ chứa keyword
                      $locationQuery->where('street', 'like', "%{$search}%") // Tìm trong tên đường
                                   ->orWhere('district', 'like', "%{$search}%") // Tìm trong quận/huyện
                                   ->orWhere('ward', 'like', "%{$search}%") // Tìm trong phường/xã
                                   ->orWhere('city', 'like', "%{$search}%"); // Tìm trong thành phố
                  })
                  ->orWhereHas('location2025', function($locationQuery) use ($search) { // Tìm kiếm trong location2025 (new system) → Tìm properties có địa chỉ mới chứa keyword
                      $locationQuery->where('street', 'like', "%{$search}%") // Tìm trong tên đường
                                   ->orWhere('ward', 'like', "%{$search}%") // Tìm trong phường/xã (new system không có district)
                                   ->orWhere('city', 'like', "%{$search}%"); // Tìm trong thành phố
                  });
            });
        }

        if ($request->filled('type')) { // Nếu có filter theo loại BĐS
            $query->where('properties.property_type_id', $request->type); // Filter theo property_type_id → Chỉ lấy properties thuộc loại được chọn
        }

        if ($request->filled('status')) { // Nếu có filter theo trạng thái
            $query->where('properties.status', $request->status); // Filter theo status (1 = active, 0 = inactive) → Chỉ lấy properties có trạng thái được chọn
        }

        if ($request->filled('date_from')) { // Nếu có filter từ ngày
            $query->whereDate('properties.created_at', '>=', $request->date_from); // Filter properties tạo từ ngày này trở đi → Lọc theo ngày tạo
        }
        if ($request->filled('date_to')) { // Nếu có filter đến ngày
            $query->whereDate('properties.created_at', '<=', $request->date_to); // Filter properties tạo đến ngày này → Lọc theo ngày tạo
        }

        if ($request->filled('province')) { // Nếu có filter theo tỉnh/thành phố (old system)
            $query->whereHas('location', function($locationQuery) use ($request) { // Tìm properties có location với province_code → Filter theo tỉnh/thành phố
                $locationQuery->where('province_code', $request->province); // Filter theo province_code
            });
        }
        if ($request->filled('district')) { // Nếu có filter theo quận/huyện (old system)
            $query->whereHas('location', function($locationQuery) use ($request) { // Tìm properties có location với district_code → Filter theo quận/huyện
                $locationQuery->where('district_code', $request->district); // Filter theo district_code
            });
        }

        // Filter theo location (new system 2025): province_2025 và ward_2025
        // Lưu ý: New system không có district, chỉ có province và ward
        if ($request->filled('province_2025')) { // Nếu có filter theo tỉnh/thành phố (new system 2025)
            $query->whereHas('location2025', function($locationQuery) use ($request) { // Tìm properties có location2025 với province_code → Filter theo tỉnh/thành phố mới
                $locationQuery->where('province_code', $request->province_2025); // Filter theo province_code
            });
        }
        if ($request->filled('ward_2025')) { // Nếu có filter theo phường/xã (new system 2025)
            $query->whereHas('location2025', function($locationQuery) use ($request) { // Tìm properties có location2025 với ward_code → Filter theo phường/xã mới
                $locationQuery->where('ward_code', $request->ward_2025); // Filter theo ward_code
            });
        }
        
        $sortBy = $request->get('sort_by', 'id'); // Lấy sort field từ request, mặc định là 'id' → Dùng để sắp xếp properties
        $sortOrder = $request->get('sort_order', 'desc'); // Lấy sort order từ request, mặc định là 'desc' → Dùng để sắp xếp tăng/giảm dần
        
        $allowedSortFields = ['id', 'created_at', 'name', 'status']; // Danh sách các trường được phép sort → Bảo mật: prevent SQL injection
        if (!in_array($sortBy, $allowedSortFields)) { // Nếu sort field không hợp lệ
            $sortBy = 'id'; // Set mặc định là 'id' → Tránh lỗi SQL injection
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) { // Nếu sort order không hợp lệ
            $sortOrder = 'desc'; // Set mặc định là 'desc' → Tránh lỗi SQL injection
        }
        
        $properties = $query->orderBy("properties.{$sortBy}", $sortOrder)->paginate(15)->withQueryString(); // Sắp xếp và phân trang 15 items/trang → Hiển thị danh sách properties, giữ lại query string cho pagination
        
        $properties->load(['propertyType', 'location', 'location2025', 'masterLeases.landlord', 'units']); // Eager load relationships → Tối ưu query, tránh N+1 problem
        $propertyTypes = PropertyType::where('organization_id', $organizationId)->get(); // Lấy property types của organization → Dùng cho filter dropdown, chỉ lấy types của organization hiện tại
        
        $provinces = \App\Models\GeoProvince::all(); // Lấy tất cả provinces (old system) → Dùng cho filter dropdown
        $districts = \App\Models\GeoDistrict::all(); // Lấy tất cả districts (old system) → Dùng cho filter dropdown
        $provinces2025 = \App\Models\GeoProvince2025::all(); // Lấy tất cả provinces 2025 (new system) → Dùng cho filter dropdown
        
        $wards2025 = collect(); // Khởi tạo collection rỗng → Dùng cho filter dropdown
        if ($request->filled('province_2025')) { // Nếu có chọn province 2025
            $wards2025 = \App\Models\GeoWard2025::where('district_code', $request->province_2025)->get(); // Lấy wards theo province 2025 → Cascading dropdown, chỉ hiển thị wards của province được chọn
        }

        $isHtmx = $request->header('HX-Request') === 'true'; // Kiểm tra request có phải HTMX không → Xử lý khác nhau cho HTMX và normal request
        
        if ($isHtmx) { // Nếu là HTMX request
            try {
                $tableHtml = view('staff.asset.properties.partials.table', compact('properties', 'sortBy', 'sortOrder'))->render(); // Render table partial → Dùng để update table content qua HTMX
                
                $statsFormatted = [ // Format statistics cho response → Dùng để update statistics cards
                    'total' => [
                        'value' => $stats['total'] ?? 0, // Tổng số properties
                        'label' => 'Tổng cộng', // Label hiển thị
                        'icon' => 'fa-list', // Icon
                        'color' => 'primary', // Màu
                        'filter' => '', // Filter value (rỗng = tất cả)
                    ],
                    'active' => [
                        'value' => $stats['active'] ?? 0, // Số properties đang hoạt động
                        'label' => 'Hoạt động',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => '1', // Filter value = 1 (active)
                    ],
                    'inactive' => [
                        'value' => $stats['inactive'] ?? 0, // Số properties tạm ngưng
                        'label' => 'Tạm ngưng',
                        'icon' => 'fa-pause-circle',
                        'color' => 'warning',
                        'filter' => '0', // Filter value = 0 (inactive)
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [ // Render statistics cards → Dùng để update statistics qua HTMX
                    'stats' => $statsFormatted, // Statistics data
                    'currentFilter' => request('status', ''), // Filter hiện tại
                    'filterKey' => 'status', // Key của filter
                    'onFilterClick' => 'htmx-filter', // Event khi click filter
                    'onClearClick' => 'htmx-clear', // Event khi clear filter
                    'tableContainerId' => 'properties-table-container', // ID của table container
                    'action' => route('staff.properties.index'), // Action URL
                    'columns' => 3 // Số cột hiển thị
                ])->render();
                
                $innerTableHtml = $tableHtml; // Khởi tạo inner HTML → Dùng để extract inner content
                
                if (class_exists('DOMDocument')) { // Nếu có DOMDocument class
                    libxml_use_internal_errors(true); // Bật internal errors → Tránh warning khi parse HTML
                    $dom = new \DOMDocument(); // Tạo DOMDocument → Dùng để parse HTML
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Load HTML → Parse HTML string
                    $xpath = new \DOMXPath($dom); // Tạo XPath → Dùng để query DOM
                    $container = $xpath->query('//div[@id="properties-table-container"]')->item(0); // Tìm container div → Extract inner content
                    if ($container) { // Nếu tìm thấy container
                        $innerHtml = ''; // Khởi tạo inner HTML
                        foreach ($container->childNodes as $child) { // Loop qua các child nodes
                            $innerHtml .= $dom->saveHTML($child); // Lưu HTML của child → Build inner HTML
                        }
                        $innerTableHtml = trim($innerHtml); // Set inner HTML → Dùng để update table
                    }
                    libxml_clear_errors(); // Clear errors → Clean up
                }
                
                if ($innerTableHtml === $tableHtml) { // Nếu DOMDocument không extract được (fallback)
                    if (preg_match('/<div[^>]*id=["\']properties-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) { // Regex extract inner HTML → Fallback method
                        $innerTableHtml = trim($matches[1]); // Set inner HTML từ regex match
                    }
                }
                
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>"; // Combine table HTML và stats HTML với hx-swap-oob → Update cả table và stats qua HTMX
                
                return response($html) // Trả về HTML response
                    ->header('HX-Push-Url', $request->fullUrl()); // Push URL vào browser history → Update URL khi filter
            } catch (\Exception $e) { // Nếu có lỗi
                Log::error('PropertyController HTMX Error: ' . $e->getMessage()); // Ghi log lỗi → Để debug
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500); // Trả về lỗi 500 với thông báo
            }
        }

        return view('staff.asset.properties.index', compact('properties', 'propertyTypes', 'provinces', 'districts', 'provinces2025', 'wards2025', 'stats', 'sortBy', 'sortOrder')); // Trả về full page view → Normal request (không phải HTMX)
    }

    /**
     * Hiển thị form tạo property mới
     * 
     * MỤC ĐÍCH:
     * Hiển thị form tạo property mới với đầy đủ dropdowns (property types, geo data, payment cycles, lease service sets, staff users)
     * 
     * INPUT:
     * - Session: organization_id
     * - Database: property_types, geo_provinces, geo_provinces_2025, payment_cycles, lease_service_sets, users, roles
     * 
     * OUTPUT:
     * - View: staff.asset.properties.create
     * - Data: propertyTypes, provinces, provinces2025, paymentCycles, organizationPaymentCycle, managers, agents, leaseServiceSets, defaultLeaseServiceSet
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền tạo property
     * 2. Lấy organization_id
     * 3. Load property types của organization
     * 4. Load geo data (old và new system 2025)
     * 5. Load payment cycles của organization
     * 6. Load lease service sets của organization
     * 7. Load staff users (managers và agents, exclude tenants/admin/landlord)
     * 8. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng property_types: Lấy property types của organization
     * - Bảng geo_provinces, geo_provinces_2025: Lấy provinces cho dropdown
     * - Bảng payment_cycles: Lấy payment cycles của organization
     * - Bảng lease_service_sets: Lấy lease service sets của organization
     * - Bảng users, organization_users, roles: Lấy staff users (managers và agents)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function create()
    {
        $this->requireCapability('asset.property.create', 'Bạn không có quyền tạo bất động sản.'); // Kiểm tra quyền tạo property → Dừng nếu không có quyền

        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter data theo organization
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn phải thuộc một tổ chức để tạo bất động sản.'); // Dừng request và trả về lỗi 403
        }
        
        $propertyTypes = PropertyType::where('organization_id', $organizationId)->get(); // Lấy property types của organization → Dùng cho dropdown, chỉ lấy types của organization hiện tại

        $provinces = GeoProvince::where('country_code', 'VN')->get(); // Lấy provinces (old system) → Dùng cho dropdown địa chỉ cũ
        $provinces2025 = GeoProvince2025::where('country_code', 'VN')->get(); // Lấy provinces 2025 (new system) → Dùng cho dropdown địa chỉ mới

        $paymentCycles = collect(); // Khởi tạo collection rỗng → Dùng cho dropdown payment cycles
        $organizationPaymentCycle = null; // Khởi tạo default payment cycle → Dùng để set giá trị mặc định
        
        if ($organizationId) { // Nếu có organization ID
            $organizationPaymentCycle = PaymentCycle::where('organization_id', $organizationId) // Tìm default payment cycle → Dùng để set giá trị mặc định trong form
                ->where('is_default', true)
                ->first();
            
            $paymentCycles = PaymentCycle::where('organization_id', $organizationId) // Lấy tất cả payment cycles của organization → Dùng cho dropdown
                ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
                ->orderBy('is_default', 'desc') // Sắp xếp default trước → Hiển thị default ở đầu
                ->orderBy('name', 'asc') // Sắp xếp theo tên tăng dần
                ->get();
        }

        // Get all staff users from organization (managers and agents, EXCLUDE tenants)
        $allUsers = collect();
        $managers = collect();
        $agents = collect();
        
        if ($organizationId) {
            // Get staff users only (exclude tenants)
            $allUsers = User::with('userProfile')
                ->whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->where('status', 'active')
                      ->whereNull('deleted_at');
                })
                ->whereDoesntHave('userRoles', function($q) {
                    $q->where('key_code', 'tenant'); // Exclude tenants
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->full_name ?? '';
                })->values();
            
            // Get manager role IDs
            $managerIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'manager')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            // Separate managers and agents (both excluding tenants)
            $managers = $allUsers->filter(function($user) use ($managerIds) {
                return in_array($user->id, $managerIds);
            })->values();
            
            // For agents: filter to only users with role_id = 3 (agent role)
            // Get user IDs that have role_id = 3 in this organization
            $agentUserIds = DB::table('organization_users')
                ->where('organization_id', $organizationId)
                ->where('role_id', 3) // Role ID = 3 for agent
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->pluck('user_id')
                ->unique()
                ->toArray();
            
            // Get role IDs for admin and landlord to exclude
            $adminRoleId = DB::table('roles')->where('key_code', 'admin')->value('id');
            $landlordRoleId = DB::table('roles')->where('key_code', 'landlord')->value('id');
            
            // Get user IDs that have admin or landlord role in this organization (to exclude)
            $excludedUserIds = [];
            if ($adminRoleId || $landlordRoleId) {
                $excludedQuery = DB::table('organization_users')
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at');
                
                if ($adminRoleId && $landlordRoleId) {
                    $excludedQuery->whereIn('role_id', [$adminRoleId, $landlordRoleId]);
                } elseif ($adminRoleId) {
                    $excludedQuery->where('role_id', $adminRoleId);
                } elseif ($landlordRoleId) {
                    $excludedQuery->where('role_id', $landlordRoleId);
                }
                
                $excludedUserIds = $excludedQuery->pluck('user_id')->unique()->toArray();
            }
            
            // Filter agents: users that are not managers AND have role_id = 3 AND not admin/landlord
            $agents = $allUsers->filter(function($user) use ($managerIds, $agentUserIds, $excludedUserIds) {
                // Must not be a manager
                if (in_array($user->id, $managerIds)) {
                    return false;
                }
                // Must not be admin or landlord
                if (in_array($user->id, $excludedUserIds)) {
                    return false;
                }
                // Must have role_id = 3
                return in_array($user->id, $agentUserIds);
            })->values();
        }

        // Get lease service sets for dropdown
        $leaseServiceSets = collect();
        $defaultLeaseServiceSet = null;
        
        if ($organizationId) {
            // Get default lease service set for this organization
            $defaultLeaseServiceSet = \App\Models\LeaseServiceSet::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all lease service sets for this organization
            $leaseServiceSets = \App\Models\LeaseServiceSet::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        }

        return view('staff.asset.properties.create', compact('propertyTypes', 'provinces', 'provinces2025', 'paymentCycles', 'organizationPaymentCycle', 'managers', 'agents', 'leaseServiceSets', 'defaultLeaseServiceSet'));
    }

    /**
     * Tạo property mới
     * 
     * MỤC ĐÍCH:
     * Tạo property mới với validation, check subscription limit, tạo location (old và new system 2025), upload images, assign staff
     * 
     * INPUT:
     * - Request: name, property_type_id, description, images, total_floors, status, assigned_manager_id, assigned_agent_ids, payment_cycle_id_override, location fields (old và new system)
     * - Session: organization_id, user_id
     * - Database: property_types, geo_provinces, geo_districts, geo_wards, geo_provinces_2025, geo_wards_2025, payment_cycles, users
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "...", redirect: "..."}
     * - Database: Tạo bản ghi mới trong properties, locations, locations_2025, documents, properties_user (pivot)
     * - Storage: Upload images qua ImageService
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền tạo property
     * 2. Lấy organization_id và organization object
     * 3. Kiểm tra subscription limit (max_properties)
     * 4. Validate input (name, property_type_id, location, images, etc.)
     * 5. Validate geo relationships (district thuộc province, ward thuộc district)
     * 6. Bắt đầu transaction
     * 7. Tạo location (old system) nếu có
     * 8. Tạo location2025 (new system) nếu có
     * 9. Tạo property với location_id và location_id_2025
     * 10. Upload images qua ImageService và tạo documents
     * 11. Assign staff (managers và agents) vào properties_user pivot table
     * 12. Commit transaction
     * 13. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Lấy organization object để check subscription limit
     * - Bảng property_types: Validate property_type_id
     * - Bảng geo_provinces, geo_districts, geo_wards: Validate và lấy tên địa chỉ (old system)
     * - Bảng geo_provinces_2025, geo_wards_2025: Validate và lấy tên địa chỉ (new system)
     * - Bảng payment_cycles: Validate và lấy payment cycle
     * - Bảng users: Validate assigned_manager_id và assigned_agent_ids
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng properties: Tạo property mới
     * - Bảng locations: Tạo location (old system) nếu có
     * - Bảng locations_2025: Tạo location2025 (new system) nếu có
     * - Bảng documents: Tạo documents cho images (polymorphic)
     * - Bảng properties_user: Tạo pivot records cho assigned staff
     * - Storage: Upload images qua ImageService
     * 
     * LƯU Ý:
     * - Sử dụng transaction để đảm bảo data consistency
     * - Hỗ trợ cả old location system và new system 2025 (có thể có cả 2)
     * - Images được upload qua ImageService và lưu vào documents table (polymorphic)
     * - Subscription limit được check trước khi tạo property
     * - Assigned staff bao gồm managers và agents (exclude tenants, admin, landlord)
     */
    public function store(Request $request)
    {
        $this->requireCapability('asset.property.create', 'Bạn không có quyền tạo bất động sản.'); // Kiểm tra quyền tạo property → Dừng nếu không có quyền

        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để tạo property và check subscription limit
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON error
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $organization = \App\Models\Organization::find($organizationId); // Lấy organization object → Dùng để check subscription limit
        
        if (!$organization) { // Nếu không tìm thấy organization
            return response()->json([ // Trả về JSON error
                'success' => false,
                'message' => 'Tổ chức không tồn tại.'
            ], 403);
        }

        if (!$this->limitChecker->canAddProperty($organization)) { // Kiểm tra subscription limit → Dừng nếu vượt quá limit
            $limit = $this->limitChecker->getLimit($organization, 'max_properties'); // Lấy limit từ subscription plan → Hiển thị trong error message
            $current = $this->limitChecker->getPropertiesCount($organization); // Lấy số properties hiện tại → Hiển thị trong error message
            
            return response()->json([ // Trả về JSON error với thông tin limit
                'success' => false,
                'message' => "Bạn đã đạt giới hạn số lượng bất động sản của gói dịch vụ. Hiện tại: {$current}/{$limit}",
                'error_type' => 'subscription_limit',
            ], 403);
        }

        try {
            Log::info('Property creation request data:', $request->all()); // Ghi log request data → Để debug
            
            $validated = $request->validate([ // Validate input → Đảm bảo dữ liệu hợp lệ
                'name' => 'required|string|max:255', // name: bắt buộc, string, tối đa 255 ký tự
                'property_type_id' => 'nullable|exists:property_types,id', // property_type_id: có thể null, phải tồn tại trong property_types
                'description' => 'nullable|string', // description: có thể null, string
                'images' => 'nullable|array|max:10', // images: có thể null, array, tối đa 10 items
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Mỗi image: có thể null, phải là image, định dạng jpeg/png/jpg/gif/webp, tối đa 5120KB
                'total_floors' => 'nullable|integer|min:1', // total_floors: có thể null, integer, tối thiểu 1
                'status' => 'nullable|integer|in:0,1', // status: có thể null, integer, chỉ 0 hoặc 1
                'assigned_manager_id' => 'nullable|exists:users,id', // assigned_manager_id: có thể null, phải tồn tại trong users
                'assigned_agent_ids' => 'nullable|array', // assigned_agent_ids: có thể null, array
                'assigned_agent_ids.*' => 'exists:users,id', // Mỗi agent_id: phải tồn tại trong users
                'payment_cycle_id_override' => 'nullable|exists:payment_cycles,id', // payment_cycle_id_override: có thể null, phải tồn tại trong payment_cycles
                'province_code' => 'nullable|string|max:20', // province_code (old system): có thể null, string, tối đa 20 ký tự
                'district_code' => 'nullable|string|max:20', // district_code (old system): có thể null, string, tối đa 20 ký tự
                'ward_code' => 'nullable|string|max:20', // ward_code (old system): có thể null, string, tối đa 20 ký tự
                'street' => 'nullable|string|max:255', // street (old system): có thể null, string, tối đa 255 ký tự
                'province_code_2025' => 'nullable|string|max:20', // province_code_2025 (new system): có thể null, string, tối đa 20 ký tự
                'ward_code_2025' => 'nullable|string|max:20', // ward_code_2025 (new system): có thể null, string, tối đa 20 ký tự
                'street_2025' => 'nullable|string|max:255', // street_2025 (new system): có thể null, string, tối đa 255 ký tự
            ]);

            if ($request->filled('district_code') && $request->filled('province_code')) { // Nếu có district_code và province_code (old system)
                $district = DB::table('geo_districts') // Tìm district trong geo_districts → Validate district thuộc province
                    ->where('code', $request->district_code)
                    ->where('province_code', $request->province_code)
                    ->first();
                
                if (!$district) { // Nếu không tìm thấy district
                    return response()->json([ // Trả về JSON error → District không thuộc province
                        'success' => false,
                        'message' => 'Quận/Huyện được chọn không thuộc Tỉnh/Thành phố đã chọn. Vui lòng kiểm tra lại thông tin địa chỉ.'
                    ], 422);
                }
            }

            if ($request->filled('ward_code') && $request->filled('district_code')) { // Nếu có ward_code và district_code (old system)
                $ward = DB::table('geo_wards') // Tìm ward trong geo_wards → Validate ward thuộc district
                    ->where('code', $request->ward_code)
                    ->where('district_code', $request->district_code)
                    ->first();
                
                if (!$ward) { // Nếu không tìm thấy ward
                    return response()->json([ // Trả về JSON error → Ward không thuộc district
                        'success' => false,
                        'message' => 'Phường/Xã được chọn không thuộc Quận/Huyện đã chọn. Vui lòng kiểm tra lại thông tin địa chỉ.'
                    ], 422);
                }
            }

            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            try {
                Log::info('Starting property creation transaction'); // Ghi log bắt đầu transaction → Để debug
                
                $locationId = null; // Khởi tạo location ID (old system) → Dùng để lưu location_id vào property
                if ($request->filled('province_code')) { // Nếu có province_code (old system)
                    $province = DB::table('geo_provinces')->where('code', $request->province_code)->first(); // Lấy tên province từ geo_provinces → Dùng để lưu tên
                    $district = $request->district_code ? DB::table('geo_districts')->where('code', $request->district_code)->first() : null; // Lấy tên district nếu có → Dùng để lưu tên
                    $ward = $request->ward_code ? DB::table('geo_wards')->where('code', $request->ward_code)->first() : null; // Lấy tên ward nếu có → Dùng để lưu tên
                    $country = DB::table('geo_countries')->where('code', 'VN')->first(); // Lấy tên country → Dùng để lưu tên

                    $location = Location::create([ // Tạo location (old system) → Lưu địa chỉ cũ
                        'country_code' => 'VN', // Mã quốc gia
                        'province_code' => $request->province_code, // Mã tỉnh/thành phố
                        'district_code' => $request->district_code, // Mã quận/huyện
                        'ward_code' => $request->ward_code, // Mã phường/xã
                        'street' => $request->street, // Tên đường
                        'country' => $country->name ?? 'Việt Nam', // Tên quốc gia (lưu tên để truy vấn nhanh)
                        'city' => $province->name ?? null, // Tên tỉnh/thành phố (lưu tên để truy vấn nhanh)
                        'district' => $district->name ?? null, // Tên quận/huyện (lưu tên để truy vấn nhanh)
                        'ward' => $ward->name ?? null, // Tên phường/xã (lưu tên để truy vấn nhanh)
                    ]);
                    $locationId = $location->id; // Lưu location ID → Dùng để gán vào property
                }

                $locationId2025 = null; // Khởi tạo location ID 2025 (new system) → Dùng để lưu location_id_2025 vào property
                if ($request->filled('province_code_2025')) { // Nếu có province_code_2025 (new system)
                    $province2025 = DB::table('geo_provinces_2025')->where('code', $request->province_code_2025)->first(); // Lấy tên province từ geo_provinces_2025 → Dùng để lưu tên
                    $ward2025 = $request->ward_code_2025 ? DB::table('geo_wards_2025')->where('code', $request->ward_code_2025)->first() : null; // Lấy tên ward nếu có → Dùng để lưu tên
                    $country = DB::table('geo_countries')->where('code', 'VN')->first(); // Lấy tên country → Dùng để lưu tên

                    $location2025 = Location2025::create([ // Tạo location2025 (new system) → Lưu địa chỉ mới
                        'country_code' => 'VN', // Mã quốc gia
                        'province_code' => $request->province_code_2025, // Mã tỉnh/thành phố
                        'ward_code' => $request->ward_code_2025, // Mã phường/xã (new system không có district)
                        'street' => $request->street_2025, // Tên đường
                        'country' => $country->name ?? 'Việt Nam', // Tên quốc gia (lưu tên để truy vấn nhanh)
                        'city' => $province2025->name ?? null, // Tên tỉnh/thành phố (lưu tên để truy vấn nhanh)
                        'ward' => $ward2025->name ?? null, // Tên phường/xã (lưu tên để truy vấn nhanh)
                    ]);
                    $locationId2025 = $location2025->id; // Lưu location ID 2025 → Dùng để gán vào property
                }

                Log::info('Creating property with data:', [ // Ghi log data trước khi tạo property → Để debug
                    'organization_id' => $organizationId,
                    'name' => $validated['name'],
                    'property_type_id' => $validated['property_type_id'] ?? null,
                    'location_id' => $locationId,
                    'location_id_2025' => $locationId2025,
                    'description' => $validated['description'] ?? null,
                    'total_floors' => $validated['total_floors'] ?? null,
                    'status' => $validated['status'] ?? 1,
                ]);

                $paymentCycleId = null; // Khởi tạo payment cycle ID → Dùng để gán vào property
                if ($request->filled('payment_cycle_id_override')) { // Nếu có chọn payment cycle riêng
                    $paymentCycle = PaymentCycle::where('id', $request->payment_cycle_id_override) // Tìm payment cycle được chọn → Dùng payment cycle riêng của property
                        ->where('organization_id', $organizationId) // Chỉ lấy payment cycle của organization
                        ->first();
                    if ($paymentCycle) { // Nếu tìm thấy
                        $paymentCycleId = $paymentCycle->id; // Lưu payment cycle ID → Dùng payment cycle riêng
                    }
                } else { // Nếu không chọn payment cycle riêng
                    $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId) // Tìm default payment cycle của organization → Fallback về default
                        ->where('is_default', true)
                        ->first();
                    if ($defaultPaymentCycle) { // Nếu có default payment cycle
                        $paymentCycleId = $defaultPaymentCycle->id; // Lưu default payment cycle ID → Dùng default của organization
                    }
                }

                $leaseServiceSetId = null; // Khởi tạo lease service set ID → Dùng để gán vào property
                if ($request->filled('lease_services_id')) { // Nếu có chọn lease service set riêng
                    $leaseServiceSet = LeaseServiceSet::where('id', $request->lease_services_id) // Tìm lease service set được chọn → Dùng lease service set riêng của property
                        ->where(function($query) use ($organizationId) { // Filter theo organization hoặc global
                            $query->where('organization_id', $organizationId) // Lease service set của organization
                                  ->orWhereNull('organization_id'); // Hoặc global lease service set
                        })
                        ->first();
                    if ($leaseServiceSet) { // Nếu tìm thấy
                        $leaseServiceSetId = $leaseServiceSet->id; // Lưu lease service set ID → Dùng lease service set riêng
                    }
                } elseif (!empty($validated['services']) && is_array($validated['services'])) { // Nếu có services array (tùy chỉnh)
                    $leaseServiceSetId = LeaseServiceSet::findOrCreateMatching( // Tìm hoặc tạo lease service set từ services → Tạo lease service set tùy chỉnh
                        $validated['services'], // Danh sách services
                        $organizationId, // Organization ID
                        'Dịch vụ BĐS: ' . $validated['name'], // Tên lease service set
                        'Dịch vụ tùy chỉnh cho bất động sản' // Mô tả
                    );
                } else { // Nếu không chọn lease service set riêng
                    $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId) // Tìm default lease service set của organization → Fallback về default
                        ->where('is_default', true)
                        ->first();
                    if ($defaultLeaseServiceSet) { // Nếu có default lease service set
                        $leaseServiceSetId = $defaultLeaseServiceSet->id; // Lưu default lease service set ID → Dùng default của organization
                    }
                }

                $property = Property::create([ // Tạo property mới → Lưu property vào database
                    'organization_id' => $organizationId, // Organization ID
                    'name' => $validated['name'], // Tên property
                    'property_type_id' => $validated['property_type_id'] ?? null, // Property type ID
                    'location_id' => $locationId, // Location ID (old system)
                    'location_id_2025' => $locationId2025, // Location ID 2025 (new system)
                    'description' => $validated['description'] ?? null, // Mô tả
                    'total_floors' => $validated['total_floors'] ?? null, // Tổng số tầng
                    'status' => $validated['status'] ?? 1, // Trạng thái (mặc định: 1 = active)
                    'payment_cycle_id' => $paymentCycleId, // Payment cycle ID
                    'lease_services_id' => $leaseServiceSetId, // Lease service set ID
                ]);

                if ($request->hasFile('images')) { // Nếu có upload images
                    try {
                        $uploadedImages = $this->imageService->uploadMultipleImages($request->file('images'), 'properties'); // Upload nhiều images qua ImageService → Lưu images vào storage
                        $sortOrder = 0; // Khởi tạo sort order → Dùng để đánh số thứ tự images
                        foreach ($uploadedImages as $uploadedImage) { // Loop qua từng image đã upload
                            $fileUrl = $uploadedImage['original']; // Lấy file URL từ ImageService → ImageService đã trả về path đúng format (không có storage/ prefix)
                            
                            $document = Document::create([ // Tạo document cho image → Lưu thông tin image vào documents table (polymorphic)
                                'owner_type' => Property::class, // Owner type là Property → Polymorphic relationship
                                'owner_id' => $property->id, // Owner ID là property ID
                                'file_url' => $fileUrl, // File URL
                                'file_name' => basename($fileUrl), // File name (chỉ tên file)
                                'mime_type' => $uploadedImage['mime_type'] ?? 'image/jpeg', // MIME type
                                'file_size' => $uploadedImage['size'] ?? null, // File size
                                'document_type' => 'image', // Document type là 'image'
                                'is_primary' => $sortOrder === 0, // Image đầu tiên là primary → Dùng để hiển thị ảnh chính
                                'uploaded_by' => Auth::id(), // User upload image
                            ]);
                            
                            $sortOrder++; // Tăng sort order → Image tiếp theo
                        }
                    } catch (\Exception $e) { // Nếu có lỗi khi upload images
                        Log::error('Error uploading images: ' . $e->getMessage()); // Ghi log lỗi → Không fail toàn bộ request, chỉ log lỗi
                    }
                }
                
                if ($request->filled('assigned_manager_id')) { // Nếu có gán manager
                    $managerId = $request->assigned_manager_id; // Lấy manager ID
                    $manager = User::whereHas('organizationUsers', function($q) use ($organizationId, $managerId) { // Verify manager thuộc organization → Bảo mật: chỉ gán manager của organization
                        $q->where('organization_id', $organizationId) // Manager phải thuộc organization
                          ->where('user_id', $managerId) // User ID phải khớp
                          ->whereNull('deleted_at'); // Chưa bị xóa
                    })->first();
                    
                    if ($manager) { // Nếu tìm thấy manager
                        $property->assignedUsers()->attach($managerId, [ // Gán manager vào property → Lưu vào pivot table properties_user
                            'role_key' => 'manager', // Role key là 'manager'
                            'assigned_at' => now(), // Thời gian gán
                            'updated_by' => Auth::id(), // User thực hiện gán
                            'created_at' => now(), // Created at
                            'updated_at' => now(), // Updated at
                        ]);
                    }
                }
                
                if ($request->filled('assigned_agent_ids') && is_array($request->assigned_agent_ids)) { // Nếu có gán agents
                    foreach ($request->assigned_agent_ids as $agentId) { // Loop qua từng agent ID
                        $agent = User::whereHas('organizationUsers', function($q) use ($organizationId, $agentId) { // Verify agent thuộc organization → Bảo mật: chỉ gán agent của organization
                            $q->where('organization_id', $organizationId) // Agent phải thuộc organization
                              ->where('user_id', $agentId) // User ID phải khớp
                              ->whereNull('deleted_at'); // Chưa bị xóa
                        })->first();
                        
                        if ($agent) { // Nếu tìm thấy agent
                            $property->assignedUsers()->attach($agentId, [ // Gán agent vào property → Lưu vào pivot table properties_user
                                'role_key' => 'agent', // Role key là 'agent'
                                'assigned_at' => now(), // Thời gian gán
                                'updated_by' => Auth::id(), // User thực hiện gán
                                'created_at' => now(), // Created at
                                'updated_at' => now(), // Updated at
                            ]);
                        }
                    }
                }
                
                Log::info('Property created successfully with ID: ' . $property->id); // Ghi log thành công → Để tracking

                DB::commit(); // Commit transaction → Lưu tất cả thay đổi

                return response()->json([ // Trả về JSON success
                    'success' => true,
                    'message' => 'Bất động sản đã được tạo thành công!',
                    'property_id' => $property->id // Property ID để redirect
                ]);
            } catch (\Exception $e) { // Nếu có lỗi trong transaction
                DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi
                Log::error('Error in property creation transaction: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Throw lại exception → Để catch bên ngoài xử lý
            }
        } catch (\Illuminate\Validation\ValidationException $e) { // Nếu có lỗi validation
            Log::error('Validation error in property creation: ' . implode(', ', $e->validator->errors()->all())); // Ghi log lỗi validation → Để debug
            return response()->json([ // Trả về JSON error với validation errors
                'success' => false,
                'message' => 'Thông tin bất động sản không hợp lệ: ' . implode(', ', $e->validator->errors()->all()) . '. Vui lòng kiểm tra lại và thử lại.'
            ], 422);
        } catch (\Exception $e) { // Nếu có lỗi khác
            Log::error('General error in property creation: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([ // Trả về JSON error với thông báo lỗi hệ thống
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.'
            ], 500);
        }
    }

    /**
     * Hiển thị chi tiết property
     * 
     * MỤC ĐÍCH:
     * Hiển thị chi tiết property với đầy đủ thông tin (property type, location, units, master leases, assigned staff, images, payment cycle, lease service set)
     * 
     * INPUT:
     * - Route parameter: id (property ID)
     * - Session: organization_id, user_id
     * - Database: properties, property_types, locations, locations_2025, units, master_leases, users, payment_cycles, lease_service_sets, documents
     * 
     * OUTPUT:
     * - View: staff.asset.properties.show
     * - Data: property (với đầy đủ relationships)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền xem property
     * 2. Lấy organization_id
     * 3. Load property với relationships (propertyType, location, location2025, masterLeases.landlord, units, paymentCycle, leaseServiceSet.items.service)
     * 4. Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem assigned properties
     * 5. Trả về view với property data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy property theo ID
     * - Bảng property_types: Lấy loại BĐS
     * - Bảng locations, locations_2025: Lấy địa chỉ
     * - Bảng units: Lấy danh sách phòng
     * - Bảng master_leases, users: Lấy thông tin chủ nhà
     * - Bảng payment_cycles: Lấy chu kỳ thanh toán
     * - Bảng lease_service_sets: Lấy bộ dịch vụ hợp đồng
     * - Bảng documents: Lấy hình ảnh
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Manager có thể xem tất cả properties trong organization
     * - Agent chỉ có thể xem assigned properties
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và ownership
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter property
        
        $this->requireCapability('asset.property.view', 'Bạn không có quyền xem bất động sản.'); // Kiểm tra quyền xem property → Dừng nếu không có quyền
        
        $property = Property::where('organization_id', $organizationId) // Tìm property theo organization ID → Chỉ lấy property của organization hiện tại
            ->with([ // Eager load relationships → Tối ưu query, tránh N+1 problem
                'propertyType', // Loại BĐS
                'location', // Địa chỉ (old system)
                'location2025', // Địa chỉ (new system 2025)
                'masterLeases.landlord', // Master leases với landlord
                'units', // Danh sách phòng
                'paymentCycle', // Chu kỳ thanh toán
                'leaseServiceSet.items.service' // Bộ dịch vụ hợp đồng với items và services
            ])
            ->findOrFail($id); // Tìm property theo ID, nếu không tìm thấy thì 404
        
        $canViewAll = $this->canViewAll('asset.property'); // Kiểm tra user có quyền xem tất cả properties không → Manager: true, Agent: false
        if (!$canViewAll) { // Nếu user không thể xem tất cả (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách assigned property IDs → Dùng để kiểm tra
            if (!$assignedPropertyIds->contains($property->id)) { // Nếu property không nằm trong danh sách assigned
                abort(403, 'Bạn không có quyền xem bất động sản này.'); // Dừng request và trả về lỗi 403 → Agent chỉ xem assigned properties
            }
        }
        
        return view('staff.asset.properties.show', compact('property')); // Trả về view với property data → Hiển thị chi tiết property
    }

    /**
     * Hiển thị form chỉnh sửa property
     * 
     * MỤC ĐÍCH:
     * Hiển thị form chỉnh sửa property với đầy đủ dropdowns và data hiện tại. Chỉ cho phép chỉnh sửa property có status = 0 (inactive).
     * 
     * INPUT:
     * - Route parameter: id (property ID)
     * - Session: organization_id, user_id
     * - Database: properties, property_types, geo_provinces, geo_districts, geo_wards, geo_provinces_2025, geo_wards_2025, payment_cycles, lease_service_sets, users, roles, documents
     * 
     * OUTPUT:
     * - View: staff.asset.properties.edit
     * - Data: property, propertyTypes, provinces, provinces2025, districts, wards, wards2025, leaseServiceSets, defaultLeaseServiceSet, paymentCycles, organizationPaymentCycle, managers, agents, assignedManagerId, assignedAgentIds
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền cập nhật property
     * 2. Lấy organization_id
     * 3. Load property với relationships (location, location2025, propertyType, masterLeases.landlord, documents)
     * 4. Kiểm tra property status: Nếu active (status = 1) thì redirect về show page với warning
     * 5. Load property types của organization
     * 6. Load geo data (old và new system 2025) và cascading dropdowns (districts, wards, wards2025) dựa trên location hiện tại
     * 7. Load payment cycles và lease service sets của organization
     * 8. Load staff users (managers và agents, exclude tenants/admin/landlord)
     * 9. Lấy assigned manager và agents hiện tại của property
     * 10. Trả về view với data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy property theo ID
     * - Bảng property_types: Lấy property types của organization
     * - Bảng geo_provinces, geo_districts, geo_wards: Lấy geo data (old system) và cascading dropdowns
     * - Bảng geo_provinces_2025, geo_wards_2025: Lấy geo data (new system 2025) và cascading dropdowns
     * - Bảng payment_cycles: Lấy payment cycles của organization
     * - Bảng lease_service_sets: Lấy lease service sets của organization
     * - Bảng users, organization_users, roles: Lấy staff users (managers và agents)
     * - Bảng properties_user: Lấy assigned manager và agents hiện tại
     * - Bảng documents: Lấy hình ảnh hiện tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Chỉ cho phép chỉnh sửa property có status = 0 (inactive)
     * - Property có status = 1 (active) sẽ redirect về show page với warning
     * - Cascading dropdowns được load dựa trên location hiện tại của property
     */
    public function edit($id)
    {
        $this->requireCapability('asset.property.update', 'Bạn không có quyền cập nhật bất động sản.'); // Kiểm tra quyền cập nhật property → Dừng nếu không có quyền

        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter property
        $property = Property::where('organization_id', $organizationId) // Tìm property theo organization ID → Chỉ lấy property của organization hiện tại
            ->with([ // Eager load relationships → Tối ưu query, tránh N+1 problem
                'location', // Địa chỉ (old system)
                'location2025', // Địa chỉ (new system 2025)
                'propertyType', // Loại BĐS
                'masterLeases.landlord', // Master leases với landlord
                'documents' => function($query) { // Documents với filter chỉ lấy images → Dùng để hiển thị hình ảnh trong form
                    $query->where('document_type', 'image') // Chỉ lấy hình ảnh
                          ->orderBy('sort_order') // Sắp xếp theo sort_order
                          ->orderBy('created_at'); // Sắp xếp theo created_at
                }
            ])
            ->findOrFail($id); // Tìm property theo ID, nếu không tìm thấy thì 404
        
        if ($property->status == 1) { // Nếu property đang active (status = 1)
            return redirect()->route('staff.properties.show', $id) // Redirect về show page → Không cho phép chỉnh sửa property active
                ->with('warning', 'Không thể chỉnh sửa bất động sản đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Tạm ngưng) trước khi chỉnh sửa.');
        }
        
        if (!$organizationId) { // Nếu không có organization ID
            abort(403, 'Bạn phải thuộc một tổ chức để chỉnh sửa bất động sản.'); // Dừng request và trả về lỗi 403
        }
            
        $propertyTypes = PropertyType::where('organization_id', $organizationId)->get(); // Lấy property types của organization → Dùng cho dropdown, chỉ lấy types của organization hiện tại

        // Get both old and new geo data
        $provinces = GeoProvince::where('country_code', 'VN')->get();
        $provinces2025 = GeoProvince2025::where('country_code', 'VN')->get();
        
        $districts = collect();
        $wards = collect();
        $wards2025 = collect();

        if ($property->location) {
            if ($property->location->province_code) {
                $districts = DB::table('geo_districts')
                    ->where('province_code', $property->location->province_code)
                    ->get();
            }
            if ($property->location->district_code) {
                $wards = DB::table('geo_wards')
                    ->where('district_code', $property->location->district_code)
                    ->get();
            }
        }

        if ($property->location2025) {
            if ($property->location2025->province_code) {
                $wards2025 = DB::table('geo_wards_2025')
                    ->where('district_code', $property->location2025->province_code)
                    ->get();
            }
        }

        // Get lease service sets for dropdown
        $leaseServiceSets = collect();
        $defaultLeaseServiceSet = null;
        
        if ($organizationId) {
            // Get default lease service set for this organization
            $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all lease service sets for this organization
            $leaseServiceSets = LeaseServiceSet::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        }

        // Get organization payment cycles for dropdown
        $paymentCycles = collect();
        $organizationPaymentCycle = null;
        
        if ($organizationId) {
            // Get default payment cycle for this organization
            $organizationPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all payment cycles for this organization
            $paymentCycles = PaymentCycle::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        }

        // Get all staff users from organization (managers and agents, EXCLUDE tenants) - same as create
        $allUsers = collect();
        $managers = collect();
        $agents = collect();
        $assignedManagerId = null;
        $assignedAgentIds = collect();
        
        if ($organizationId) {
            // Get staff users only (exclude tenants) - same logic as create
            $allUsers = User::with('userProfile')
                ->whereHas('organizationUsers', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId)
                      ->where('status', 'active')
                      ->whereNull('deleted_at');
                })
                ->whereDoesntHave('userRoles', function($q) {
                    $q->where('key_code', 'tenant'); // Exclude tenants
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->full_name ?? '';
                })->values();
            
            // Get manager role IDs
            $managerIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'manager')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            // Separate managers and agents (both excluding tenants) - same logic as create
            $managers = $allUsers->filter(function($user) use ($managerIds) {
                return in_array($user->id, $managerIds);
            })->values();
            
            // For agents: filter to only users with role_id = 3 (agent role)
            // Get user IDs that have role_id = 3 in this organization
            $agentUserIds = DB::table('organization_users')
                ->where('organization_id', $organizationId)
                ->where('role_id', 3) // Role ID = 3 for agent
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->pluck('user_id')
                ->unique()
                ->toArray();
            
            // Get role IDs for admin and landlord to exclude
            $adminRoleId = DB::table('roles')->where('key_code', 'admin')->value('id');
            $landlordRoleId = DB::table('roles')->where('key_code', 'landlord')->value('id');
            
            // Get user IDs that have admin or landlord role in this organization (to exclude)
            $excludedUserIds = [];
            if ($adminRoleId || $landlordRoleId) {
                $excludedQuery = DB::table('organization_users')
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at');
                
                if ($adminRoleId && $landlordRoleId) {
                    $excludedQuery->whereIn('role_id', [$adminRoleId, $landlordRoleId]);
                } elseif ($adminRoleId) {
                    $excludedQuery->where('role_id', $adminRoleId);
                } elseif ($landlordRoleId) {
                    $excludedQuery->where('role_id', $landlordRoleId);
                }
                
                $excludedUserIds = $excludedQuery->pluck('user_id')->unique()->toArray();
            }
            
            // Filter agents: users that are not managers AND have role_id = 3 AND not admin/landlord
            $agents = $allUsers->filter(function($user) use ($managerIds, $agentUserIds, $excludedUserIds) {
                // Must not be a manager
                if (in_array($user->id, $managerIds)) {
                    return false;
                }
                // Must not be admin or landlord
                if (in_array($user->id, $excludedUserIds)) {
                    return false;
                }
                // Must have role_id = 3
                return in_array($user->id, $agentUserIds);
            })->values();
            
            // Get currently assigned manager for this property
            $assignedManager = $property->getPrimaryManager();
            if ($assignedManager) {
                $assignedManagerId = $assignedManager->id;
            }
            
            // Get currently assigned agents for this property
            $assignedAgentIds = $property->assignedUsers()
                ->wherePivot('role_key', 'agent')
                ->pluck('users.id');
        }

        return view('staff.asset.properties.edit', compact('property', 'propertyTypes', 'provinces', 'provinces2025', 'districts', 'wards', 'wards2025', 'leaseServiceSets', 'defaultLeaseServiceSet', 'paymentCycles', 'organizationPaymentCycle', 'managers', 'agents', 'assignedManagerId', 'assignedAgentIds', 'organizationId'));
    }

    /**
     * Cập nhật property
     * 
     * MỤC ĐÍCH:
     * Cập nhật property với validation, security checks, update location (old và new system 2025), upload/delete images, sync assigned staff.
     * Chỉ cho phép cập nhật property có status = 0 (inactive).
     * 
     * INPUT:
     * - Route parameter: id (property ID)
     * - Request: name, property_type_id, description, images, deleted_image_ids, total_floors, status, assigned_manager_id, assigned_agent_ids, payment_cycle_id, lease_services_id, location fields (old và new system)
     * - Session: organization_id, user_id
     * - Database: properties, property_types, geo_provinces, geo_districts, geo_wards, geo_provinces_2025, geo_wards_2025, payment_cycles, lease_service_sets, users, documents
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "...", redirect: "..."}
     * - Database: Cập nhật bản ghi trong properties, locations, locations_2025, documents, properties_user (pivot)
     * - Storage: Upload/delete images qua ImageService
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền cập nhật property
     * 2. Lấy organization_id và property
     * 3. Kiểm tra property status: Nếu active (status = 1) thì block tất cả updates
     * 4. Security check: Prevent organization ownership manipulation
     * 5. Validate input (name, property_type_id, location, images, etc.)
     * 6. Validate geo relationships (district thuộc province, ward thuộc district)
     * 7. Bắt đầu transaction
     * 8. Update hoặc create location (old system) nếu có
     * 9. Update hoặc create location2025 (new system) nếu có
     * 10. Soft delete images được đánh dấu xóa
     * 11. Upload images mới qua ImageService và tạo documents
     * 12. Handle lease service set (selected, custom, hoặc default)
     * 13. Handle payment cycle (chỉ cho phép update khi status = 0)
     * 14. Update property với updateData
     * 15. Sync assigned manager (remove old, add new)
     * 16. Sync assigned agents (remove old, add new)
     * 17. Commit transaction
     * 18. Trả về JSON success với redirect URL
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy property theo ID
     * - Bảng property_types: Validate property_type_id
     * - Bảng geo_provinces, geo_districts, geo_wards: Validate và lấy tên địa chỉ (old system)
     * - Bảng geo_provinces_2025, geo_wards_2025: Validate và lấy tên địa chỉ (new system)
     * - Bảng payment_cycles: Validate và lấy payment cycle
     * - Bảng lease_service_sets: Validate và lấy lease service set
     * - Bảng users: Validate assigned_manager_id và assigned_agent_ids
     * - Bảng documents: Lấy images hiện tại để xóa
     * - Bảng properties_user: Lấy assigned manager và agents hiện tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng properties: Cập nhật property
     * - Bảng locations: Update hoặc create location (old system)
     * - Bảng locations_2025: Update hoặc create location2025 (new system)
     * - Bảng documents: Soft delete images được đánh dấu xóa, create documents cho images mới
     * - Bảng properties_user: Sync assigned staff (managers và agents)
     * - Storage: Upload/delete images qua ImageService
     * 
     * LƯU Ý:
     * - Sử dụng transaction để đảm bảo data consistency
     * - Chỉ cho phép cập nhật property có status = 0 (inactive)
     * - Security check: Prevent organization ownership manipulation (organization_id, org_id, owner_id)
     * - Payment cycle chỉ có thể update khi status = 0
     * - Images được soft delete (có thể khôi phục)
     * - Assigned staff được sync (remove old, add new, restore nếu soft deleted)
     */
    public function update(Request $request, $id)
    {
        $this->requireCapability('asset.property.update', 'Bạn không có quyền cập nhật bất động sản.'); // Kiểm tra quyền cập nhật property → Dừng nếu không có quyền

        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter property
        $property = Property::where('organization_id', $organizationId)->findOrFail($id); // Tìm property theo organization ID và ID → Chỉ lấy property của organization hiện tại

        if ($property->status == 1) { // Nếu property đang active (status = 1)
            return response()->json([ // Trả về JSON error → Block tất cả updates khi property active
                'success' => false,
                'message' => 'Không thể cập nhật bất động sản đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Tạm ngưng) từ trang chi tiết trước khi chỉnh sửa.'
            ], 422);
        }

        // SECURITY CHECK: Prevent organization ownership manipulation
        // Note: user_organization_id is allowed as it may be used for legitimate purposes
        $dangerousFields = ['organization_id', 'org_id', 'owner_id'];
        $suspiciousFields = [];
        
        // Log all request keys for debugging
        Log::debug('Property Update - Request keys check', [
            'all_keys' => array_keys($request->all()),
            'dangerous_fields_check' => array_map(function($field) use ($request) {
                return [
                    'field' => $field,
                    'has' => $request->has($field),
                    'filled' => $request->filled($field),
                    'value' => $request->input($field)
                ];
            }, $dangerousFields)
        ]);
        
        foreach ($dangerousFields as $field) {
            // Check if field exists and has a non-empty value
            if ($request->has($field) && !empty($request->input($field))) {
                $suspiciousFields[] = $field;
            }
        }
        
        if (!empty($suspiciousFields)) {
            Log::critical('SECURITY ALERT: Attempted property ownership manipulation', [
                'property_id' => $id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'suspicious_fields' => $suspiciousFields,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu không hợp lệ. Hành động đã được ghi nhận.'
            ], 403);
        }

        try {
            // Debug: Log request data
            Log::info('Property Update Request', [
                'property_id' => $id,
                'request_data' => $request->all(),
                'files' => $request->hasFile('images') ? count($request->file('images')) : 0
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'property_type_id' => 'nullable|exists:property_types,id',
                'description' => 'nullable|string',
                'images' => 'nullable|array|max:10',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'total_floors' => 'nullable|integer|min:1',
                'status' => 'nullable|integer|in:0,1',
                'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
                'lease_services_id' => 'nullable|exists:lease_service_sets,id',
                'assigned_manager_id' => 'nullable|exists:users,id',
                'assigned_agent_ids' => 'nullable|array',
                'assigned_agent_ids.*' => 'exists:users,id',
                'services' => 'nullable|array',
                'services.*.service_id' => 'required_with:services|exists:services,id',
                'services.*.price' => 'required_with:services|numeric|min:0',
                // Old location fields
                'province_code' => 'nullable|string|max:20',
                'district_code' => 'nullable|string|max:20',
                'ward_code' => 'nullable|string|max:20',
                'street' => 'nullable|string|max:255',
                // New location fields
                'province_code_2025' => 'nullable|string|max:20',
                'ward_code_2025' => 'nullable|string|max:20',
                'street_2025' => 'nullable|string|max:255',
            ]);

            // Additional validation for geo relationships
            if ($request->filled('district_code') && $request->filled('province_code')) {
                $district = DB::table('geo_districts')
                    ->where('code', $request->district_code)
                    ->where('province_code', $request->province_code)
                    ->first();
                
                if (!$district) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quận/Huyện được chọn không thuộc Tỉnh/Thành phố đã chọn. Vui lòng kiểm tra lại thông tin địa chỉ.'
                    ], 422);
                }
            }

            if ($request->filled('ward_code') && $request->filled('district_code')) {
                $ward = DB::table('geo_wards')
                    ->where('code', $request->ward_code)
                    ->where('district_code', $request->district_code)
                    ->first();
                
                if (!$ward) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phường/Xã được chọn không thuộc Quận/Huyện đã chọn. Vui lòng kiểm tra lại thông tin địa chỉ.'
                    ], 422);
                }
            }

            DB::beginTransaction();
            try {
                // Update or create old location
                if ($request->filled('province_code')) {
                    // Get names from geo tables
                    $province = DB::table('geo_provinces')->where('code', $request->province_code)->first();
                    $district = $request->district_code ? DB::table('geo_districts')->where('code', $request->district_code)->first() : null;
                    $ward = $request->ward_code ? DB::table('geo_wards')->where('code', $request->ward_code)->first() : null;
                    $country = DB::table('geo_countries')->where('code', 'VN')->first();

                    if ($property->location_id) {
                        $property->location->update([
                            'province_code' => $request->province_code,
                            'district_code' => $request->district_code,
                            'ward_code' => $request->ward_code,
                            'street' => $request->street,
                            // Update names for quick access
                            'country' => $country->name ?? 'Việt Nam',
                            'city' => $province->name ?? null,
                            'district' => $district->name ?? null,
                            'ward' => $ward->name ?? null,
                        ]);
                    } else {
                        $location = Location::create([
                            'country_code' => 'VN',
                            'province_code' => $request->province_code,
                            'district_code' => $request->district_code,
                            'ward_code' => $request->ward_code,
                            'street' => $request->street,
                            // Store names for quick access
                            'country' => $country->name ?? 'Việt Nam',
                            'city' => $province->name ?? null,
                            'district' => $district->name ?? null,
                            'ward' => $ward->name ?? null,
                        ]);
                        $property->location_id = $location->id;
                    }
                }

                // Update or create new location
                if ($request->filled('province_code_2025')) {
                    // Get names from geo tables
                    $province2025 = DB::table('geo_provinces_2025')->where('code', $request->province_code_2025)->first();
                    $ward2025 = $request->ward_code_2025 ? DB::table('geo_wards_2025')->where('code', $request->ward_code_2025)->first() : null;
                    $country = DB::table('geo_countries')->where('code', 'VN')->first();

                    if ($property->location_id_2025) {
                        $property->location2025->update([
                            'province_code' => $request->province_code_2025,
                            'ward_code' => $request->ward_code_2025,
                            'street' => $request->street_2025,
                            // Update names for quick access
                            'country' => $country->name ?? 'Việt Nam',
                            'city' => $province2025->name ?? null,
                            'ward' => $ward2025->name ?? null,
                        ]);
                    } else {
                        $location2025 = Location2025::create([
                            'country_code' => 'VN',
                            'province_code' => $request->province_code_2025,
                            'ward_code' => $request->ward_code_2025,
                            'street' => $request->street_2025,
                            // Store names for quick access
                            'country' => $country->name ?? 'Việt Nam',
                            'city' => $province2025->name ?? null,
                            'ward' => $ward2025->name ?? null,
                        ]);
                        $property->location_id_2025 = $location2025->id;
                    }
                }

                // Process images - images are now stored in documents table
                // Soft delete marked images from documents (chỉ xóa khi submit form)
                $deletedImagesCount = 0;
                if ($request->has('deleted_image_ids') && is_array($request->deleted_image_ids)) {
                    try {
                        foreach ($request->deleted_image_ids as $documentId) {
                            $document = Document::find($documentId);
                            // Kiểm tra document thuộc về property này
                            if ($document && 
                                $document->owner_type === Property::class && 
                                $document->owner_id == $property->id &&
                                $document->document_type === 'image') {
                                // Soft delete document (sử dụng trait HasSoftDeletesWithUser nếu có)
                                if (method_exists($document, 'delete')) {
                                    $document->delete(); // Soft delete nếu model có SoftDeletes trait
                                    $deletedImagesCount++;
                                } else {
                                    // Fallback: hard delete nếu không có soft delete
                                    // Xóa file từ storage (lưu trực tiếp vào public/storage)
                                    $fullPath = public_path('storage/' . $document->file_url);
                                    if (file_exists($fullPath)) {
                                        @unlink($fullPath);
                                    }
                                    $document->delete();
                                    $deletedImagesCount++;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error deleting images: ' . $e->getMessage());
                        // Don't fail the entire request if image deletion fails
                    }
                }
                
                // Upload new images and save to documents
                if ($request->hasFile('images')) {
                    try {
                        // Get current max sort_order
                        $currentMaxSort = $property->documents()
                            ->where('document_type', 'image')
                            ->max('sort_order') ?? -1;
                        
                        $uploadedImages = $this->imageService->uploadMultipleImages($request->file('images'), 'properties');
                        $sortOrder = $currentMaxSort + 1;
                        
                        foreach ($uploadedImages as $uploadedImage) {
                            // ImageService đã trả về path đúng format (không có storage/ prefix)
                            $fileUrl = $uploadedImage['original'];
                            
                            $document = Document::create([
                                'owner_type' => Property::class,
                                'owner_id' => $property->id,
                                'file_url' => $fileUrl,
                                'file_name' => basename($fileUrl),
                                'mime_type' => $uploadedImage['mime_type'] ?? 'image/jpeg',
                                'file_size' => $uploadedImage['size'] ?? null,
                                'document_type' => 'image',
                                'is_primary' => false, // Don't override existing primary
                                'uploaded_by' => Auth::id(),
                            ]);
                            
                            // Attach document to property
                            // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                            $sortOrder++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error uploading images: ' . $e->getMessage());
                        // Don't fail the entire request if image upload fails
                    }
                }

                // Handle lease service set
                $leaseServiceSetId = null;
                if ($request->filled('lease_services_id')) {
                    // Use selected lease service set
                    $leaseServiceSet = LeaseServiceSet::where('id', $request->lease_services_id)
                        ->where(function($query) use ($organizationId) {
                            $query->where('organization_id', $organizationId)
                                  ->orWhereNull('organization_id');
                        })
                        ->first();
                    if ($leaseServiceSet) {
                        $leaseServiceSetId = $leaseServiceSet->id;
                    }
                } elseif (!empty($validated['services']) && is_array($validated['services'])) {
                    // Find or create matching lease service set from services
                    $leaseServiceSetId = LeaseServiceSet::findOrCreateMatching(
                        $validated['services'],
                        $organizationId,
                        'Dịch vụ BĐS: ' . $validated['name'],
                        'Dịch vụ tùy chỉnh cho bất động sản'
                    );
                } else {
                    // Use organization's default lease service set if available
                    $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                        ->where('is_default', true)
                        ->first();
                    if ($defaultLeaseServiceSet) {
                        $leaseServiceSetId = $defaultLeaseServiceSet->id;
                    }
                }

                // Handle payment cycle - only allow update when status is 0 (draft/tạm ngưng)
                $paymentCycleId = $property->payment_cycle_id; // Keep current value by default
                $currentStatus = $property->status;
                $newStatus = $validated['status'] ?? $currentStatus;
                
                // Only allow payment cycle update when status is 0 (draft/tạm ngưng)
                if ($newStatus == 0) {
                    if ($request->filled('payment_cycle_id')) {
                        // Validate payment cycle belongs to organization
                        $paymentCycle = PaymentCycle::where('id', $request->payment_cycle_id)
                            ->where('organization_id', $organizationId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($paymentCycle) {
                            $paymentCycleId = $paymentCycle->id;
                        }
                    } else {
                        // Use organization's default payment cycle if available
                        $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
                            ->where('is_default', true)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($defaultPaymentCycle) {
                            $paymentCycleId = $defaultPaymentCycle->id;
                        }
                    }
                } else {
                    // If trying to change payment cycle when status is active, reject it
                    if ($request->filled('payment_cycle_id') && $request->payment_cycle_id != $property->payment_cycle_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Không thể thay đổi chu kỳ thanh toán khi bất động sản đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp trước.'
                        ], 422);
                    }
                }

                // Prepare update data
                $updateData = [
                    'name' => $validated['name'],
                    'property_type_id' => $validated['property_type_id'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'total_floors' => $validated['total_floors'] ?? null,
                    'status' => $newStatus,
                    'lease_services_id' => $leaseServiceSetId,
                ];
                
                // Only update payment_cycle_id if status is 0 (draft)
                if ($newStatus == 0) {
                    $updateData['payment_cycle_id'] = $paymentCycleId;
                }
                
                $property->update($updateData);

                // Update assigned manager
                if ($request->has('assigned_manager_id')) {
                    $newManagerId = $request->input('assigned_manager_id');
                    
                    // Get current assigned manager
                    $currentManager = $property->getPrimaryManager();
                    $currentManagerId = $currentManager ? $currentManager->id : null;
                    
                    if ($newManagerId != $currentManagerId) {
                        // Remove old manager if exists
                        if ($currentManagerId) {
                            DB::table('properties_user')
                                ->where('property_id', $property->id)
                                ->where('user_id', $currentManagerId)
                                ->where('role_key', 'manager')
                                ->whereNull('deleted_at')
                                ->update([
                                    'deleted_at' => now(),
                                    'deleted_by' => Auth::id(),
                                    'updated_at' => now(),
                                    'updated_by' => Auth::id(),
                                ]);
                        }
                        
                        // Add new manager if provided
                        if ($newManagerId) {
                            // Verify manager belongs to organization
                            $manager = User::whereHas('organizationUsers', function($q) use ($organizationId, $newManagerId) {
                                $q->where('organization_id', $organizationId)
                                  ->where('user_id', $newManagerId)
                                  ->whereNull('deleted_at');
                            })->first();
                            
                            if ($manager) {
                                // Check if relationship already exists (soft deleted)
                                $existing = DB::table('properties_user')
                                    ->where('property_id', $property->id)
                                    ->where('user_id', $newManagerId)
                                    ->where('role_key', 'manager')
                                    ->first();
                                
                                if ($existing) {
                                    // Restore if soft deleted
                                    DB::table('properties_user')
                                        ->where('property_id', $property->id)
                                        ->where('user_id', $newManagerId)
                                        ->where('role_key', 'manager')
                                        ->update([
                                            'deleted_at' => null,
                                            'deleted_by' => null,
                                            'updated_at' => now(),
                                            'updated_by' => Auth::id(),
                                        ]);
                                } else {
                                    // Create new relationship
                                    DB::table('properties_user')->insert([
                                        'property_id' => $property->id,
                                        'user_id' => $newManagerId,
                                        'role_key' => 'manager',
                                        'assigned_at' => now(),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Update assigned agents
                if ($request->has('assigned_agent_ids')) {
                    $newAgentIds = $request->input('assigned_agent_ids', []);
                    
                    // Get current assigned agent IDs
                    $currentAgentIds = $property->assignedUsers()
                        ->wherePivot('role_key', 'agent')
                        ->pluck('users.id')
                        ->toArray();
                    
                    // Find agents to remove (in current but not in new)
                    $agentsToRemove = array_diff($currentAgentIds, $newAgentIds);
                    
                    // Find agents to add (in new but not in current)
                    $agentsToAdd = array_diff($newAgentIds, $currentAgentIds);
                    
                    // Remove agents that are no longer assigned
                    if (!empty($agentsToRemove)) {
                        DB::table('properties_user')
                            ->where('property_id', $property->id)
                            ->whereIn('user_id', $agentsToRemove)
                            ->where('role_key', 'agent')
                            ->whereNull('deleted_at')
                            ->update([
                                'deleted_at' => now(),
                                'deleted_by' => Auth::id(),
                                'updated_at' => now(),
                                'updated_by' => Auth::id(),
                            ]);
                    }
                    
                    // Add new agents
                    if (!empty($agentsToAdd)) {
                        foreach ($agentsToAdd as $agentId) {
                            // Verify agent belongs to organization
                            $agent = User::whereHas('organizationUsers', function($q) use ($organizationId, $agentId) {
                                $q->where('organization_id', $organizationId)
                                  ->where('user_id', $agentId)
                                  ->whereNull('deleted_at');
                            })->first();
                            
                            if ($agent) {
                                // Check if relationship already exists (soft deleted)
                                $existing = DB::table('properties_user')
                                    ->where('property_id', $property->id)
                                    ->where('user_id', $agentId)
                                    ->where('role_key', 'agent')
                                    ->first();
                                
                                if ($existing) {
                                    // Restore if soft deleted
                                    DB::table('properties_user')
                                        ->where('property_id', $property->id)
                                        ->where('user_id', $agentId)
                                        ->where('role_key', 'agent')
                                        ->update([
                                            'deleted_at' => null,
                                            'deleted_by' => null,
                                            'updated_at' => now(),
                                            'updated_by' => Auth::id(),
                                        ]);
                                } else {
                                    // Create new relationship
                                    DB::table('properties_user')->insert([
                                        'property_id' => $property->id,
                                        'user_id' => $agentId,
                                        'role_key' => 'agent',
                                        'assigned_at' => now(),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                }
                            }
                        }
                    }
                }

                DB::commit();

                // Build success message
                $message = 'Bất động sản đã được cập nhật thành công!';
                if ($deletedImagesCount > 0) {
                    $message .= ' Đã xóa ' . $deletedImagesCount . ' ảnh.';
                }
                $uploadedImagesCount = $request->hasFile('images') ? count($request->file('images')) : 0;
                if ($uploadedImagesCount > 0) {
                    $message .= ' Đã thêm ' . $uploadedImagesCount . ' ảnh mới.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors for debugging
            Log::error('Property Update Validation Error', [
                'property_id' => $id,
                'errors' => $e->validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Thông tin bất động sản không hợp lệ: ' . implode(', ', $e->validator->errors()->all()) . '. Vui lòng kiểm tra lại và thử lại.',
                'errors' => $e->validator->errors()->toArray() // Include detailed errors for debugging
            ], 422);
        } catch (\Exception $e) {
            // Log system errors for debugging
            Log::error('Property Update System Error', [
                'property_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.'
            ], 500);
        }
    }

    /**
     * Xóa property (soft delete)
     * 
     * MỤC ĐÍCH:
     * Xóa property (soft delete) cùng với images và locations (old và new system 2025). Sử dụng transaction để đảm bảo data consistency.
     * 
     * INPUT:
     * - Route parameter: id (property ID)
     * - Session: organization_id, user_id
     * - Database: properties, documents, locations, locations_2025
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Soft delete property, documents (images), locations, locations_2025
     * - Storage: Xóa images từ storage qua ImageService
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền xóa property
     * 2. Lấy organization_id và property
     * 3. Bắt đầu transaction
     * 4. Xóa images từ storage qua ImageService (nếu có)
     * 5. Soft delete locations (old và new system 2025) nếu có
     * 6. Soft delete property
     * 7. Commit transaction
     * 8. Trả về JSON success
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy property theo ID
     * - Bảng documents: Lấy images để xóa
     * - Bảng locations, locations_2025: Lấy locations để xóa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng properties: Soft delete property (set deleted_at, deleted_by)
     * - Bảng documents: Soft delete images
     * - Bảng locations, locations_2025: Soft delete locations
     * - Storage: Xóa images từ storage
     * 
     * LƯU Ý:
     * - Sử dụng soft delete (có thể khôi phục)
     * - Sử dụng transaction để đảm bảo data consistency
     * - Nếu xóa images thất bại, vẫn tiếp tục xóa property
     */
    public function destroy($id)
    {
        $this->requireCapability('asset.property.delete', 'Bạn không có quyền xóa bất động sản.'); // Kiểm tra quyền xóa property → Dừng nếu không có quyền

        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter property
        $property = Property::where('organization_id', $organizationId)->findOrFail($id); // Tìm property theo organization ID và ID → Chỉ lấy property của organization hiện tại

        DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
        try {
            if ($property->images && is_array($property->images)) { // Nếu property có images
                try {
                    $this->imageService->deleteMultipleImages($property->images); // Xóa images từ storage qua ImageService → Xóa files từ storage
                } catch (\Exception $e) { // Nếu có lỗi khi xóa images
                    Log::error('Error deleting property images: ' . $e->getMessage()); // Ghi log lỗi → Vẫn tiếp tục xóa property
                }
            }
            
            if ($property->location_id) { // Nếu property có location (old system)
                $property->location->delete(); // Soft delete location → Xóa location cũ
            }
            
            if ($property->location_id_2025) { // Nếu property có location2025 (new system)
                $property->location2025->delete(); // Soft delete location2025 → Xóa location mới
            }
            
            $property->delete(); // Soft delete property → Xóa property (set deleted_at, deleted_by)

            DB::commit(); // Commit transaction → Lưu tất cả thay đổi

            return response()->json([ // Trả về JSON success
                'success' => true,
                'message' => 'Bất động sản, ảnh và địa chỉ đã được xóa thành công!'
            ]);
        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ tất cả thay đổi
            return response()->json([ // Trả về JSON error
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint: Lấy districts theo province code (old system) - AJAX
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách districts theo province code để populate cascading dropdown → Dùng cho form tạo/chỉnh sửa property
     * 
     * INPUT:
     * - Route parameter: provinceCode (province code)
     * - Database: geo_districts
     * 
     * OUTPUT:
     * - JSON: Array of districts [{code, name, name_local, province_code, kind}, ...]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy districts từ geo_districts theo province_code
     * 2. Sắp xếp theo name_local, name
     * 3. Map thành array với code, name, name_local, province_code, kind
     * 4. Trả về JSON response
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng geo_districts: Lấy districts theo province_code
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function getDistricts($provinceCode)
    {
        try {
            Log::info('Loading districts for province', ['province_code' => $provinceCode]); // Ghi log → Để debug
            
            $districts = DB::table('geo_districts') // Lấy districts từ geo_districts → Dùng cho cascading dropdown
                ->where('province_code', $provinceCode) // Filter theo province_code → Chỉ lấy districts của province được chọn
                ->orderBy('name_local', 'asc') // Sắp xếp theo name_local tăng dần → Hiển thị theo tên địa phương
                ->orderBy('name', 'asc') // Sắp xếp theo name tăng dần → Hiển thị theo tên
                ->get() // Lấy tất cả kết quả
                ->map(function ($district) { // Map mỗi district thành array → Format data cho frontend
                    return [
                        'code' => $district->code, // Mã district
                        'name' => $district->name ?? '', // Tên district
                        'name_local' => $district->name_local ?? $district->name ?? '', // Tên địa phương (ưu tiên name_local)
                        'province_code' => $district->province_code ?? '', // Mã province
                        'kind' => $district->kind ?? 'district' // Loại (district)
                    ];
                });

            Log::info('Districts loaded', [ // Ghi log → Để debug
                'province_code' => $provinceCode,
                'count' => $districts->count()
            ]);

            return response()->json($districts->values()->all()); // Trả về JSON response → Dùng cho cascading dropdown
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error loading districts: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'province_code' => $provinceCode,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500); // Trả về JSON error
        }
    }

    /**
     * API endpoint: Lấy wards theo district code (old system) - AJAX
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách wards theo district code để populate cascading dropdown → Dùng cho form tạo/chỉnh sửa property
     * 
     * INPUT:
     * - Route parameter: districtCode (district code)
     * - Database: geo_wards
     * 
     * OUTPUT:
     * - JSON: Array of wards [{code, name, name_local, district_code, kind}, ...]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy wards từ geo_wards theo district_code
     * 2. Sắp xếp theo name_local, name
     * 3. Map thành array với code, name, name_local, district_code, kind
     * 4. Trả về JSON response
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng geo_wards: Lấy wards theo district_code
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function getWards($districtCode)
    {
        try {
            $wards = DB::table('geo_wards') // Lấy wards từ geo_wards → Dùng cho cascading dropdown
                ->where('district_code', $districtCode) // Filter theo district_code → Chỉ lấy wards của district được chọn
                ->orderBy('name_local', 'asc') // Sắp xếp theo name_local tăng dần → Hiển thị theo tên địa phương
                ->orderBy('name', 'asc') // Sắp xếp theo name tăng dần → Hiển thị theo tên
                ->get() // Lấy tất cả kết quả
                ->map(function ($ward) { // Map mỗi ward thành array → Format data cho frontend
                    return [
                        'code' => $ward->code, // Mã ward
                        'name' => $ward->name ?? '', // Tên ward
                        'name_local' => $ward->name_local ?? $ward->name ?? '', // Tên địa phương (ưu tiên name_local)
                        'district_code' => $ward->district_code ?? '', // Mã district
                        'kind' => $ward->kind ?? 'ward' // Loại (ward)
                    ];
                });

            return response()->json($wards->values()->all()); // Trả về JSON response → Dùng cho cascading dropdown
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error loading wards: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'district_code' => $districtCode,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500); // Trả về JSON rỗng với status 500
        }
    }

    /**
     * API endpoint: Cập nhật status của property (active/inactive) - AJAX
     * 
     * MỤC ĐÍCH:
     * Cập nhật status của property (1 = active, 0 = inactive) với ownership check. Manager có thể cập nhật tất cả properties, Agent chỉ có thể cập nhật assigned properties.
     * 
     * INPUT:
     * - Route parameter: id (property ID)
     * - Request: status (0 hoặc 1)
     * - Session: organization_id, user_id
     * - Database: properties, organization_users, roles, properties_user
     * 
     * OUTPUT:
     * - JSON: {success: true/false, message: "..."}
     * - Database: Cập nhật status trong properties
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền cập nhật property
     * 2. Lấy organization_id
     * 3. Kiểm tra ownership: Manager xem tất cả, Agent chỉ xem assigned properties
     * 4. Validate status (0 hoặc 1)
     * 5. Bắt đầu transaction
     * 6. Cập nhật property status
     * 7. Commit transaction
     * 8. Trả về JSON success với status label
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng properties: Lấy property theo ID
     * - Bảng organization_users, roles: Kiểm tra role của user
     * - Bảng properties_user: Kiểm tra assigned properties (cho Agent)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng properties: Cập nhật status
     * 
     * LƯU Ý:
     * - Manager có thể cập nhật tất cả properties trong organization
     * - Agent chỉ có thể cập nhật assigned properties
     * - Sử dụng transaction để đảm bảo data consistency
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Dùng để check quyền và ownership
        
        $this->requireCapability('asset.property.update', 'Bạn không có quyền cập nhật trạng thái bất động sản.'); // Kiểm tra quyền cập nhật property → Dừng nếu không có quyền
        
        $organizationId = $this->getCurrentOrganizationId(); // Lấy organization ID từ session → Dùng để filter property
        
        if (!$organizationId) { // Nếu không có organization ID
            return response()->json([ // Trả về JSON error
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
        $canViewAll = $this->checkCapability('asset.property.view_all'); // Kiểm tra quyền view_all → Manager: true, Agent: false
        if (!$canViewAll) { // Nếu không có quyền view_all
            $hasViewOwn = $this->checkCapability('asset.property.view_own'); // Kiểm tra quyền view_own → Agent: true
            if ($hasViewOwn) { // Nếu có quyền view_own
                $canViewAll = false; // Agent chỉ xem assigned properties
            } else { // Backward compatibility
                $canViewAll = $this->checkCapability('asset.property.view'); // Kiểm tra quyền view → Backward compatibility
                if ($canViewAll) { // Nếu có quyền view
                    $roleKey = session('auth_role_key'); // Lấy role key từ session → Dùng để check manager
                    if (!$roleKey) { // Nếu không có trong session
                        $orgUser = \App\Models\OrganizationUser::where('user_id', $user->id) // Tìm organization user → Lấy role key
                            ->where('organization_id', $organizationId)
                            ->where('status', 'active')
                            ->with('role')
                            ->first();
                        $roleKey = $orgUser?->role?->key_code; // Lấy role key từ organization user
                    }
                    $canViewAll = ($roleKey === 'manager'); // Manager có quyền xem tất cả → Backward compatibility
                }
            }
        }
        
        $query = Property::where('organization_id', $organizationId) // Tìm property theo organization ID → Chỉ lấy property của organization hiện tại
            ->whereNull('deleted_at'); // Chỉ lấy chưa bị xóa
        
        if (!$canViewAll) { // Nếu user không thể xem tất cả (Agent)
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách assigned property IDs → Dùng để kiểm tra
            if ($assignedPropertyIds->isEmpty() || !$assignedPropertyIds->contains($id)) { // Nếu không có assigned properties hoặc property không nằm trong danh sách
                return response()->json([ // Trả về JSON error → Agent chỉ cập nhật assigned properties
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật trạng thái bất động sản này.'
                ], 403);
            }
            $query->whereIn('id', $assignedPropertyIds); // Filter chỉ lấy assigned properties
        }
        
        $property = $query->findOrFail($id); // Tìm property theo ID, nếu không tìm thấy thì 404
        
        $request->validate([ // Validate input → Đảm bảo status hợp lệ
            'status' => 'required|in:0,1' // status: bắt buộc, chỉ 0 hoặc 1
        ]);
        
        try {
            DB::beginTransaction(); // Bắt đầu transaction → Đảm bảo data consistency
            
            $property->status = $request->status; // Cập nhật status → Set status mới
            $property->save(); // Lưu property → Cập nhật vào database
            
            DB::commit(); // Commit transaction → Lưu thay đổi
            
            $statusLabels = [ // Map status sang label tiếng Việt → Dùng để hiển thị trong message
                1 => 'Hoạt động', // 1 = active
                0 => 'Tạm ngưng' // 0 = inactive
            ];
            
            return response()->json([ // Trả về JSON success
                'success' => true,
                'message' => 'Trạng thái đã được cập nhật thành "' . $statusLabels[$request->status] . '"!'
            ]);
            
        } catch (\Exception $e) { // Nếu có lỗi
            DB::rollBack(); // Rollback transaction → Hủy bỏ thay đổi
            Log::error('Error updating property status: ' . $e->getMessage()); // Ghi log lỗi → Để debug
            
            return response()->json([ // Trả về JSON error
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint: Lấy wards theo province code (new system 2025) - AJAX
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách wards theo province code (new system 2025) để populate cascading dropdown → Dùng cho form tạo/chỉnh sửa property
     * 
     * INPUT:
     * - Route parameter: provinceCode (province code)
     * - Database: geo_wards_2025
     * 
     * OUTPUT:
     * - JSON: Array of wards [{code, name, name_local, district_code, kind}, ...]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy wards từ geo_wards_2025 theo district_code (new system 2025 sử dụng district_code để link với province)
     * 2. Filter: district_code = provinceCode hoặc '01' (default) hoặc null
     * 3. Sắp xếp theo name_local, name
     * 4. Map thành array với code, name, name_local, district_code, kind
     * 5. Trả về JSON response
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng geo_wards_2025: Lấy wards theo district_code (new system 2025)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - New system 2025 sử dụng 2-level (province → ward), không có district
     * - Wards được link với province qua district_code (có thể là province code hoặc '01' hoặc null)
     */
    public function getWards2025($provinceCode)
    {
        try {
            $wards = DB::table('geo_wards_2025') // Lấy wards từ geo_wards_2025 → Dùng cho cascading dropdown (new system 2025)
                ->where(function($query) use ($provinceCode) { // Filter theo district_code → New system 2025 sử dụng district_code để link với province
                    $query->where('district_code', $provinceCode) // district_code = provinceCode → Wards của province được chọn
                          ->orWhere('district_code', '01') // Hoặc district_code = '01' (default từ seeder) → Fallback
                          ->orWhereNull('district_code'); // Hoặc district_code = null → Fallback
                })
                ->orderBy('name_local', 'asc') // Sắp xếp theo name_local tăng dần → Hiển thị theo tên địa phương
                ->orderBy('name', 'asc') // Sắp xếp theo name tăng dần → Hiển thị theo tên
                ->get() // Lấy tất cả kết quả
                ->map(function ($ward) { // Map mỗi ward thành array → Format data cho frontend
                    return [
                        'code' => $ward->code, // Mã ward
                        'name' => $ward->name ?? '', // Tên ward
                        'name_local' => $ward->name_local ?? $ward->name ?? '', // Tên địa phương (ưu tiên name_local)
                        'district_code' => $ward->district_code ?? '', // Mã district (new system 2025)
                        'kind' => $ward->kind ?? 'ward' // Loại (ward)
                    ];
                });

            return response()->json($wards->values()->all()); // Trả về JSON response → Dùng cho cascading dropdown
        } catch (\Exception $e) { // Nếu có lỗi
            Log::error('Error loading wards-2025: ' . $e->getMessage(), [ // Ghi log lỗi → Để debug
                'province_code' => $provinceCode,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([], 500); // Trả về JSON rỗng với status 500
        }
    }
}


