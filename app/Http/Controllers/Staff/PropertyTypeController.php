<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Controller quản lý Property Types (Loại bất động sản) trong tổ chức (Asset module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách property types trong tổ chức (xem, tạo, sửa, xóa)
 * - Hỗ trợ cả organization-specific và global property types
 * - Quản lý thông tin property type: key_code, name, icon, description, status
 * - Tính toán statistics: total, active, inactive, properties count
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Chỉ manager mới có quyền tạo/sửa/xóa (agent chỉ xem)
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách property types với filters (search, status)
 *    - Filter theo organization_id (org-specific + global)
 *    - Tính statistics (total, active, inactive) bằng aggregation
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép (id, key_code, name, status, created_at, updated_at)
 *    - Eager load relationships (organization, properties count)
 * 2. create(): Hiển thị form tạo property type mới (chỉ manager)
 * 3. store(): Tạo property type mới với validation
 *    - Validate tất cả fields (key_code, name, icon, description, status)
 *    - Check key_code uniqueness trong scope (organization_id)
 *    - Create property type (org-specific hoặc global)
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết property type (properties, masterLeases, landlord)
 * 5. edit(): Hiển thị form edit property type (chỉ manager)
 * 6. update(): Cập nhật property type (key_code, name, icon, description, status)
 *    - Validate và check key_code uniqueness (excluding current)
 *    - Check permission: chỉ update property types của organization hoặc global
 * 7. destroy(): Xóa property type (soft delete)
 *    - Check permission: chỉ delete property types của organization hoặc global
 *    - Check nếu property type đang được sử dụng (có properties)
 *    - Không cho phép xóa nếu đang được sử dụng
 * 8. restore(): Khôi phục property type đã xóa (soft delete)
 * 9. forceDelete(): Xóa vĩnh viễn property type
 * 10. updateStatus(): API endpoint cập nhật status (active/inactive) (AJAX)
 * 11. getOptions(): API endpoint lấy property types cho select options (AJAX)
 * 12. deleteUnusedPropertyTypes(): Xóa tất cả property types không được sử dụng
 * 
 * ENDPOINTS:
 * - GET /staff/property-types: Danh sách property types (hỗ trợ HTMX/AJAX)
 * - GET /staff/property-types/create: Form tạo property type
 * - POST /staff/property-types: Tạo property type mới
 * - GET /staff/property-types/{id}: Chi tiết property type
 * - GET /staff/property-types/{id}/edit: Form edit property type
 * - PUT/PATCH /staff/property-types/{id}: Cập nhật property type
 * - DELETE /staff/property-types/{id}: Xóa property type
 * - POST /staff/property-types/{id}/restore: Khôi phục property type
 * - DELETE /staff/property-types/{id}/force: Xóa vĩnh viễn property type
 * - POST /staff/property-types/{id}/status: Cập nhật status (AJAX)
 * - GET /staff/property-types/options: Lấy options (AJAX)
 * - POST /staff/property-types/delete-unused: Xóa unused property types
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: PropertyType, Property
 * - Database tables: property_types, properties
 * - Request: search, status, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: property_types
 * - Không có thay đổi properties table (chỉ đọc để check usage)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (asset.access, asset.property_type.view, asset.property_type.create, etc.)
 * 
 * CAPABILITY CHECKING:
 * - asset.access: Quyền truy cập module Asset (required cho tất cả methods)
 * - asset.property_type.view: Quyền xem danh sách property types (index, show, getOptions)
 * - asset.property_type.create: Quyền tạo property type (create, store) - chỉ manager
 * - asset.property_type.update: Quyền cập nhật property type (edit, update, restore, updateStatus) - chỉ manager
 * - asset.property_type.delete: Quyền xóa property type (destroy, forceDelete, deleteUnusedPropertyTypes) - chỉ manager
 * 
 * OWNERSHIP FILTERING:
 * - Không có ownership filtering (property types là shared resource)
 * - User trong organization: Xem property types của organization + global
 * - User không trong organization (global admin): Xem tất cả property types
 * - Sử dụng forOrganization() scope để filter
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng withCount() để đếm properties trong một query
 * - Eager loading relationships (organization, properties) để tránh N+1 queries
 * - Tính statistics bằng aggregation (COUNT) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * - Sử dụng forOrganization() scope để filter hiệu quả
 * 
 * PROPERTY TYPE SCOPE:
 * - Organization-specific: property_type có organization_id = organization ID
 * - Global: property_type có organization_id = null (dùng cho tất cả organizations)
 * - User trong organization: Xem property types của organization + global
 * - User không trong organization: Xem tất cả property types (global admin)
 * 
 * KEY_CODE UNIQUENESS:
 * - key_code phải unique trong scope (organization_id)
 * - Có thể có cùng key_code ở các organizations khác nhau
 * - Global property types (organization_id = null) có key_code unique globally
 * 
 * VALIDATION:
 * - key_code: required, string, max:100, unique trong scope (organization_id)
 * - name: required, string, max:150
 * - icon: nullable, string, max:50
 * - description: nullable, string
 * - status: nullable, integer, in:0,1 (active/inactive)
 * - is_global: nullable, boolean (chỉ cho system admin)
 * 
 * SECURITY:
 * - Chỉ manager mới có quyền tạo/sửa/xóa property types
 * - Agent chỉ có quyền xem
 * - User chỉ có thể update/delete property types của organization hoặc global
 * - Validate sort fields để prevent SQL injection
 * - Check usage trước khi xóa (không cho phép xóa nếu đang được sử dụng)
 * 
 * LƯU Ý:
 * - Property types có thể là organization-specific hoặc global
 * - key_code phải unique trong scope (organization_id)
 * - Không cho phép xóa property type nếu đang được sử dụng bởi properties
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Properties count được tính với withoutGlobalScope('organization') để đếm tất cả properties
 * - Có thể xóa unused property types hàng loạt qua deleteUnusedPropertyTypes()
 */
class PropertyTypeController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Hiển thị danh sách property types với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: asset.access và asset.property_type.view
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Build query với forOrganization() scope (org-specific + global)
     * 4. Tính statistics (total, active, inactive) bằng aggregation
     * 5. Apply filters: search, status
     * 6. Apply sorting (validate sort fields)
     * 7. Paginate results (20 items per page)
     * 8. Eager load relationships (organization, properties count)
     * 9. Check request type (HTMX/AJAX):
     *     - HTMX: Return table partial HTML với stats update
     *     - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: property_types, properties (để đếm)
     * - Request: search, status, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - asset.access: Quyền truy cập module Asset
     * - asset.property_type.view: Quyền xem danh sách property types
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng withCount() để đếm properties trong một query
     * - Eager loading relationships để tránh N+1 queries
     * - Tính statistics bằng aggregation trong một query
     * - Validate sort fields để prevent SQL injection
     * 
     * FILTERS:
     * - search: Tìm kiếm theo name, key_code
     * - status: Filter theo status (active/inactive, hỗ trợ cả '1'/'0' và 'active'/'inactive')
     * 
     * SORTING:
     * - Supported fields: id, key_code, name, status, created_at, updated_at
     * - Default: id DESC
     * 
     * PROPERTY COUNT:
     * - Properties count được tính với withoutGlobalScope('organization') để đếm tất cả properties
     * - Không chỉ đếm properties trong organization (vì property type có thể được dùng bởi nhiều organizations)
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Asset.');
        }
        
        // Check capability - manager can manage all, agent can only view
        $this->requireCapability('asset.property_type.view', 'Bạn không có quyền xem Property Types.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Require organization - only show property types of the user's organization
        if (!$organizationId) {
            abort(403, 'Bạn phải thuộc một tổ chức để xem loại bất động sản.');
        }
        
        // Build base query for statistics (before filters)
        // Only get property types belonging to the organization (exclude global property types)
        $statsQuery = PropertyType::where('organization_id', $organizationId)
            ->withCount(['properties' => function($q) {
                // Temporarily disable organization scope for properties count
                $q->withoutGlobalScope('organization');
            }]);
        
        // Calculate statistics from base query
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'active' => (int) (clone $statsQuery)->where('status', 1)->count(),
            'inactive' => (int) (clone $statsQuery)->where('status', 0)->count(),
        ];
        
        // Build query for filtered results
        // Only get property types belonging to the organization (exclude global property types)
        $query = PropertyType::where('organization_id', $organizationId)
            ->with('organization')
            ->withCount(['properties' => function($q) {
                // Temporarily disable organization scope for properties count
                $q->withoutGlobalScope('organization');
            }]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key_code', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            // Handle both '1'/'0' and 'active'/'inactive' formats
            if ($request->status === '1' || $request->status === 'active') {
                $query->where('status', 1);
            } elseif ($request->status === '0' || $request->status === 'inactive') {
                $query->where('status', 0);
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map sort fields to actual database columns
        $sortableFields = [
            'id' => 'id',
            'key_code' => 'key_code',
            'name' => 'name',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        
        if (isset($sortableFields[$sortBy])) {
            $query->orderBy($sortableFields[$sortBy], $sortOrder);
        } else {
            $query->orderBy('id', 'desc');
        }

        $propertyTypes = $query->paginate(20);
        
        // Check if user has manage capability (only manager)
        $canManage = $this->checkCapability('asset.property_type.create');

        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            try {
                // Render table content
                $tableHtml = view('staff.asset.property-types.partials.table', [
                    'propertyTypes' => $propertyTypes,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                    'canManage' => $canManage
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
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'property-types-table-container',
                    'action' => route('staff.property-types.index'),
                    'columns' => 3
                ])->render();
                
                // Extract inner HTML from tableHtml (remove the outer wrapper div if exists)
                $innerTableHtml = $tableHtml;
                
                // Try to extract using DOMDocument for better HTML parsing
                if (class_exists('DOMDocument')) {
                    libxml_use_internal_errors(true);
                    $dom = new \DOMDocument();
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $xpath = new \DOMXPath($dom);
                    $container = $xpath->query('//div[@id="property-types-table-container"]')->item(0);
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
                    // Match the opening div with id="property-types-table-container" and extract everything inside
                    if (preg_match('/<div[^>]*id=["\']property-types-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) {
                        $innerTableHtml = trim($matches[1]);
                    }
                }
                
                // Return inner HTML with stats update via hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('PropertyTypeController HTMX Error: ' . $e->getMessage());
                $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500);
            }
        }

        return view('staff.asset.property-types.index', compact('propertyTypes', 'canManage', 'stats', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check capability - only manager can create
        $this->requireCapability('asset.property_type.create', 'Bạn không có quyền tạo Property Type.');
        
        return view('staff.asset.property-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can create
        $this->requireCapability('asset.property_type.create', 'Bạn không có quyền tạo Property Type.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Require organization - only allow creating property types for the user's organization
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để tạo loại bất động sản.'
            ], 403);
        }
        
        $validated = $request->validate([
            'key_code' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ], [
            'key_code.required' => 'Vui lòng nhập mã loại bất động sản.',
            'key_code.max' => 'Mã loại bất động sản không được vượt quá 100 ký tự.',
            'name.required' => 'Vui lòng nhập tên loại bất động sản.',
            'name.max' => 'Tên loại bất động sản không được vượt quá 150 ký tự.',
        ]);

        try {
            DB::beginTransaction();

            // Only create property types for the user's organization
            // Check if key_code is unique within the organization (including soft deleted records)
            $existingPropertyType = PropertyType::withTrashed()
                ->where('key_code', $validated['key_code'])
                ->where('organization_id', $organizationId)
                ->first();
            
            if ($existingPropertyType) {
                if ($existingPropertyType->trashed()) {
                    // If soft deleted record exists, force delete it first to avoid unique constraint violation
                    $existingPropertyType->forceDelete();
                } else {
                    // Active record exists
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mã loại bất động sản đã tồn tại trong tổ chức này.'
                    ], 422);
                }
            }

            $propertyType = PropertyType::create([
                'organization_id' => $organizationId,
                'key_code' => $validated['key_code'],
                'name' => $validated['name'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 1,
            ]);

            DB::commit();

            \Illuminate\Support\Facades\Log::info('PropertyType created', [
                'property_type_id' => $propertyType->id,
                'name' => $propertyType->name,
                'organization_id' => $propertyType->organization_id,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loại bất động sản đã được tạo thành công!',
                'property_type_id' => $propertyType->id,
                'redirect' => route('staff.property-types.show', $propertyType->id)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error creating property type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => ErrorHelper::getSafeErrorMessage($e, 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.')
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Check capability
        $this->requireCapability('asset.property_type.view', 'Bạn không có quyền xem Property Type.');
        
        $propertyType = PropertyType::withCount('properties')
            ->with(['properties.masterLeases.landlord'])
            ->findOrFail($id);
        
        // Check permission: User can only view property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        $this->checkOrganizationAccess(
            $propertyType->organization_id,
            'Bạn không có quyền xem loại bất động sản này. Chỉ có thể xem loại bất động sản của tổ chức bạn.',
            'property_type',
            $propertyType->id
        );
        
        // Check if user has manage capability (only manager)
        $canManage = $this->checkCapability('asset.property_type.create');
        
        return view('staff.asset.property-types.show', compact('propertyType', 'canManage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Check capability - only manager can edit
        $this->requireCapability('asset.property_type.update', 'Bạn không có quyền chỉnh sửa Property Type.');
        
        $propertyType = PropertyType::findOrFail($id);
        
        // Check permission: User can only edit property types belonging to their organization
        $this->checkOrganizationAccess(
            $propertyType->organization_id,
            'Bạn không có quyền chỉnh sửa loại bất động sản này. Chỉ có thể chỉnh sửa loại bất động sản của tổ chức bạn.',
            'property_type',
            $propertyType->id
        );
        
        return view('staff.asset.property-types.edit', compact('propertyType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update
        $this->requireCapability('asset.property_type.update', 'Bạn không có quyền cập nhật Property Type.');
        
        $propertyType = PropertyType::findOrFail($id);

        // Check permission: User can only update property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        // Require organization
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để chỉnh sửa loại bất động sản.'
            ], 403);
        }
        
        // Check if property type belongs to user's organization
        $this->checkOrganizationAccess(
            $propertyType->organization_id,
            'Bạn không có quyền chỉnh sửa loại bất động sản này. Chỉ có thể chỉnh sửa loại bất động sản của tổ chức bạn.',
            'property_type',
            $propertyType->id
        );

        $validated = $request->validate([
            'key_code' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ], [
            'key_code.required' => 'Vui lòng nhập mã loại bất động sản.',
            'key_code.max' => 'Mã loại bất động sản không được vượt quá 100 ký tự.',
            'name.required' => 'Vui lòng nhập tên loại bất động sản.',
            'name.max' => 'Tên loại bất động sản không được vượt quá 150 ký tự.',
        ]);

        try {
            DB::beginTransaction();

            // Check if key_code is unique within the scope (excluding current property type, including soft deleted records)
            $existingPropertyType = PropertyType::withTrashed()
                ->where('key_code', $validated['key_code'])
                ->where('organization_id', $propertyType->organization_id)
                ->where('id', '!=', $id)
                ->first();
            
            if ($existingPropertyType) {
                if ($existingPropertyType->trashed()) {
                    // If soft deleted record exists, force delete it first to avoid unique constraint violation
                    $existingPropertyType->forceDelete();
                } else {
                    // Active record exists
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mã loại bất động sản đã tồn tại trong phạm vi này.'
                    ], 422);
                }
            }

            $propertyType->update([
                'key_code' => $validated['key_code'],
                'name' => $validated['name'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 1,
            ]);

            DB::commit();

            \Illuminate\Support\Facades\Log::info('PropertyType updated', [
                'property_type_id' => $propertyType->id,
                'name' => $propertyType->name,
                'updated_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loại bất động sản đã được cập nhật thành công!',
                'redirect' => route('staff.property-types.show', $propertyType->id)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error updating property type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => ErrorHelper::getSafeErrorMessage($e, 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.')
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can delete
        $this->requireCapability('asset.property_type.delete', 'Bạn không có quyền xóa Property Type.');
        
        $propertyType = PropertyType::with('properties')->findOrFail($id);

        // Check permission: User can only delete property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        // Require organization
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để xóa loại bất động sản.'
            ], 403);
        }
        
        // Check if property type belongs to user's organization
        if ($propertyType->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa loại bất động sản này. Chỉ có thể xóa loại bất động sản của tổ chức bạn.'
            ], 403);
        }
        
        try {
            // Check if property type is being used
            $propertiesCount = $propertyType->properties()->count();
            if ($propertiesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa loại bất động sản này vì đang được sử dụng bởi {$propertiesCount} bất động sản."
                ], 422);
            }

            DB::beginTransaction();

            $propertyTypeName = $propertyType->name;
            $propertyType->delete();

            DB::commit();

            \Illuminate\Support\Facades\Log::info('PropertyType deleted', [
                'property_type_id' => $id,
                'name' => $propertyTypeName,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loại bất động sản đã được xóa thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error deleting property type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => ErrorHelper::getSafeErrorMessage($e, 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.')
            ], 500);
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        // Check capability - only manager can restore
        $this->requireCapability('asset.property_type.update', 'Bạn không có quyền khôi phục Property Type.');
        
        $propertyType = PropertyType::onlyTrashed()->findOrFail($id);
        
        // Check permission: User can only restore property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để khôi phục loại bất động sản.'
            ], 403);
        }
        
        if ($propertyType->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền khôi phục loại bất động sản này. Chỉ có thể khôi phục loại bất động sản của tổ chức bạn.'
            ], 403);
        }
        
        try {
            $propertyType->restore();

            return response()->json([
                'success' => true,
                'message' => 'Loại bất động sản đã được khôi phục thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ErrorHelper::getSafeErrorMessage($e, 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.')
            ], 500);
        }
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        // Check capability - only manager can force delete
        $this->requireCapability('asset.property_type.delete', 'Bạn không có quyền xóa vĩnh viễn Property Type.');
        
        $propertyType = PropertyType::withTrashed()->findOrFail($id);
        
        // Check permission: User can only force delete property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để xóa vĩnh viễn loại bất động sản.'
            ], 403);
        }
        
        if ($propertyType->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa vĩnh viễn loại bất động sản này. Chỉ có thể xóa loại bất động sản của tổ chức bạn.'
            ], 403);
        }
        
        try {
            $propertyType->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Loại bất động sản đã được xóa vĩnh viễn!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ErrorHelper::getSafeErrorMessage($e, 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.')
            ], 500);
        }
    }

    /**
     * Update property type status.
     */
    public function updateStatus(Request $request, $id)
    {
        // Check capability
        if (!$this->checkCapability('asset.property_type.update')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái loại bất động sản.'
            ], 403);
        }

        $propertyType = PropertyType::findOrFail($id);
        
        // Check permission: User can only update status of property types belonging to their organization
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để cập nhật trạng thái loại bất động sản.'
            ], 403);
        }
        
        if ($propertyType->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái loại bất động sản này. Chỉ có thể cập nhật loại bất động sản của tổ chức bạn.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|integer|in:0,1'
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $propertyType->status;
            $propertyType->status = $request->integer('status');
            $propertyType->save();

            DB::commit();

            $statusLabels = [
                1 => 'Hoạt động',
                0 => 'Tạm ngưng'
            ];

            return response()->json([
                'success' => true,
                'message' => "Trạng thái loại bất động sản đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$propertyType->status]}'.",
                'property_type' => $propertyType->loadCount('properties')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái loại bất động sản.',
                'error' => ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.')
            ], 500);
        }
    }

    /**
     * Get property types for API/Select options
     */
    public function getOptions()
    {
        // Check capability - anyone with asset.access can view options
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Only return property types of the user's organization (exclude global property types)
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn phải thuộc một tổ chức để xem loại bất động sản.'], 403);
        }
        
        $propertyTypes = PropertyType::where('organization_id', $organizationId)
            ->where('status', 1)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'key_code']);

        return response()->json($propertyTypes);
    }

    /**
     * Delete unused property types
     */
    public function deleteUnusedPropertyTypes(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.property_type.delete', 'Bạn không có quyền xóa loại bất động sản.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Require organization - only delete property types of the user's organization
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thuộc một tổ chức để xóa loại bất động sản.'
            ], 403);
        }
        
        try {
            DB::beginTransaction();

            // Only get property types from the user's organization (exclude global property types)
            $allPropertyTypes = PropertyType::where('organization_id', $organizationId)
                ->with('properties')
                ->get();
            
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($allPropertyTypes as $propertyType) {
                // Check if property type is being used
                $usedInProperties = $propertyType->properties->count();

                // If not used, delete it
                if ($usedInProperties == 0) {
                    $propertyType->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }

            \Illuminate\Support\Facades\Log::info('Unused property types deleted', [
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'deleted_by' => $user->id,
                'organization_id' => $organizationId,
            ]);

            DB::commit();

            $message = $deletedCount > 0 
                ? "Đã xóa {$deletedCount} loại bất động sản không sử dụng thành công!"
                : "Không có loại bất động sản nào cần xóa.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error deleting unused property types: ' . $e->getMessage());
            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
            return response()->json(['error' => $safeMessage], 500);
        }
    }
}
