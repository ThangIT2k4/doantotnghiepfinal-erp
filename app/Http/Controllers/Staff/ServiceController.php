<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    use ChecksCapabilities;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.view', 'Bạn không có quyền xem danh sách dịch vụ.');
        
        // Redirect to system-settings with services tab
        return redirect()->route('staff.system-settings.index')->with('active_tab', 'services');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.create', 'Bạn không có quyền tạo dịch vụ.');
        
        return view('staff.settings.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.create', 'Bạn không có quyền tạo dịch vụ.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        $request->validate([
            'key_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'pricing_type' => 'nullable|string|in:fixed,per_unit,per_area',
            'unit_label' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_global' => 'nullable|boolean', // Only for system admin
        ], [
            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'name.max' => 'Tên dịch vụ không được vượt quá 255 ký tự.',
            'key_code.max' => 'Mã dịch vụ không được vượt quá 50 ký tự.',
            'pricing_type.in' => 'Loại giá không hợp lệ.',
            'unit_label.max' => 'Nhãn đơn vị không được vượt quá 50 ký tự.',
        ]);

        try {
            DB::beginTransaction();

            // Determine organization_id for the service
            // - If user not in any organization and chooses global: organization_id = null
            // - Otherwise: organization_id = current organization
            $serviceOrganizationId = null;
            
            if ($organizationId) {
                // User belongs to organization - always create org-specific service
                $serviceOrganizationId = $organizationId;
            } elseif ($request->is_global) {
                // User not in organization and chooses global - create global service
                $serviceOrganizationId = null;
            }

            // Check if key_code is unique within the scope (organization_id)
            if ($request->key_code) {
                $existingService = Service::where('key_code', $request->key_code)
                    ->where('organization_id', $serviceOrganizationId)
                    ->first();
                
                if ($existingService) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withErrors(['key_code' => 'Mã dịch vụ đã tồn tại trong phạm vi này.'])
                        ->withInput();
                }
            }

            $service = Service::create([
                'organization_id' => $serviceOrganizationId,
                'key_code' => $request->key_code,
                'name' => $request->name,
                'pricing_type' => $request->pricing_type ?? 'fixed',
                'unit_label' => $request->unit_label ?? 'tháng',
                'description' => $request->description,
            ]);

            DB::commit();

            Log::info('Service created', [
                'service_id' => $service->id,
                'name' => $service->name,
                'organization_id' => $service->organization_id,
                'is_global' => $service->isGlobal(),
                'created_by' => $user->id,
            ]);

            return redirect()->route('staff.services.index')
                ->with('success', 'Đã tạo dịch vụ thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating service: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.view', 'Bạn không có quyền xem chi tiết dịch vụ.');
        
        $service = Service::with(['meters', 'leaseServiceSetItems.leaseServiceSet'])
            ->findOrFail($id);

        return view('staff.settings.services.show', [
            'service' => $service,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.update', 'Bạn không có quyền sửa dịch vụ.');
        
        $service = Service::findOrFail($id);

        return view('staff.settings.services.edit', [
            'service' => $service,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.update', 'Bạn không có quyền cập nhật dịch vụ.');
        
        $service = Service::findOrFail($id);

        // Check permission: 
        // - User can only update services belonging to their organization
        // - Or global services if user not in any organization
        $organizationId = $this->getCurrentOrganizationId();
        
        // Check if user can edit this service
        $canEdit = $service->organization_id === $organizationId || 
                   ($service->organization_id === null && !$organizationId);
        
        if (!$canEdit) {
            return redirect()->back()
                ->withErrors(['error' => 'Bạn không có quyền chỉnh sửa dịch vụ này.'])
                ->withInput();
        }

        $request->validate([
            'key_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'pricing_type' => 'nullable|string|in:fixed,per_unit,per_area',
            'unit_label' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'name.max' => 'Tên dịch vụ không được vượt quá 255 ký tự.',
            'key_code.max' => 'Mã dịch vụ không được vượt quá 50 ký tự.',
            'pricing_type.in' => 'Loại giá không hợp lệ.',
            'unit_label.max' => 'Nhãn đơn vị không được vượt quá 50 ký tự.',
        ]);

        try {
            DB::beginTransaction();

            // Check if key_code is unique within the scope (excluding current service)
            if ($request->key_code) {
                $existingService = Service::where('key_code', $request->key_code)
                    ->where('organization_id', $service->organization_id)
                    ->where('id', '!=', $id)
                    ->first();
                
                if ($existingService) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withErrors(['key_code' => 'Mã dịch vụ đã tồn tại trong phạm vi này.'])
                        ->withInput();
                }
            }

            $service->update([
                'key_code' => $request->key_code,
                'name' => $request->name,
                'pricing_type' => $request->pricing_type ?? 'fixed',
                'unit_label' => $request->unit_label ?? 'tháng',
                'description' => $request->description,
            ]);

            DB::commit();

            Log::info('Service updated', [
                'service_id' => $service->id,
                'name' => $service->name,
                'updated_by' => $user->id,
            ]);

            return redirect()->route('staff.services.index')
                ->with('success', 'Đã cập nhật dịch vụ thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating service: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.delete', 'Bạn không có quyền xóa dịch vụ.');
        
        $service = Service::with(['meters', 'leaseServiceSetItems'])->findOrFail($id);

        // Check permission: 
        // - User can only delete services belonging to their organization
        // - Or global services if user not in any organization
        $organizationId = $this->getCurrentOrganizationId();
        
        // Check if user can delete this service
        $canDelete = $service->organization_id === $organizationId || 
                     ($service->organization_id === null && !$organizationId);
        
        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa dịch vụ này.'
            ], 403);
        }

        // Check if service is being used
        if ($service->meters->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa dịch vụ này vì đang có ' . $service->meters->count() . ' đồng hồ đang sử dụng.'
            ], 422);
        }

        if ($service->leaseServiceSetItems->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa dịch vụ này vì đang được sử dụng trong ' . $service->leaseServiceSetItems->count() . ' bộ dịch vụ.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $serviceName = $service->name;
            $service->delete();

            DB::commit();

            Log::info('Service deleted', [
                'service_id' => $id,
                'name' => $serviceName,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa dịch vụ thành công.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa dịch vụ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete unused services
     */
    public function deleteUnusedServices(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.service.delete', 'Bạn không có quyền xóa dịch vụ.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        try {
            DB::beginTransaction();

            // Get services based on user's organization
            $servicesQuery = Service::with(['meters', 'leaseServiceSetItems']);
            
            if ($organizationId) {
                // User in organization: only get services from their organization
                $servicesQuery->where('organization_id', $organizationId);
            }
            // User not in organization: get all services (global admin)
            
            $allServices = $servicesQuery->get();
            
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($allServices as $service) {
                // Check if service is being used
                $usedInMeters = $service->meters->count();
                $usedInLeaseServiceSets = $service->leaseServiceSetItems->count();

                // If not used, delete it
                if ($usedInMeters == 0 && $usedInLeaseServiceSets == 0) {
                    $service->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('Unused services deleted', [
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'deleted_by' => $user->id,
                'organization_id' => $organizationId,
            ]);

            DB::commit();

            $message = $deletedCount > 0 
                ? "Đã xóa {$deletedCount} dịch vụ không sử dụng thành công!"
                : "Không có dịch vụ nào cần xóa.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting unused services: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
}
