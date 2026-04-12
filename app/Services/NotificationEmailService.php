<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Support\MailHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Service: NotificationEmailService
 * 
 * MỤC ĐÍCH:
 * Service quản lý gửi email notifications cho users - gửi email thông báo đơn lẻ hoặc hàng loạt,
 * lưu notification vào database, và áp dụng organization mail config để gửi email với SMTP riêng
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. sendNotification(): Gửi email notification cho một user → Validate, apply mail config, gửi email, lưu vào database
 * 2. sendBulkNotification(): Gửi email notification cho nhiều users → Duyệt qua từng user và gửi
 * 3. sendTenantNotification(): Gửi email notification cho tenant → Wrapper cho sendNotification với action button
 * 4. sendInvoiceNotification(): Gửi email thông báo hóa đơn → Format nội dung hóa đơn và gửi
 * 5. sendPaymentSuccessNotification(): Gửi email thông báo thanh toán thành công → Format nội dung thanh toán và gửi
 * 6. sendViewingNotification(): Gửi email thông báo lịch hẹn xem phòng → Format nội dung viewing và gửi
 * 7. MailHelper / queue: Gửi đồng bộ hoặc qua queue; SMTP tổ chức được áp dụng trong worker khi dùng queue
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: User (bảng users) - Lấy thông tin user để gửi email
 * - Model: OrganizationUser (bảng organization_users) - Lấy tên organization
 * - Model: NotificationChannel (bảng notification_channels) - Lấy channel email
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng notifications: Lưu notification record (status: queued, sent, failed)
 * - Email: Gửi email đến user
 * - Logs: Ghi log quá trình gửi email
 * 
 * LƯU Ý:
 * - Áp dụng organization mail config nếu có để gửi email với SMTP riêng
 * - Lưu notification vào database trước khi gửi (status: queued)
 * - Cập nhật status sau khi gửi thành công (sent) hoặc thất bại (failed)
 * - Hỗ trợ nhiều loại notification: invoice, payment, viewing, custom
 */
class NotificationEmailService
{
    /**
     * Gửi email thông báo cho một user
     * 
     * MỤC ĐÍCH:
     * Gửi email notification cho một user - validate email, áp dụng organization mail config,
     * tạo mailable, gửi email, và lưu notification vào database
     * 
     * INPUT:
     * - user: User cần gửi email
     * - subject: Tiêu đề email
     * - content: Nội dung email
     * - type: Loại notification (info, success, warning, error)
     * - actionUrl: URL nút hành động (optional)
     * - actionText: Text nút hành động (optional)
     * - saveToDatabase: Có lưu vào database không (mặc định true)
     * 
     * OUTPUT:
     * - array: {success: bool, message: string, notification: Notification|null}
     * - Database: Tạo/cập nhật notification record
     * - Email: Gửi email đến user
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra user có email không → Validate trước khi gửi
     * 2. Áp dụng organization mail config → Dùng SMTP riêng nếu có
     * 3. Lấy tên user và tên organization → Dùng để hiển thị trong email
     * 4. Tạo NotificationMail mailable → Format email với template
     * 5. Lưu notification vào database (status: queued) → Track trạng thái
     * 6. Gửi email → Gửi email đến user
     * 7. Cập nhật status thành 'sent' → Đánh dấu đã gửi thành công
     * 8. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng users: Lấy thông tin user
     * - Bảng organization_users: Lấy tên organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Lưu notification record
     * - Email: Gửi email đến user
     * - Logs: Ghi log quá trình gửi
     * 
     * LƯU Ý:
     * - Áp dụng organization mail config nếu có để gửi email với SMTP riêng
     * - Lưu notification vào database trước khi gửi (status: queued)
     * - Cập nhật status sau khi gửi thành công (sent) hoặc thất bại (failed)
     */
    public function sendNotification(
        User $user,
        string $subject,
        string $content,
        string $type = 'info',
        ?string $actionUrl = null,
        ?string $actionText = null,
        bool $saveToDatabase = true
    ): array {
        try {
            if (empty($user->email)) { // Kiểm tra user có email không → Validate trước khi gửi
                throw new Exception('Người dùng không có địa chỉ email');
            }

            $actorOrganizationId = $this->resolveActorOrganizationId();

            $userName = $user->full_name ?? $user->email; // Lấy tên user → Dùng để hiển thị trong email
            
            $organizationName = 'ZoroRMS Team'; // Tên organization mặc định → Fallback
            try {
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first(); // Tìm organization của user → Lấy tên organization
                if ($organizationUser && $organizationUser->organization) {
                    $organizationName = $organizationUser->organization->name ?? 'ZoroRMS Team'; // Lấy tên organization → Dùng để hiển thị trong email
                }
            } catch (\Exception $e) {
                // Use default if error → Không ảnh hưởng đến việc gửi email
            }

            $mailable = new NotificationMail(
                $subject,
                $content,
                $userName,
                $type,
                $actionUrl,
                $actionText,
                $organizationName
            ); // Tạo mailable → Format email với template

            $notification = null;
            if ($saveToDatabase) { // Nếu cần lưu vào database
                $notification = $this->saveNotificationToDatabase(
                    $user,
                    $subject,
                    $content,
                    'queued'
                ); // Lưu notification vào database → Track trạng thái
            }

            MailHelper::sendWithOptionalOrgMail($mailable, $user->email, $actorOrganizationId);

            if ($notification) { // Nếu có notification record
                $this->updateNotificationStatus($notification, 'sent', null); // Cập nhật status thành 'sent' → Đánh dấu đã gửi thành công
            }

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Email notification queued' : 'Email notification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject,
            ]); // Ghi log info → Để tracking

