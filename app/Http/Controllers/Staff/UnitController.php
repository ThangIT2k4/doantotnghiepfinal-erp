<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Property;
use App\Models\Lease;
use App\Models\BookingDeposit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Amenity;
use App\Models\Document;
use App\Services\ImageService;
use App\Services\CapabilityService;
use App\Services\Subscription\PlanLimitChecker;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * Controller quản lý Units (Căn hộ/Phòng) trong tổ chức (Asset module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách units trong tổ chức (xem, tạo, sửa, xóa)
 * - Manager: Xem tất cả units trong organization
 * - Agent: Chỉ xem units của assigned properties
 * - Quản lý thông tin unit: code, unit_type, area, price, status, amenities
 * - Quản lý images, documents, leases, invoices, payments
 * - Tính toán statistics: total, available, occupied, maintenance, reserved, revenue, outstanding
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Kiểm tra subscription plan limits khi tạo unit mới
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách units với filters (search, property, status, unit_type, availability)
 *    - Filter theo organization_id (qua properties), ownership (assigned properties cho agent)
 *    - Tính statistics: total, available, occupied, maintenance, reserved, total_leases, active_leases, total_revenue, total_outstanding
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép (code, created_at, status, etc.)
 *    - Eager load relationships (property, leases, amenities)
 * 2. create(): Hiển thị form tạo unit mới (cần property_id)
 *    - Load property, amenities, existing units trong property
 * 3. store(): Tạo unit mới với validation, check subscription limit
 *    - Validate tất cả fields (code, unit_type, area, price, property_id, etc.)
 *    - Check subscription plan limit (max_units)
 *    - Create unit, images, documents, amenities
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết unit (leases, invoices, payments, images, documents, amenities)
 * 5. edit(): Hiển thị form edit unit với tất cả data
 * 6. update(): Cập nhật unit (code, unit_type, area, price, status, amenities, images, etc.)
 *    - Validate và update unit, images, documents, amenities
 *    - Handle image upload/delete
 * 7. deleteImage(): API endpoint xóa image của unit (AJAX)
 * 8. updateStatus(): API endpoint cập nhật status của unit (AJAX)
 * 9. destroy(): Xóa unit (soft delete)
 *    - Soft delete unit và related records
 * 10. statistics(): API endpoint lấy statistics tổng hợp (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/units: Danh sách units (hỗ trợ HTMX/AJAX)
 * - GET /staff/units/create: Form tạo unit (cần property_id)
 * - POST /staff/units: Tạo unit mới
 * - GET /staff/units/{id}: Chi tiết unit
 * - GET /staff/units/{id}/edit: Form edit unit
 * - PUT/PATCH /staff/units/{id}: Cập nhật unit
 * - DELETE /staff/units/{id}: Xóa unit
 * - POST /staff/units/{id}/delete-image: Xóa image (AJAX)
 * - POST /staff/units/{id}/status: Cập nhật status (AJAX)
 * - GET /staff/units/statistics: Lấy statistics (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: Unit, Property, Lease, BookingDeposit, Invoice, Payment, Amenity, Document
 * - Database tables: units, properties, leases, booking_deposits, invoices, payments, amenities, documents, amenity_unit (pivot)
 * - Request: search, property_id, status, unit_type, availability, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: units, documents, amenity_unit (pivot table cho amenities)
 * - Storage: Images được upload qua ImageService
 * - Không có thay đổi properties, leases, invoices, payments, amenities (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (asset.access, asset.unit.view, asset.unit.create, etc.)
 * - FiltersByOwnership: Filter theo ownership (view_all vs view_own cho assigned properties)
 * 
 * SERVICES SỬ DỤNG:
 * - ImageService: Upload, delete, get URL cho unit images
 * - PlanLimitChecker: Kiểm tra subscription plan limits (max_units)
 * - CapabilityService: Quản lý capabilities (nếu cần)
 * 
 * CAPABILITY CHECKING:
 * - asset.access: Quyền truy cập module Asset (required cho tất cả methods)
 * - asset.unit.view: Quyền xem danh sách units (index, show)
 * - asset.unit.create: Quyền tạo unit (create, store)
 * - asset.unit.update: Quyền cập nhật unit (edit, update)
 * - asset.unit.delete: Quyền xóa unit (destroy)
 * 
 * OWNERSHIP FILTERING:
 * - Manager: Xem tất cả units trong organization (canViewAll = true)
 * - Agent: Chỉ xem units của assigned properties (canViewAll = false, filter theo assignedProperties)
 * - Sử dụng FiltersByOwnership trait để handle logic
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng JOINs với properties để filter theo organization_id
 * - Sử dụng indexes: idx_units_deleted_at_property, idx_units_deleted_at_status
 * - Eager loading relationships để tránh N+1 queries
 * - Tính statistics bằng aggregation (COUNT, SUM) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * - Sử dụng whereIn() với assignedPropertyIds để filter hiệu quả
 * 
 * SUBSCRIPTION LIMITS:
 * - Kiểm tra max_units limit khi tạo unit mới
 * - Sử dụng PlanLimitChecker để check limits và get current count
 * - Trả về error message với current/limit nếu vượt quá limit
 * 
 * UNIT STATUS:
 * - available: Unit đang trống, có thể cho thuê
 * - occupied: Unit đang được thuê (có lease active)
 * - maintenance: Unit đang bảo trì
 * - reserved: Unit đã được đặt cọc (có booking deposit pending/paid)
 * 
 * STATISTICS CALCULATION:
 * - total: Tổng số units
 * - available/occupied/maintenance/reserved: Đếm theo status
 * - total_leases: Tổng số leases của các units
 * - active_leases: Số lượng leases active
 * - total_revenue: Tổng doanh thu từ payments thành công
 * - total_outstanding: Tổng số tiền chưa thanh toán (invoices issued/overdue - payments)
 * 
 * IMAGE HANDLING:
 * - Sử dụng ImageService để upload, delete, get URLs
 * - Hỗ trợ multiple images cho một unit
 * - Images được lưu trong public storage
 * 
 * VALIDATION:
 * - code: required, string, unique trong property, max:50
 * - unit_type: required, string
 * - area: nullable, numeric, min:0
 * - price: nullable, numeric, min:0
 * - property_id: required, exists:properties
 * - status: required, in:available,occupied,maintenance,reserved
 * - images: array, image files, max size
 * 
 * SECURITY:
 * - Manager có quyền quản lý tất cả units
 * - Agent chỉ có quyền xem units của assigned properties
 * - Validate sort fields để prevent SQL injection
 * - Image upload validation (file type, size)
 * 
 * LƯU Ý:
 * - Unit phải thuộc về một property (property_id required)
 * - Unit code phải unique trong property (không phải global unique)
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - Revenue được tính từ payments với status = 'success'
 * - Outstanding được tính từ invoices (issued/overdue) trừ đi payments đã trả
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Subscription limits được check trước khi tạo unit mới
 * - Images được quản lý qua ImageService với public storage
 */
class UnitController extends Controller
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
     * Hiển thị danh sách units với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: asset.access
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Kiểm tra ownership: canViewAll (manager xem tất cả, agent chỉ xem units của assigned properties)
     * 4. Build query với JOIN properties
     * 5. Apply ownership filter: Nếu agent, chỉ lấy units của assigned properties
     * 6. Tính statistics (total, available, occupied, maintenance, reserved, leases, revenue, outstanding) bằng aggregation
     * 7. Apply filters: search, property_id, status, unit_type, availability
     * 8. Apply sorting (validate sort fields)
     * 9. Paginate results
     * 10. Eager load relationships (property, leases, amenities)
     * 11. Check request type (HTMX/AJAX):
     *     - HTMX: Return table partial HTML với stats update
     *     - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: units, properties, leases, invoices, payments
     * - Request: search, property_id, status, unit_type, availability, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - asset.access: Quyền truy cập module Asset
     * 
     * OWNERSHIP FILTERING:
     * - Manager: Xem tất cả units (canViewAll = true)
     * - Agent: Chỉ xem units của assigned properties (canViewAll = false, filter theo assignedProperties)
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng JOINs với properties để filter theo organization_id
     * - Sử dụng indexes: idx_units_deleted_at_property, idx_units_deleted_at_status
     * - Eager loading relationships để tránh N+1 queries
     * - Tính statistics bằng aggregation trong một query
     * - Validate sort fields để prevent SQL injection
     * 
     * STATISTICS:
     * - total, available, occupied, maintenance, reserved: Đếm theo status
     * - total_leases, active_leases: Đếm từ leases table
     * - total_revenue: SUM từ payments với status = 'success'
     * - total_outstanding: SUM từ invoices (issued/overdue) trừ payments đã trả
     * 
     * FILTERS:
     * - search: Tìm kiếm theo code, unit_type, property name
     * - property_id: Filter theo property
     * - status: Filter theo status (available, occupied, maintenance, reserved)
     * - unit_type: Filter theo unit_type
     * - availability: Filter theo availability (available = status available và không có lease active)
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Tài sản.');
        }

        // Kiểm tra user có thể xem tất cả units hay chỉ units của assigned properties
        // Sử dụng FiltersByOwnership trait method: view_all > view_own > view (backward compatibility)
        // Manager: canViewAll = true (xem tất cả)
        // Agent: canViewAll = false (chỉ xem units của assigned properties)
        $canViewAll = $this->canViewAll('asset.unit');

        // Build query tối ưu sử dụng JOINs và proper index order
        // JOIN với properties để filter theo organization_id và lấy property_name
        $query = Unit::select([
            'units.*',
            'properties.name as property_name',
            'properties.organization_id'
        ])
        ->join('properties', 'units.property_id', '=', 'properties.id')
        ->where('properties.organization_id', $organizationId); // Sử dụng index idx_properties_deleted_at_org
        
        // Nếu user không thể xem tất cả, chỉ hiển thị units của assigned properties
        if (!$canViewAll) {
            // Lấy danh sách property IDs được assign cho agent này
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            
            // Nếu không có assigned properties, trả về view rỗng với stats = 0
            if ($assignedPropertyIds->isEmpty()) {
                $stats = [
                    'total' => 0,
                    'available' => 0,
                    'occupied' => 0,
                    'maintenance' => 0,
                    'reserved' => 0,
                ];
                return view('staff.asset.units.index', [
                    'units' => collect(),
                    'properties' => collect(),
                    'stats' => $stats,
                    'sortBy' => 'code',
                    'sortOrder' => 'asc'
                ]);
            }
            // Filter query theo assigned property IDs
            $query->whereIn('units.property_id', $assignedPropertyIds);
        }
        
        // Apply filters theo thứ tự tối ưu: organization_id -> deleted_at -> status
        // Sử dụng indexes để tối ưu performance
        $query->whereNull('units.deleted_at') // Sử dụng index idx_units_deleted_at_property
              ->whereNull('properties.deleted_at'); // Sử dụng index idx_properties_deleted_at_org

        // Tính statistics TRƯỚC KHI apply các filters khác (search, property_id, etc.)
        // Query trực tiếp từ Unit model với JOIN properties để đảm bảo statistics chính xác
        // Statistics phải tính trên toàn bộ data, không bị ảnh hưởng bởi filters
        $statsQuery = Unit::join('properties', 'units.property_id', '=', 'properties.id')
            ->where('properties.organization_id', $organizationId)
            ->whereNull('units.deleted_at')
            ->whereNull('properties.deleted_at');
        
        // Nếu agent, chỉ đếm units của assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
            if ($assignedPropertyIds->isEmpty()) {
                // Nếu không có assigned properties, set whereRaw('1 = 0') để không có kết quả
                $statsQuery->whereRaw('1 = 0');
            } else {
                $statsQuery->whereIn('units.property_id', $assignedPropertyIds);
            }
        }
        
        // Đếm theo status bằng database aggregation để đảm bảo chính xác
        // Sử dụng clone để không ảnh hưởng đến statsQuery gốc
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'available' => (int) (clone $statsQuery)->where('units.status', 'available')->count(),
            'occupied' => (int) (clone $statsQuery)->where('units.status', 'occupied')->count(),
            'maintenance' => (int) (clone $statsQuery)->where('units.status', 'maintenance')->count(),
            'reserved' => (int) (clone $statsQuery)->where('units.status', 'reserved')->count(),
        ];

        // Tính lease và financial statistics từ base query
        // Lấy danh sách unit IDs từ statsQuery để tính các statistics liên quan
        $baseUnitIds = (clone $statsQuery)->pluck('units.id')->toArray();
        
        // Chỉ tính statistics nếu có units
        if (!empty($baseUnitIds)) {
            // Total leases count: Tổng số leases của các units này
            $stats['total_leases'] = (int) Lease::whereIn('unit_id', $baseUnitIds)
                ->whereNull('deleted_at')
                ->count();
            
            // Active leases count: Số lượng leases đang active
            $stats['active_leases'] = (int) Lease::whereIn('unit_id', $baseUnitIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();
            
            // Total revenue: Tổng doanh thu từ payments thành công cho các units này
            // JOIN payments -> invoices -> leases để lấy payments theo unit_id
            // Chỉ tính payments với status = 'success'
            $totalRevenue = Payment::selectRaw('SUM(payments.amount) as total')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('leases', 'invoices.lease_id', '=', 'leases.id')
                ->whereIn('leases.unit_id', $baseUnitIds)
                ->where('payments.status', 'success')
                ->whereNull('payments.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereNull('leases.deleted_at')
                ->value('total');
            $stats['total_revenue'] = (float) ($totalRevenue ?? 0);
            
            // Total outstanding: Tổng số tiền chưa thanh toán
            // Tính từ invoices (issued/overdue) trừ đi payments đã trả
            // Sử dụng subquery để tính total_paid cho mỗi invoice
            $totalOutstanding = Invoice::selectRaw('SUM(invoices.total_amount - COALESCE(payments.total_paid, 0)) as total')
                ->join('leases', 'invoices.lease_id', '=', 'leases.id')
                // Subquery để tính tổng payments đã trả cho mỗi invoice
                ->leftJoin(DB::raw('(SELECT invoice_id, SUM(amount) as total_paid FROM payments WHERE status = "success" AND deleted_at IS NULL GROUP BY invoice_id) as payments'), 'invoices.id', '=', 'payments.invoice_id')
                ->whereIn('leases.unit_id', $baseUnitIds)
                ->whereIn('invoices.status', ['issued', 'overdue']) // Chỉ tính invoices chưa thanh toán
                ->whereNull('invoices.deleted_at')
                ->whereNull('leases.deleted_at')
                ->value('total');
            // Đảm bảo outstanding không âm (nếu có lỗi tính toán)
            $stats['total_outstanding'] = max(0, (float) ($totalOutstanding ?? 0));
        } else {
            // Nếu không có units, set tất cả statistics = 0
            $stats['total_leases'] = 0;
            $stats['active_leases'] = 0;
            $stats['total_revenue'] = 0;
            $stats['total_outstanding'] = 0;
        }

        // Apply filters - optimized with indexes
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('units.code', 'like', "%{$search}%") // Uses FULLTEXT index ft_units_code_note if available
                  ->orWhere('units.unit_type', 'like', "%{$search}%")
                  ->orWhere('properties.name', 'like', "%{$search}%");
            });
        }

        // Uses idx_units_deleted_at_property
        if ($request->filled('property_id')) {
            $query->where('units.property_id', $request->get('property_id'));
        }

        // Uses idx_units_deleted_at_status
        if ($request->filled('status')) {
            $query->where('units.status', $request->get('status'));
        }

        if ($request->filled('unit_type')) {
            $query->where('units.unit_type', $request->get('unit_type'));
        }

        if ($request->filled('availability')) {
            if ($request->get('availability') === 'available') {
                $query->where('units.status', 'available') // Uses idx_units_deleted_at_status
                      ->whereDoesntHave('leases', function($q) {
                          $q->where('status', 'active')->whereNull('deleted_at');
                      });
            } elseif ($request->get('availability') === 'occupied') {
                $query->whereHas('leases', function($q) {
                    $q->where('status', 'active')->whereNull('deleted_at'); // Uses idx_leases_deleted_at_status
                });
            }
        }

        if ($request->filled('rent_min')) {
            $query->where('units.base_rent', '>=', $request->get('rent_min'));
        }

        if ($request->filled('rent_max')) {
            $query->where('units.base_rent', '<=', $request->get('rent_max'));
        }

        if ($request->filled('area_min')) {
            $query->where('units.area_m2', '>=', $request->get('area_min'));
        }

        if ($request->filled('area_max')) {
            $query->where('units.area_m2', '<=', $request->get('area_max'));
        }

        // Uses idx_units_deleted_at_created
        if ($request->filled('date_from')) {
            $query->whereDate('units.created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('units.created_at', '<=', $request->get('date_to'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'code');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'code', 'created_at', 'status'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'code';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        
        // Apply sorting to query
        $query->orderBy("units.{$sortBy}", $sortOrder);

        // Get pagination per page
        $perPage = $request->get('per_page', 20);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 20;
        }

        // Get units with their related data
        $units = $query->paginate($perPage)->withQueryString();
        
        // Eager load relationships for display
        $units->load(['property', 'leases.tenant']);

        // Get unit IDs for batch processing
        $unitIds = $units->pluck('id')->toArray();

        // Optimized revenue data query using JOINs
        $revenueData = Payment::selectRaw('leases.unit_id, SUM(payments.amount) as total_revenue')
        ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
        ->join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->whereIn('leases.unit_id', $unitIds) // Uses idx_leases_unit_id
        ->where('payments.status', 'success')
        ->whereNull('payments.deleted_at') // Uses idx_payments_invoice_deleted_status
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->groupBy('leases.unit_id')
        ->pluck('total_revenue', 'unit_id');

        // Optimized outstanding amounts query using JOINs
        $outstandingData = Invoice::selectRaw('leases.unit_id, SUM(invoices.total_amount - IFNULL(payments.total_paid, 0)) as outstanding')
        ->join('leases', 'invoices.lease_id', '=', 'leases.id')
        ->leftJoin(DB::raw('(SELECT invoice_id, SUM(amount) as total_paid FROM payments WHERE status = "success" AND deleted_at IS NULL GROUP BY invoice_id) as payments'), 'invoices.id', '=', 'payments.invoice_id')
        ->whereIn('leases.unit_id', $unitIds) // Uses idx_leases_unit_id
        ->whereIn('invoices.status', ['issued', 'overdue']) // Uses idx_invoices_deleted_at_status
        ->whereNull('invoices.deleted_at') // Uses idx_invoices_deleted_at_status
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->groupBy('leases.unit_id')
        ->pluck('outstanding', 'unit_id')
        ->map(function($amount) {
            return max(0, (float)$amount);
        });

        // Get lease counts for all units
        $leaseCounts = Lease::whereIn('unit_id', $unitIds)
            ->selectRaw('unit_id, COUNT(*) as total_leases')
            ->groupBy('unit_id')
            ->pluck('total_leases', 'unit_id');

        // Get active lease counts for all units
        $activeLeaseCounts = Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->selectRaw('unit_id, COUNT(*) as active_leases')
            ->groupBy('unit_id')
            ->pluck('active_leases', 'unit_id');

        // Get booking deposit counts for all units
        $depositCounts = BookingDeposit::whereIn('unit_id', $unitIds)
            ->selectRaw('unit_id, COUNT(*) as booking_deposits')
            ->groupBy('unit_id')
            ->pluck('booking_deposits', 'unit_id');

        // Add additional data for each unit
        foreach ($units as $unit) {
            $unit->total_leases = $leaseCounts->get($unit->id, 0);
            $unit->active_leases = $activeLeaseCounts->get($unit->id, 0);
            $unit->total_revenue = $revenueData->get($unit->id, 0);
            $unit->outstanding_amount = $outstandingData->get($unit->id, 0);
            $unit->booking_deposits = $depositCounts->get($unit->id, 0);
        }

        // Get properties for filter dropdown
        $properties = Property::where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();


        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            try {
                // Render table content
                $tableHtml = view('staff.asset.units.partials.table', compact('units', 'sortBy', 'sortOrder'))->render();
                
                // Format stats for response
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'available' => [
                        'value' => $stats['available'] ?? 0,
                        'label' => 'Trống',
                        'icon' => 'fa-door-open',
                        'color' => 'success',
                        'filter' => 'available',
                    ],
                    'occupied' => [
                        'value' => $stats['occupied'] ?? 0,
                        'label' => 'Đã thuê',
                        'icon' => 'fa-home',
                        'color' => 'info',
                        'filter' => 'occupied',
                    ],
                    'maintenance' => [
                        'value' => $stats['maintenance'] ?? 0,
                        'label' => 'Bảo trì',
                        'icon' => 'fa-tools',
                        'color' => 'warning',
                        'filter' => 'maintenance',
                    ],
                    'reserved' => [
                        'value' => $stats['reserved'] ?? 0,
                        'label' => 'Đã đặt',
                        'icon' => 'fa-bookmark',
                        'color' => 'secondary',
                        'filter' => 'reserved',
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'units-table-container',
                    'action' => route('staff.units.index'),
                    'columns' => 5
                ])->render();
                
                // Extract inner HTML from tableHtml (remove the outer wrapper div if exists)
                $innerTableHtml = $tableHtml;
                
                // Try to extract using DOMDocument for better HTML parsing
                if (class_exists('DOMDocument')) {
                    libxml_use_internal_errors(true);
                    $dom = new \DOMDocument();
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $xpath = new \DOMXPath($dom);
                    $container = $xpath->query('//div[@id="units-table-container"]')->item(0);
                    if ($container) {
                        $innerHtml = '';
                        foreach ($container->childNodes as $child) {
                            $innerHtml .= $dom->saveHTML($child);
                        }
                        $innerTableHtml = trim($innerHtml);
                    }
                    libxml_clear_errors();
                }
                
                // Fallback to regex if DOMDocument didn't work
                if ($innerTableHtml === $tableHtml) {
                    // Match the opening div with id="units-table-container" and extract everything inside
                    if (preg_match('/<div[^>]*id=["\']units-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) {
                        $innerTableHtml = trim($matches[1]);
                    }
                }
                
                // Return inner HTML with stats update via hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('UnitController HTMX Error: ' . $e->getMessage());
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500);
            }
        }

        return view('staff.asset.units.index', compact('units', 'properties', 'stats', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        if (!$this->checkCapability('asset.unit.create')) {
            abort(403, 'Bạn không có quyền tạo phòng.');
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get property_id from query parameter and convert to integer
        $propertyId = $request->get('property_id');
        if ($propertyId) {
            $propertyId = (int)$propertyId;
        }

        // Check if user has asset.unit.view capability (manager sees all properties)
        $canViewAll = $this->canViewAll('asset.unit');
        
        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        } else {
            // Agent only sees assigned properties
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if ($assignedPropertyIds->isEmpty()) {
                return redirect()->route('staff.units.index')
                    ->with('error', 'Bạn chưa được gán quản lý bất động sản nào.');
            }
            $properties = Property::whereIn('id', $assignedPropertyIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        }

        // Validate property_id if provided
        if ($propertyId) {
            $property = $properties->firstWhere('id', $propertyId);
            if (!$property) {
                // Property not found or user doesn't have access, clear property_id
                $propertyId = null;
            }
        }

        $amenities = Amenity::whereNull('deleted_at')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('staff.asset.units.create', compact('properties', 'amenities', 'propertyId'));
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request)
    {
        // Check capability
        if (!$this->checkCapability('asset.unit.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo phòng.'
            ], 403);
        }
        
        $creationMode = $request->input('creation_mode', 'single');
        
        if ($creationMode === 'bulk') {
            return $this->storeBulk($request);
        }
        
        return $this->storeSingle($request);
    }
    
    private function storeSingle(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $organization = \App\Models\Organization::find($organizationId);
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Tổ chức không tồn tại.'
            ], 403);
        }

        // Check subscription limit
        if (!$this->limitChecker->canAddUnit($organization)) {
            $limit = $this->limitChecker->getLimit($organization, 'max_units');
            $current = $this->limitChecker->getUnitsCount($organization);
            
            return response()->json([
                'success' => false,
                'message' => "Bạn đã đạt giới hạn số lượng đơn vị/phòng của gói dịch vụ. Hiện tại: {$current}/{$limit}",
                'error_type' => 'subscription_limit',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'code' => 'required|string|max:50',
            'unit_type' => 'required|in:room,apartment,dorm,shared',
            'max_occupancy' => 'required|integer|min:1',
            'base_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'area_m2' => 'nullable|numeric|min:0',
            'floor' => 'nullable|integer|min:0',
            'status' => 'required|in:available,reserved,occupied,maintenance',
            'note' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000', // Support both note and description for backward compatibility
            'images' => 'nullable|array|max:20',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max, support webp
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id'
        ]);

        if ($validator->fails()) {
            $imagesInfo = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    if ($file) {
                        $imagesInfo[$index] = [
                            'name' => $file->getClientOriginalName(),
                            'extension' => $file->getClientOriginalExtension(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                            'is_valid' => $file->isValid(),
                            'error' => $file->getError(),
                            'error_message' => $file->getErrorMessage(),
                            'real_path' => $file->getRealPath(),
                            'is_file' => is_file($file->getRealPath()),
                            'file_exists' => file_exists($file->getRealPath())
                        ];
                    } else {
                        $imagesInfo[$index] = ['error' => 'File is null'];
                    }
                }
            }
            
            Log::warning('Unit creation validation failed (single)', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['images', 'amenities']),
                'has_images' => $request->hasFile('images'),
                'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
                'images_info' => $imagesInfo,
                'all_request_keys' => array_keys($request->all()),
                'files_in_request' => $request->hasFile('images') ? 'yes' : 'no'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify property belongs to organization
            $property = Property::where('id', $request->property_id)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bất động sản không tồn tại hoặc không thuộc tổ chức của bạn.'
                ], 404);
            }

            // For agent, check if property is assigned to them
            $canViewAll = $this->canViewAll('asset.unit');
            if (!$canViewAll) {
                /** @var \App\Models\User $currentUser */
                $currentUser = Auth::user();
                $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
                if (!$assignedPropertyIds->contains($request->property_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền tạo phòng cho bất động sản này.'
                    ], 403);
                }
            }

            // Check if unit code already exists in the same property
            $existingUnit = Unit::where('property_id', $request->property_id)
                ->where('code', $request->code)
                ->whereNull('deleted_at')
                ->first();

            if ($existingUnit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã phòng đã tồn tại trong bất động sản này.'
                ], 422);
            }

            DB::beginTransaction();

            $unit = Unit::create([
                'property_id' => $request->property_id,
                'code' => $request->code,
                'unit_type' => $request->unit_type,
                'max_occupancy' => $request->max_occupancy,
                'base_rent' => $request->base_rent,
                'deposit_amount' => $request->deposit_amount ?? 0,
                'area_m2' => $request->area_m2,
                'floor' => $request->floor ?? null,
                'status' => $request->status,
                'note' => $request->note ?? $request->description ?? null, // Support both note and description
            ]);

            // Handle image uploads and save to documents
            if ($request->hasFile('images')) {
                try {
                    $images = $request->file('images');
                    // Filter out null values
                    $images = array_filter($images, function($image) {
                        return $image !== null && $image->isValid();
                    });
                    
                    if (!empty($images)) {
                        $this->handleImageUploads($unit, array_values($images));
                        Log::info('Unit images uploaded successfully', [
                            'unit_id' => $unit->id,
                            'images_count' => count($images)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error uploading unit images: ' . $e->getMessage(), [
                        'unit_id' => $unit->id ?? null,
                        'exception' => $e->getTraceAsString(),
                        'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0
                    ]);
                    // Don't fail the entire request if image upload fails
                }
            }

            // Attach amenities to unit
            if ($request->has('amenities') && is_array($request->amenities)) {
                try {
                    $amenityIds = array_filter(array_map('intval', $request->amenities));
                    if (!empty($amenityIds)) {
                        $unit->amenities()->attach($amenityIds);
                    }
                } catch (\Exception $e) {
                    Log::error('Error attaching amenities to unit: ' . $e->getMessage());
                    // Don't fail the entire request if amenities attachment fails
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Phòng đã được tạo thành công!',
                'unit' => $unit->load(['property', 'amenities']),
                'redirect' => route('staff.units.index', ['property_id' => $unit->property_id])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo phòng: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function storeBulk(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $organization = \App\Models\Organization::find($organizationId);
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Tổ chức không tồn tại.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bulk_property_id' => 'required|exists:properties,id',
            'bulk_unit_type' => 'required|in:room,apartment,dorm,shared',
            'bulk_max_occupancy' => 'required|integer|min:1',
            'bulk_base_rent' => 'required|numeric|min:0',
            'bulk_deposit_amount' => 'nullable|numeric|min:0',
            'bulk_area_m2' => 'nullable|numeric|min:0',
            'bulk_status' => 'required|in:available,reserved,occupied,maintenance',
            'bulk_description' => 'nullable|string',
            'bulk_images' => 'nullable|array',
            'bulk_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'bulk_amenities' => 'nullable|array',
            'bulk_amenities.*' => 'exists:amenities,id',
            'floor_config_mode' => 'required|in:simple,advanced',
            'start_floor' => 'required_if:floor_config_mode,simple|integer|min:1',
            'end_floor' => 'required_if:floor_config_mode,simple|integer|min:1',
            'rooms_per_floor' => 'required_if:floor_config_mode,simple|integer|min:1',
            'room_prefix' => 'nullable|string|max:10',
            'floor_configs' => 'required_if:floor_config_mode,advanced|array',
            'floor_configs.*.floor_number' => 'required_if:floor_config_mode,advanced|integer|min:1',
            'floor_configs.*.rooms_count' => 'required_if:floor_config_mode,advanced|integer|min:1',
            'floor_configs.*.room_type' => 'required_if:floor_config_mode,advanced|in:room,apartment,dorm,shared',
            'floor_configs.*.room_prefix' => 'nullable|string|max:10',
            'floor_configs.*.custom_room_numbers' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify property belongs to organization
            $property = Property::where('id', $request->bulk_property_id)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bất động sản không tồn tại hoặc không thuộc tổ chức của bạn.'
                ], 404);
            }

            // For agent, check if property is assigned to them
            $canViewAll = $this->canViewAll('asset.unit');
            if (!$canViewAll) {
                /** @var \App\Models\User $currentUser */
                $currentUser = Auth::user();
                $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
                if (!$assignedPropertyIds->contains($request->bulk_property_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền tạo phòng cho bất động sản này.'
                    ], 403);
                }
            }

            $propertyId = $request->bulk_property_id;
            $unitType = $request->bulk_unit_type;
            $maxOccupancy = $request->bulk_max_occupancy;
            $baseRent = $request->bulk_base_rent;
            $depositAmount = $request->bulk_deposit_amount;
            $areaM2 = $request->bulk_area_m2;
            $status = $request->bulk_status;
            $description = $request->bulk_description;
            $floorConfigMode = $request->floor_config_mode;
            
            $units = [];
            $roomConfigs = [];
            
            if ($floorConfigMode === 'simple') {
                $roomConfigs = $this->generateSimpleRoomConfigs($request);
            } else {
                $roomConfigs = $this->generateAdvancedRoomConfigs($request);
            }
            
            if (empty($roomConfigs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có cấu hình phòng nào được tạo. Vui lòng kiểm tra lại thông tin.'
                ], 400);
            }
            
            // Check if total rooms exceed limit
            $totalRooms = count($roomConfigs);
            if ($totalRooms > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng phòng quá lớn (' . $totalRooms . '). Tối đa 100 phòng mỗi lần tạo.'
                ], 400);
            }
            
            // Check subscription limit
            $currentUnitsCount = $this->limitChecker->getUnitsCount($organization);
            $limit = $this->limitChecker->getLimit($organization, 'max_units');
            
            if ($limit !== -1 && ($currentUnitsCount + $totalRooms) > $limit) {
                $remaining = max(0, $limit - $currentUnitsCount);
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã đạt hoặc vượt quá giới hạn số lượng đơn vị/phòng của gói dịch vụ. Hiện tại: {$currentUnitsCount}/{$limit}. Bạn có thể tạo thêm tối đa {$remaining} phòng.",
                    'error_type' => 'subscription_limit',
                ], 403);
            }
            
            DB::beginTransaction();
            
            // Create units
            foreach ($roomConfigs as $roomConfig) {
                $unit = Unit::create([
                    'property_id' => $propertyId,
                    'code' => $roomConfig['code'],
                    'unit_type' => $roomConfig['unit_type'],
                    'max_occupancy' => $maxOccupancy,
                    'base_rent' => $baseRent,
                    'deposit_amount' => $depositAmount ?? 0,
                    'area_m2' => $areaM2,
                    'status' => $status,
                    'note' => $description, // Note: description field maps to note in database
                ]);
                
                $units[] = $unit;
            }
            
            // Handle image uploads for all units
            if ($request->hasFile('bulk_images')) {
                foreach ($units as $unit) {
                    $this->handleImageUploads($unit, $request->file('bulk_images'));
                }
            }

            // Attach amenities to all units
            // Support both 'bulk_amenities' and 'amenities' field names
            $amenitiesInput = $request->input('bulk_amenities', $request->input('amenities', []));
            if (!empty($amenitiesInput) && is_array($amenitiesInput)) {
                try {
                    $amenityIds = array_filter(array_map('intval', $amenitiesInput));
                    if (!empty($amenityIds)) {
                        foreach ($units as $unit) {
                            $unit->amenities()->attach($amenityIds);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error attaching amenities to bulk units: ' . $e->getMessage());
                    // Don't fail the entire request if amenities attachment fails
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo thành công ' . count($units) . ' phòng!',
                'units' => $units,
                'total_created' => count($units),
                'redirect' => route('staff.units.index', ['property_id' => $propertyId])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo phòng hàng loạt: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function generateSimpleRoomConfigs(Request $request)
    {
        $startFloor = $request->start_floor;
        $endFloor = $request->end_floor;
        $roomsPerFloor = $request->rooms_per_floor;
        $roomPrefix = $request->room_prefix ?: 'P';
        $unitType = $request->bulk_unit_type;
        
        $roomConfigs = [];
        
        for ($floor = $startFloor; $floor <= $endFloor; $floor++) {
            for ($room = 1; $room <= $roomsPerFloor; $room++) {
                $roomNumber = str_pad($floor, 2, '0', STR_PAD_LEFT) . str_pad($room, 2, '0', STR_PAD_LEFT);
                $roomCode = $roomPrefix . $roomNumber;
                
                $roomConfigs[] = [
                    'code' => $roomCode,
                    'unit_type' => $unitType,
                    'floor' => $floor,
                    'room_number' => $room
                ];
            }
        }
        
        return $roomConfigs;
    }
    
    private function generateAdvancedRoomConfigs(Request $request)
    {
        $floorConfigs = $request->floor_configs;
        $roomConfigs = [];
        
        foreach ($floorConfigs as $config) {
            $floorNumber = $config['floor_number'];
            $roomsCount = $config['rooms_count'];
            $roomType = $config['room_type'];
            $roomPrefix = $config['room_prefix'] ?: 'P';
            $customRoomNumbers = $config['custom_room_numbers'] ?? null;
            
            $roomNumbers = [];
            
            if ($customRoomNumbers) {
                // Parse custom room numbers
                $roomNumbers = array_map('trim', explode(',', $customRoomNumbers));
                $roomNumbers = array_filter($roomNumbers, function($num) {
                    return !empty($num);
                });
            } else {
                // Generate automatic room numbers
                for ($room = 1; $room <= $roomsCount; $room++) {
                    $roomNumbers[] = (string)$room;
                }
            }
            
            foreach ($roomNumbers as $roomNumber) {
                $roomCode = $roomPrefix . $roomNumber;
                
                $roomConfigs[] = [
                    'code' => $roomCode,
                    'unit_type' => $roomType,
                    'floor' => $floorNumber,
                    'room_number' => $roomNumber
                ];
            }
        }
        
        return $roomConfigs;
    }
    
    /**
     * Handle image uploads for unit and attach as documents
     * 
     * @param \App\Models\Unit $unit
     * @param array|\Illuminate\Http\UploadedFile[] $images
     * @param int|null $startSortOrder Optional starting sort order (default: auto-calculate from existing)
     * @return bool
     */
    private function handleImageUploads($unit, $images, $startSortOrder = null)
    {
        try {
            if (empty($images)) {
                return true;
            }

            // Filter out invalid files
            $validImages = array_filter($images, function($image) {
                return $image !== null && 
                       $image instanceof \Illuminate\Http\UploadedFile && 
                       $image->isValid();
            });

            if (empty($validImages)) {
                Log::warning('No valid images to upload', [
                    'unit_id' => $unit->id ?? null,
                    'total_images' => count($images)
                ]);
                return false;
            }

            // Get current max sort_order for this unit
            if ($startSortOrder === null) {
                $currentMaxSort = $unit->documents()
                    ->where('document_type', 'image')
                    ->max('sort_order') ?? -1;
                $sortOrder = $currentMaxSort + 1;
            } else {
                $sortOrder = $startSortOrder;
            }

            $uploadedImages = $this->imageService->uploadMultipleImages(array_values($validImages), 'units');
            $firstImageSortOrder = $sortOrder;
            
            foreach ($uploadedImages as $uploadedImage) {
                // ImageService đã trả về path đúng format (không có storage/ prefix)
                $fileUrl = $uploadedImage['original'];
                
                // Ensure uploaded_by is not null
                $uploadedBy = Auth::id();
                if (!$uploadedBy) {
                    throw new \Exception('User not authenticated');
                }
                
                // Đảm bảo unit đã có ID
                if (!$unit->id) {
                    throw new \Exception('Unit must be saved before attaching documents');
                }

                // Validate file URL
                if (empty($fileUrl)) {
                    Log::warning('Empty file URL for unit image', [
                        'unit_id' => $unit->id,
                        'uploaded_image' => $uploadedImage
                    ]);
                    continue;
                }
                
                $document = Document::create([
                    'owner_type' => Unit::class, // Giữ lại để backward compatibility
                    'owner_id' => $unit->id,     // Giữ lại để backward compatibility
                    'file_url' => $fileUrl,
                    'file_name' => basename($fileUrl),
                    'mime_type' => $uploadedImage['mime_type'] ?? 'image/jpeg',
                    'file_size' => $uploadedImage['size'] ?? null,
                    'document_type' => 'image',
                    'is_primary' => $sortOrder === $firstImageSortOrder, // First image is primary
                    'uploaded_by' => $uploadedBy,
                    'created_at' => now(),
                ]);
                
                // Đảm bảo document đã có ID
                if (!$document->id) {
                    throw new \Exception('Document chưa có ID sau khi tạo');
                }
                
                // Attach document to unit
                // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                
                Log::debug('Unit image uploaded and attached successfully', [
                    'unit_id' => $unit->id,
                    'document_id' => $document->id,
                    'file_url' => $fileUrl,
                    'sort_order' => $sortOrder
                ]);
                
                $sortOrder++;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error uploading unit images: ' . $e->getMessage(), [
                'unit_id' => $unit->id ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete unit image
     */
    public function deleteImage(Request $request, $id)
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->findOrFail($id);

        $imagePath = $request->input('image_path');
        
        if (!$imagePath) {
            return response()->json([
                'success' => false,
                'message' => 'Đường dẫn ảnh không hợp lệ.'
            ], 400);
        }

        try {
            // Get current images
            $images = $unit->images ?? [];
            
            // Find and remove the image
            $updatedImages = array_filter($images, function($image) use ($imagePath) {
                return $image['original'] !== $imagePath;
            });
            
            // Re-index array
            $updatedImages = array_values($updatedImages);
            
            // Update unit
            $unit->update(['images' => $updatedImages]);
            
            // Delete from storage
            $this->imageService->deleteImage($imagePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa hình ảnh thành công!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting unit image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa hình ảnh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified unit.
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->with([
                'property', 
                'amenities',
                'documents' => function($query) {
                    $query->where('document_type', 'image')
                          ->orderBy('sort_order')
                          ->orderBy('created_at');
                }
            ])
            ->findOrFail($id);

        // Get unit's leases
        $leases = Lease::where('unit_id', $unit->id)
            ->with(['tenant', 'agent'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unit's booking deposits
        $bookingDeposits = BookingDeposit::where('unit_id', $unit->id)
            ->with(['tenantUser', 'agent', 'lead'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unit's invoices
        $invoices = Invoice::whereHas('lease', function($q) use ($unit) {
            $q->where('unit_id', $unit->id);
        })
        ->with(['lease.tenant'])
        ->orderBy('created_at', 'desc')
        ->get();

        // Get unit's tickets
        $tickets = \App\Models\Ticket::where('unit_id', $unit->id)
            ->with(['createdBy', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unit's meters
        $meters = \App\Models\Meter::where('unit_id', $unit->id)
            ->whereNull('deleted_at')
            ->with(['service', 'readings' => function($q) {
                $q->with('takenBy')->latest('reading_date')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $totalRevenue = Payment::whereHas('invoice', function($q) use ($unit) {
            $q->whereHas('lease', function($leaseQuery) use ($unit) {
                $leaseQuery->where('unit_id', $unit->id);
            });
        })->where('status', 'success')
        ->whereNull('deleted_at')
        ->sum('amount');

        // Calculate outstanding amount correctly
        $issuedInvoices = $invoices->whereIn('status', ['issued', 'overdue']);
        $outstandingAmount = $issuedInvoices->sum(function($invoice) {
            $paidAmount = $invoice->payments()
                ->where('status', 'success')
                ->whereNull('deleted_at')
                ->sum('amount');
            return max(0, $invoice->total_amount - $paidAmount);
        });

        $stats = [
            'total_leases' => $leases->count(),
            'active_leases' => $leases->where('status', 'active')->count(),
            'total_revenue' => $totalRevenue,
            'outstanding_amount' => $outstandingAmount,
            'booking_deposits' => $bookingDeposits->count(),
            'average_rent' => $leases->where('status', 'active')->avg('rent_amount')
        ];

        // For agent, check if unit's property is assigned to them
        $canViewAll = $this->canViewAll('asset.unit');
        if (!$canViewAll) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if (!$assignedPropertyIds->contains($unit->property_id)) {
                abort(403, 'Bạn không có quyền xem phòng này.');
            }
        }

        return view('staff.asset.units.show', compact('unit', 'leases', 'bookingDeposits', 'invoices', 'tickets', 'meters', 'stats'));
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->with([
                'property', 
                'amenities',
                'documents' => function($query) {
                    $query->where('document_type', 'image')
                          ->orderBy('sort_order')
                          ->orderBy('created_at');
                }
            ])
            ->findOrFail($id);

        // For agent, check if unit's property is assigned to them
        $canViewAll = $this->canViewAll('asset.unit');
        if (!$canViewAll) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if (!$assignedPropertyIds->contains($unit->property_id)) {
                abort(403, 'Bạn không có quyền chỉnh sửa phòng này.');
            }
        }

        // Check if unit is occupied or has active lease - block edit
        if ($unit->status === 'occupied' || $unit->is_rented) {
            return redirect()->route('staff.units.show', $id)
                ->with('warning', 'Không thể chỉnh sửa phòng đang ở trạng thái đã thuê. Vui lòng kết thúc hợp đồng thuê trước khi chỉnh sửa.');
        }

        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        } else {
            $properties = Property::whereIn('id', $assignedPropertyIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        }

        return view('staff.asset.units.edit', compact('unit', 'properties'));
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        if (!$this->checkCapability('asset.unit.update')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật phòng.'
            ], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->findOrFail($id);

        // Check if unit is occupied or has active lease - block all updates
        if ($unit->status === 'occupied' || $unit->is_rented) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật phòng đang ở trạng thái đã thuê. Vui lòng kết thúc hợp đồng thuê từ trang chi tiết trước khi chỉnh sửa.'
            ], 422);
        }

        // SECURITY CHECK: Prevent organization/property manipulation
        // Sanitize dangerous fields instead of blocking to prevent false positives
        $dangerousFields = ['organization_id', 'user_organization_id', 'org_id'];
        $providedDangerous = collect($request->only($dangerousFields))
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            });

        if ($providedDangerous->isNotEmpty()) {
            // Log warning instead of critical - this might be false positive from middleware/auto-injection
            Log::warning('Sanitized dangerous fields on unit update', [
                'unit_id' => $id,
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'sanitized_fields' => $providedDangerous->keys()->all(),
                'sanitized_values' => $providedDangerous->toArray()
            ]);
        }

        // Remove dangerous fields from request to prevent any manipulation
        foreach ($dangerousFields as $field) {
            if ($request->has($field)) {
                $request->request->remove($field);
            }
        }

        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'code' => 'required|string|max:50',
            'floor' => 'required|integer|min:0',
            'area_m2' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'base_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,maintenance,unavailable',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify property belongs to organization
            $property = Property::where('id', $request->property_id)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bất động sản không tồn tại hoặc không thuộc tổ chức của bạn.'
                ], 404);
            }

            // Check if unit code already exists in the same property (excluding current unit)
            $existingUnit = Unit::where('property_id', $request->property_id)
                ->where('code', $request->code)
                ->where('id', '!=', $id)
                ->whereNull('deleted_at')
                ->first();

            if ($existingUnit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã phòng đã tồn tại trong bất động sản này.'
                ], 422);
            }

            DB::beginTransaction();

            $unit->update([
                'property_id' => $request->property_id,
                'code' => $request->code,
                'floor' => $request->floor,
                'area_m2' => $request->area_m2,
                'unit_type' => $request->unit_type,
                'base_rent' => $request->base_rent,
                'deposit_amount' => $request->deposit_amount,
                'max_occupancy' => $request->max_occupancy,
                'status' => $request->status,
                'note' => $request->note,
            ]);

            // Process images - images are now stored in documents table
            // Delete marked images from documents
            if ($request->has('deleted_image_ids') && is_array($request->deleted_image_ids)) {
                try {
                    foreach ($request->deleted_image_ids as $documentId) {
                        $document = Document::find($documentId);
                        if ($document && $document->attachments()->where('unit_id', $unit->id)->exists()) {
                            // Delete attachment relationship
                            $document->attachments()->where('unit_id', $unit->id)->delete();
                            // Optionally delete document if not used elsewhere
                            if ($document->attachments()->count() === 0) {
                                // Delete file from storage (lưu trực tiếp vào public/storage)
                                $fullPath = public_path('storage/' . $document->file_url);
                                if (file_exists($fullPath)) {
                                    @unlink($fullPath);
                                }
                                $document->delete();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error deleting unit images: ' . $e->getMessage());
                    // Don't fail the entire request if image deletion fails
                }
            }
            
            // Upload new images and save to documents
            if ($request->hasFile('images')) {
                try {
                    // Get current max sort_order
                    $currentMaxSort = $unit->documents()
                        ->where('document_type', 'image')
                        ->max('sort_order') ?? -1;
                    
                    $uploadedImages = $this->imageService->uploadMultipleImages($request->file('images'), 'units');
                    $sortOrder = $currentMaxSort + 1;
                    
                    foreach ($uploadedImages as $uploadedImage) {
                        // ImageService đã trả về path đúng format (không có storage/ prefix)
                        $fileUrl = $uploadedImage['original'];
                        
                        // Validate file URL
                        if (empty($fileUrl)) {
                            Log::warning('Empty file URL for unit image in update', [
                                'unit_id' => $unit->id,
                                'uploaded_image' => $uploadedImage
                            ]);
                            continue;
                        }
                        
                        // Đảm bảo unit đã có ID
                        if (!$unit->id) {
                            throw new \Exception('Unit must be saved before attaching documents');
                        }
                        
                        $document = Document::create([
                            'owner_type' => Unit::class, // Giữ lại để backward compatibility
                            'owner_id' => $unit->id,     // Giữ lại để backward compatibility
                            'file_url' => $fileUrl,
                            'file_name' => basename($fileUrl),
                            'mime_type' => $uploadedImage['mime_type'] ?? 'image/jpeg',
                            'file_size' => $uploadedImage['size'] ?? null,
                            'document_type' => 'image',
                            'is_primary' => false, // Don't override existing primary
                            'uploaded_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        
                        // Đảm bảo document đã có ID
                        if (!$document->id) {
                            throw new \Exception('Document chưa có ID sau khi tạo');
                        }
                        
                        // Attach document to unit
                        // Document đã được tạo với owner_type và owner_id, không cần attachTo()
                        $sortOrder++;
                    }
                } catch (\Exception $e) {
                    Log::error('Error uploading unit images in update: ' . $e->getMessage(), [
                        'unit_id' => $unit->id ?? null,
                        'exception' => $e->getTraceAsString()
                    ]);
                    // Don't fail the entire request if image upload fails
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phòng thành công.',
                'redirect' => route('staff.units.show', $unit->id),
                'unit' => $unit->load('property')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật phòng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update unit status.
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        if (!$this->checkCapability('asset.unit.update')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái phòng.'
            ], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->findOrFail($id);

        // For agent, check if unit's property is assigned to them
        $canViewAll = $this->canViewAll('asset.unit');
        if (!$canViewAll) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if (!$assignedPropertyIds->contains($unit->property_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật trạng thái phòng này.'
                ], 403);
            }
        }

        $request->validate([
            'status' => 'required|in:available,occupied,maintenance,unavailable'
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $unit->status;
            $unit->status = $request->status;
            $unit->save();

            DB::commit();

            $statusLabels = [
                'available' => 'Trống',
                'occupied' => 'Đã thuê',
                'maintenance' => 'Bảo trì',
                'unavailable' => 'Không khả dụng'
            ];

            return response()->json([
                'success' => true,
                'message' => "Trạng thái phòng đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$request->status]}'.",
                'unit' => $unit->load('property')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái phòng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        if (!$this->checkCapability('asset.unit.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa phòng.'
            ], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $unit = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->findOrFail($id);

        // For agent, check if unit's property is assigned to them
        $canViewAll = $this->canViewAll('asset.unit');
        if (!$canViewAll) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if (!$assignedPropertyIds->contains($unit->property_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa phòng này.'
                ], 403);
            }
        }

        try {
            // Check if unit has active leases
            $activeLeases = Lease::where('unit_id', $unit->id)
                ->where('status', 'active')
                ->count();

            if ($activeLeases > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa phòng đang có hợp đồng hoạt động.'
                ], 400);
            }

            // Check if unit has pending booking deposits
            $pendingDeposits = BookingDeposit::where('unit_id', $unit->id)
                ->whereIn('payment_status', ['pending', 'paid'])
                ->count();

            if ($pendingDeposits > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa phòng đang có đặt cọc chưa xử lý.'
                ], 400);
            }

            // Soft delete unit - trait will automatically set deleted_by
            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa phòng thành công.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa phòng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unit statistics
     */
    public function statistics()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        try {
            $totalUnits = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->whereNull('deleted_at')->count();

            $availableUnits = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->where('status', 'available')
            ->whereNull('deleted_at')
            ->whereDoesntHave('leases', function($q) {
                $q->where('status', 'active');
            })->count();

            $occupiedUnits = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->whereHas('leases', function($q) {
                $q->where('status', 'active');
            })->whereNull('deleted_at')->count();

            $maintenanceUnits = Unit::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->where('status', 'maintenance')
            ->whereNull('deleted_at')->count();

            // Get total leases
            $totalLeases = Lease::whereHas('unit.property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->whereNull('deleted_at')->count();

            // Get active leases
            $activeLeases = Lease::whereHas('unit.property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->where('status', 'active')
            ->whereNull('deleted_at')->count();

            // Get total revenue - only from successful payments
            $totalRevenue = Payment::whereHas('invoice', function($q) use ($organizationId) {
                $q->whereHas('lease', function($leaseQuery) use ($organizationId) {
                    $leaseQuery->whereHas('unit.property', function($propertyQuery) use ($organizationId) {
                        $propertyQuery->where('organization_id', $organizationId);
                    });
                });
            })->where('payments.status', 'success')
            ->whereNull('payments.deleted_at')
            ->sum('payments.amount');

            // Get total outstanding - only from issued invoices
            $issuedInvoices = Invoice::whereHas('lease', function($q) use ($organizationId) {
                $q->whereHas('unit.property', function($propertyQuery) use ($organizationId) {
                    $propertyQuery->where('organization_id', $organizationId);
                });
            })->whereIn('invoices.status', ['issued', 'overdue'])
            ->whereNull('invoices.deleted_at')
            ->get();

            $totalOutstanding = $issuedInvoices->sum(function($invoice) {
                $paidAmount = $invoice->payments()
                    ->where('payments.status', 'success')
                    ->whereNull('payments.deleted_at')
                    ->sum('payments.amount');
                return max(0, $invoice->total_amount - $paidAmount);
            });

            // Get total booking deposits
            $totalDeposits = BookingDeposit::whereHas('unit.property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->whereNull('deleted_at')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_units' => $totalUnits,
                    'available_units' => $availableUnits,
                    'occupied_units' => $occupiedUnits,
                    'maintenance_units' => $maintenanceUnits,
                    'total_leases' => $totalLeases,
                    'active_leases' => $activeLeases,
                    'total_revenue' => $totalRevenue,
                    'total_outstanding' => $totalOutstanding,
                    'total_deposits' => $totalDeposits,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thống kê.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
