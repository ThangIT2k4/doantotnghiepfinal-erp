<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Capability;
use App\Models\OrganizationUser;

/**
 * Service: CapabilityService
 * 
 * MỤC ĐÍCH:
 * Service quản lý capabilities (quyền) của users trong organization - kiểm tra, cấp, thu hồi, và lấy danh sách users có capability
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. userHas(): Kiểm tra user có capability không (role defaults + overrides)
 * 2. getUserCapabilities(): Lấy tất cả capabilities của user (role defaults + overrides)
 * 3. grantCapability(): Cấp capability cho user (tạo/update override)
 * 4. revokeCapability(): Thu hồi capability từ user (tạo/update override với granted = false)
 * 5. removeOverride(): Xóa override để user kế thừa từ role default
 * 6. getUsersWithCapability(): Lấy danh sách users có capability cụ thể
 * 7. getUsersWithCapabilityModels(): Lấy User models có capability
 * 8. getUsersWithModuleAccess(): Lấy users có quyền truy cập module (manager hoặc có module.access capability)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng organization_users: Lấy OrganizationUser để kiểm tra role
 * - Bảng roles: Lấy role key_code để lấy role default capabilities từ config
 * - Bảng capabilities: Lấy capability ID từ key_code
 * - Bảng organization_user_capabilities: Lấy user's overrides
 * - Config: role_capabilities (config/role_capabilities.php)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng organization_user_capabilities: Tạo/cập nhật/xóa capability overrides
 * 
 * LƯU Ý:
 * - Capabilities được kế thừa từ role default, có thể override
 * - Manager role có wildcard (*) = true (tất cả quyền)
 * - Override có priority cao hơn role default
 * - Hỗ trợ legacy capabilities_json cho backward compatibility
 */
