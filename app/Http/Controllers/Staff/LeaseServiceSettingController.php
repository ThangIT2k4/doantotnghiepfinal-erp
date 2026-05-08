<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Lease;
use App\Models\Service;
use App\Models\LeaseServiceSet;
use App\Models\LeaseServiceSetItem;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Controller quản lý Lease Service Settings (Cài đặt dịch vụ cho hợp đồng) trong tổ chức (Billing module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý các bộ dịch vụ (LeaseServiceSet) cho leases
 * - Mỗi bộ dịch vụ chứa nhiều services với giá cụ thể
 * - Có thể set default cho organization hoặc property
 * - Có thể apply bộ dịch vụ cho nhiều properties cùng lúc
 * - Quản lý cài đặt dịch vụ ở level organization và property
 * - Chỉ manager mới có quyền quản lý (agent chỉ xem)
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Redirect đến system-settings với tab lease-service
 * 2. storeSet(): Tạo bộ dịch vụ mới (LeaseServiceSet) với các services
 *    - Validate name, description, services (service_id, price)
 *    - Nếu is_default = true, unset tất cả default sets khác
 *    - Create LeaseServiceSet và LeaseServiceSetItems
 *    - Sử dụng transaction để đảm bảo data consistency
 * 3. updateSet(): Cập nhật bộ dịch vụ
 *    - Validate và update LeaseServiceSet
 *    - Delete existing items và create new items
 *    - Handle is_default logic
 * 4. destroySet(): Xóa bộ dịch vụ (soft delete)
 *    - Check permission và usage trước khi xóa
 * 5. deleteUnusedSets(): Xóa tất cả bộ dịch vụ không được sử dụng
 * 6. updateOrganization(): Cập nhật default lease service set cho organization
 * 7. updateProperty(): Cập nhật lease service set cho property cụ thể
 * 8. getSet(): API endpoint lấy chi tiết bộ dịch vụ (AJAX)
 * 9. store(): Tạo lease service setting (legacy method, có thể redirect đến storeSet)
 * 10. update(): Cập nhật lease service setting (legacy method)
 * 11. destroy(): Xóa lease service setting (legacy method)
 * 12. getPropertyLeases(): API endpoint lấy leases của property (AJAX)
 * 13. applyToProperties(): Apply bộ dịch vụ cho nhiều properties cùng lúc (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/lease-service-settings: Redirect đến system-settings
 * - POST /staff/lease-service-settings/sets: Tạo bộ dịch vụ mới
 * - PUT/PATCH /staff/lease-service-settings/sets/{id}: Cập nhật bộ dịch vụ
 * - DELETE /staff/lease-service-settings/sets/{id}: Xóa bộ dịch vụ
 * - POST /staff/lease-service-settings/delete-unused: Xóa unused sets
 * - POST /staff/lease-service-settings/organization: Cập nhật default cho organization
 * - POST /staff/lease-service-settings/property/{propertyId}: Cập nhật cho property
 * - GET /staff/lease-service-settings/sets/{id}: Lấy chi tiết bộ dịch vụ (AJAX)
 * - POST /staff/lease-service-settings: Tạo setting (legacy)
 * - PUT/PATCH /staff/lease-service-settings/{id}: Cập nhật setting (legacy)
 * - DELETE /staff/lease-service-settings/{id}: Xóa setting (legacy)
 * - GET /staff/lease-service-settings/property/{propertyId}/leases: Lấy leases (AJAX)
 * - POST /staff/lease-service-settings/apply-to-properties: Apply cho nhiều properties (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: LeaseServiceSet, LeaseServiceSetItem, Service, Organization, Property, Lease
 * - Database tables: lease_service_sets, lease_service_set_items, services, organizations, properties, leases
 * - Request: name, description, is_default, services (array), property_ids (array)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: lease_service_sets, lease_service_set_items, properties (lease_service_set_id)
 * - Không có thay đổi services, leases (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (billing.lease_service.view, billing.lease_service.create, etc.)
 * 
 * CAPABILITY CHECKING:
 * - billing.lease_service.view: Quyền xem lease service settings (index, getSet, getPropertyLeases)
 * - billing.lease_service.create: Quyền tạo lease service settings (storeSet, store, applyToProperties) - chỉ manager
 * - billing.lease_service.update: Quyền cập nhật lease service settings (updateSet, update, updateOrganization, updateProperty) - chỉ manager
 * - billing.lease_service.delete: Quyền xóa lease service settings (destroySet, destroy, deleteUnusedSets) - chỉ manager
 * 
 * OWNERSHIP FILTERING:
 * - Không có ownership filtering (lease service settings là organization-level resource)
 * - Tất cả users trong organization đều xem cùng danh sách settings
 * 
 * QUERY OPTIMIZATION:
 * - Eager loading relationships (items.service) để tránh N+1 queries
 * - Sử dụng transactions cho data consistency
 * - Batch operations cho applyToProperties
 * 
 * LEASE SERVICE SET:
 * - Một bộ dịch vụ (LeaseServiceSet) chứa nhiều services với giá cụ thể
 * - Mỗi service trong set có: service_id, price, sort_order, meta_json
 * - Có thể set is_default cho organization (mặc định cho tất cả properties)
 * - Có thể set lease_service_set_id cho property cụ thể (override default)
 * 
 * DEFAULT LOGIC:
 * - Nếu property có lease_service_set_id: Sử dụng bộ dịch vụ của property
 * - Nếu property không có lease_service_set_id: Sử dụng default của organization
 * - Nếu organization không có default: Không có dịch vụ nào được apply
 * 
 * VALIDATION:
 * - name: required, string, max:255
 * - description: nullable, string
 * - is_default: nullable, boolean
 * - services: required, array, min:1
 * - services.*.service_id: required, exists:services
 * - services.*.price: required, numeric, min:0
 * - property_ids: array, exists:properties (khi apply)
 * 
 * SECURITY:
 * - Chỉ manager mới có quyền tạo/sửa/xóa lease service settings
 * - Agent chỉ có quyền xem
 * - User chỉ có thể update/delete settings của organization
 * - Check usage trước khi xóa (không cho phép xóa nếu đang được sử dụng)
 * 
 * LƯU Ý:
 * - Lease service set được sử dụng để tính phí dịch vụ cho leases
 * - Mỗi property có thể có bộ dịch vụ riêng hoặc dùng default của organization
 * - Khi update set, tất cả items cũ được xóa và tạo lại (đảm bảo consistency)
 * - Có thể apply một bộ dịch vụ cho nhiều properties cùng lúc
 * - Unused sets có thể được xóa hàng loạt
 * - Legacy methods (store, update, destroy) có thể redirect đến methods mới
 */
class LeaseServiceSettingController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Hiển thị trang lease service settings (redirect đến system-settings)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capability: billing.lease_service.view
     * 2. Redirect đến system-settings với active_tab = 'lease-service'
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ redirect
     * 
     * CAPABILITY CHECKING:
     * - billing.lease_service.view: Quyền xem lease service settings
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.view', 'Bạn không có quyền truy cập Lease Service Settings.');
        
        // Redirect to system-settings with lease-service tab
        return redirect()->route('staff.system-settings.index')->with('active_tab', 'lease-service');
    }

    /**
     * Store a new lease service set
     */
    public function storeSet(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.create', 'Bạn không có quyền tạo Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.price' => 'required|numeric|min:0',
        ], [
            'name.required' => 'Vui lòng nhập tên bộ dịch vụ.',
            'services.required' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.min' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.*.service_id.required' => 'Vui lòng chọn dịch vụ.',
            'services.*.service_id.exists' => 'Dịch vụ không tồn tại.',
            'services.*.price.required' => 'Vui lòng nhập giá.',
            'services.*.price.numeric' => 'Giá phải là số.',
            'services.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        try {
            DB::beginTransaction();

            // If is_default is true, unset all other default sets
            if ($request->is_default) {
                LeaseServiceSet::where('organization_id', $organizationId)
                    ->update(['is_default' => false]);
            }

            // Create lease service set
            $leaseServiceSet = LeaseServiceSet::create([
                'organization_id' => $organizationId,
                'name' => $request->name,
                'description' => $request->description,
                'is_default' => $request->is_default ?? false,
            ]);

            // Add services to set
            foreach ($request->services as $index => $serviceData) {
                LeaseServiceSetItem::create([
                    'lease_service_set_id' => $leaseServiceSet->id,
                    'service_id' => $serviceData['service_id'],
                    'price' => $serviceData['price'],
                    'sort_order' => $index,
                    'meta_json' => $serviceData['meta_json'] ?? null,
                ]);
            }

            DB::commit();

            Log::info('Lease service set created', [
                'lease_service_set_id' => $leaseServiceSet->id,
                'organization_id' => $organizationId,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bộ dịch vụ thành công.',
                'leaseServiceSet' => $leaseServiceSet->load('items.service')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating lease service set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update lease service set
     */
    public function updateSet(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.update', 'Bạn không có quyền cập nhật Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get lease service set
        $leaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // If is_default is true, unset all other default sets
            if ($request->is_default) {
                LeaseServiceSet::where('organization_id', $organizationId)
                    ->where('id', '!=', $leaseServiceSet->id)
                    ->update(['is_default' => false]);
            }

            // Update lease service set
            $leaseServiceSet->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_default' => $request->is_default ?? false,
            ]);

            // Delete existing items
            $leaseServiceSet->items()->delete();

            // Add new services to set
            foreach ($request->services as $index => $serviceData) {
                LeaseServiceSetItem::create([
                    'lease_service_set_id' => $leaseServiceSet->id,
                    'service_id' => $serviceData['service_id'],
                    'price' => $serviceData['price'],
                    'sort_order' => $index,
                    'meta_json' => $serviceData['meta_json'] ?? null,
                ]);
            }

            DB::commit();

            Log::info('Lease service set updated', [
                'lease_service_set_id' => $leaseServiceSet->id,
                'organization_id' => $organizationId,
                'updated_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật bộ dịch vụ thành công.',
                'leaseServiceSet' => $leaseServiceSet->load('items.service')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating lease service set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete lease service set
     */
    public function destroySet($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.delete', 'Bạn không có quyền xóa Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get lease service set with relationships
        $leaseServiceSet = LeaseServiceSet::with(['properties', 'leases'])
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        // Check if lease service set is being used
        $usedInProperties = $leaseServiceSet->properties()->count();
        $usedInLeases = $leaseServiceSet->leases()->count();

        if ($usedInProperties > 0 || $usedInLeases > 0) {
            $message = 'Không thể xóa bộ dịch vụ này vì đang được sử dụng bởi ';
            $parts = [];
            if ($usedInProperties > 0) {
                $parts[] = $usedInProperties . ' bất động sản';
            }
            if ($usedInLeases > 0) {
                $parts[] = $usedInLeases . ' hợp đồng thuê';
            }
            $message .= implode(' và ', $parts) . '.';
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        try {
            DB::beginTransaction();

            $leaseServiceSetName = $leaseServiceSet->name;
            $leaseServiceSet->delete();

            DB::commit();

            Log::info('Lease service set deleted', [
                'lease_service_set_id' => $id,
                'name' => $leaseServiceSetName,
                'organization_id' => $organizationId,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa bộ dịch vụ thành công.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting lease service set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete unused lease service sets
     */
    public function deleteUnusedSets(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.delete', 'Bạn không có quyền xóa Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        try {
            DB::beginTransaction();

            // Get all lease service sets for this organization
            $allSets = LeaseServiceSet::where('organization_id', $organizationId)->get();
            
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($allSets as $set) {
                // Skip default sets
                if ($set->is_default) {
                    $skippedCount++;
                    continue;
                }

                // Check if set is being used
                $usedInProperties = Property::where('lease_services_id', $set->id)->count();
                $usedInLeases = Lease::where('lease_services_id', $set->id)->count();

                // If not used, delete it
                if ($usedInProperties == 0 && $usedInLeases == 0) {
                    $set->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('Unused lease service sets deleted', [
                'organization_id' => $organizationId,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'deleted_by' => $user->id,
            ]);

            DB::commit();

            $message = $deletedCount > 0 
                ? "Đã xóa {$deletedCount} bộ dịch vụ không sử dụng thành công!"
                : "Không có bộ dịch vụ nào cần xóa.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting unused lease service sets: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update organization's default lease service set
     */
    public function updateOrganization(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.update', 'Bạn không có quyền cập nhật Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $request->validate([
            'lease_service_set_id' => 'nullable|exists:lease_service_sets,id',
        ], [
            'lease_service_set_id.exists' => 'Bộ dịch vụ không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            // If lease_service_set_id is provided, set it as default
            if ($request->lease_service_set_id) {
                $leaseServiceSet = LeaseServiceSet::where('id', $request->lease_service_set_id)
                    ->where(function($query) use ($organizationId) {
                        $query->where('organization_id', $organizationId)
                              ->orWhereNull('organization_id');
                    })
                    ->firstOrFail();
                
                // Unset all other default sets for this organization
                LeaseServiceSet::where('organization_id', $organizationId)
                    ->where('id', '!=', $leaseServiceSet->id)
                    ->update(['is_default' => false]);
                
                // Set this set as default
                $leaseServiceSet->update(['is_default' => true]);
            }

            Log::info('Organization lease service set settings updated', [
                'organization_id' => $organizationId,
                'lease_service_set_id' => $request->lease_service_set_id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return back()->with('success', 'Đã cập nhật bộ dịch vụ mặc định cho tổ chức thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating organization lease service set settings: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Update property's lease service set
     */
    public function updateProperty(Request $request, $propertyId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.update', 'Bạn không có quyền cập nhật Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $request->validate([
            'lease_services_id' => 'nullable|exists:lease_service_sets,id',
        ], [
            'lease_services_id.exists' => 'Bộ dịch vụ không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            $property = Property::where('organization_id', $organizationId)
                ->where('id', $propertyId)
                ->firstOrFail();
            
            // Verify the set belongs to this organization
            if ($request->lease_services_id) {
                $leaseServiceSet = LeaseServiceSet::where('id', $request->lease_services_id)
                    ->where(function($q) use ($organizationId) {
                        $q->where('organization_id', $organizationId)
                          ->orWhereNull('organization_id');
                    })
                    ->firstOrFail();
            }

            $property->update([
                'lease_services_id' => $request->lease_services_id,
            ]);

            DB::commit();

            Log::info('Property lease service set updated', [
                'property_id' => $propertyId,
                'lease_services_id' => $request->lease_services_id,
                'updated_by' => $user->id,
            ]);

            return back()->with('success', 'Đã cập nhật bộ dịch vụ mặc định cho bất động sản thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating property lease service set: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Get lease service set details (API endpoint)
     */
    public function getSet($id)
    {
        try {
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
            }

            $leaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                ->where('id', $id)
                ->with('items.service')
                ->firstOrFail();

            // Get usage statistics
            $propertiesCount = Property::where('organization_id', $organizationId)
                ->where('lease_services_id', $leaseServiceSet->id)
                ->count();
            
            $leasesCount = Lease::where('organization_id', $organizationId)
                ->where('lease_services_id', $leaseServiceSet->id)
                ->count();

            $leaseServiceSet->properties_count = $propertiesCount;
            $leaseServiceSet->leases_count = $leasesCount;
            $leaseServiceSet->total_usage = $propertiesCount + $leasesCount;

            return response()->json([
                'success' => true,
                'leaseServiceSet' => $leaseServiceSet
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting lease service set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a new service to the organization's default set
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.create', 'Bạn không có quyền tạo Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'price' => 'required|numeric|min:0',
        ], [
            'service_id.required' => 'Vui lòng chọn dịch vụ.',
            'service_id.exists' => 'Dịch vụ không tồn tại.',
            'price.required' => 'Vui lòng nhập giá.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        try {
            DB::beginTransaction();

            // Get or create organization's default set
            $leaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();

            if (!$leaseServiceSet) {
                // Unset all other default sets for this organization
                LeaseServiceSet::where('organization_id', $organizationId)
                    ->update(['is_default' => false]);
                
                // Create default set for organization
                $leaseServiceSet = LeaseServiceSet::create([
                    'organization_id' => $organizationId,
                    'name' => 'Dịch vụ mặc định',
                    'description' => 'Bộ dịch vụ mặc định cho tổ chức',
                    'is_default' => true,
                ]);
            }

            // Check if service already exists in the set
            $existingItem = LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                ->where('service_id', $request->service_id)
                ->first();

            if ($existingItem) {
                DB::rollBack();
                return redirect()->back()->withErrors(['service_id' => 'Dịch vụ này đã tồn tại trong danh sách mặc định.'])->withInput();
            }

            // Get current max sort_order
            $maxSortOrder = LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                ->max('sort_order') ?? -1;

            // Add service to set
            $leaseServiceSetItem = LeaseServiceSetItem::create([
                'lease_service_set_id' => $leaseServiceSet->id,
                'service_id' => $request->service_id,
                'price' => $request->price,
                'sort_order' => $maxSortOrder + 1,
            ]);

            DB::commit();

            Log::info('Lease service added to default set', [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'organization_id' => $organizationId,
                'created_by' => $user->id,
            ]);

            return redirect()->route('staff.lease-service-settings.index')
                ->with('success', 'Đã thêm dịch vụ mặc định thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding lease service to default set: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update a service in the organization's default set
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.update', 'Bạn không có quyền cập nhật Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
        ], [
            'price.required' => 'Vui lòng nhập giá.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        try {
            DB::beginTransaction();

            // Get organization's default set
            $leaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();

            if (!$leaseServiceSet) {
                abort(404, 'Không tìm thấy bộ dịch vụ mặc định.');
            }

            // Get the item
            $leaseServiceSetItem = LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                ->where('id', $id)
                ->firstOrFail();

            // Update price
            $leaseServiceSetItem->update([
                'price' => $request->price,
            ]);

            DB::commit();

            Log::info('Lease service updated in default set', [
                'lease_service_set_item_id' => $id,
                'organization_id' => $organizationId,
                'updated_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật giá dịch vụ thành công.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating lease service in default set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a service from the organization's default set
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.delete', 'Bạn không có quyền xóa Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        try {
            DB::beginTransaction();

            // Get organization's default set
            $leaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();

            if (!$leaseServiceSet) {
                abort(404, 'Không tìm thấy bộ dịch vụ mặc định.');
            }

            // Get the item
            $leaseServiceSetItem = LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                ->where('id', $id)
                ->firstOrFail();

            // Delete the item
            $leaseServiceSetItem->delete();

            DB::commit();

            Log::info('Lease service deleted from default set', [
                'lease_service_set_item_id' => $id,
                'organization_id' => $organizationId,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa dịch vụ thành công.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting lease service from default set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get leases for a property
     */
    public function getPropertyLeases($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check capability
            $hasBillingAccess = $this->checkCapability('billing.lease_service.view');
            if (!$hasBillingAccess) {
                return response()->json(['success' => false, 'error' => 'Bạn không có quyền truy cập.'], 403);
            }
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json(['success' => false, 'error' => 'Bạn không thuộc tổ chức nào.'], 403);
            }

            // Get property with lease service set and organization
            $property = Property::where('organization_id', $organizationId)
                ->where('id', $propertyId)
                ->with(['leaseServiceSet.items.service', 'organization'])
                ->firstOrFail();

        // Get leases for this property with lease service sets
        $leases = Lease::where('organization_id', $organizationId)
            ->whereHas('unit', function($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })
            ->with(['unit', 'tenant', 'leaseServiceSet.items.service'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($lease) {
                $set = $lease->leaseServiceSet;
                return [
                    'id' => $lease->id,
                    'contract_no' => $lease->contract_no,
                    'unit_code' => $lease->unit->code ?? 'N/A',
                    'tenant_name' => $lease->tenant->full_name ?? 'N/A',
                    'status' => $lease->status,
                    'lease_service_set' => $set ? [
                        'id' => $set->id,
                        'name' => $set->name,
                        'description' => $set->description,
                        'items_count' => $set->items->count(),
                    ] : null,
                    'created_at' => $lease->created_at->format('d/m/Y'),
                ];
            });

        $effectiveSet = $property->getEffectiveLeaseServiceSet();

        return response()->json([
            'success' => true,
            'property' => [
                'id' => $property->id,
                'name' => $property->name,
                'lease_service_set' => $property->leaseServiceSet ? [
                    'id' => $property->leaseServiceSet->id,
                    'name' => $property->leaseServiceSet->name,
                    'description' => $property->leaseServiceSet->description,
                    'items_count' => $property->leaseServiceSet->items->count(),
                ] : null,
                'effective_lease_service_set' => $effectiveSet ? [
                    'id' => $effectiveSet->id,
                    'name' => $effectiveSet->name,
                    'description' => $effectiveSet->description,
                    'items_count' => $effectiveSet->items->count(),
                ] : null,
            ],
            'leases' => $leases
        ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Không tìm thấy bất động sản.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in getPropertyLeases: ' . $e->getMessage(), [
                'property_id' => $propertyId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply organization lease service set to all properties
     */
    public function applyToProperties(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.lease_service.update', 'Bạn không có quyền cập nhật Lease Service Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get user's organization
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        $request->validate([
            'apply_to_properties' => 'required|boolean',
        ]);

        if (!$request->apply_to_properties) {
            return back()->with('warning', 'Vui lòng xác nhận áp dụng cài đặt cho tất cả bất động sản.');
        }

        // Get default lease service set for this organization
        $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
            ->where('is_default', true)
            ->first();

        if (!$defaultLeaseServiceSet) {
            return back()->with('error', 'Tổ chức chưa có bộ dịch vụ mặc định. Vui lòng tạo bộ dịch vụ mặc định trước.');
        }

        try {
            DB::beginTransaction();

            // Get all properties for this organization
            $properties = Property::where('organization_id', $organizationId)
                ->where('status', 1)
                ->get();

            $updatedCount = 0;
            foreach ($properties as $property) {
                $property->update([
                    'lease_services_id' => $defaultLeaseServiceSet->id,
                ]);
                $updatedCount++;
            }

            DB::commit();

            Log::info('Default lease service set applied to properties', [
                'organization_id' => $organizationId,
                'lease_service_set_id' => $defaultLeaseServiceSet->id,
                'properties_updated' => $updatedCount,
                'applied_by' => $user->id,
            ]);

            return back()->with('success', "Đã áp dụng bộ dịch vụ mặc định cho {$updatedCount} bất động sản thành công!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying organization lease service set to properties: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi áp dụng cài đặt: ' . $e->getMessage());
        }
    }
}
