<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: Notification
 * 
 * MỤC ĐÍCH:
 * Lưu trữ notifications gửi cho users (tenants, managers, agents). Notifications được tạo tự động
 * từ audit logs khi có entity thay đổi (lease, invoice, ticket, payment, review, etc.)
 * 
 * DỮ LIỆU LƯU TRỮ:
 * - audit_log_id: ID của audit log tạo ra notification này → Dùng để link với audit log
 * - channel_id: ID của notification channel (1 = in-app, 2 = email, etc.) → Xác định kênh gửi
 * - to_user_id: ID của user nhận notification → Xác định user nhận notification
 * - subject: Tiêu đề notification → Hiển thị trong UI
 * - content: Nội dung notification → Hiển thị trong UI
 * - status: Trạng thái (queued = chưa đọc, sent = đã đọc) → Dùng để filter và tracking
 * - error_msg: Thông báo lỗi (nếu có) → Dùng để debug
 * - created_at: Thời gian tạo notification → Dùng để sắp xếp và hiển thị
 * - sent_at: Thời gian đánh dấu đã đọc → Dùng để tracking
 * 
 * RELATIONSHIPS:
 * - user(): User nhận notification
 * - channel(): Notification channel (in-app, email, etc.)
 * - auditLog(): Audit log tạo ra notification này
 * 
 * LƯU Ý:
 * - Không dùng timestamps tự động (chỉ dùng created_at và sent_at thủ công)
 * - Status 'queued' = chưa đọc, 'sent' = đã đọc
 */
class Notification extends Model
{
    public $timestamps = false; // Tắt timestamps tự động → Chỉ dùng created_at và sent_at thủ công
    
    protected $fillable = [
        'audit_log_id', // ID của audit log tạo ra notification → Link với audit log
        'channel_id', // ID của notification channel → Xác định kênh gửi (in-app, email)
        'to_user_id', // ID của user nhận notification → Xác định user nhận
        'subject', // Tiêu đề notification → Hiển thị trong UI
        'content', // Nội dung notification → Hiển thị trong UI
        'status', // Trạng thái (queued = chưa đọc, sent = đã đọc) → Dùng để filter
        'error_msg', // Thông báo lỗi (nếu có) → Dùng để debug
        'created_at', // Thời gian tạo notification → Dùng để sắp xếp
        'sent_at', // Thời gian đánh dấu đã đọc → Dùng để tracking
    ];

    protected $casts = [
        'created_at' => 'datetime', // Cast created_at thành Carbon datetime → Dùng để format
        'sent_at' => 'datetime', // Cast sent_at thành Carbon datetime → Dùng để format
    ];

    /**
     * Relationship với User - User nhận notification
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin user nhận notification này
     * 
     * RETURN:
     * - BelongsTo: User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id'); // Relationship với User → Lấy user nhận notification
    }

    /**
     * Relationship với NotificationChannel - Notification channel
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin notification channel (in-app, email, etc.)
     * 
     * RETURN:
     * - BelongsTo: NotificationChannel model
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id'); // Relationship với NotificationChannel → Lấy channel info
    }

    /**
     * Relationship với AuditLog - Audit log tạo ra notification
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin audit log tạo ra notification này (để lấy entity link, entity type, etc.)
     * 
     * RETURN:
     * - BelongsTo: AuditLog model
     */
    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id'); // Relationship với AuditLog → Lấy audit log info để tạo entity link
    }
}