class CapabilityService
{
    /**
     * Kiểm tra user có capability không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra user có một capability cụ thể không, tính cả role defaults và overrides
     * 
     * INPUT:
     * - userId: ID của user cần kiểm tra
     * - orgId: Organization ID (optional, nếu null sẽ lấy organization đầu tiên)
     * - capability: Capability key_code cần kiểm tra
     * - Database: organization_users, roles, capabilities, organization_user_capabilities
     * 
     * OUTPUT:
     * - bool: true nếu user có capability, false nếu không
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy OrganizationUser theo userId và orgId
     * 2. Lấy role key_code và role default capabilities từ config
     * 3. Nếu role có wildcard (*) = true: return true
     * 4. Kiểm tra role default có capability không
     * 5. Kiểm tra user-specific overrides (granted = true và revoked_at = null)
     * 6. Nếu có override granted: return true
     * 7. Nếu có override denied (granted = false hoặc revoked_at != null): return false
     * 8. Fallback: Kiểm tra legacy capabilities_json
     * 9. Return role default result
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy OrganizationUser
     * - Bảng roles: Lấy role key_code
     * - Bảng capabilities: Lấy capability ID
     * - Bảng organization_user_capabilities: Lấy user's overrides
     * - Config: role_capabilities
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Override có priority cao hơn role default
     * - Manager role có wildcard (*) = true (tất cả quyền)
     * 
     * @param int $userId ID của user cần kiểm tra
     * @param int|null $orgId Organization ID (optional)
     * @param string $capability Capability key_code cần kiểm tra
     * @return bool true nếu user có capability, false nếu không
     */
    public static function userHas(int $userId, ?int $orgId, string $capability): bool
    {
        $ou = DB::table('organization_users') // Query từ bảng organization_users
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId)) // Nếu có orgId, filter theo organization
            ->where('user_id', $userId) // Chỉ lấy của user này
            ->orderByDesc('id') // Sắp xếp theo ID giảm dần → Lấy bản ghi mới nhất
            ->first(); // Lấy bản ghi đầu tiên → Có thể null

        if (!$ou) { // Nếu không tìm thấy OrganizationUser
            return false; // User không thuộc organization → Không có quyền
        }

        $roleKey = DB::table('roles')->where('id', $ou->role_id)->value('key_code'); // Lấy role key_code → Dùng để lấy role default capabilities
        $roleCaps = config('role_capabilities.' . ($roleKey ?? 'agent'), []); // Lấy role default capabilities từ config → Mặc định là 'agent' nếu không tìm thấy

        if (($roleCaps['*'] ?? false) === true) { // Nếu role có wildcard (*) = true (manager có tất cả quyền)
            return true; // Manager có tất cả quyền → Return true
        }

        $roleAllowed = ($roleCaps[$capability] ?? false) === true; // Kiểm tra role default có capability không → Dùng làm fallback

        $capabilityId = DB::table('capabilities') // Query từ bảng capabilities
            ->where('key_code', $capability) // Tìm capability theo key_code
            ->value('id'); // Lấy ID → Dùng để tìm override

        if ($capabilityId) { // Nếu tìm thấy capability
            $override = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
                ->where('organization_user_id', $ou->id) // Chỉ lấy của OrganizationUser này
                ->where('capability_id', $capabilityId) // Chỉ lấy của capability này
                ->where('granted', true) // Chỉ lấy granted = true
                ->whereNull('revoked_at') // Chỉ lấy chưa bị revoked
                ->first(); // Lấy bản ghi đầu tiên → Có thể null

            if ($override) { // Nếu có override granted
                return true; // User có quyền qua override → Return true
            }

            $denied = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
                ->where('organization_user_id', $ou->id) // Chỉ lấy của OrganizationUser này
                ->where('capability_id', $capabilityId) // Chỉ lấy của capability này
                ->where(function($q) { // Tạo group where → Kiểm tra denied
                    $q->where('granted', false) // Hoặc granted = false
                      ->orWhereNotNull('revoked_at'); // Hoặc đã bị revoked
                })
                ->exists(); // Kiểm tra tồn tại → Trả về true/false

            if ($denied) { // Nếu có override denied
                return false; // User bị từ chối quyền → Return false
            }
        }

        // Fallback: Check legacy capabilities_json for backward compatibility during transition
        if (property_exists($ou, 'capabilities_json') || isset($ou->capabilities_json)) { // Nếu có capabilities_json (legacy)
            $decoded = json_decode($ou->capabilities_json ?? '{}', true) ?: []; // Decode JSON → Dùng cho backward compatibility
            if (array_key_exists($capability, $decoded)) { // Nếu có capability trong JSON
                return (bool) $decoded[$capability]; // Return giá trị từ JSON → Backward compatibility
            }
        }

        return $roleAllowed; // Return role default result → Fallback nếu không có override
    }

    /**
     * Lấy tất cả capabilities của user (role defaults + overrides)
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả capabilities của user bao gồm role defaults và user-specific overrides
     * 
     * INPUT:
     * - userId: ID của user cần lấy capabilities
     * - orgId: Organization ID (optional, nếu null sẽ lấy organization đầu tiên)
     * - Database: organization_users, roles, capabilities, organization_user_capabilities
     * 
     * OUTPUT:
     * - array: ['capability.key' => true/false] - Mảng capabilities với giá trị true/false
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy OrganizationUser theo userId và orgId
     * 2. Lấy role key_code và role default capabilities từ config
     * 3. Nếu role có wildcard (*) = true: Lấy tất cả capabilities từ database
     * 4. Nếu không: Lấy capabilities từ role defaults
     * 5. Lấy user-specific overrides từ organization_user_capabilities
     * 6. Apply overrides lên capabilities (override có priority cao hơn)
     * 7. Fallback: Apply legacy capabilities_json
     * 8. Return capabilities array
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy OrganizationUser
     * - Bảng roles: Lấy role key_code
     * - Bảng capabilities: Lấy tất cả capabilities (nếu wildcard) hoặc từ overrides
     * - Bảng organization_user_capabilities: Lấy user's overrides
     * - Config: role_capabilities
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Override có priority cao hơn role default
     * - Manager role có wildcard (*) = true (tất cả quyền)
     * 
     * @param int $userId ID của user cần lấy capabilities
     * @param int|null $orgId Organization ID (optional)
     * @return array ['capability.key' => true/false] Mảng capabilities
     */
    public static function getUserCapabilities(int $userId, ?int $orgId): array
    {
        $ou = DB::table('organization_users') // Query từ bảng organization_users
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId)) // Nếu có orgId, filter theo organization
            ->where('user_id', $userId) // Chỉ lấy của user này
            ->orderByDesc('id') // Sắp xếp theo ID giảm dần → Lấy bản ghi mới nhất
            ->first(); // Lấy bản ghi đầu tiên → Có thể null

        if (!$ou) { // Nếu không tìm thấy OrganizationUser
            return []; // User không thuộc organization → Trả về array rỗng
        }

        $roleKey = DB::table('roles')->where('id', $ou->role_id)->value('key_code'); // Lấy role key_code → Dùng để lấy role default capabilities
        $roleCaps = config('role_capabilities.' . ($roleKey ?? 'agent'), []); // Lấy role default capabilities từ config → Mặc định là 'agent' nếu không tìm thấy

        // Start with role defaults
        $capabilities = []; // Khởi tạo mảng capabilities → Dùng để lưu kết quả
        
        if (($roleCaps['*'] ?? false) === true) { // Nếu role có wildcard (*) = true (manager có tất cả quyền)
            $allCaps = DB::table('capabilities')->pluck('key_code')->toArray(); // Lấy tất cả capability key_codes từ database → Manager có tất cả quyền
            foreach ($allCaps as $cap) { // Loop qua từng capability
                $capabilities[$cap] = true; // Set capability = true → Manager có tất cả quyền
            }
        } else {
            foreach ($roleCaps as $cap => $allowed) { // Loop qua role default capabilities
                if ($cap !== '*' && $allowed === true) { // Nếu không phải wildcard và allowed = true
                    $capabilities[$cap] = true; // Set capability = true → User có quyền từ role default
                }
            }
        }

        // Apply overrides from relational table
        $overrides = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->join('capabilities', 'organization_user_capabilities.capability_id', '=', 'capabilities.id') // JOIN với capabilities → Lấy key_code
            ->where('organization_user_capabilities.organization_user_id', $ou->id) // Chỉ lấy của OrganizationUser này
            ->where(function($q) { // Tạo group where → Lấy cả granted và denied
                $q->where(function($q2) { // Group 1: Granted overrides
                    $q2->where('organization_user_capabilities.granted', true) // granted = true
                       ->whereNull('organization_user_capabilities.revoked_at'); // Chưa bị revoked
                })->orWhere(function($q2) { // Group 2: Denied overrides
                    $q2->where('organization_user_capabilities.granted', false) // granted = false
                       ->orWhereNotNull('organization_user_capabilities.revoked_at'); // Hoặc đã bị revoked
                });
            })
            ->select('capabilities.key_code', 'organization_user_capabilities.granted', 'organization_user_capabilities.revoked_at') // Chỉ select các fields cần thiết
            ->get(); // Lấy tất cả overrides → Dùng để apply lên capabilities

        foreach ($overrides as $override) { // Loop qua từng override
            $capabilities[$override->key_code] = ($override->granted && !$override->revoked_at); // Apply override → Override có priority cao hơn role default
        }

        // Fallback: legacy capabilities_json
        if (property_exists($ou, 'capabilities_json') || isset($ou->capabilities_json)) { // Nếu có capabilities_json (legacy)
            $decoded = json_decode($ou->capabilities_json ?? '{}', true) ?: []; // Decode JSON → Dùng cho backward compatibility
            foreach ($decoded as $cap => $allowed) { // Loop qua từng capability trong JSON
                $capabilities[$cap] = (bool) $allowed; // Apply legacy capabilities → Backward compatibility
            }
        }

        return $capabilities; // Trả về mảng capabilities → Dùng để hiển thị và kiểm tra quyền
    }

    /**
     * Cấp capability cho user
     * 
     * MỤC ĐÍCH:
     * Cấp một capability cho user bằng cách tạo hoặc cập nhật override trong organization_user_capabilities
     * 
     * INPUT:
     * - orgUserId: OrganizationUser ID (ID của bản ghi trong organization_users)
     * - capabilityKey: Capability key_code cần cấp
     * - grantedBy: User ID đang cấp quyền (optional, dùng để track ai cấp)
     * - Database: capabilities, organization_user_capabilities
     * 
     * OUTPUT:
     * - bool: true nếu cấp thành công, false nếu capability không tồn tại
     * - Database: Tạo hoặc cập nhật bản ghi trong organization_user_capabilities với granted = true
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm capability theo key_code
     * 2. Kiểm tra override đã tồn tại chưa
     * 3. Nếu đã tồn tại: Cập nhật override (granted = true, revoked_at = null)
     * 4. Nếu chưa tồn tại: Tạo override mới (granted = true)
     * 5. Return true
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Kiểm tra override đã tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng organization_user_capabilities: Tạo hoặc cập nhật override với granted = true
     * 
     * LƯU Ý:
     * - Override sẽ ghi đè role default capability
     * - Nếu override đã tồn tại, sẽ cập nhật thay vì tạo mới
     * 
     * @param int $orgUserId OrganizationUser ID
     * @param string $capabilityKey Capability key_code cần cấp
     * @param int|null $grantedBy User ID đang cấp quyền (optional)
     * @return bool true nếu cấp thành công, false nếu capability không tồn tại
     */
    public static function grantCapability(int $orgUserId, string $capabilityKey, ?int $grantedBy = null): bool
    {
        $capability = Capability::where('key_code', $capabilityKey)->first(); // Tìm capability theo key_code → Validate tồn tại
        if (!$capability) { // Nếu không tìm thấy capability
            return false; // Capability không tồn tại → Return false
        }

        $existing = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->where('organization_user_id', $orgUserId) // Chỉ lấy của OrganizationUser này
            ->where('capability_id', $capability->id) // Chỉ lấy của capability này
            ->first(); // Lấy bản ghi đầu tiên → Có thể null

        if ($existing) { // Nếu override đã tồn tại
            DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
                ->where('id', $existing->id) // Chỉ cập nhật override này
                ->update([
                    'granted' => true, // Set granted = true → Cấp quyền
                    'granted_by' => $grantedBy, // Lưu user ID đang cấp quyền → Track ai cấp
                    'granted_at' => now(), // Cập nhật thời gian cấp quyền → Track khi nào cấp
                    'revoked_at' => null, // Xóa revoked_at → Đảm bảo quyền được cấp
                    'updated_at' => now(), // Cập nhật thời gian → Track thay đổi
                ]); // Cập nhật override → Ghi đè role default
        } else {
            DB::table('organization_user_capabilities')->insert([
                'organization_user_id' => $orgUserId, // OrganizationUser ID → Liên kết với OrganizationUser
                'capability_id' => $capability->id, // Capability ID → Liên kết với Capability
                'granted' => true, // Set granted = true → Cấp quyền
                'granted_by' => $grantedBy, // Lưu user ID đang cấp quyền → Track ai cấp
                'granted_at' => now(), // Lưu thời gian cấp quyền → Track khi nào cấp
                'created_at' => now(), // Lưu thời gian tạo → Track khi nào tạo
                'updated_at' => now(), // Lưu thời gian cập nhật → Track thay đổi
            ]); // Tạo override mới → Ghi đè role default
        }

        return true; // Cấp quyền thành công → Return true
    }

    /**
     * Thu hồi capability từ user
     * 
     * MỤC ĐÍCH:
     * Thu hồi một capability từ user bằng cách tạo hoặc cập nhật override với granted = false hoặc set revoked_at
     * 
     * INPUT:
     * - orgUserId: OrganizationUser ID (ID của bản ghi trong organization_users)
     * - capabilityKey: Capability key_code cần thu hồi
     * - Database: capabilities, organization_user_capabilities
     * 
     * OUTPUT:
     * - bool: true nếu thu hồi thành công, false nếu capability không tồn tại
     * - Database: Tạo hoặc cập nhật bản ghi trong organization_user_capabilities với granted = false hoặc revoked_at
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm capability theo key_code
     * 2. Kiểm tra override đã tồn tại chưa
     * 3. Nếu đã tồn tại: Cập nhật override (set revoked_at = now())
     * 4. Nếu chưa tồn tại: Tạo override mới (granted = false, revoked_at = now())
     * 5. Return true
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Kiểm tra override đã tồn tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng organization_user_capabilities: Tạo hoặc cập nhật override với granted = false hoặc revoked_at
     * 
     * LƯU Ý:
     * - Override sẽ ghi đè role default capability (từ chối quyền)
     * - Nếu override đã tồn tại, sẽ set revoked_at thay vì tạo mới
     * 
     * @param int $orgUserId OrganizationUser ID
     * @param string $capabilityKey Capability key_code cần thu hồi
     * @return bool true nếu thu hồi thành công, false nếu capability không tồn tại
     */
    public static function revokeCapability(int $orgUserId, string $capabilityKey): bool
    {
        $capability = Capability::where('key_code', $capabilityKey)->first(); // Tìm capability theo key_code → Validate tồn tại
        if (!$capability) { // Nếu không tìm thấy capability
            return false; // Capability không tồn tại → Return false
        }

        $existing = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->where('organization_user_id', $orgUserId) // Chỉ lấy của OrganizationUser này
            ->where('capability_id', $capability->id) // Chỉ lấy của capability này
            ->first(); // Lấy bản ghi đầu tiên → Có thể null

        if ($existing) { // Nếu override đã tồn tại
            DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
                ->where('id', $existing->id) // Chỉ cập nhật override này
                ->update([
                    'revoked_at' => now(), // Set revoked_at = now() → Đánh dấu đã thu hồi
                    'updated_at' => now(), // Cập nhật thời gian → Track thay đổi
                ]); // Cập nhật override → Thu hồi quyền
        } else {
            DB::table('organization_user_capabilities')->insert([
                'organization_user_id' => $orgUserId, // OrganizationUser ID → Liên kết với OrganizationUser
                'capability_id' => $capability->id, // Capability ID → Liên kết với Capability
                'granted' => false, // Set granted = false → Từ chối quyền
                'granted_by' => null, // Không có granted_by → Vì đây là từ chối quyền
                'granted_at' => null, // Không có granted_at → Vì đây là từ chối quyền
                'revoked_at' => now(), // Set revoked_at = now() → Đánh dấu đã thu hồi
                'created_at' => now(), // Lưu thời gian tạo → Track khi nào tạo
                'updated_at' => now(), // Lưu thời gian cập nhật → Track thay đổi
            ]); // Tạo override mới → Từ chối quyền
        }

        return true; // Thu hồi quyền thành công → Return true
    }

    /**
     * Xóa override để user kế thừa từ role default
     * 
     * MỤC ĐÍCH:
     * Xóa override capability để user kế thừa quyền từ role mặc định thay vì override
     * 
     * INPUT:
     * - orgUserId: OrganizationUser ID (ID của bản ghi trong organization_users)
     * - capabilityKey: Capability key_code cần xóa override
     * - Database: capabilities, organization_user_capabilities
     * 
     * OUTPUT:
     * - bool: true nếu xóa thành công, false nếu capability không tồn tại
     * - Database: Xóa bản ghi trong organization_user_capabilities
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm capability theo key_code
     * 2. Xóa override record nếu tồn tại
     * 3. Return true
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Tìm override để xóa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng organization_user_capabilities: Xóa override record
     * 
     * LƯU Ý:
     * - Sau khi xóa override, user sẽ kế thừa quyền từ role mặc định
     * - Nếu override không tồn tại, vẫn return true (idempotent)
     * 
     * @param int $orgUserId OrganizationUser ID
     * @param string $capabilityKey Capability key_code cần xóa override
     * @return bool true nếu xóa thành công, false nếu capability không tồn tại
     */
    public static function removeOverride(int $orgUserId, string $capabilityKey): bool
    {
        $capability = Capability::where('key_code', $capabilityKey)->first(); // Tìm capability theo key_code → Validate tồn tại
        if (!$capability) { // Nếu không tìm thấy capability
            return false; // Capability không tồn tại → Return false
        }

        DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->where('organization_user_id', $orgUserId) // Chỉ xóa của OrganizationUser này
            ->where('capability_id', $capability->id) // Chỉ xóa của capability này
            ->delete(); // Xóa override record → User sẽ kế thừa từ role default

        return true; // Xóa override thành công → Return true
    }

    /**
     * Lấy danh sách users có capability cụ thể
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách users có một capability cụ thể (qua override granted = true và chưa revoked)
     * 
     * INPUT:
     * - capabilityKey: Capability key_code cần tìm
     * - orgId: Organization ID (optional, nếu null sẽ lấy tất cả organizations)
     * - Database: capabilities, organization_users, users, organization_user_capabilities
     * 
     * OUTPUT:
     * - array: [{id, full_name, email, organization_id}, ...] - Mảng users có capability
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm capability theo key_code
     * 2. JOIN organization_user_capabilities với organization_users và users
     * 3. Filter: capability_id, granted = true, revoked_at = null, status = active, deleted_at = null
     * 4. Nếu có orgId: Filter theo organization_id
     * 5. Select: users.id, users.full_name, users.email, organization_users.organization_id
     * 6. Return array
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Lấy overrides với granted = true
     * - Bảng organization_users: Lấy OrganizationUser với status = active
     * - Bảng users: Lấy thông tin users (chưa bị xóa)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Chỉ lấy users có override granted = true và chưa revoked
     * - Không bao gồm users có quyền từ role default (chỉ qua override)
     * - Chỉ lấy users active và chưa bị xóa
     * 
     * @param string $capabilityKey Capability key_code cần tìm
     * @param int|null $orgId Organization ID (optional)
     * @return array [{id, full_name, email, organization_id}, ...] Mảng users có capability
     */
    public static function getUsersWithCapability(string $capabilityKey, ?int $orgId = null): array
    {
        $capability = Capability::where('key_code', $capabilityKey)->first(); // Tìm capability theo key_code → Validate tồn tại
        if (!$capability) { // Nếu không tìm thấy capability
            return []; // Capability không tồn tại → Trả về array rỗng
        }

        $query = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->join('organization_users', 'organization_user_capabilities.organization_user_id', '=', 'organization_users.id') // JOIN với organization_users → Lấy OrganizationUser
            ->join('users', 'organization_users.user_id', '=', 'users.id') // JOIN với users → Lấy thông tin users
            ->where('organization_user_capabilities.capability_id', $capability->id) // Chỉ lấy của capability này
            ->where('organization_user_capabilities.granted', true) // Chỉ lấy granted = true → Users có quyền qua override
            ->whereNull('organization_user_capabilities.revoked_at') // Chỉ lấy chưa bị revoked → Quyền vẫn còn hiệu lực
            ->where('organization_users.status', 'active') // Chỉ lấy OrganizationUser active → Users đang hoạt động
            ->whereNull('users.deleted_at'); // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users

        if ($orgId) { // Nếu có orgId
            $query->where('organization_users.organization_id', $orgId); // Chỉ lấy của organization này → Filter theo organization
        }

        return $query->select('users.id', 'users.full_name', 'users.email', 'organization_users.organization_id') // Chỉ select các fields cần thiết
            ->get() // Lấy tất cả kết quả → Collection
            ->toArray(); // Convert sang array → Dùng để trả về JSON
    }

    /**
     * Lấy User models có capability cụ thể
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách User models có một capability cụ thể (qua override granted = true và chưa revoked)
     * 
     * INPUT:
     * - capabilityKey: Capability key_code cần tìm
     * - orgId: Organization ID (optional, nếu null sẽ lấy tất cả organizations)
     * - Database: capabilities, organization_users, users, organization_user_capabilities
     * 
     * OUTPUT:
     * - Collection: Collection of User models với eager loaded userProfile
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm capability theo key_code
     * 2. JOIN organization_user_capabilities với organization_users và users
     * 3. Filter: capability_id, granted = true, revoked_at = null, status = active, deleted_at = null
     * 4. Nếu có orgId: Filter theo organization_id
     * 5. Lấy user IDs
     * 6. Load User models với eager loading userProfile
     * 7. Return Collection
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability theo key_code
     * - Bảng organization_user_capabilities: Lấy overrides với granted = true
     * - Bảng organization_users: Lấy OrganizationUser với status = active
     * - Bảng users: Lấy User models
     * - Bảng user_profiles: Eager load userProfile
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Trả về User models thay vì array (dùng khi cần relationships)
     * - Eager load userProfile để tránh N+1 query
     * 
     * @param string $capabilityKey Capability key_code cần tìm
     * @param int|null $orgId Organization ID (optional)
     * @return \Illuminate\Support\Collection Collection of User models
     */
    public static function getUsersWithCapabilityModels(string $capabilityKey, ?int $orgId = null)
    {
        $capability = Capability::where('key_code', $capabilityKey)->first(); // Tìm capability theo key_code → Validate tồn tại
        if (!$capability) { // Nếu không tìm thấy capability
            return collect(); // Capability không tồn tại → Trả về Collection rỗng
        }

        $query = DB::table('organization_user_capabilities') // Query từ bảng organization_user_capabilities
            ->join('organization_users', 'organization_user_capabilities.organization_user_id', '=', 'organization_users.id') // JOIN với organization_users → Lấy OrganizationUser
            ->join('users', 'organization_users.user_id', '=', 'users.id') // JOIN với users → Lấy thông tin users
            ->where('organization_user_capabilities.capability_id', $capability->id) // Chỉ lấy của capability này
            ->where('organization_user_capabilities.granted', true) // Chỉ lấy granted = true → Users có quyền qua override
            ->whereNull('organization_user_capabilities.revoked_at') // Chỉ lấy chưa bị revoked → Quyền vẫn còn hiệu lực
            ->where('organization_users.status', 'active') // Chỉ lấy OrganizationUser active → Users đang hoạt động
            ->whereNull('users.deleted_at'); // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users

        if ($orgId) { // Nếu có orgId
            $query->where('organization_users.organization_id', $orgId); // Chỉ lấy của organization này → Filter theo organization
        }

        $userIds = $query->pluck('users.id')->toArray(); // Lấy danh sách user IDs → Dùng để load User models

        if (empty($userIds)) { // Nếu không có user nào
            return collect(); // Trả về Collection rỗng → Không có users có capability
        }

        return \App\Models\User::with('userProfile')->whereIn('id', $userIds)->get(); // Load User models với eager loading userProfile → Tránh N+1 query
    }

    /**
     * Lấy users có quyền truy cập module
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách users có quyền truy cập một module cụ thể (manager hoặc agent có module.access capability)
     * 
     * INPUT:
     * - moduleKey: Module key (ví dụ: 'work', 'billing', 'party')
     * - orgId: Organization ID (optional, nếu null sẽ lấy tất cả organizations)
     * - Database: capabilities, organization_users, users, roles, organization_user_capabilities
     * 
     * OUTPUT:
     * - Collection: Collection of User models với eager loaded userProfile
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo capability key: "{moduleKey}.access"
     * 2. Lấy users có capability này qua getUsersWithCapabilityModels()
     * 3. Lấy users có role manager (manager có tất cả quyền qua wildcard)
     * 4. Merge 2 collections (loại bỏ duplicate)
     * 5. Return Collection
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng capabilities: Tìm capability "{moduleKey}.access"
     * - Bảng organization_user_capabilities: Lấy overrides với granted = true
     * - Bảng organization_users: Lấy OrganizationUser với status = active
     * - Bảng roles: Lấy users có role manager
     * - Bảng users: Lấy User models
     * - Bảng user_profiles: Eager load userProfile
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Manager có tất cả quyền qua wildcard (*) = true
     * - Agent có quyền nếu có override "{moduleKey}.access"
     * - Helper method để thay thế role-based queries cũ
     * 
     * @param string $moduleKey Module key (ví dụ: 'work', 'billing', 'party')
     * @param int|null $orgId Organization ID (optional)
     * @return \Illuminate\Support\Collection Collection of User models
     */
    public static function getUsersWithModuleAccess(string $moduleKey, ?int $orgId = null)
    {
        $capabilityKey = "{$moduleKey}.access"; // Tạo capability key → Dùng để tìm users có quyền truy cập module
        
        $usersWithCapability = self::getUsersWithCapabilityModels($capabilityKey, $orgId); // Lấy users có capability này → Users có quyền qua override
        
        $managerUsers = DB::table('organization_users') // Query từ bảng organization_users
            ->join('users', 'organization_users.user_id', '=', 'users.id') // JOIN với users → Lấy thông tin users
            ->join('roles', 'organization_users.role_id', '=', 'roles.id') // JOIN với roles → Lấy role key_code
            ->where('roles.key_code', 'manager') // Chỉ lấy users có role manager → Manager có tất cả quyền
            ->where('organization_users.status', 'active') // Chỉ lấy OrganizationUser active → Users đang hoạt động
            ->whereNull('users.deleted_at'); // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users

        if ($orgId) { // Nếu có orgId
            $managerUsers->where('organization_users.organization_id', $orgId); // Chỉ lấy của organization này → Filter theo organization
        }

        $managerIds = $managerUsers->pluck('users.id')->toArray(); // Lấy danh sách manager user IDs → Dùng để load User models
        
        if (!empty($managerIds)) { // Nếu có managers
            $existingIds = $usersWithCapability->pluck('id')->toArray(); // Lấy IDs của users đã có trong collection → Tránh duplicate
            $newManagerIds = array_diff($managerIds, $existingIds); // Lấy manager IDs chưa có trong collection → Chỉ load managers mới
            
            if (!empty($newManagerIds)) { // Nếu có managers mới
                $managerModels = \App\Models\User::with('userProfile')->whereIn('id', $newManagerIds)->get(); // Load manager User models → Tránh N+1 query
                $usersWithCapability = $usersWithCapability->merge($managerModels); // Merge vào collection → Kết hợp users có capability và managers
            }
        }

        return $usersWithCapability; // Trả về Collection → Dùng để hiển thị và filter
    }
}


