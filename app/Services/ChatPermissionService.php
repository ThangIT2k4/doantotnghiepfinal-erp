<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Service: ChatPermissionService
 * 
 * MỤC ĐÍCH:
 * Kiểm tra quyền sử dụng tính năng Chat với AI dựa trên subscription của organization
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. canUseChat(): Kiểm tra organization có quyền sử dụng chat không (có subscription và feature enable_chat)
 * 2. requireChatPermission(): Yêu cầu quyền chat, nếu không có thì throw exception hoặc trả về JSON error
 * 3. getChatStatus(): Lấy trạng thái quyền chat của organization (có subscription, feature enabled, ...)
 * 4. getOrganization(): Lấy organization instance từ parameter hoặc từ user hiện tại
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model Organization: Kiểm tra subscription và feature enable_chat
 * - Model OrganizationUser: Lấy organization từ user hiện tại
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log khi check quyền, khi bị từ chối, hoặc khi có lỗi
 * 
 * LƯU Ý:
 * - Feature key: 'enable_chat' - phải được enable trong subscription plan
 * - Organization phải có active subscription (status: 'trial' hoặc 'active')
 * - Nếu không có quyền, trả về 403 với message thân thiện
 */
class ChatPermissionService
{
    /**
     * Feature key for chat permission
     */
    const FEATURE_KEY = 'enable_chat'; // Key của feature chat → Dùng để check trong subscription plan

    /**
     * Kiểm tra organization có quyền sử dụng chat không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra organization có subscription active và feature enable_chat được bật không
     * 
     * INPUT:
     * - organization (Organization|int|null): Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * 
     * OUTPUT:
     * - bool: true nếu có quyền, false nếu không có quyền
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance (từ parameter hoặc user hiện tại)
     * 2. Kiểm tra organization có tồn tại không
     * 3. Kiểm tra organization có active subscription không
     * 4. Kiểm tra feature enable_chat có được bật không
     * 5. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model Organization: hasActiveSubscription(), canUseFeature()
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning nếu không tìm thấy organization, info nếu không có subscription/feature
     */
    public function canUseChat($organization = null): bool
    {
        try {
            $org = $this->getOrganization($organization); // Lấy organization instance → Từ parameter hoặc user hiện tại
            
            if (!$org) { // Nếu không tìm thấy organization
                Log::warning('ChatPermissionService: Organization not found'); // Ghi log warning → Để debug
                return false; // Trả về false → Không có quyền
            }

            if (!$org->hasActiveSubscription()) { // Nếu organization không có subscription active
                Log::info('ChatPermissionService: Organization does not have active subscription', [
                    'organization_id' => $org->id
                ]); // Ghi log info → Để tracking
                return false; // Trả về false → Không có quyền
            }

            $canUse = $org->canUseFeature(self::FEATURE_KEY); // Kiểm tra feature enable_chat → Có được bật trong subscription plan không
            
            if (!$canUse) { // Nếu feature không được bật
                Log::info('ChatPermissionService: Chat feature is not enabled', [
                    'organization_id' => $org->id,
                    'feature_key' => self::FEATURE_KEY
                ]); // Ghi log info → Để tracking
            }

            return $canUse; // Trả về kết quả → true nếu có quyền, false nếu không

        } catch (\Exception $e) {
            Log::error('ChatPermissionService: Error checking chat permission', [
                'error' => $e->getMessage(), // Thông báo lỗi → Để debug
                'trace' => $e->getTraceAsString() // Stack trace → Để debug
            ]); // Ghi log error → Để debug
            return false; // Trả về false → An toàn hơn khi có lỗi
        }
    }

