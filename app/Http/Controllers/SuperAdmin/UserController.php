<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['organizations', 'userRoles', 'userProfile']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('userProfile', function ($profileQuery) use ($search) {
                      $profileQuery->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->whereHas('organizations', function ($q) use ($request) {
                $q->where('organizations.id', $request->organization_id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $organizations = Organization::where('status', true)->get();

        return view('superadmin.users.index', compact('users', 'organizations'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        $organizations = Organization::where('status', true)->get();
        $roles = Role::all();
        $selectedOrganizationId = $request->get('organization_id');
        
        return view('superadmin.users.create', compact('organizations', 'roles', 'selectedOrganizationId'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'password' => 'required|string|min:6',
            'status' => 'required|in:0,1',
            'organizations' => 'required|array|min:1',
            'organizations.*' => 'exists:organizations,id',
            'org_roles' => 'nullable|array',
            'org_roles.*' => 'nullable|exists:roles,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id'
        ]);

        DB::beginTransaction();
        try {
            Log::info('Starting user creation', [
                'request_data' => $request->all()
            ]);
            
            // Create user
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'status' => $request->status,
            ]);
            
            // Create user profile with full_name
            \App\Models\UserProfile::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
            ]);
            
            Log::info('User created successfully', [
                'user_id' => $user->id
            ]);

            // Attach organizations with roles
            foreach ($request->organizations as $index => $organizationId) {
                $roleId = $request->org_roles[$index] ?? null;
                if ($roleId) {
                    Log::info('Attaching organization', [
                        'user_id' => $user->id,
                        'organization_id' => $organizationId,
                        'role_id' => $roleId
                    ]);
                    
                    // Check if user already exists in this organization
                    // Một user chỉ có thể có 1 role trong 1 organization
                    $existingOrgUser = \App\Models\OrganizationUser::where('organization_id', $organizationId)
                        ->where('user_id', $user->id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$existingOrgUser) {
                        // Tạo mới
                        \App\Models\OrganizationUser::create([
                            'organization_id' => $organizationId,
                            'user_id' => $user->id,
                            'role_id' => $roleId,
                            'status' => 'active'
                        ]);
                    } else {
                        // Nếu đã tồn tại, cập nhật role
                        $existingOrgUser->update([
                            'role_id' => $roleId,
                            'status' => 'active'
                        ]);
                    }
                }
            }

            // Attach global roles - skip for now as it's causing issues
            // $user->userRoles()->attach($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Người dùng đã được tạo thành công!',
                'redirect' => route('superadmin.users.show', $user)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo người dùng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['organizations.roles', 'userRoles', 'userProfile', 'commissionEvents', 'salaryContracts']);
        
        // Get user statistics
        $stats = $this->getUserStats($user);
        
        return view('superadmin.users.show', compact('user', 'stats'));
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user)
    {
        $user->load(['organizations', 'userRoles', 'userProfile']);
        $organizations = Organization::where('status', true)->get();
        $roles = Role::all();
        
        return view('superadmin.users.edit', compact('user', 'organizations', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        Log::info('Starting user update', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'status' => 'required|in:0,1',
            'organizations' => 'required|array|min:1',
            'organizations.*' => 'exists:organizations,id',
            'org_roles' => 'nullable|array',
            'org_roles.*' => 'nullable|exists:roles,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id'
        ]);

        DB::beginTransaction();
        try {
            // Update user
            $updateData = [
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => $request->status,
            ];

            if ($request->filled('password')) {
                $updateData['password_hash'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Update or create user profile with full_name
            $profile = $user->getOrCreateProfile();
            $profile->update([
                'full_name' => $request->full_name,
            ]);

            // Update organization relationships
            Log::info('Processing organizations', [
                'organizations' => $request->organizations,
                'org_roles' => $request->org_roles
            ]);
            
            // Get current organization relationships
            $currentRelationships = $user->organizations()->get()->map(function($org) {
                return [
                    'organization_id' => $org->id,
                    'role_id' => $org->pivot->role_id,
                    'status' => $org->pivot->status
                ];
            })->toArray();
            
            Log::info('Current relationships', ['current' => $currentRelationships]);
            
            // Build new relationships from form data
            $newRelationships = [];
            foreach ($request->organizations as $index => $organizationId) {
                $roleId = $request->org_roles[$index] ?? null;
                if ($roleId) {
                    $newRelationships[] = [
                        'organization_id' => $organizationId,
                        'role_id' => $roleId,
                        'status' => 'active'
                    ];
                }
            }
            
            Log::info('New relationships from form', ['new' => $newRelationships]);
            
            // Find relationships to remove (in current but not in new)
            $toRemove = [];
            foreach ($currentRelationships as $current) {
                $found = false;
                foreach ($newRelationships as $new) {
                    if ($current['organization_id'] == $new['organization_id'] && 
                        $current['role_id'] == $new['role_id']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $toRemove[] = $current;
                }
            }
            
            // Find relationships to add (in new but not in current)
            $toAdd = [];
            foreach ($newRelationships as $new) {
                $found = false;
                foreach ($currentRelationships as $current) {
                    if ($current['organization_id'] == $new['organization_id'] && 
                        $current['role_id'] == $new['role_id']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $toAdd[] = $new;
                }
            }
            
            Log::info('Relationships to remove', ['remove' => $toRemove]);
            Log::info('Relationships to add', ['add' => $toAdd]);
            
            // Remove relationships
            foreach ($toRemove as $rel) {
                $user->organizations()->wherePivot('organization_id', $rel['organization_id'])
                     ->wherePivot('role_id', $rel['role_id'])
                     ->detach();
            }
            
            // Add new relationships
            foreach ($toAdd as $rel) {
                $user->organizations()->attach($rel['organization_id'], [
                    'organization_id' => $rel['organization_id'],
                    'role_id' => $rel['role_id'],
                    'status' => $rel['status']
                ]);
            }

            // Update global roles - skip for now as it's causing issues
            // $user->userRoles()->sync($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Người dùng đã được cập nhật thành công!',
                'redirect' => route('superadmin.users.show', $user)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật người dùng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            
            // Soft delete all organization_users records for this user
            $organizationUsers = \App\Models\OrganizationUser::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->get();
            
            foreach ($organizationUsers as $orgUser) {
                $orgUser->deleted_by = $currentUser->id;
                $orgUser->save();
                $orgUser->delete(); // Soft delete
            }
            
            // Soft delete user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Người dùng đã được xóa thành công!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa người dùng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        try {
            $user->update(['status' => !$user->status]);
            
            $status = $user->status ? 'kích hoạt' : 'tạm dừng';
            
            return response()->json([
                'success' => true,
                'message' => "Người dùng đã được {$status} thành công!",
                'status' => $user->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics.
     */
    private function getUserStats($user)
    {
        try {
            return [
                'total_organizations' => $user->organizations->count(),
                'total_commissions' => $user->commissionEvents->count(),
                'total_commission_amount' => $user->commissionEvents->sum('commission_total'),
                'active_salary_contracts' => $user->salaryContracts->where('status', 'active')->count(),
                'last_login' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        } catch (\Exception $e) {
            return [
                'total_organizations' => 0,
                'total_commissions' => 0,
                'total_commission_amount' => 0,
                'active_salary_contracts' => 0,
                'last_login' => null,
                'created_at' => $user->created_at,
            ];
        }
    }
}
