<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Service: AdvancedPermissionsService
 * 
 * MỤC ĐÍCH:
 * Service kiểm tra quyền sử dụng tính năng Phân quyền nâng cao dựa trên subscription của organization
 * - Kiểm tra organization có subscription active và feature enable_advanced_permissions được bật không
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. canUseAdvancedPermissions(): Kiểm tra organization có quyền sử dụng phân quyền nâng cao không
 * 2. requireAdvancedPermissions(): Yêu cầu quyền, nếu không có thì throw exception hoặc trả về JSON error
 * 3. getAdvancedPermissionsStatus(): Lấy trạng thái quyền phân quyền nâng cao của organization
 * 4. getOrganization(): Lấy organization instance từ parameter hoặc từ user hiện tại
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Organization (bảng organizations) - Kiểm tra subscription và feature enable_advanced_permissions
 * - Model: OrganizationUser (bảng organization_users) - Lấy organization từ user hiện tại
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log khi check quyền, khi bị từ chối, hoặc khi có lỗi
 * 
 * LƯU Ý:
 * - Feature key: 'enable_advanced_permissions' - phải được enable trong subscription plan
 * - Organization phải có active subscription (status: 'trial' hoặc 'active')
 * - Nếu không có quyền, trả về 403 với message thân thiện
 */
class AdvancedPermissionsService
{
    const FEATURE_KEY = 'enable_advanced_permissions'; // Key của feature phân quyền nâng cao → Dùng để check trong subscription plan

