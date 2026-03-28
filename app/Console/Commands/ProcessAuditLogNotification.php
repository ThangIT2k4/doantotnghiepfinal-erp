<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Services\NotificationFromAuditService;
use Illuminate\Support\Facades\Log;

/**
 * Command: ProcessAuditLogNotification
 * 
 * MỤC ĐÍCH:
 * Xử lý và tạo notifications từ một audit log cụ thể.
 * Command này được dùng để xử lý lại notification khi có vấn đề hoặc để debug.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số từ command line: audit_log_id (bắt buộc)
 * 2. Tìm audit log từ: Model AuditLog (bảng audit_logs)
 * 3. Kiểm tra xem đã có notification chưa (tránh duplicate)
 * 4. Xử lý: Gọi NotificationFromAuditService->createNotificationsFromAuditLog()
 * 5. Hiển thị kết quả: Số lượng notification đã tạo
 * 
 * CÁCH CHẠY:
 * php artisan notification:process-audit-log {id}
 * 
 * Ví dụ:
 * php artisan notification:process-audit-log 123
 */
class ProcessAuditLogNotification extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Tham số:
     * - {id}: ID của audit log cần xử lý (bắt buộc)
     * 
     * @var string
     */
    protected $signature = 'notification:process-audit-log {id : The audit log ID to process}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Process and create notifications for a specific audit log ID';

    /**
     * Hàm chính xử lý command
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Lấy audit_log_id từ command line
     * 2. Tìm audit log trong database:
     *    - Gọi AuditLog::find($auditLogId)
     *    - Nếu không tìm thấy: Hiển thị lỗi và return
     * 3. Kiểm tra notification đã tồn tại:
     *    - Query từ bảng notifications với audit_log_id
     *    - Nếu đã có: Hỏi người dùng có muốn xử lý lại không (có thể tạo duplicate)
     * 4. Gọi service để xử lý:
     *    - NotificationFromAuditService->createNotificationsFromAuditLog($auditLog)
     *    - Service này sẽ tạo notifications dựa trên audit log
     * 5. Hiển thị kết quả: Số lượng notification đã tạo
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: App\Models\AuditLog (bảng audit_logs)
     * - Model: App\Models\Notification (bảng notifications) - kiểm tra duplicate
     * 
     * DỮ LIỆU GHI VÀO:
     * - Tạo bản ghi mới trong bảng notifications (qua NotificationFromAuditService)
     * - Ghi log vào storage/logs/laravel.log
     * 
     * SERVICE ĐƯỢC GỌI:
     * - App\Services\NotificationFromAuditService::createNotificationsFromAuditLog()
     *   File: app/Services/NotificationFromAuditService.php
     *   Method này sẽ:
     *   + Phân tích audit log (action, entity_type, entity_id)
     *   + Tạo notifications cho các user liên quan
     *   + Lưu vào bảng notifications
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        /**
         * Bước 1: Lấy audit_log_id từ command line
         * 
         * $this->argument('id') - Lấy giá trị của argument 'id' từ command line
         *   - argument() là method của Laravel Command để lấy giá trị của command argument
         *   - 'id' là tên argument được định nghĩa trong $signature (bắt buộc)
         *   - Nếu user chạy: php artisan notification:process-audit-log 123 => trả về "123"
         *   - Trả về string (ID của audit log cần xử lý)
         * 
         * $auditLogId - Biến lưu ID của audit log cần xử lý
         *   - Sẽ được sử dụng để tìm audit log trong database
         */
        $auditLogId = $this->argument('id');
        
        /**
         * Hiển thị thông báo bắt đầu xử lý audit log
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Processing audit log ID: {$auditLogId}"
         *   - {$auditLogId} là string interpolation, sẽ thay thế bằng giá trị $auditLogId
         *   - Ví dụ: "Processing audit log ID: 123"
         *   - Thông báo cho user biết đang xử lý audit log nào
         */
        $this->info("Processing audit log ID: {$auditLogId}");
        
        /**
         * Bước 2: Tìm audit log trong database bằng ID
         * 
         * AuditLog::find($auditLogId) - Tìm audit log trong database bằng primary key
         *   - find() là method của Eloquent Model để tìm record bằng ID
         *   - find() sẽ execute SQL query: SELECT * FROM audit_logs WHERE id = $auditLogId LIMIT 1
         *   - Trả về AuditLog model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - find() chỉ tìm bằng primary key (id), không tìm bằng các field khác
         * 
         * $auditLog - Biến lưu AuditLog model instance (hoặc null)
         *   - Nếu tìm thấy: $auditLog là instance của App\Models\AuditLog
         *   - Nếu không tìm thấy: $auditLog = null
         */
        $auditLog = AuditLog::find($auditLogId);
        
        /**
         * Kiểm tra xem audit log có tồn tại không
         * 
         * if (!$auditLog) - Kiểm tra xem $auditLog có null không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $auditLog = null, !null = true, vào block if
         *   - Nếu $auditLog != null, !$auditLog = false, không vào block if
         * 
         * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
         *   - "Audit log with ID {$auditLogId} not found!"
         *   - {$auditLogId} là string interpolation
         *   - Ví dụ: "Audit log with ID 123 not found!"
         *   - Hiển thị lỗi cho user để họ biết audit log không tồn tại
         * 
         * return 1 - Trả về 1 (Command::FAILURE) để báo command thất bại
         *   - 1 nghĩa là command thất bại, có lỗi
         *   - Command sẽ dừng ở đây, không thực thi code phía dưới
         */
        if (!$auditLog) {
            $this->error("Audit log with ID {$auditLogId} not found!");
            return 1;
        }
        
        /**
         * Hiển thị thông tin audit log đã tìm thấy
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "Found audit log:" - Tiêu đề
         * 
         * $this->line() - Hiển thị message màu trắng trong console (thông tin bình thường)
         *   - "  - ID: {$auditLog->id}" - Hiển thị ID của audit log
         *     - {$auditLog->id} là string interpolation, truy cập field id từ AuditLog model
         *   - "  - Action: {$auditLog->action}" - Hiển thị action (ví dụ: 'created', 'updated', 'deleted')
         *   - "  - Entity Type: {$auditLog->entity_type}" - Hiển thị loại entity (ví dụ: 'App\Models\Invoice')
         *   - "  - Entity ID: {$auditLog->entity_id}" - Hiển thị ID của entity liên quan
         *   - "  - Organization ID: {$auditLog->organization_id}" - Hiển thị ID của organization
         *   - "  - Created At: {$auditLog->created_at}" - Hiển thị thời gian tạo
         *     - $auditLog->created_at là Carbon date instance (đã được cast trong model)
         *     - Khi convert sang string, sẽ hiển thị format mặc định
         * 
         * Thông tin này giúp user xác nhận đúng audit log cần xử lý
         */
        $this->info("Found audit log:");
        $this->line("  - ID: {$auditLog->id}");
        $this->line("  - Action: {$auditLog->action}");
        $this->line("  - Entity Type: {$auditLog->entity_type}");
        $this->line("  - Entity ID: {$auditLog->entity_id}");
        $this->line("  - Organization ID: {$auditLog->organization_id}");
        $this->line("  - Created At: {$auditLog->created_at}");
        
        /**
         * Bước 3: Kiểm tra notification đã tồn tại
         * 
         * Notification::where('audit_log_id', $auditLogId) - Query notifications từ database
         *   - where() là method của Eloquent query builder để lọc với điều kiện
         *   - 'audit_log_id' là tên column trong bảng notifications
         *   - $auditLogId là giá trị cần tìm
         *   - Query sẽ tìm tất cả notifications có audit_log_id = $auditLogId
         * 
         * ->get() - Thực thi query và trả về Collection chứa các Notification models
         *   - get() sẽ execute SQL query: SELECT * FROM notifications WHERE audit_log_id = $auditLogId
         *   - Trả về Collection chứa các Notification models
         * 
         * $existingNotifications - Biến lưu Collection chứa các Notification models đã tồn tại
         *   - Mục đích: Kiểm tra xem đã có notification nào được tạo từ audit log này chưa
         *   - Tránh tạo duplicate notification
         */
        $existingNotifications = Notification::where('audit_log_id', $auditLogId)->get();
        
        /**
         * Hiển thị số lượng notifications đã tồn tại
         * 
         * $existingNotifications->count() - Đếm số phần tử trong Collection
         *   - count() là method của Laravel Collection để đếm số phần tử
         *   - Trả về integer (số lượng notifications)
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "\nExisting notifications: " . $existingNotifications->count()
         *   - "\n" là newline character (xuống dòng)
         *   - Dấu . là string concatenation operator trong PHP
         *   - Ví dụ: "Existing notifications: 3"
         *   - Thông báo cho user biết số lượng notifications đã tồn tại
         */
        $this->info("\nExisting notifications: " . $existingNotifications->count());
        
        /**
         * Nếu đã có notification, hỏi người dùng có muốn xử lý lại không
         * 
         * if ($existingNotifications->count() > 0) - Kiểm tra xem có notification nào không
         *   - count() > 0 nghĩa là có ít nhất 1 notification đã tồn tại
         *   - Nếu có, vào block if để hỏi user
         */
        if ($existingNotifications->count() > 0) {
            /**
             * Hiển thị cảnh báo và danh sách notifications đã tồn tại
             * 
             * $this->warn() - Hiển thị message màu vàng trong console (cảnh báo)
             *   - "Notifications already exist for this audit log:"
             *   - Thông báo cho user biết đã có notifications từ audit log này
             * 
             * foreach ($existingNotifications as $notif) - Lặp qua từng notification
             *   - $existingNotifications là Collection chứa các Notification models
             *   - $notif là từng Notification model trong Collection
             *   - Mỗi lần lặp, $notif sẽ là một instance của App\Models\Notification
             * 
             * $this->line() - Hiển thị thông tin từng notification
             *   - "  - Notification ID: {$notif->id}, User ID: {$notif->to_user_id}, Status: {$notif->status}"
             *   - {$notif->id}, {$notif->to_user_id}, {$notif->status} là string interpolation
             *   - Ví dụ: "  - Notification ID: 5, User ID: 10, Status: queued"
             *   - Hiển thị chi tiết từng notification để user biết
             */
            $this->warn("Notifications already exist for this audit log:");
            foreach ($existingNotifications as $notif) {
                $this->line("  - Notification ID: {$notif->id}, User ID: {$notif->to_user_id}, Status: {$notif->status}");
            }
            
            /**
             * Hỏi người dùng có muốn xử lý lại không
             * 
             * $this->confirm() - Hiển thị câu hỏi yes/no và chờ user trả lời
             *   - confirm() là method của Laravel Command để xác nhận từ user
             *   - Hiển thị: "Do you want to process again? (This will create duplicate notifications) (yes/no) [no]:"
             *   - User có thể nhập: yes, y, no, n (case insensitive)
             *   - Trả về boolean: true nếu user chọn yes, false nếu user chọn no
             * 
             * if (!$this->confirm(...)) - Kiểm tra xem user có chọn no không
             *   - ! là NOT operator, đảo ngược giá trị boolean
             *   - Nếu user chọn no (false), !false = true, vào block if
             * 
             * $this->info('Cancelled.') - Hiển thị message thông báo đã hủy
             * 
             * return 0 - Trả về 0 (Command::SUCCESS) để báo command đã hoàn thành
             *   - Command sẽ dừng ở đây, không tạo notification mới
             *   - User đã chọn không xử lý lại
             */
            if (!$this->confirm('Do you want to process again? (This will create duplicate notifications)')) {
                $this->info('Cancelled.');
                return 0;
            }
        }
        
        /**
         * Bước 4: Gọi service để xử lý notification
         * 
         * $this->info() - Hiển thị message màu xanh trong console
         *   - "\nProcessing notification..."
         *   - "\n" là newline character (xuống dòng)
         *   - Thông báo cho user biết đang bắt đầu xử lý notification
         */
        $this->info("\nProcessing notification...");
        
        /**
         * Xử lý lỗi khi gọi service
         * 
         * try { ... } catch { ... } - Xử lý lỗi khi xử lý notification
         * - Nếu xử lý thành công: tiếp tục
         * - Nếu xử lý thất bại: catch exception, ghi log, hiển thị lỗi, và return 1
         * - Đảm bảo command không bị crash khi có lỗi
         */
        try {
            /**
             * Lấy service từ Laravel container
             * 
             * app(NotificationFromAuditService::class) - Resolve service từ Laravel service container
             *   - app() là helper function của Laravel để resolve class từ container
             *   - NotificationFromAuditService::class trả về fully qualified class name
             *   - Laravel sẽ tự động inject dependencies nếu service có constructor dependencies
             *   - Service nằm tại: app/Services/NotificationFromAuditService.php
             *   - Trả về instance của NotificationFromAuditService
             * 
             * $service - Biến lưu NotificationFromAuditService instance
             *   - Sẽ được sử dụng để gọi method createNotificationsFromAuditLog()
             */
            $service = app(NotificationFromAuditService::class);
            
            /**
             * Gọi method createNotificationsFromAuditLog() để tạo notifications
             * 
             * $service->createNotificationsFromAuditLog($auditLog) - Gọi method của service
             *   - createNotificationsFromAuditLog() là method trong NotificationFromAuditService
             *   - Method này nhận AuditLog model instance làm tham số
             *   - Method này sẽ:
             *     1. Phân tích audit log (action, entity_type, entity_id, organization_id)
             *     2. Xác định các user cần nhận notification dựa trên:
             *        - Entity type và ID (ví dụ: Invoice ID 123)
             *        - Organization ID
             *        - Action (ví dụ: 'created', 'updated', 'deleted')
             *     3. Tạo notifications cho các user đó với:
             *        - Subject và content phù hợp với action
             *        - Channel (in-app, email, etc.)
             *        - Status = 'queued'
             *     4. Lưu vào bảng notifications
             *   - Trả về boolean: true nếu thành công (có tạo ít nhất 1 notification), false nếu không
             * 
             * $result - Biến lưu kết quả (true/false)
             *   - Sẽ được sử dụng để hiển thị kết quả cho user
             */
            $result = $service->createNotificationsFromAuditLog($auditLog);
            
            /**
             * Kiểm tra kết quả và hiển thị thông báo
             * 
             * if ($result) - Kiểm tra xem có thành công không
             *   - Nếu $result = true (thành công), vào block if
             *   - Nếu $result = false (không thành công), vào block else
             */
            if ($result) {
                /**
                 * Hiển thị thông báo thành công
                 * 
                 * $this->info() - Hiển thị message màu xanh trong console
                 *   - "✓ Notifications processed successfully!"
                 *   - ✓ là ký tự checkmark (Unicode)
                 *   - Thông báo cho user biết đã xử lý thành công
                 */
                $this->info("✓ Notifications processed successfully!");
                
                /**
                 * Query lại notifications để hiển thị số lượng mới
                 * 
                 * Notification::where('audit_log_id', $auditLogId) - Query notifications từ database
                 *   - where() là method của Eloquent query builder để lọc với điều kiện
                 *   - 'audit_log_id' là tên column trong bảng notifications
                 *   - $auditLogId là giá trị cần tìm
                 *   - Query sẽ tìm tất cả notifications có audit_log_id = $auditLogId (bao gồm cả notifications mới tạo)
                 * 
                 * ->get() - Thực thi query và trả về Collection chứa các Notification models
                 *   - get() sẽ execute SQL query: SELECT * FROM notifications WHERE audit_log_id = $auditLogId
                 *   - Trả về Collection chứa các Notification models (bao gồm cả notifications cũ và mới)
                 * 
                 * $newNotifications - Biến lưu Collection chứa tất cả Notification models (cũ + mới)
                 */
                $newNotifications = Notification::where('audit_log_id', $auditLogId)->get();
                
                /**
                 * Hiển thị tổng số notifications hiện tại
                 * 
                 * $newNotifications->count() - Đếm số phần tử trong Collection
                 *   - count() là method của Laravel Collection để đếm số phần tử
                 *   - Trả về integer (tổng số notifications, bao gồm cả cũ và mới)
                 * 
                 * $this->info() - Hiển thị message màu xanh trong console
                 *   - "\nTotal notifications now: " . $newNotifications->count()
                 *   - "\n" là newline character (xuống dòng)
                 *   - Dấu . là string concatenation operator
                 *   - Ví dụ: "Total notifications now: 5"
                 *   - Thông báo cho user biết tổng số notifications hiện tại
                 */
                $this->info("\nTotal notifications now: " . $newNotifications->count());
                
                /**
                 * Hiển thị thông tin từng notification
                 * 
                 * foreach ($newNotifications as $notif) - Lặp qua từng phần tử trong Collection
                 * - $newNotifications là Collection chứa các Notification models
                 * - $notif là từng Notification model trong Collection
                 * - Mỗi lần lặp, $notif sẽ là một instance của App\Models\Notification
                 * 
                 * $this->line() - Hiển thị thông tin từng notification
                 *   - "  - Notification ID: {$notif->id}, User ID: {$notif->to_user_id}, Status: {$notif->status}"
                 *   - {$notif->id}, {$notif->to_user_id}, {$notif->status} là string interpolation
                 *   - Ví dụ: "  - Notification ID: 5, User ID: 10, Status: queued"
                 *   - Hiển thị chi tiết từng notification để user biết
                 */
                foreach ($newNotifications as $notif) {
                    $this->line("  - Notification ID: {$notif->id}, User ID: {$notif->to_user_id}, Status: {$notif->status}");
                }
            } else {
                /**
                 * Hiển thị cảnh báo nếu không tạo được notification
                 * 
                 * $this->warn() - Hiển thị message màu vàng trong console (cảnh báo)
                 *   - "No notifications were created. Check logs for details."
                 *   - Thông báo cho user biết không có notification nào được tạo
                 *   - Có thể do: không có user nào cần nhận notification, hoặc có lỗi trong service
                 *   - User nên kiểm tra logs để biết chi tiết
                 */
                $this->warn("No notifications were created. Check logs for details.");
            }
            
        } catch (\Exception $e) {
            /**
             * Xử lý lỗi khi xử lý notification
             * 
             * catch (\Exception $e) - Bắt exception khi xử lý notification thất bại
             * - Có thể là: database error, service error, validation error, etc.
             * - $e là exception object chứa thông tin về lỗi
             * 
             * $this->error() - Hiển thị message màu đỏ trong console (báo lỗi)
             *   - "Error processing notification: " . $e->getMessage()
             *   - $e->getMessage() trả về error message của exception
             *   - Dấu . là string concatenation operator trong PHP
             *   - Ví dụ: "Error processing notification: Database connection failed"
             *   - Hiển thị lỗi cho user để họ biết lý do thất bại
             * 
             * $this->error("Trace: " . $e->getTraceAsString()) - Hiển thị stack trace
             *   - $e->getTraceAsString() trả về stack trace của exception dạng string
             *   - Stack trace cho biết vị trí chính xác trong code nơi lỗi xảy ra
             *   - Giúp debug khi có lỗi
             * 
             * Log::error() - Ghi log với level ERROR (lỗi nghiêm trọng)
             *   - Log được ghi vào: storage/logs/laravel.log
             *   - Format: [YYYY-MM-DD HH:MM:SS] local.ERROR: Message {context}
             * 
             * Tham số 1: 'Command failed to process audit log notification' - Message mô tả lỗi
             * 
             * Tham số 2: Array chứa context data
             * - 'audit_log_id' => $auditLogId - ID của audit log gặp lỗi (để debug)
             * - 'error' => $e->getMessage() - Error message của exception
             * - 'trace' => $e->getTraceAsString() - Stack trace của exception
             * 
             * return 1 - Trả về 1 (Command::FAILURE) để báo command thất bại
             *   - 1 nghĩa là command thất bại, có lỗi
             *   - Command sẽ dừng ở đây
             */
            $this->error("Error processing notification: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            Log::error('Command failed to process audit log notification', [
                'audit_log_id' => $auditLogId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        // Trả về 0 (Command::SUCCESS) để báo cho Laravel biết command đã chạy thành công
        // Giá trị này sẽ được sử dụng bởi cron job hoặc scheduler để biết command có thành công không
        return 0;
    }
}