    /**
     * Yêu cầu quyền chat, nếu không có thì throw exception hoặc trả về JSON error
     * 
     * MỤC ĐÍCH:
     * Kiểm tra quyền chat, nếu không có quyền thì dừng request và trả về lỗi 403 với message thân thiện
     * 
     * INPUT:
     * - organization (Organization|int|null): Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - message (string|null): Message lỗi tùy chỉnh, nếu null thì dùng message mặc định
     * 
     * OUTPUT:
     * - void: Không trả về gì, nếu không có quyền thì throw exception hoặc send JSON response
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền bằng canUseChat()
     * 2. Nếu không có quyền:
     *    - Lấy message lỗi (custom hoặc mặc định)
     *    - Ghi log warning
     *    - Nếu request là JSON/AJAX → Trả về JSON error 403
     *    - Nếu không → Throw exception 403
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Method canUseChat(): Kiểm tra quyền
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log warning khi bị từ chối quyền
     * 
     * LƯU Ý:
     * - Method này sẽ dừng request nếu không có quyền (không return false)
     * - Trả về JSON nếu request là AJAX/JSON, throw exception nếu không
     */
    public function requireChatPermission($organization = null, ?string $message = null): void
    {
        if (!$this->canUseChat($organization)) { // Nếu không có quyền chat
            $defaultMessage = 'Gói dịch vụ của tổ chức không hỗ trợ tính năng Chat với AI. Vui lòng nâng cấp gói để sử dụng tính năng này.'; // Message mặc định → Thân thiện với user
            $errorMessage = $message ?? $defaultMessage; // Dùng message custom nếu có, nếu không dùng message mặc định → Linh hoạt hơn
            
            Log::warning('ChatPermissionService: Chat permission denied', [
                'organization_id' => $this->getOrganization($organization)?->id, // ID organization → Để tracking
                'message' => $errorMessage // Message lỗi → Để debug
            ]); // Ghi log warning → Để tracking và debug

            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) { // Nếu request là JSON/AJAX
                response()->json([
                    'success' => false,
                    'message' => $errorMessage, // Message lỗi → Hiển thị cho user
                    'error' => 'subscription_required' // Error code → Để frontend xử lý
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
     * - organization (Organization|int|null): Organization instance, ID, hoặc null
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
     * - Model Organization: find() → Tìm organization theo ID
     * - Model OrganizationUser: where() → Tìm organization từ user hiện tại
     * - Auth::user(): Lấy user hiện tại
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
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

        $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id) // Tìm OrganizationUser của user
            ->where('status', 'active') // Chỉ lấy status active → Tránh lấy organization đã inactive
            ->first(); // Lấy bản ghi đầu tiên → Mỗi user chỉ có 1 organization active

        return $organizationUser?->organization; // Trả về organization → Null nếu không tìm thấy
    }

    /**
     * Lấy trạng thái quyền chat của organization
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết về quyền chat: có subscription không, feature có enabled không, message thân thiện
     * 
     * INPUT:
     * - organization (Organization|int|null): Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * 
     * OUTPUT:
     * - array: {
     *     can_use: bool,           // Có thể sử dụng chat không
     *     has_subscription: bool,   // Có subscription active không
     *     feature_enabled: bool,    // Feature enable_chat có được bật không
     *     message: string           // Message thân thiện giải thích trạng thái
     *   }
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance
     * 2. Kiểm tra organization có tồn tại không
     * 3. Kiểm tra có subscription active không
     * 4. Kiểm tra feature enable_chat có được bật không
     * 5. Tạo message thân thiện dựa trên trạng thái
     * 6. Trả về array với thông tin chi tiết
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model Organization: hasActiveSubscription(), canUseFeature()
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     */
    public function getChatStatus($organization = null): array
    {
        $org = $this->getOrganization($organization); // Lấy organization instance → Từ parameter hoặc user hiện tại
        
        if (!$org) { // Nếu không tìm thấy organization
            return [
                'can_use' => false, // Không thể sử dụng → Không có organization
                'has_subscription' => false, // Không có subscription → Không có organization
                'feature_enabled' => false, // Feature không enabled → Không có organization
                'message' => 'Không tìm thấy tổ chức' // Message thân thiện → Giải thích lỗi
            ]; // Trả về status mặc định → Tất cả đều false
        }

        $hasSubscription = $org->hasActiveSubscription(); // Kiểm tra có subscription active không → Dùng để check quyền
        $featureEnabled = $hasSubscription && $org->canUseFeature(self::FEATURE_KEY); // Kiểm tra feature enable_chat → Phải có subscription VÀ feature enabled

        return [
            'can_use' => $featureEnabled, // Có thể sử dụng không → Kết quả cuối cùng
            'has_subscription' => $hasSubscription, // Có subscription không → Thông tin chi tiết
            'feature_enabled' => $featureEnabled, // Feature có enabled không → Thông tin chi tiết
            'message' => $featureEnabled 
                ? 'Tính năng Chat với AI đã được kích hoạt' // Message khi có quyền → Thông báo tích cực
                : 'Tính năng Chat với AI chưa được kích hoạt trong gói dịch vụ hiện tại' // Message khi không có quyền → Hướng dẫn nâng cấp
        ]; // Trả về status chi tiết → Dùng để hiển thị cho user
    }
}

