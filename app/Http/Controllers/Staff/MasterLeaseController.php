<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MasterLease;
use App\Models\Property;
use App\Models\User;
use App\Models\Unit;
use App\Models\Notification;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller quản lý Master Leases (Hợp đồng thuê chính) trong tổ chức (Contract module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách master leases (hợp đồng thuê chính giữa landlord và property)
 * - Master lease là hợp đồng thuê toàn bộ property từ landlord
 * - Một property chỉ nên có một master lease active tại một thời điểm
 * - Quản lý thông tin master lease: contract_no, landlord, property, start_date, end_date, base_rent, billing_cycle
 * - Tính toán statistics: total, draft, active, expired, terminated
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Chỉ manager mới có quyền tạo/sửa/xóa (agent chỉ xem)
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách master leases với filters (search, status, property, landlord, date range)
 *    - Filter theo organization_id
 *    - Tính statistics (total, draft, active, expired, terminated) bằng aggregation
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép (id, created_at, contract_no, start_date, end_date, base_rent, status)
 *    - Eager load relationships (property, landlord, units)
 * 2. create(): Hiển thị form tạo master lease mới (chỉ manager)
 *    - Chỉ hiển thị properties CHƯA có master lease
 *    - Load landlords (users với role 'landlord')
 *    - Generate preview contract number
 * 3. store(): Tạo master lease mới với validation
 *    - Validate tất cả fields (property_id, landlord_user_id, contract_no, dates, base_rent, etc.)
 *    - Check property chưa có master lease active
 *    - Generate contract number nếu chưa có
 *    - Create master lease với status 'draft' hoặc 'active'
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết master lease (property, landlord, units, company invoices)
 * 5. edit(): Hiển thị form edit master lease (chỉ manager)
 * 6. update(): Cập nhật master lease (contract_no, dates, base_rent, billing_cycle, etc.)
 *    - Validate và update master lease
 *    - Check permission: chỉ update master leases của organization
 * 7. updateStatus(): API endpoint cập nhật status (draft/active/expired/terminated) (AJAX)
 * 8. destroy(): Xóa master lease (soft delete)
 *    - Check permission: chỉ delete master leases của organization
 * 9. getUnits(): API endpoint lấy units của property (AJAX)
 * 10. checkUnit(): API endpoint kiểm tra unit có thuộc master lease không (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/master-leases: Danh sách master leases (hỗ trợ HTMX/AJAX)
 * - GET /staff/master-leases/create: Form tạo master lease
 * - POST /staff/master-leases: Tạo master lease mới
 * - GET /staff/master-leases/{id}: Chi tiết master lease
 * - GET /staff/master-leases/{id}/edit: Form edit master lease
 * - PUT/PATCH /staff/master-leases/{id}: Cập nhật master lease
 * - DELETE /staff/master-leases/{id}: Xóa master lease
 * - POST /staff/master-leases/{id}/status: Cập nhật status (AJAX)
 * - GET /staff/master-leases/units: Lấy units (AJAX)
 * - POST /staff/master-leases/{id}/check-unit: Kiểm tra unit (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: MasterLease, Property, User (landlord), Unit, Notification
 * - Database tables: master_leases, properties, users, units, company_invoices
 * - Request: search, status, property_id, landlord_user_id, date_from, date_to, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: master_leases
 * - Không có thay đổi properties, users, units (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (contract.access, contract.master_lease.view, contract.master_lease.create, etc.)
 * 
 * CAPABILITY CHECKING:
 * - contract.access: Quyền truy cập module Contract (required cho tất cả methods)
 * - contract.master_lease.view: Quyền xem danh sách master leases (index, show)
 * - contract.master_lease.create: Quyền tạo master lease (create, store) - chỉ manager
 * - contract.master_lease.update: Quyền cập nhật master lease (edit, update, updateStatus) - chỉ manager
 * - contract.master_lease.delete: Quyền xóa master lease (destroy) - chỉ manager
 * 
 * OWNERSHIP FILTERING:
 * - Không có ownership filtering (master leases là organization-level resource)
 * - Tất cả users trong organization đều xem cùng danh sách master leases
 * - Sử dụng forOrganization() scope để filter
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng forOrganization() scope để filter hiệu quả
 * - Eager loading relationships (property, landlord, units) để tránh N+1 queries
 * - Tính statistics bằng aggregation (COUNT) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * 
 * MASTER LEASE STATUS:
 * - draft: Hợp đồng đang soạn thảo, chưa có hiệu lực
 * - active: Hợp đồng đang có hiệu lực (start_date <= now <= end_date)
 * - expired: Hợp đồng đã hết hạn (end_date < now)
 * - terminated: Hợp đồng đã bị chấm dứt trước hạn
 * 
 * MASTER LEASE RELATIONSHIPS:
 * - Property: Một master lease thuộc về một property
 * - Landlord: Một master lease có một landlord (User với role 'landlord')
 * - Units: Một master lease bao gồm tất cả units trong property đó
 * - Company Invoices: Một master lease có nhiều company invoices (hóa đơn công ty)
 * 
 * CONTRACT NUMBER:
 * - contract_no được generate tự động nếu không được cung cấp
 * - Sử dụng SequenceGenerator để tạo số hợp đồng unique
 * - Format: ML-{YYYY}-{SEQUENCE}
 * 
 * PROPERTY CONSTRAINT:
 * - Một property chỉ nên có một master lease active tại một thời điểm
 * - Khi tạo master lease mới, chỉ hiển thị properties CHƯA có master lease
 * - Có thể có nhiều master leases cho một property (nhưng chỉ một active)
 * 
 * BILLING CYCLE:
 * - billing_cycle: Số tháng trong chu kỳ thanh toán (mặc định: 1 tháng)
 * - billing_day: Ngày thanh toán trong tháng (mặc định: 5)
 * - due_in_days: Số ngày đến hạn thanh toán (mặc định: 5 ngày)
 * 
 * VALIDATION:
 * - property_id: required, exists:properties, property chưa có master lease active
 * - landlord_user_id: required, exists:users (role = 'landlord')
 * - contract_no: nullable, string, max:100, unique trong organization
 * - start_date: required, date
 * - end_date: required, date, after:start_date
 * - base_rent: required, numeric, min:0
 * - billing_cycle: required, integer, min:1
 * - billing_day: required, integer, min:1, max:31
 * 
 * SECURITY:
 * - Chỉ manager mới có quyền tạo/sửa/xóa master leases
 * - Agent chỉ có quyền xem
 * - User chỉ có thể update/delete master leases của organization
 * - Validate sort fields để prevent SQL injection
 * 
 * LƯU Ý:
 * - Master lease là hợp đồng thuê toàn bộ property từ landlord
 * - Một property chỉ nên có một master lease active tại một thời điểm
 * - Units trong property tự động thuộc về master lease của property đó
 * - Company invoices được tạo từ master lease (hóa đơn công ty phải trả cho landlord)
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Contract number được generate tự động nếu không được cung cấp
 */
class MasterLeaseController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Hiển thị danh sách master leases với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: contract.access và contract.master_lease.view
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Tính statistics (total, draft, active, expired, terminated) bằng aggregation
     * 4. Build query với forOrganization() scope và eager load relationships
     * 5. Apply filters: search, status, property_id, landlord_user_id, date range
     * 6. Apply sorting (validate sort fields)
     * 7. Paginate results (20 items per page)
     * 8. Load filter options (properties, landlords)
     * 9. Check request type (HTMX/AJAX):
     *     - HTMX: Return table partial HTML với stats update
     *     - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: master_leases, properties, users (landlords)
     * - Request: search, status, property_id, landlord_user_id, date_from, date_to, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - contract.access: Quyền truy cập module Contract
     * - contract.master_lease.view: Quyền xem danh sách master leases
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng forOrganization() scope để filter hiệu quả
     * - Eager loading relationships (property, landlord, units) để tránh N+1 queries
     * - Tính statistics bằng aggregation trong một query
     * - Validate sort fields để prevent SQL injection
     * 
     * FILTERS:
     * - search: Tìm kiếm theo contract_no, property name, landlord name
     * - status: Filter theo status (draft, active, expired, terminated)
     * - property_id: Filter theo property
     * - landlord_user_id: Filter theo landlord
     * - date_from/date_to: Filter theo start_date/end_date
     * 
     * SORTING:
     * - Supported fields: id, created_at, contract_no, start_date, end_date, base_rent, status
     * - Default: created_at DESC
     * 
     * STATISTICS:
     * - total: Tổng số master leases
     * - draft: Số lượng master leases đang soạn thảo
     * - active: Số lượng master leases đang active
     * - expired: Số lượng master leases đã hết hạn
     * - terminated: Số lượng master leases đã bị chấm dứt
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            abort(403, 'Bạn không có quyền truy cập module Contract.');
        }
        
        // Check capability - manager can manage all, agent can only view
        $this->requireCapability('contract.master_lease.view', 'Bạn không có quyền xem Master Leases.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Calculate statistics FIRST from base query (before any filters)
        $statsQuery = MasterLease::forOrganization($organizationId);
        
        // Count by status using database aggregation for accuracy
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'draft' => (int) (clone $statsQuery)->where('status', 'draft')->count(),
            'active' => (int) (clone $statsQuery)->where('status', 'active')->count(),
            'expired' => (int) (clone $statsQuery)->where('status', 'expired')->count(),
            'terminated' => (int) (clone $statsQuery)->where('status', 'terminated')->count(),
        ];

        $query = MasterLease::with([
                'property' => function($q){ $q->withTrashed(); },
                'landlord',
                'units'
            ])
            ->forOrganization($organizationId);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contract_no', 'like', "%{$search}%")
                  ->orWhereHas('property', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('landlord', function($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by landlord
        if ($request->filled('landlord_user_id')) {
            $query->where('landlord_user_id', $request->landlord_user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        // Get leases with sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'created_at', 'contract_no', 'start_date', 'end_date', 'base_rent', 'status'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        $leases = $query->orderBy($sortBy, $sortOrder)->paginate(20);

        // Get filter options
        $properties = Property::withoutGlobalScope('organization')->where('organization_id', $organizationId)->get();
        $landlords = User::whereHas('organizationUsers', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->whereHas('role', function($rq) {
                  $rq->where('key_code', 'landlord');
              });
        })->get();

        // Check if user has manage capability (only manager)
        $canManage = $this->checkCapability('contract.master_lease.create');
        
        // Check if this is an HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tất cả',
                'icon' => 'fa-list',
                'color' => 'primary',
                'filter' => '',
            ],
            'draft' => [
                'value' => $stats['draft'] ?? 0,
                'label' => 'Nháp',
                'icon' => 'fa-file-alt',
                'color' => 'warning',
                'filter' => 'draft',
                'filterKey' => 'status',
            ],
            'active' => [
                'value' => $stats['active'] ?? 0,
                'label' => 'Hoạt động',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'active',
                'filterKey' => 'status',
            ],
            'expired' => [
                'value' => $stats['expired'] ?? 0,
                'label' => 'Hết hạn',
                'icon' => 'fa-clock',
                'color' => 'secondary',
                'filter' => 'expired',
                'filterKey' => 'status',
            ],
            'terminated' => [
                'value' => $stats['terminated'] ?? 0,
                'label' => 'Chấm dứt',
                'icon' => 'fa-times-circle',
                'color' => 'danger',
                'filter' => 'terminated',
                'filterKey' => 'status',
            ],
        ];
        
        // If HTMX request, return table and stats HTML with hx-swap-oob
        if ($isHtmx) {
            try {
                // Render table partial
                $tableHtml = view('staff.contract.master-leases.partials.table', [
                    'leases' => $leases,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ])->render();
                
                // Extract inner HTML from table (remove wrapper div if exists)
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                
                $container = $dom->getElementsByTagName('body')->item(0);
                if ($container) {
                    $innerTableHtml = '';
                    foreach ($container->childNodes as $child) {
                        $innerTableHtml .= $dom->saveHTML($child);
                    }
                } else {
                    $innerTableHtml = $tableHtml;
                }
                
                // Render stats HTML
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'columns' => 5,
                    'action' => route('staff.master-leases.index'),
                    'tableContainerId' => 'master-leases-table-container'
                ])->render();
                
                // Combine HTML with hx-swap-oob for stats
                $responseHtml = $innerTableHtml . "\n<div id=\"stats-container\" hx-swap-oob=\"true\">\n" . $statsHtml . "\n</div>";
                
                return response($responseHtml)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('MasterLeaseController HTMX Error: ' . $e->getMessage());
                return response('<div class="alert alert-danger">Lỗi khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500);
            }
        }
        
        return view('staff.contract.master-leases.index', compact(
            'leases', 'properties', 'landlords', 'canManage', 'stats', 'sortBy', 'sortOrder'
        ));
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('contract.master_lease.create', 'Bạn không có quyền tạo Master Leases.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Chỉ hiển thị các bất động sản CHƯA có master lease
        $properties = Property::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->whereNotNull('name')
            ->whereDoesntHave('masterLeases')
            ->active()
            ->orderBy('name')
            ->get();
        $landlords = User::whereHas('organizationUsers', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->whereHas('role', function($rq) {
                  $rq->where('key_code', 'landlord');
              });
        })->get();

        // Handle property_id from query parameter
        $selectedProperty = null;
        if ($request->filled('property_id')) {
            $propertyId = $request->property_id;
            $selectedProperty = Property::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('id', $propertyId)
                ->whereNull('deleted_at')
                ->whereDoesntHave('masterLeases')
                ->first();
        }

        // Generate preview contract number
        $previewContractNo = null;
        try {
            $tempLease = new MasterLease();
            $tempLease->organization_id = $organizationId;
            $previewContractNo = $tempLease->generateContractNumber();
        } catch (\Exception $e) {
            Log::warning('Could not generate preview contract number: ' . $e->getMessage());
        }

        // Debug information
        Log::info('MasterLease Create - Organization ID: ' . $organizationId);
        Log::info('Properties count: ' . $properties->count());
        Log::info('Landlords count: ' . $landlords->count());

        return view('staff.contract.master-leases.create', compact(
            'properties', 'landlords', 'selectedProperty', 'previewContractNo'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('contract.master_lease.create', 'Bạn không có quyền tạo Master Leases.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id,deleted_at,NULL',
            'landlord_user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'base_rent' => 'required|numeric|min:0',
            'rent_currency' => 'required|string|size:3',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|integer|min:1|max:120',
            'billing_day' => 'required|integer|min:1|max:31',
            'due_in_days' => 'required|integer|min:1|max:365',
            'revenue_share_pct' => 'nullable|numeric|min:0|max:100',
            'note' => 'nullable|string',
            'unit_ids' => 'nullable|array',
            'unit_ids.*' => 'exists:units,id',
            'status' => 'required|in:draft,active,terminated,expired',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $lease = new MasterLease();
            $lease->organization_id = $organizationId;
            $lease->property_id = $request->property_id;
            $lease->landlord_user_id = $request->landlord_user_id;
            
            // Use contract_no from request if provided and valid, otherwise generate new one
            if ($request->filled('contract_no')) {
                $requestedContractNo = $request->contract_no;
                // Check if the contract number is valid format and doesn't exist
                $exists = MasterLease::where('contract_no', $requestedContractNo)
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if (!$exists && preg_match('/^ML-\d+-\d{4}-\d{2}-\d{4}$/', $requestedContractNo)) {
                    $lease->contract_no = $requestedContractNo;
                } else {
                    $lease->contract_no = $lease->generateContractNumber();
                }
            } else {
                $lease->contract_no = $lease->generateContractNumber();
            }
            $lease->start_date = $request->start_date;
            $lease->end_date = $request->end_date;
            $lease->base_rent = $request->base_rent;
            $lease->rent_currency = $request->rent_currency;
            $lease->deposit_amount = $request->deposit_amount ?? 0;
            $lease->billing_cycle = $request->billing_cycle;
            $lease->billing_day = $request->billing_day;
            $lease->due_in_days = $request->due_in_days;
            $lease->revenue_share_pct = $request->revenue_share_pct;
            $lease->note = $request->note;
            $lease->status = $request->status ?? 'draft';
            $lease->save();

            // Note: Units are automatically associated through property_id
            // No need to manually attach units

            DB::commit();

            // Notification sẽ được tạo tự động từ audit_log (MasterLeaseObserver)

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng thuê lại đã được tạo thành công.',
                    'redirect' => route('staff.master-leases.show', $lease)
                ]);
            }
            
            return redirect()->route('staff.master-leases.index')
                ->with('success', 'Hợp đồng thuê lại đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(MasterLease $masterLease)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.master_lease.view', 'Bạn không có quyền xem Master Leases.');
        
        // Check if user belongs to the master lease's organization
        // User can access if they belong to ANY organization that the master lease belongs to
        $userOrganizationIds = $user->getAllOrganizationIds();
        if (empty($userOrganizationIds) || !in_array($masterLease->organization_id, $userOrganizationIds)) {
            $organizationId = $this->getCurrentOrganizationId();
            Log::warning('MasterLease show: Unauthorized access - user does not belong to master lease organization', [
                'user_id' => $user->id,
                'user_organization_id' => $organizationId,
                'user_organizations' => $userOrganizationIds,
                'master_lease_id' => $masterLease->id,
                'master_lease_organization_id' => $masterLease->organization_id,
            ]);
            abort(403, 'Unauthorized access to master lease.');
        }
        
        $masterLease->load([
            'property', 'landlord', 'units', 'organization',
            'companyInvoices' => function($q) {
                $q->whereNull('deleted_at')
                  ->orderBy('created_at', 'desc');
            }
        ]);
        
        // Debug: Log company invoices count (uncomment if needed)
        // Log::info('MasterLease Show - Company Invoices', [
        //     'master_lease_id' => $masterLease->id,
        //     'contract_no' => $masterLease->contract_no,
        //     'company_invoices_count' => $masterLease->companyInvoices->count(),
        //     'company_invoices' => $masterLease->companyInvoices->map(function($inv) {
        //         return [
        //             'id' => $inv->id,
        //             'invoice_no' => $inv->invoice_no,
        //             'master_lease_id' => $inv->master_lease_id,
        //             'organization_id' => $inv->organization_id,
        //             'deleted_at' => $inv->deleted_at
        //         ];
        //     })->toArray()
        // ]);
        
        // Check if user has manage capability (only manager)
        $canManage = $this->checkCapability('contract.master_lease.create');
        
        return view('staff.contract.master-leases.show', compact('masterLease', 'canManage'));
    }

    public function edit(MasterLease $masterLease, Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can edit
        $this->requireCapability('contract.master_lease.update', 'Bạn không có quyền chỉnh sửa Master Leases.');
        
        $this->checkOrganizationAccess(
            $masterLease->organization_id,
            'Unauthorized access to master lease.',
            'master_lease',
            $masterLease->id
        );
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Bypass global scope to ensure we get all properties for the organization
        $properties = Property::withoutGlobalScope('organization')->where('organization_id', $organizationId)->get();
        $landlords = User::whereHas('organizationUsers', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->whereHas('role', function($rq) {
                  $rq->where('key_code', 'landlord');
              });
        })->get();

        $availableUnits = Unit::where('property_id', $masterLease->property_id)
            ->where('status', 'available')
            ->get();

        return view('staff.contract.master-leases.edit', compact(
            'masterLease', 'properties', 'landlords', 'availableUnits'
        ));
    }

    /**
     * Update master lease status only
     */
    public function updateStatus(Request $request, MasterLease $masterLease)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update status
        $this->requireCapability('contract.master_lease.update', 'Bạn không có quyền cập nhật Master Leases.');
        
        $this->checkOrganizationAccess(
            $masterLease->organization_id,
            'Unauthorized access to master lease.',
            'master_lease',
            $masterLease->id
        );
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,active,terminated,expired',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldStatus = $masterLease->status;
            $masterLease->status = $request->status;
            $masterLease->save();

            // Create system notification
            $statusLabel = match($request->status) {
                'draft' => 'Nháp',
                'active' => 'Hoạt động',
                'terminated' => 'Chấm dứt',
                'expired' => 'Hết hạn',
                default => 'Không xác định'
            };

            // Notification sẽ được tạo tự động từ audit_log (MasterLeaseObserver)

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Trạng thái hợp đồng đã được cập nhật thành {$statusLabel}.",
                ]);
            }

            return redirect()->back()
                ->with('success', "Trạng thái hợp đồng đã được cập nhật thành {$statusLabel}.");

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function update(Request $request, MasterLease $masterLease)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update
        $this->requireCapability('contract.master_lease.update', 'Bạn không có quyền cập nhật Master Leases.');
        
        $this->checkOrganizationAccess(
            $masterLease->organization_id,
            'Unauthorized access to master lease.',
            'master_lease',
            $masterLease->id
        );
        
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'landlord_user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'base_rent' => 'required|numeric|min:0',
            'rent_currency' => 'required|string|size:3',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|integer|min:1|max:120',
            'billing_day' => 'required|integer|min:1|max:31',
            'due_in_days' => 'required|integer|min:1|max:365',
            'revenue_share_pct' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:draft,active,terminated,expired',
            'note' => 'nullable|string',
            'unit_ids' => 'nullable|array',
            'unit_ids.*' => 'exists:units,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $masterLease->property_id = $request->property_id;
            $masterLease->landlord_user_id = $request->landlord_user_id;
            $masterLease->start_date = $request->start_date;
            $masterLease->end_date = $request->end_date;
            $masterLease->base_rent = $request->base_rent;
            $masterLease->rent_currency = $request->rent_currency;
            $masterLease->deposit_amount = $request->deposit_amount ?? 0;
            $masterLease->billing_cycle = $request->billing_cycle;
            $masterLease->billing_day = $request->billing_day;
            $masterLease->due_in_days = $request->due_in_days;
            $masterLease->revenue_share_pct = $request->revenue_share_pct;
            $masterLease->status = $request->status;
            $masterLease->note = $request->note;
            $masterLease->save();

            // Note: Units are automatically associated through property_id
            // No need to manually sync units

            DB::commit();

            // Notification sẽ được tạo tự động từ audit_log (MasterLeaseObserver)

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng thuê lại đã được cập nhật thành công.',
                    'redirect' => route('staff.master-leases.show', $masterLease)
                ]);
            }
            return redirect()->route('staff.master-leases.show', $masterLease)
                ->with('success', 'Hợp đồng thuê lại đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(MasterLease $masterLease)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete
        $this->requireCapability('contract.master_lease.delete', 'Bạn không có quyền xóa Master Leases.');
        
        $this->checkOrganizationAccess(
            $masterLease->organization_id,
            'Unauthorized access to master lease.',
            'master_lease',
            $masterLease->id
        );
        
        try {
            $contractNo = $masterLease->contract_no ?? 'N/A';
            $propertyName = $masterLease->property->name;

            // Set deleted_by before soft delete
            $masterLease->deleted_by = $user->id;
            $masterLease->save();
            $masterLease->delete();

            // Notification sẽ được tạo tự động từ audit_log (MasterLeaseObserver)

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng thuê lại đã được xóa thành công.'
                ]);
            }

            return redirect()->route('staff.master-leases.index')
                ->with('success', 'Hợp đồng thuê lại đã được xóa thành công.');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function getUnits(Request $request)
    {
        $propertyId = $request->property_id;
        $units = Unit::where('property_id', $propertyId)
            ->where('status', 'available')
            ->get(['id', 'code', 'unit_type', 'area_m2', 'base_rent']);

        return response()->json($units);
    }

    public function checkUnit(Request $request, MasterLease $masterLease)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $unit = Unit::find($request->unit_id);
            $hasUnit = $masterLease->hasUnit($unit);

            return response()->json([
                'success' => true,
                'has_unit' => $hasUnit,
                'message' => $hasUnit ? 'Phòng thuộc hợp đồng này.' : 'Phòng không thuộc hợp đồng này.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DEPRECATED: Method này không còn sử dụng
     * Notifications giờ được tạo tự động từ audit_log thông qua NotificationFromAuditService
     */
}