    /**
     * Kiểm tra organization có quyền sử dụng phân quyền nâng cao không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra organization có subscription active và feature enable_advanced_permissions được bật không
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - Database: organizations, organization_users
     * 
     * OUTPUT:
     * - bool: true nếu có quyền, false nếu không có quyền
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance (từ parameter hoặc user hiện tại)
     * 2. Kiểm tra organization có tồn tại không
     * 3. Kiểm tra organization có active subscription không
     * 4. Kiểm tra feature enable_advanced_permissions có được bật không
     * 5. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Kiểm tra subscription và feature
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning/info/error
     */
    public function canUseAdvancedPermissions($organization = null): bool
    {
        try {
            $org = $this->getOrganization($organization); // Lấy organization instance → Từ parameter hoặc user hiện tại
            
            if (!$org) { // Nếu không tìm thấy organization
                Log::warning('AdvancedPermissionsService: Organization not found'); // Ghi log warning → Để debug
                return false; // Trả về false → Không có quyền
            }

            if (!$org->hasActiveSubscription()) { // Nếu organization không có subscription active
                Log::info('AdvancedPermissionsService: Organization does not have active subscription', [
                    'organization_id' => $org->id
                ]); // Ghi log info → Để tracking
                return false; // Trả về false → Không có quyền
            }

            $canUse = $org->canUseFeature(self::FEATURE_KEY); // Kiểm tra feature enable_advanced_permissions → Có được bật trong subscription plan không
            
            if (!$canUse) { // Nếu feature không được bật
                Log::info('AdvancedPermissionsService: Advanced permissions feature is not enabled', [
                    'organization_id' => $org->id,
                    'feature_key' => self::FEATURE_KEY
                ]); // Ghi log info → Để tracking
            }

            return $canUse; // Trả về kết quả → true nếu có quyền, false nếu không

        } catch (\Exception $e) {
            Log::error('AdvancedPermissionsService: Error checking advanced permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]); // Ghi log error → Để debug
            return false; // Trả về false → An toàn hơn khi có lỗi
        }
    }

    /**
     * Yêu cầu quyền phân quyền nâng cao, nếu không có thì throw exception hoặc trả về JSON error
     * 
     * MỤC ĐÍCH:
     * Kiểm tra quyền phân quyền nâng cao, nếu không có quyền thì dừng request và trả về lỗi 403 với message thân thiện
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - message: Message lỗi tùy chỉnh, nếu null thì dùng message mặc định
     * 
     * OUTPUT:
     * - void: Không trả về gì, nếu không có quyền thì throw exception hoặc send JSON response
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền bằng canUseAdvancedPermissions()
     * 2. Nếu không có quyền:
     *    - Lấy message lỗi (custom hoặc mặc định)
     *    - Nếu request là JSON/AJAX → Trả về JSON error 403
     *    - Nếu không → Throw exception 403
     * 
     * LƯU Ý:
     * - Method này sẽ dừng request nếu không có quyền (không return false)
     * - Trả về JSON nếu request là AJAX/JSON, throw exception nếu không
     */
    public function requireAdvancedPermissions($organization = null, ?string $message = null): void
    {
        if (!$this->canUseAdvancedPermissions($organization)) { // Nếu không có quyền
            $errorMessage = $message ?? 'Gói dịch vụ của bạn không hỗ trợ tính năng Phân quyền nâng cao. Vui lòng nâng cấp gói để sử dụng tính năng này.'; // Message lỗi → Thân thiện với user
            
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) { // Nếu request là JSON/AJAX
                response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 403)->send(); // Trả về JSON error 403 → Dừng request
                exit; // Dừng execution → Không chạy code tiếp theo
            }
            
            abort(403, $errorMessage); // Throw exception 403 → Laravel sẽ xử lý và trả về error page
        }
    }

    /**
     * Lấy organization instance từ parameter hoặc từ user hiện tại
     * 
     * MỤC ĐÍCH:
     * Lấy organization instance để kiểm tra quyền, ưu tiên từ parameter, nếu không có thì lấy từ user hiện tại
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null
     * 
     * OUTPUT:
     * - Organization|null: Organization instance nếu tìm thấy, null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Nếu organization là Organization instance → Trả về luôn
     * 2. Nếu organization là int (ID) → Tìm Organization theo ID
     * 3. Nếu organization là null → Lấy từ user hiện tại qua OrganizationUser
     * 4. Trả về Organization hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Tìm organization theo ID
     * - Bảng organization_users: Tìm organization từ user hiện tại
     */
    protected function getOrganization($organization = null): ?Organization
    {
        if ($organization instanceof Organization) { // Nếu là Organization instance
            return $organization; // Trả về luôn → Không cần tìm kiếm
        }

        if (is_int($organization)) { // Nếu là ID (int)
            return Organization::find($organization); // Tìm organization theo ID → Trả về Organization hoặc null
        }

        $user = Auth::user(); // Lấy user hiện tại → Dùng để tìm organization
        if (!$user) { // Nếu không có user đăng nhập
            return null; // Trả về null → Không tìm thấy organization
        }

        $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->first(); // Tìm OrganizationUser của user → Lấy organization active

        if (!$organizationUser) { // Nếu không tìm thấy OrganizationUser
            return null; // Trả về null → Không tìm thấy organization
        }

        return Organization::find($organizationUser->organization_id); // Tìm organization theo ID → Trả về Organization hoặc null
    }

    /**
     * Lấy trạng thái quyền phân quyền nâng cao của organization
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết về quyền phân quyền nâng cao: có subscription không, feature có enabled không,
     * subscription status, và organization ID
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * 
     * OUTPUT:
     * - array: Thông tin trạng thái {can_use, has_subscription, feature_enabled, organization_id, subscription_status, message?}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance
     * 2. Kiểm tra organization có tồn tại không
     * 3. Kiểm tra có subscription active không
     * 4. Kiểm tra feature enable_advanced_permissions có được bật không
     * 5. Trả về array với thông tin chi tiết
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Kiểm tra subscription và feature
     */
    public function getAdvancedPermissionsStatus($organization = null): array
    {
        $org = $this->getOrganization($organization); // Lấy organization instance → Từ parameter hoặc user hiện tại
        
        if (!$org) { // Nếu không tìm thấy organization
            return [
                'can_use' => false,
                'message' => 'Không tìm thấy tổ chức',
                'has_subscription' => false,
                'feature_enabled' => false,
            ]; // Trả về status mặc định → Tất cả đều false
        }

        $hasSubscription = $org->hasActiveSubscription(); // Kiểm tra có subscription active không → Dùng để check quyền
        $featureEnabled = $hasSubscription && $org->canUseFeature(self::FEATURE_KEY); // Kiểm tra feature → Phải có subscription VÀ feature enabled

        return [
            'can_use' => $featureEnabled, // Có thể sử dụng không → Kết quả cuối cùng
            'has_subscription' => $hasSubscription, // Có subscription không → Thông tin chi tiết
            'feature_enabled' => $featureEnabled, // Feature có enabled không → Thông tin chi tiết
            'organization_id' => $org->id, // ID organization → Dùng để tracking
            'subscription_status' => $org->getSubscriptionStatus(), // Trạng thái subscription → Hiển thị cho user
        ];
    }
}

