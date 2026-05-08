<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Service: WebhooksPermissionService
 * 
 * MỤC ĐÍCH:
 * Service kiểm tra quyền sử dụng tính năng Webhooks dựa trên subscription của organization
 * - Kiểm tra organization có subscription active và feature enable_webhooks được bật không
 * - Hỗ trợ kiểm tra quyền sử dụng SePay payment gateway (yêu cầu webhooks feature)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. canUseWebhooks(): Kiểm tra organization có quyền sử dụng webhooks không
 * 2. requireWebhooksPermission(): Yêu cầu quyền, nếu không có thì throw exception hoặc trả về JSON error
 * 3. canUseSepay(): Kiểm tra organization có quyền sử dụng SePay không (yêu cầu webhooks)
 * 4. requireSepayPermission(): Yêu cầu quyền SePay, nếu không có thì throw exception
 * 5. getWebhooksStatus(): Lấy trạng thái quyền webhooks của organization
 * 6. getOrganization(): Lấy organization instance từ parameter hoặc từ user hiện tại
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Organization (bảng organizations) - Kiểm tra subscription và feature enable_webhooks
 * - Model: OrganizationUser (bảng organization_users) - Lấy organization từ user hiện tại
 * 
 * DỮ LIỆU GHI VÀO:
 * - Logs: Ghi log chi tiết khi check quyền, khi bị từ chối, hoặc khi có lỗi
 * 
 * LƯU Ý:
 * - Feature key: 'enable_webhooks' - phải được enable trong subscription plan
 * - Organization phải có active subscription (status: 'trial' hoặc 'active')
 * - SePay payment gateway yêu cầu webhooks feature được bật
 * - Có logging chi tiết để debug subscription và feature issues
 */
class WebhooksPermissionService
{
    const FEATURE_KEY = 'enable_webhooks'; // Key của feature webhooks → Dùng để check trong subscription plan

