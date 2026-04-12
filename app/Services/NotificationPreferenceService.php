<?php

namespace App\Services;

use App\Models\UserNotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service: NotificationPreferenceService
 * 
 * MỤC ĐÍCH:
 * Quản lý notification preferences của users (cài đặt nhận email/in-app notifications cho từng entity type).
 * Service này kiểm tra và cập nhật preferences của users để quyết định có gửi notifications hay không.
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. shouldSendEmail(): Kiểm tra user có muốn nhận email notification không
 * 2. shouldSendInApp(): Kiểm tra user có muốn nhận in-app notification không
 * 3. getAllPreferences(): Lấy tất cả preferences của user (kèm defaults)
 * 4. updateAllPreferences(): Cập nhật tất cả preferences của user
 * 5. initializeDefaults(): Khởi tạo preferences mặc định cho user mới
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: UserNotificationPreference (bảng user_notification_preferences) - Preferences của users
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng user_notification_preferences: Tạo/cập nhật preferences của users
 * - Logs: Ghi log lỗi khi cập nhật preferences thất bại
 * 
 * LƯU Ý:
 * - Mặc định là true (bật) nếu user chưa có preference
 * - Preferences được lưu theo entity_type (lease, invoice, ticket, payment, review, etc.)
 * - Mỗi entity_type có 2 settings: email_enabled và in_app_enabled
 */
class NotificationPreferenceService
{
    /**
     * Kiểm tra user có muốn nhận email notification cho entity type này không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra preference của user để quyết định có gửi email notification cho entity type này không
     * 
     * INPUT:
     * - User $user: User cần kiểm tra
     * - string $entityType: Loại entity (lease, invoice, ticket, payment, review, etc.)
     * 
     * OUTPUT:
     * - bool: true nếu user muốn nhận email, false nếu không (mặc định là true)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy preference của user cho entity type → Kiểm tra cài đặt
     * 2. Trả về email_enabled nếu có preference, mặc định là true → Đảm bảo luôn gửi nếu chưa cài đặt
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Lấy preference của user cho entity type
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function shouldSendEmail(User $user, string $entityType): bool
    {
        $preference = $this->getPreference($user, $entityType); // Lấy preference của user cho entity type → Kiểm tra cài đặt
        
        // Trả về email_enabled nếu có preference, mặc định là true → Đảm bảo luôn gửi nếu chưa cài đặt
        return $preference ? $preference->email_enabled : true;
    }

    /**
     * Kiểm tra user có muốn nhận in-app notification cho entity type này không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra preference của user để quyết định có tạo in-app notification cho entity type này không
     * 
     * INPUT:
     * - User $user: User cần kiểm tra
     * - string $entityType: Loại entity (lease, invoice, ticket, payment, review, etc.)
     * 
     * OUTPUT:
     * - bool: true nếu user muốn nhận in-app, false nếu không (mặc định là true)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy preference của user cho entity type → Kiểm tra cài đặt
     * 2. Trả về in_app_enabled nếu có preference, mặc định là true → Đảm bảo luôn tạo nếu chưa cài đặt
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Lấy preference của user cho entity type
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function shouldSendInApp(User $user, string $entityType): bool
    {
        $preference = $this->getPreference($user, $entityType); // Lấy preference của user cho entity type → Kiểm tra cài đặt
        
        // Trả về in_app_enabled nếu có preference, mặc định là true → Đảm bảo luôn tạo nếu chưa cài đặt
        return $preference ? $preference->in_app_enabled : true;
    }

    /**
     * Lấy preference của user cho entity type
     * 
     * MỤC ĐÍCH:
     * Lấy preference record của user cho entity type cụ thể
     * 
     * INPUT:
     * - User $user: User cần lấy preference
     * - string $entityType: Loại entity (lease, invoice, ticket, payment, review, etc.)
     * 
     * OUTPUT:
     * - UserNotificationPreference|null: Preference record hoặc null nếu chưa có
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Lấy preference của user cho entity type
     */
    public function getPreference(User $user, string $entityType): ?UserNotificationPreference
    {
        return UserNotificationPreference::where('user_id', $user->id) // Tìm preference của user
            ->where('entity_type', $entityType) // Filter theo entity type
            ->first(); // Lấy preference đầu tiên → Trả về null nếu chưa có
    }

