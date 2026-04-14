<?php

namespace App\Events;

use App\Models\CommissionEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: CommissionEventNotification
 * 
 * MỤC ĐÍCH:
 * Event được dispatch khi có thay đổi về commission event (tạo mới, cập nhật, thanh toán, hủy, duyệt).
 * Event này kích hoạt việc gửi thông báo cho agent và managers khi có sự kiện liên quan đến commission.
 * 
 * LUỒNG XỬ LÝ TỔNG QUAN:
 * 1. DISPATCH EVENT (Nơi gọi event):
 *    - CommissionEventController::store() - Khi tạo commission event mới
 *    - CommissionEventController::update() - Khi cập nhật commission event
 *    - CommissionEventController::cancel() - Khi hủy commission event
 *    - CommissionEventController::approve() - Khi duyệt commission event
 *    - CommissionEventController::markAsPaid() - Khi đánh dấu đã thanh toán
 *    - CommissionEventController::updateStatus() - Khi cập nhật trạng thái
 * 
 * 2. EVENT LISTENER (Nơi xử lý event):
 *    - App\Listeners\SendCommissionNotification
 *    - Được đăng ký trong: app/Providers/EventServiceProvider.php
 *    - Mapping: CommissionEventNotification::class => SendCommissionNotification::class
 * 
 * 3. SERVICE ĐƯỢC GỌI:
 *    - SendCommissionNotification->handle() gọi:
 *      + CommissionNotificationService->notifyCommissionEvent()
 *      + Service này sẽ:
 *        * Gửi in-app notification cho tất cả managers của organization
 *        * Gửi in-app notification cho agent (chủ sở hữu commission)
 *        * Lưu notification vào bảng notifications
 * 
 * 4. DỮ LIỆU ĐƯỢC TRUYỀN:
 *    - $commissionEvent: Model CommissionEvent (chứa thông tin commission)
 *    - $eventType: Loại sự kiện ('created', 'updated', 'paid', 'cancelled', 'approved')
 * 
 * VÍ DỤ SỬ DỤNG:
 * 
 * // Trong Controller:
 * $commissionEvent = CommissionEvent::create([...]);
 * event(new CommissionEventNotification($commissionEvent, 'created'));
 * 
 * // Hoặc sử dụng helper function:
 * event(new \App\Events\CommissionEventNotification($commissionEvent, 'paid'));
 * 
 * CÁC LOẠI EVENT TYPE:
 * - 'created': Khi tạo commission event mới
 * - 'updated': Khi cập nhật commission event
 * - 'paid': Khi đánh dấu đã thanh toán
 * - 'cancelled': Khi hủy commission event
 * - 'approved': Khi duyệt commission event
 */
class CommissionEventNotification
{
    /**
     * Sử dụng các traits của Laravel để hỗ trợ event system:
     * 
     * - Dispatchable: Cho phép dispatch event bằng method dispatch() hoặc event() helper
     * - InteractsWithSockets: Hỗ trợ broadcasting (nếu cần broadcast qua WebSocket)
     * - SerializesModels: Tự động serialize models khi event được queue
     */
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Model CommissionEvent chứa thông tin về commission event
     * 
     * Thông tin bao gồm:
     * - organization_id: ID của organization
     * - agent_id: ID của agent (người nhận commission)
     * - policy_id: ID của commission policy
     * - trigger_event: Sự kiện kích hoạt commission
     * - ref_type: Loại reference ('lease', 'manual')
     * - ref_id: ID của reference (ví dụ: lease_id)
     * - occurred_at: Thời gian xảy ra
     * - amount_base: Số tiền cơ sở
     * - commission_total: Tổng số tiền commission
     * - status: Trạng thái ('pending', 'approved', 'paid', 'cancelled')
     * 
     * @var CommissionEvent
     */
    public $commissionEvent;

    /**
     * Loại sự kiện commission
     * 
     * Các giá trị có thể:
     * - 'created': Commission event vừa được tạo mới
     * - 'updated': Commission event vừa được cập nhật
     * - 'paid': Commission event vừa được đánh dấu đã thanh toán
     * - 'cancelled': Commission event vừa bị hủy
     * - 'approved': Commission event vừa được duyệt
     * 
     * Loại event này được dùng để xác định nội dung thông báo phù hợp
     * 
     * @var string
     */
    public $eventType; // 'created', 'updated', 'paid', 'cancelled'

    /**
     * Khởi tạo event instance
     * 
     * LUỒNG XỬ LÝ:
     * 1. Nhận CommissionEvent model và eventType từ nơi dispatch
     * 2. Lưu vào các property public để Listener có thể truy cập
     * 3. Event sẽ được Laravel tự động dispatch đến các Listener đã đăng ký
     * 
     * CÁCH HOẠT ĐỘNG:
     * - Khi event được dispatch: Laravel tìm các Listener đã đăng ký trong EventServiceProvider
     * - Gọi method handle() của từng Listener với event instance này
     * - Listener có thể truy cập $event->commissionEvent và $event->eventType
     * 
     * VÍ DỤ:
     * // Trong Controller:
     * $commissionEvent = CommissionEvent::create([...]);
     * event(new CommissionEventNotification($commissionEvent, 'created'));
     * 
     * // Laravel sẽ tự động:
     * // 1. Tìm Listener: SendCommissionNotification
     * // 2. Gọi: SendCommissionNotification->handle($event)
     * // 3. Listener gọi: CommissionNotificationService->notifyCommissionEvent(...)
     * 
     * @param CommissionEvent $commissionEvent Model CommissionEvent chứa thông tin commission
     * @param string $eventType Loại sự kiện (mặc định: 'created')
     */
    public function __construct(CommissionEvent $commissionEvent, string $eventType = 'created')
    {
        /**
         * Lưu CommissionEvent model vào property
         * 
         * Model này chứa đầy đủ thông tin về commission event:
         * - Thông tin agent, organization
         * - Số tiền commission
         * - Trạng thái, thời gian
         * - Reference đến lease hoặc manual entry
         */
        $this->commissionEvent = $commissionEvent;
        
        /**
         * Lưu loại sự kiện vào property
         * 
         * EventType được dùng để:
         * - Xác định nội dung thông báo phù hợp
         * - Ghi log đúng loại sự kiện
         * - Xử lý logic khác nhau tùy loại event
         */
        $this->eventType = $eventType;
    }
}