    /**
     * Kiểm tra organization có quyền sử dụng webhooks không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra organization có subscription active và feature enable_webhooks được bật không
     * Có logging chi tiết để debug subscription và feature issues
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - Database: organizations, organization_users, subscriptions, plans
     * 
     * OUTPUT:
     * - bool: true nếu có quyền, false nếu không có quyền
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance (từ parameter hoặc user hiện tại)
     * 2. Reload organization để đảm bảo relationships fresh
     * 3. Kiểm tra organization có tồn tại không
     * 4. Kiểm tra organization có active subscription không
     * 5. Lấy subscription và plan để logging chi tiết
     * 6. Kiểm tra feature enable_webhooks có được bật không
     * 7. Ghi log chi tiết cho debugging
     * 8. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Kiểm tra subscription và feature
     * - Bảng subscriptions: Lấy subscription details
     * - Bảng plans: Lấy plan và feature details
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log chi tiết warning/info/error
     * 
     * LƯU Ý:
     * - Có logging chi tiết để debug subscription và feature issues
     * - Reload organization để đảm bảo relationships fresh
     */
    public function canUseWebhooks($organization = null): bool
    {
        try {
            $org = $this->getOrganization($organization); // Lấy organization instance → Từ parameter hoặc user hiện tại
            
            if (!$org) { // Nếu không tìm thấy organization
                Log::warning('WebhooksPermissionService: Organization not found', [
                    'organization_param' => $organization
                ]); // Ghi log warning → Để debug
                return false; // Trả về false → Không có quyền
            }

            $org->refresh(); // Reload organization → Đảm bảo relationships fresh
            
            $hasActiveSubscription = $org->hasActiveSubscription(); // Kiểm tra có subscription active không → Dùng để check quyền
            
            if (!$hasActiveSubscription) { // Nếu không có subscription active
                $subscription = $org->activeSubscription; // Lấy subscription → Dùng để logging chi tiết
                Log::info('WebhooksPermissionService: Organization does not have active subscription', [
                    'organization_id' => $org->id,
                    'has_active_subscription' => $hasActiveSubscription,
                    'subscription_exists' => $subscription ? true : false,
                    'subscription_status' => $subscription ? $subscription->status : null,
                    'subscription_id' => $subscription ? $subscription->id : null,
                ]); // Ghi log info chi tiết → Để debug subscription issues
                return false; // Trả về false → Không có quyền
            }

            $subscription = $org->activeSubscription; // Lấy subscription → Dùng để logging
            $plan = $subscription ? $subscription->plan : null; // Lấy plan → Dùng để logging
            
            $canUse = $org->canUseFeature(self::FEATURE_KEY); // Kiểm tra feature enable_webhooks → Có được bật trong subscription plan không
            
            Log::info('WebhooksPermissionService: Checking webhooks feature', [
                'organization_id' => $org->id,
                'feature_key' => self::FEATURE_KEY,
                'has_active_subscription' => $hasActiveSubscription,
                'subscription_id' => $subscription ? $subscription->id : null,
                'plan_id' => $plan ? $plan->id : null,
                'plan_name' => $plan ? $plan->name : null,
                'can_use_feature' => $canUse,
            ]); // Ghi log info chi tiết → Để debug feature issues
            
            if (!$canUse && $subscription && $plan) { // Nếu feature không được bật và có subscription/plan
                $feature = $plan->getFeature(self::FEATURE_KEY); // Lấy feature trực tiếp → Dùng để logging chi tiết
                Log::info('WebhooksPermissionService: Webhooks feature is not enabled', [
                    'organization_id' => $org->id,
                    'feature_key' => self::FEATURE_KEY,
                    'feature_exists' => $feature ? true : false,
                    'feature_type' => $feature ? $feature->feature_type : null,
                    'feature_value' => $feature ? $feature->feature_value : null,
                ]); // Ghi log info chi tiết → Để debug feature không enabled
            }

            return $canUse; // Trả về kết quả → true nếu có quyền, false nếu không

        } catch (\Exception $e) {
            Log::error('WebhooksPermissionService: Error checking webhooks permission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'organization_param' => $organization
            ]); // Ghi log error → Để debug
            return false; // Trả về false → An toàn hơn khi có lỗi
        }
    }

    /**
     * Yêu cầu quyền webhooks, nếu không có thì throw exception hoặc trả về JSON error
     * 
     * MỤC ĐÍCH:
     * Kiểm tra quyền webhooks, nếu không có quyền thì dừng request và trả về lỗi 403 với message thân thiện
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - message: Message lỗi tùy chỉnh, nếu null thì dùng message mặc định
     * 
     * OUTPUT:
     * - void: Không trả về gì, nếu không có quyền thì throw exception hoặc send JSON response
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền bằng canUseWebhooks()
     * 2. Nếu không có quyền:
     *    - Lấy message lỗi (custom hoặc mặc định)
     *    - Nếu request là JSON/AJAX → Trả về JSON error 403
     *    - Nếu không → Throw exception 403
     * 
     * LƯU Ý:
     * - Method này sẽ dừng request nếu không có quyền (không return false)
     * - Trả về JSON nếu request là AJAX/JSON, throw exception nếu không
     */
    public function requireWebhooksPermission($organization = null, ?string $message = null): void
    {
        if (!$this->canUseWebhooks($organization)) { // Nếu không có quyền
            $errorMessage = $message ?? 'Gói dịch vụ của bạn không hỗ trợ tính năng Webhooks. Vui lòng nâng cấp gói để sử dụng tính năng này.'; // Message lỗi → Thân thiện với user
            
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
     * Kiểm tra organization có quyền sử dụng SePay payment gateway không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra organization có quyền sử dụng SePay payment gateway không - SePay yêu cầu webhooks feature được bật
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * 
     * OUTPUT:
     * - bool: true nếu có quyền, false nếu không có quyền
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền webhooks (SePay yêu cầu webhooks feature)
     * 2. Trả về kết quả
     * 
     * LƯU Ý:
     * - SePay payment gateway yêu cầu webhooks feature được bật
     * - Wrapper cho canUseWebhooks()
     */
    public function canUseSepay($organization = null): bool
    {
        return $this->canUseWebhooks($organization); // Kiểm tra quyền webhooks → SePay yêu cầu webhooks feature
    }

    /**
     * Yêu cầu quyền SePay, nếu không có thì throw exception hoặc trả về JSON error
     * 
     * MỤC ĐÍCH:
     * Kiểm tra quyền sử dụng SePay payment gateway, nếu không có quyền thì dừng request và trả về lỗi 403
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * - message: Message lỗi tùy chỉnh, nếu null thì dùng message mặc định
     * 
     * OUTPUT:
     * - void: Không trả về gì, nếu không có quyền thì throw exception hoặc send JSON response
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra quyền bằng canUseSepay()
     * 2. Nếu không có quyền:
     *    - Lấy message lỗi (custom hoặc mặc định)
     *    - Nếu request là JSON/AJAX → Trả về JSON error 403
     *    - Nếu không → Throw exception 403
     * 
     * LƯU Ý:
     * - Method này sẽ dừng request nếu không có quyền (không return false)
     * - Trả về JSON nếu request là AJAX/JSON, throw exception nếu không
     */
    public function requireSepayPermission($organization = null, ?string $message = null): void
    {
        if (!$this->canUseSepay($organization)) { // Nếu không có quyền SePay
            $errorMessage = $message ?? 'Gói dịch vụ của bạn không hỗ trợ phương thức thanh toán SePay. Vui lòng nâng cấp gói để sử dụng tính năng Webhooks.'; // Message lỗi → Thân thiện với user
            
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
     * Lấy trạng thái quyền webhooks của organization
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chi tiết về quyền webhooks: có subscription không, feature có enabled không,
     * subscription status, organization ID, và quyền sử dụng SePay
     * 
     * INPUT:
     * - organization: Organization instance, ID, hoặc null (sẽ lấy từ user hiện tại)
     * 
     * OUTPUT:
     * - array: Thông tin trạng thái {can_use, can_use_sepay, has_subscription, feature_enabled, organization_id, subscription_status, message?}
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy organization instance
     * 2. Kiểm tra organization có tồn tại không
     * 3. Kiểm tra có subscription active không
     * 4. Kiểm tra feature enable_webhooks có được bật không
     * 5. Trả về array với thông tin chi tiết (bao gồm can_use_sepay)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organizations: Kiểm tra subscription và feature
     */
    public function getWebhooksStatus($organization = null): array
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
            'can_use' => $featureEnabled, // Có thể sử dụng webhooks không → Kết quả cuối cùng
            'can_use_sepay' => $featureEnabled, // Có thể sử dụng SePay không → SePay yêu cầu webhooks feature
            'has_subscription' => $hasSubscription, // Có subscription không → Thông tin chi tiết
            'feature_enabled' => $featureEnabled, // Feature có enabled không → Thông tin chi tiết
            'organization_id' => $org->id, // ID organization → Dùng để tracking
            'subscription_status' => $org->getSubscriptionStatus(), // Trạng thái subscription → Hiển thị cho user
        ];
    }
}