    /**
     * Lấy tất cả preferences của user (kèm defaults)
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả preferences của user cho tất cả entity types, kèm theo defaults nếu chưa có preference
     * 
     * INPUT:
     * - User $user: User cần lấy preferences
     * 
     * OUTPUT:
     * - array: Mảng preferences với key là entity_type, value là [email_enabled, in_app_enabled]
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả preferences của user từ database → Lấy preferences đã cài đặt
     * 2. Lấy defaults cho tất cả entity types → Dùng cho entity types chưa có preference
     * 3. Merge preferences với defaults → Đảm bảo có preferences cho tất cả entity types
     * 4. Trả về mảng preferences đầy đủ → Dùng để hiển thị trong UI
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Lấy tất cả preferences của user
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function getAllPreferences(User $user): array
    {
        $preferences = UserNotificationPreference::where('user_id', $user->id) // Tìm tất cả preferences của user
            ->get()
            ->keyBy('entity_type'); // Key theo entity_type → Dễ lookup

        $defaults = UserNotificationPreference::getDefaults(); // Lấy defaults cho tất cả entity types → Dùng cho entity types chưa có preference
        $result = []; // Mảng kết quả → Dùng để trả về

        foreach ($defaults as $entityType => $default) { // Lặp qua tất cả entity types
            $pref = $preferences->get($entityType); // Lấy preference của user cho entity type này → Null nếu chưa có
            $result[$entityType] = [
                'email_enabled' => $pref ? $pref->email_enabled : $default['email_enabled'], // Dùng preference nếu có, không thì dùng default
                'in_app_enabled' => $pref ? $pref->in_app_enabled : $default['in_app_enabled'], // Dùng preference nếu có, không thì dùng default
            ];
        }

        return $result; // Trả về mảng preferences đầy đủ → Dùng để hiển thị trong UI
    }

    /**
     * Cập nhật hoặc tạo preference cho user
     * 
     * MỤC ĐÍCH:
     * Cập nhật hoặc tạo preference mới cho user cho entity type cụ thể
     * 
     * INPUT:
     * - User $user: User cần cập nhật preference
     * - string $entityType: Loại entity (lease, invoice, ticket, payment, review, etc.)
     * - bool $emailEnabled: Có bật email notification không
     * - bool $inAppEnabled: Có bật in-app notification không
     * 
     * OUTPUT:
     * - UserNotificationPreference: Preference record đã được tạo/cập nhật
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng user_notification_preferences: Kiểm tra preference đã tồn tại chưa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng user_notification_preferences: Tạo mới hoặc cập nhật preference
     */
    public function updatePreference(User $user, string $entityType, bool $emailEnabled, bool $inAppEnabled): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id, // Tìm theo user_id
                'entity_type' => $entityType, // Tìm theo entity_type
            ],
            [
                'email_enabled' => $emailEnabled, // Cập nhật email_enabled → Bật/tắt email notification
                'in_app_enabled' => $inAppEnabled, // Cập nhật in_app_enabled → Bật/tắt in-app notification
            ]
        ); // Tạo mới hoặc cập nhật preference → Đảm bảo luôn có preference cho user
    }

    /**
     * Cập nhật tất cả preferences của user
     * 
     * MỤC ĐÍCH:
     * Cập nhật tất cả preferences của user cho nhiều entity types cùng lúc
     * 
     * INPUT:
     * - User $user: User cần cập nhật preferences
     * - array $preferences: Mảng preferences với key là entity_type, value là [email_enabled, in_app_enabled]
     * 
     * OUTPUT:
     * - bool: true nếu thành công, false nếu có lỗi
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lặp qua tất cả preferences → Cập nhật từng preference
     * 2. Gọi updatePreference() cho từng entity type → Tạo/cập nhật preference
     * 3. Nếu có lỗi: Ghi log và trả về false → Để tracking
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng user_notification_preferences: Cập nhật tất cả preferences của user
     * - Logs: Ghi log lỗi nếu có
     */
    public function updateAllPreferences(User $user, array $preferences): bool
    {
        try {
            foreach ($preferences as $entityType => $settings) { // Lặp qua tất cả preferences
                $this->updatePreference(
                    $user,
                    $entityType,
                    $settings['email_enabled'] ?? true, // Dùng giá trị từ request, mặc định là true
                    $settings['in_app_enabled'] ?? true // Dùng giá trị từ request, mặc định là true
                ); // Cập nhật preference cho từng entity type → Tạo/cập nhật trong database
            }
            return true; // Trả về true nếu thành công → Để tracking
        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]); // Ghi log lỗi → Để debug
            return false; // Trả về false nếu có lỗi → Để tracking
        }
    }

    /**
     * Khởi tạo preferences mặc định cho user (nếu chưa có)
     * 
     * MỤC ĐÍCH:
     * Tạo preferences mặc định cho user mới hoặc user chưa có preferences cho các entity types
     * 
     * INPUT:
     * - User $user: User cần khởi tạo preferences
     * 
     * OUTPUT:
     * - void: Không trả về giá trị
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy defaults cho tất cả entity types → Dùng để tạo preferences mặc định
     * 2. Lặp qua tất cả entity types → Tạo preference cho từng entity type
     * 3. Dùng firstOrCreate() để tránh duplicate → Chỉ tạo nếu chưa có
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng user_notification_preferences: Tạo preferences mặc định cho user (nếu chưa có)
     */
    public function initializeDefaults(User $user): void
    {
        $defaults = UserNotificationPreference::getDefaults(); // Lấy defaults cho tất cả entity types → Dùng để tạo preferences mặc định
        
        foreach ($defaults as $entityType => $default) { // Lặp qua tất cả entity types
            UserNotificationPreference::firstOrCreate(
                [
                    'user_id' => $user->id, // Tìm theo user_id
                    'entity_type' => $entityType, // Tìm theo entity_type
                ],
                [
                    'email_enabled' => $default['email_enabled'], // Tạo với email_enabled từ default
                    'in_app_enabled' => $default['in_app_enabled'], // Tạo với in_app_enabled từ default
                ]
            ); // Tạo preference mặc định nếu chưa có → Đảm bảo user luôn có preferences
        }
    }
}

