<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\NotificationFromAuditService;
use Illuminate\Support\Facades\Log;

/**
 * Observer: AuditLogObserver
 * 
 * MỤC ĐÍCH:
 * Tự động tạo notifications từ audit_logs khi có audit log mới được tạo. Observer này được trigger
 * mỗi khi có entity thay đổi (created, updated, deleted) và audit log được ghi lại.
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. created(): Khi audit log được tạo → Gọi NotificationFromAuditService để tạo notifications
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: AuditLog (bảng audit_logs) - Audit log mới được tạo
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng notifications: Tạo notifications mới cho users liên quan (qua NotificationFromAuditService)
 * - Logs: Ghi log quá trình xử lý và lỗi (nếu có)
 * 
 * LƯU Ý:
 * - Observer này được đăng ký trong AppServiceProvider
 * - Không throw exception để không block audit log creation nếu có lỗi
 * - Thay thế cho các Listeners/Services notification cũ, sử dụng audit_logs làm source of truth
 */
class AuditLogObserver
{
    protected $notificationFromAuditService;

    /**
     * Constructor - Inject NotificationFromAuditService
     * 
     * MỤC ĐÍCH:
     * Khởi tạo service để tạo notifications từ audit logs
     * 
     * INPUT:
     * - NotificationFromAuditService: Service xử lý tạo notifications
     */
    public function __construct(NotificationFromAuditService $notificationFromAuditService)
    {
        $this->notificationFromAuditService = $notificationFromAuditService; // Lưu service → Dùng để tạo notifications
    }

    /**
     * Xử lý sự kiện AuditLog "created"
     * 
     * MỤC ĐÍCH:
     * Tự động tạo notifications từ audit log mới được tạo. Method này được trigger tự động
     * khi có audit log mới trong database.
     * 
     * INPUT:
     * - AuditLog $auditLog: Audit log mới được tạo (chứa thông tin về entity thay đổi)
     * 
     * OUTPUT:
     * - Database: Tạo notifications mới trong bảng notifications (qua NotificationFromAuditService)
     * - Logs: Ghi log quá trình xử lý
     * 
     * LUỒNG XỬ LÝ:
     * 1. Ghi log bắt đầu xử lý → Để tracking và debug
     * 2. Gọi NotificationFromAuditService->createNotificationsFromAuditLog() → Tạo notifications cho users liên quan
     * 3. Ghi log kết quả → Để tracking
     * 4. Nếu có lỗi: Ghi log lỗi nhưng không throw exception → Không block audit log creation
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: AuditLog - Audit log mới được tạo
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng notifications: Tạo notifications mới (qua NotificationFromAuditService)
     * - Logs: Ghi log quá trình xử lý và lỗi
     * 
     * LƯU Ý:
     * - Không throw exception để không block audit log creation nếu có lỗi khi tạo notifications
     * - Service sẽ tự xử lý việc xác định users cần nhận notifications dựa trên audit log
     */
    public function created(AuditLog $auditLog): void
    {
        try {
            Log::info('AuditLogObserver: Processing audit log created event', [
                'audit_log_id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'organization_id' => $auditLog->organization_id
            ]); // Ghi log bắt đầu xử lý → Để tracking và debug
            
            // Gọi service tạo notifications từ audit log → Tạo notifications cho users liên quan (managers, tenants, agents)
            $result = $this->notificationFromAuditService->createNotificationsFromAuditLog($auditLog);
            
            Log::info('AuditLogObserver: Notification processing completed', [
                'audit_log_id' => $auditLog->id,
                'result' => $result
            ]); // Ghi log kết quả → Để tracking
        } catch (\Exception $e) {
            // Không throw exception để không block audit log creation → Đảm bảo audit log luôn được tạo
            Log::error('Failed to create notifications from audit log in observer', [
                'audit_log_id' => $auditLog->id,
                'action' => $auditLog->action ?? null,
                'entity_type' => $auditLog->entity_type ?? null,
                'entity_id' => $auditLog->entity_id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]); // Ghi log lỗi → Để debug, nhưng không throw exception
        }
    }
}

