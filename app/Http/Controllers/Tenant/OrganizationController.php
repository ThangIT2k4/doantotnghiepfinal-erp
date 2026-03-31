<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrganizationController extends Controller
{
    /**
     * Switch to a different organization
     */
    public function switch(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập.'
                ], 401);
            }
            
            $organizationId = $request->input('organization_id');
            
            if (!$organizationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn tổ chức.'
                ], 422);
            }
            
            // Validate user thuộc organization này
            if (!$user->organizations()->where('organizations.id', $organizationId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức này.'
                ], 403);
            }
            
            // Switch organization
            $success = $user->switchOrganization($organizationId);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chuyển đổi tổ chức.'
                ], 500);
            }
            
            Log::info('User switched organization', [
                'user_id' => $user->id,
                'organization_id' => $organizationId
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển đổi tổ chức thành công.',
                'organization_id' => $organizationId,
                'organization' => $user->getCurrentOrganization()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error switching organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi chuyển đổi tổ chức: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get list of organizations user belongs to
     */
    public function list()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập.'
                ], 401);
            }
            
            $organizations = $user->organizations()
                ->with(['organizationUsers' => function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->with('role');
                }])
                ->get()
                ->map(function($org) use ($user) {
                    $orgUser = $org->organizationUsers->first();
                    return [
                        'id' => $org->id,
                        'name' => $org->name,
                        'code' => $org->code,
                        'role' => $orgUser?->role?->key_code ?? null,
                        'role_name' => $orgUser?->role?->name ?? null,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'organizations' => $organizations,
                'current_organization_id' => $user->getCurrentOrganizationId()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting organizations list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách tổ chức: ' . $e->getMessage()
            ], 500);
        }
    }
}