            return [
                'success' => true,
                'message' => MailHelper::wantsQueuedDispatch()
                    ? 'Email thông báo đã được đưa vào hàng đợi'
                    : 'Email thông báo đã được gửi thành công',
                'notification' => $notification,
            ];
        } catch (Exception $e) {
            if (isset($notification) && $notification) { // Nếu có notification record
                $this->updateNotificationStatus($notification, 'failed', $e->getMessage()); // Cập nhật status thành 'failed' → Đánh dấu gửi thất bại
            }

            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'email' => $user->email ?? 'N/A',
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]); // Ghi log error → Để debug

            return [
                'success' => false,
                'message' => 'Không thể gửi email thông báo: ' . $e->getMessage(),
                'notification' => $notification ?? null,
            ];
        }
    }

    /**
     * Gửi email notification với organization mail config
     * 
     * MỤC ĐÍCH:
     * Gửi email notification với organization-specific SMTP config và queue support
     * 
     * @param User $user User nhận email
     * @param string $subject Tiêu đề email
     * @param string $content Nội dung email
     * @param string $type Loại notification (info, success, warning, error)
     * @param string|null $actionUrl URL nút action
     * @param string|null $actionText Text nút action
     * @param bool $saveToDatabase Có lưu vào database không
     * @param int|null $organizationId Organization ID để apply mail config
     * @return array ['success' => bool, 'message' => string, 'notification' => Notification|null]
     */
    public function sendNotificationWithOrgConfig(
        User $user,
        string $subject,
        string $content,
        string $type = 'info',
        ?string $actionUrl = null,
        ?string $actionText = null,
        bool $saveToDatabase = true,
        ?int $organizationId = null
    ): array {
        try {
            if (empty($user->email)) {
                throw new Exception('Người dùng không có địa chỉ email');
            }

            if ($organizationId) {
                Log::debug('Organization mail config will be applied for notification mail', [
                    'organization_id' => $organizationId,
                    'user_email' => $user->email,
                ]);
            }

            $userName = $user->full_name ?? $user->email;
            
            $organizationName = 'ZoroRMS Team';
            if ($organizationId) {
                try {
                    $organization = \App\Models\Organization::find($organizationId);
                    if ($organization) {
                        $organizationName = $organization->name ?? 'ZoroRMS Team';
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not load organization name', [
                        'organization_id' => $organizationId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $mailable = new NotificationMail(
                $subject,
                $content,
                $userName,
                $type,
                $actionUrl,
                $actionText,
                $organizationName
            );

            $notification = null;
            if ($saveToDatabase) {
                $notification = $this->saveNotificationToDatabase(
                    $user,
                    $subject,
                    $content,
                    'queued'
                );
            }

            MailHelper::sendWithOptionalOrgMail($mailable, $user->email, $organizationId);

            Log::info(MailHelper::wantsQueuedDispatch() ? 'Email notification queued (org config)' : 'Email notification sent (org config)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject,
                'organization_id' => $organizationId,
            ]);

            if ($notification) {
                $this->updateNotificationStatus($notification, 'sent', null);
            }

            return [
                'success' => true,
                'message' => MailHelper::wantsQueuedDispatch()
                    ? 'Email đã được đưa vào hàng đợi'
                    : 'Email đã được gửi thành công',
                'notification' => $notification,
            ];
        } catch (Exception $e) {
            if (isset($notification) && $notification) {
                $this->updateNotificationStatus($notification, 'failed', $e->getMessage());
            }

            Log::error('Failed to send/queue email notification', [
                'user_id' => $user->id,
                'email' => $user->email ?? 'N/A',
                'subject' => $subject,
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email: ' . $e->getMessage(),
                'notification' => $notification ?? null,
            ];
        }
    }

    /**
     * Gửi email thông báo cho nhiều users
     * 
     * MỤC ĐÍCH:
     * Gửi email notification cho nhiều users cùng lúc - duyệt qua từng user và gửi email,
     * track số lượng thành công và thất bại
     * 
     * INPUT:
     * - users: Mảng các User objects cần gửi email
     * - subject: Tiêu đề email
     * - content: Nội dung email
     * - type: Loại notification (info, success, warning, error)
     * - actionUrl: URL nút hành động (optional)
     * - actionText: Text nút hành động (optional)
     * 
     * OUTPUT:
     * - array: {success: int, failed: int, details: array} - Thống kê kết quả gửi email
     * 
     * LUỒNG XỬ LÝ:
     * 1. Khởi tạo results array → Track số lượng thành công và thất bại
     * 2. Duyệt qua từng user → Gửi email cho từng user
     * 3. Validate user instance → Chỉ gửi cho User objects hợp lệ
     * 4. Gọi sendNotification() cho từng user → Gửi email
     * 5. Track kết quả (success/failed) → Thống kê
     * 6. Trả về results với thống kê chi tiết
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (chỉ xử lý users được truyền vào)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Lưu notification records cho từng user
     * - Email: Gửi email đến từng user
     * 
     * LƯU Ý:
     * - Duyệt qua từng user và gửi email tuần tự (không parallel)
     * - Track số lượng thành công và thất bại để báo cáo
     */
    public function sendBulkNotification(
        array $users,
        string $subject,
        string $content,
        string $type = 'info',
        ?string $actionUrl = null,
        ?string $actionText = null
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ]; // Khởi tạo results → Track số lượng thành công và thất bại

        foreach ($users as $user) { // Duyệt qua từng user
            if (!$user instanceof User) { // Validate user instance → Chỉ gửi cho User objects hợp lệ
                $results['failed']++; // Tăng số lượng thất bại → Thống kê
                continue; // Bỏ qua user không hợp lệ
            }

            $result = $this->sendNotification(
                $user,
                $subject,
                $content,
                $type,
                $actionUrl,
                $actionText
            ); // Gửi email cho từng user → Gọi sendNotification()

            if ($result['success']) { // Nếu gửi thành công
                $results['success']++; // Tăng số lượng thành công → Thống kê
            } else {
                $results['failed']++; // Tăng số lượng thất bại → Thống kê
            }

            $results['details'][] = [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message'],
            ]; // Lưu chi tiết kết quả → Dùng để báo cáo
        }

        return $results; // Trả về results với thống kê → Dùng để báo cáo
    }

    /**
     * Gửi email thông báo cho tenant với action button mặc định
     * 
     * MỤC ĐÍCH:
     * Wrapper cho sendNotification() với action button mặc định "Xem chi tiết" - dùng để gửi email cho tenant
     * 
     * INPUT:
     * - tenant: User tenant cần gửi email
     * - subject: Tiêu đề email
     * - content: Nội dung email
     * - type: Loại notification (info, success, warning, error)
     * - actionUrl: URL nút hành động (optional)
     * 
     * OUTPUT:
     * - array: Kết quả từ sendNotification()
     * 
     * LƯU Ý:
     * - Wrapper cho sendNotification() với actionText mặc định "Xem chi tiết"
     */
    public function sendTenantNotification(
        User $tenant,
        string $subject,
        string $content,
        string $type = 'info',
        ?string $actionUrl = null
    ): array {
        return $this->sendNotification(
            $tenant,
            $subject,
            $content,
            $type,
            $actionUrl,
            'Xem chi tiết', // Action text mặc định → Dùng cho tenant notifications
            true
        );
    }

    /**
     * Lưu notification vào database
     * 
     * MỤC ĐÍCH:
     * Lưu notification record vào database với status 'queued' - dùng để track trạng thái gửi email
     * 
     * INPUT:
     * - user: User nhận notification
     * - subject: Tiêu đề notification
     * - content: Nội dung notification
     * - status: Trạng thái (mặc định 'queued')
     * 
     * OUTPUT:
     * - Notification: Notification record đã được tạo
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm notification channel 'mail' → Lấy channel email
     * 2. Nếu không có channel: Tạo mới → Fallback
     * 3. Tạo notification record → Lưu vào database
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng notification_channels: Lấy channel email
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notification_channels: Tạo channel mới nếu chưa có
     * - Bảng notifications: Tạo notification record
     */
    private function saveNotificationToDatabase(
        User $user,
        string $subject,
        string $content,
        string $status = 'queued'
    ): Notification {
        $channel = NotificationChannel::where('key_code', 'mail')->first(); // Tìm channel email → Dùng để lưu notification
        
        if (!$channel) { // Nếu không có channel
            $channel = NotificationChannel::create([
                'key_code' => 'mail',
                'name' => 'Email Notifications',
                'active' => true,
            ]); // Tạo channel mới → Fallback
            
            Log::info('Created mail notification channel', ['channel_id' => $channel->id]); // Ghi log → Để tracking
        }

        return Notification::create([
            'channel_id' => $channel->id, // Channel ID → Dùng để phân loại notification
            'to_user_id' => $user->id, // User nhận notification → Dùng để filter
            'subject' => $subject, // Tiêu đề → Hiển thị trong email
            'content' => $content, // Nội dung → Hiển thị trong email
            'status' => $status, // Trạng thái → Track quá trình gửi
            'created_at' => now(), // Thời gian tạo → Dùng để sắp xếp
        ]); // Tạo notification record → Lưu vào database
    }

    /**
     * Cập nhật trạng thái notification
     * 
     * MỤC ĐÍCH:
     * Cập nhật trạng thái notification sau khi gửi email thành công hoặc thất bại - track quá trình gửi email
     * 
     * INPUT:
     * - notification: Notification record cần cập nhật
     * - status: Trạng thái mới (sent, failed)
     * - errorMsg: Thông báo lỗi (nếu có)
     * 
     * OUTPUT:
     * - void: Không trả về giá trị
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Cập nhật status, error_msg, sent_at
     */
    private function updateNotificationStatus(
        Notification $notification,
        string $status,
        ?string $errorMsg = null
    ): void {
        $notification->update([
            'status' => $status, // Trạng thái mới → sent hoặc failed
            'error_msg' => $errorMsg, // Thông báo lỗi → Dùng để debug
            'sent_at' => $status === 'sent' ? now() : null, // Thời gian gửi → Chỉ set khi sent
        ]); // Cập nhật notification → Track trạng thái
    }

    /**
     * Gửi email thông báo hóa đơn
     *
     * @param User $tenant
     * @param array $invoiceData
     * @return array
     */
    public function sendInvoiceNotification(User $tenant, array $invoiceData): array
    {
        $subject = "Thông báo hóa đơn mới - #{$invoiceData['invoice_number']}";
        $content = "Bạn có một hóa đơn mới cần thanh toán.\n\n";
        $content .= "Số hóa đơn: {$invoiceData['invoice_number']}\n";
        $content .= "Tổng tiền: " . number_format($invoiceData['total_amount'], 0, ',', '.') . " VNĐ\n";
        $content .= "Hạn thanh toán: {$invoiceData['due_date']}\n\n";
        $content .= "Vui lòng thanh toán hóa đơn đúng hạn để tránh phát sinh phí.";

        return $this->sendTenantNotification(
            $tenant,
            $subject,
            $content,
            'warning',
            $invoiceData['invoice_url'] ?? null
        );
    }

    /**
     * Gửi email thông báo thanh toán thành công
     *
     * @param User $tenant
     * @param array $paymentData
     * @return array
     */
    public function sendPaymentSuccessNotification(User $tenant, array $paymentData): array
    {
        $subject = "Thanh toán thành công - #{$paymentData['payment_code']}";
        $content = "Thanh toán của bạn đã được xác nhận thành công.\n\n";
        $content .= "Mã thanh toán: {$paymentData['payment_code']}\n";
        $content .= "Số tiền: " . number_format($paymentData['amount'], 0, ',', '.') . " VNĐ\n";
        $content .= "Thời gian: {$paymentData['paid_at']}\n\n";
        $content .= "Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!";

        return $this->sendTenantNotification(
            $tenant,
            $subject,
            $content,
            'success',
            $paymentData['receipt_url'] ?? null
        );
    }

    /**
     * Gửi email thông báo lịch hẹn xem phòng
     *
     * @param User $tenant
     * @param array $viewingData
     * @return array
     */
    public function sendViewingNotification(User $tenant, array $viewingData): array
    {
        $subject = "Xác nhận lịch hẹn xem phòng";
        $content = "Lịch hẹn xem phòng của bạn đã được xác nhận.\n\n";
        $content .= "Địa điểm: {$viewingData['property_name']}\n";
        $content .= "Địa chỉ: {$viewingData['address']}\n";
        $content .= "Thời gian: {$viewingData['viewing_time']}\n\n";
            $content .= "Vui lòng đến đúng giờ. Nếu có thay đổi, vui lòng thông báo trước.";

        return $this->sendTenantNotification(
            $tenant,
            $subject,
            $content,
            'info',
            $viewingData['viewing_url'] ?? null
        );
    }

    /**
     * Organization của user đang đăng nhập (người gửi thông báo), dùng để áp dụng SMTP riêng khi gửi mail.
     */
    private function resolveActorOrganizationId(): ?int
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return null;
            }

            $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            return $organizationUser?->organization_id;
        } catch (\Exception $e) {
            Log::debug('Could not resolve actor organization for mail: ' . $e->getMessage());

            return null;
        }
    }
}

