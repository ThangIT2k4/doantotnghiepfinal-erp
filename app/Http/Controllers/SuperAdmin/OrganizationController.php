<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request)
    {
        $query = Organization::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'email', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $organizations = $query->withCount(['users', 'properties'])->paginate(15);

        return view('superadmin.organizations.index', compact('organizations'));
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create()
    {
        return view('superadmin.organizations.create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:organizations,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $organization = Organization::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'description' => $request->description,
                'status' => $request->status,
                'settings' => $request->settings ?? []
            ]);

            // Tự động gán subscription plan mặc định (FREE plan) cho organization mới
            $this->assignDefaultSubscriptionPlan($organization);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tổ chức đã được tạo thành công!',
                    'data' => $organization
                ]);
            }

            return redirect()->route('superadmin.organizations.index')
                ->with('success', 'Tổ chức đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo tổ chức',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo tổ chức')
                ->withInput();
        }
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization)
    {
        $organization->load(['users', 'properties']);
        
        // Load organization roles for each user
        $organization->users->each(function ($user) use ($organization) {
            $user->organization_roles = $user->organizationRoles($organization->id)->get();
        });
        
        // Get detailed statistics
        $stats = $this->getOrganizationDetailedStats($organization);
        
        return view('superadmin.organizations.show', compact('organization', 'stats'));
    }

    /**
     * Get detailed statistics for organization
     */
    private function getOrganizationDetailedStats($organization)
    {
        try {
            // User statistics by role
            $userStats = DB::table('users')
                ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $organization->id)
                ->whereNull('users.deleted_at')
                ->selectRaw('
                    COUNT(CASE WHEN roles.key_code = "tenant" THEN 1 END) as tenant_count,
                    COUNT(CASE WHEN roles.key_code = "agent" THEN 1 END) as agent_count,
                    COUNT(CASE WHEN roles.key_code = "manager" THEN 1 END) as manager_count,
                    COUNT(CASE WHEN roles.key_code = "admin" THEN 1 END) as admin_count,
                    COUNT(CASE WHEN roles.key_code = "landlord" THEN 1 END) as landlord_count,
                    COUNT(*) as total_users
                ')
                ->first();

            // Property statistics
            $propertyStats = DB::table('properties')
                ->where('organization_id', $organization->id)
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_properties,
                    COUNT(CASE WHEN status = 1 THEN 1 END) as active_properties,
                    COUNT(CASE WHEN status = 0 THEN 1 END) as inactive_properties
                ')
                ->first();

            // Unit statistics
            $unitStats = DB::table('units')
                ->join('properties', 'units.property_id', '=', 'properties.id')
                ->where('properties.organization_id', $organization->id)
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->selectRaw('
                    COUNT(*) as total_units,
                    COUNT(CASE WHEN units.status = "available" THEN 1 END) as available_units,
                    COUNT(CASE WHEN units.status = "occupied" THEN 1 END) as occupied_units,
                    COUNT(CASE WHEN units.status = "maintenance" THEN 1 END) as maintenance_units
                ')
                ->first();

            // Lease statistics (active contracts)
            $leaseStats = DB::table('leases')
                ->join('units', 'leases.unit_id', '=', 'units.id')
                ->join('properties', 'units.property_id', '=', 'properties.id')
                ->where('properties.organization_id', $organization->id)
                ->where('leases.status', 'active')
                ->whereNull('leases.deleted_at')
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->selectRaw('
                    COUNT(*) as active_leases,
                    SUM(leases.rent_amount) as total_monthly_rent,
                    AVG(leases.rent_amount) as avg_monthly_rent
                ')
                ->first();

            // Commission statistics
            $commissionStats = DB::table('commission_events')
                ->join('users', 'commission_events.agent_id', '=', 'users.id')
                ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
                ->where('organization_users.organization_id', $organization->id)
                ->whereNull('commission_events.deleted_at')
                ->whereNull('users.deleted_at')
                ->selectRaw('
                    COUNT(*) as total_commissions,
                    SUM(commission_events.commission_total) as total_commission_amount,
                    AVG(commission_events.commission_total) as avg_commission_amount
                ')
                ->first();

            // Recent activities
            $recentActivities = DB::table('audit_logs')
                ->join('users', 'audit_logs.actor_id', '=', 'users.id')
                ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
                ->where('organization_users.organization_id', $organization->id)
                ->orderBy('audit_logs.created_at', 'desc')
                ->limit(10)
                ->select('audit_logs.*', 'users.full_name as actor_name')
                ->get();

            return [
                'users' => $userStats,
                'properties' => $propertyStats,
                'units' => $unitStats,
                'leases' => $leaseStats,
                'commissions' => $commissionStats,
                'recent_activities' => $recentActivities
            ];

        } catch (\Exception $e) {
            Log::error('Error getting organization stats: ' . $e->getMessage());
            
            // Return default empty stats
            return [
                'users' => (object)[
                    'tenant_count' => 0,
                    'agent_count' => 0,
                    'manager_count' => 0,
                    'admin_count' => 0,
                    'landlord_count' => 0,
                    'total_users' => 0
                ],
                'properties' => (object)[
                    'total_properties' => 0,
                    'active_properties' => 0,
                    'inactive_properties' => 0
                ],
                'units' => (object)[
                    'total_units' => 0,
                    'available_units' => 0,
                    'occupied_units' => 0,
                    'maintenance_units' => 0
                ],
                'leases' => (object)[
                    'active_leases' => 0,
                    'total_monthly_rent' => 0,
                    'avg_monthly_rent' => 0
                ],
                'commissions' => (object)[
                    'total_commissions' => 0,
                    'total_commission_amount' => 0,
                    'avg_commission_amount' => 0
                ],
                'recent_activities' => collect()
            ];
        }
    }

    /**
     * Show the form for editing the specified organization.
     */
    public function edit(Organization $organization)
    {
        return view('superadmin.organizations.edit', compact('organization'));
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:organizations,email,' . $organization->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $organization->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'description' => $request->description,
                'status' => $request->status,
                'settings' => $request->settings ?? $organization->settings
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tổ chức đã được cập nhật thành công!',
                    'data' => $organization
                ]);
            }

            return redirect()->route('superadmin.organizations.index')
                ->with('success', 'Tổ chức đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật tổ chức',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật tổ chức')
                ->withInput();
        }
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Request $request, Organization $organization)
    {
        try {
            // Check if organization has users or properties
            if ($organization->users()->count() > 0) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa tổ chức có người dùng. Vui lòng chuyển người dùng sang tổ chức khác trước.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Không thể xóa tổ chức có người dùng.');
            }

            if ($organization->properties()->count() > 0) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa tổ chức có tài sản. Vui lòng chuyển tài sản sang tổ chức khác trước.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Không thể xóa tổ chức có tài sản.');
            }

            DB::beginTransaction();

            $organization->delete();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tổ chức đã được xóa thành công!'
                ]);
            }

            return redirect()->route('superadmin.organizations.index')
                ->with('success', 'Tổ chức đã được xóa thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa tổ chức',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Có lỗi xảy ra khi xóa tổ chức');
        }
    }

    /**
     * Toggle organization status.
     */
    public function toggleStatus(Request $request, Organization $organization)
    {
        try {
            $organization->update(['status' => !$organization->status]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trạng thái tổ chức đã được cập nhật thành công!',
                    'data' => [
                        'status' => $organization->status,
                        'status_text' => $organization->status ? 'Hoạt động' : 'Tạm dừng'
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Trạng thái tổ chức đã được cập nhật thành công!');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật trạng thái',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái');
        }
    }

    /**
     * Assign default subscription plan (FREE) to newly created organization
     */
    private function assignDefaultSubscriptionPlan(Organization $organization): void
    {
        try {
            // Tìm FREE plan (plan mặc định)
            $defaultPlan = SubscriptionPlan::where('code', 'FREE')
                ->where('is_active', true)
                ->first();

            if (!$defaultPlan) {
                // Nếu không có FREE plan, lấy plan đầu tiên có sort_order thấp nhất
                $defaultPlan = SubscriptionPlan::where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->first();
            }

            if ($defaultPlan) {
                $subscriptionService = new SubscriptionService();
                
                // Gán plan mặc định với trial
                // trial_days được lấy từ subscription_plans.trial_days
                // Nếu plan có trial_days > 0, subscription sẽ bắt đầu ở trạng thái 'trial'
                $subscriptionService->assignPlan(
                    $organization,
                    $defaultPlan,
                    'monthly', // payment cycle
                    false, // auto_renew
                    true, // startTrial - bắt đầu trial, sử dụng trial_days từ subscription_plans
                    'manual' // payment_gateway
                );

                Log::info('Default subscription plan assigned to new organization', [
                    'organization_id' => $organization->id,
                    'plan_id' => $defaultPlan->id,
                    'plan_code' => $defaultPlan->code,
                    'plan_name' => $defaultPlan->name,
                    'trial_days' => $defaultPlan->trial_days, // Lấy từ subscription_plans.trial_days
                ]);
            } else {
                Log::warning('No active subscription plan found to assign to new organization', [
                    'organization_id' => $organization->id,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail organization creation
            Log::error('Error assigning default subscription plan to organization: ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
