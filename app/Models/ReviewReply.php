<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: ReviewReply
 * 
 * MỤC ĐÍCH:
 * Model đại diện cho phản hồi của staff (Manager/Agent) hoặc tenant cho review. Hỗ trợ nested replies (phản hồi lồng nhau) 
 * thông qua parent_reply_id. Lưu trữ thông tin về người phản hồi (user_id, user_type), nội dung phản hồi, và quan hệ với review.
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Staff (Manager/Agent) hoặc tenant tạo phản hồi cho review
 * 2. Phản hồi có thể là parent reply (parent_reply_id = null) hoặc nested reply (parent_reply_id != null)
 * 3. Chỉ author của reply mới có thể chỉnh sửa trong vòng 24 giờ
 * 4. Author của reply hoặc author của review có thể xóa reply
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng review_replies: Lưu trữ phản hồi
 * - Bảng reviews: Thông tin review được phản hồi
 * - Bảng users: Thông tin người phản hồi
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng review_replies: Tạo/cập nhật/xóa (soft delete) phản hồi
 * 
 * LƯU Ý:
 * - Sử dụng soft deletes (có thể khôi phục)
 * - Hỗ trợ nested replies (phản hồi lồng nhau) thông qua parent_reply_id
 * - user_type: 'manager', 'agent', 'tenant', 'owner' để phân biệt loại user
 * - Chỉ author mới có thể chỉnh sửa trong vòng 24 giờ
 */
class ReviewReply extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $fillable = [ // Các trường có thể mass assign → Bảo mật: chỉ cho phép assign các trường này
        'review_id', // ID của review được phản hồi
        'user_id', // ID của user phản hồi
        'parent_reply_id', // ID của reply cha (nếu là nested reply)
        'content', // Nội dung phản hồi
        'user_type', // Loại user (manager, agent, tenant, owner)
        'deleted_by', // ID của user xóa reply
    ];

    protected $casts = [ // Cast các trường khi lấy từ database → Tự động convert kiểu dữ liệu
        'content' => 'string', // Cast thành string
        'user_type' => 'string', // Cast thành string
    ];

    /**
     * Relationship: review - Review được phản hồi
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin review được phản hồi → Dùng để hiển thị thông tin review
     * 
     * RETURN:
     * - BelongsTo Review
     */
    public function review()
    {
        return $this->belongsTo(Review::class); // BelongsTo relationship → Một reply thuộc một review
    }

    /**
     * Relationship: user - User phản hồi
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin user phản hồi → Dùng để hiển thị thông tin người phản hồi
     * 
     * RETURN:
     * - BelongsTo User
     */
    public function user()
    {
        return $this->belongsTo(User::class); // BelongsTo relationship → Một reply thuộc một user
    }

    /**
     * Relationship: parentReply - Reply cha (cho nested replies)
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin reply cha (nếu là nested reply) → Dùng để hiển thị cấu trúc nested
     * 
     * RETURN:
     * - BelongsTo ReviewReply (parent_reply_id)
     */
    public function parentReply()
    {
        return $this->belongsTo(ReviewReply::class, 'parent_reply_id'); // BelongsTo relationship với foreign key parent_reply_id → Reply cha của nested reply
    }

    /**
     * Relationship: childReplies - Các reply con (nested replies)
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách reply con (nested replies) → Dùng để hiển thị cấu trúc nested
     * 
     * RETURN:
     * - HasMany ReviewReply (parent_reply_id)
     */
    public function childReplies()
    {
        return $this->hasMany(ReviewReply::class, 'parent_reply_id'); // HasMany relationship với foreign key parent_reply_id → Các reply con của reply này
    }

    /**
     * Relationship: deletedBy - User xóa reply
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin user xóa reply → Dùng để tracking và audit
     * 
     * RETURN:
     * - BelongsTo User (deleted_by)
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by'); // BelongsTo relationship với foreign key deleted_by → User xóa reply
    }

    /**
     * Accessor: user_type_label - Label tiếng Việt của user_type
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi user_type sang label tiếng Việt → Dùng để hiển thị loại user bằng tiếng Việt
     * 
     * RETURN:
     * - String: Label tiếng Việt của user_type
     */
    public function getUserTypeLabelAttribute()
    {
        return match($this->user_type) { // Match expression → Chuyển đổi user_type sang label
            'tenant' => 'Người thuê', // tenant → Người thuê
            'manager' => 'Quản lý', // manager → Quản lý
            'agent' => 'Nhân viên', // agent → Nhân viên
            'owner' => 'Chủ nhà', // owner → Chủ nhà
            default => ucfirst($this->user_type) // Các user_type khác → Uppercase first letter
        };
    }

    /**
     * Method: canBeEditedBy - Kiểm tra user có thể chỉnh sửa reply không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem user có quyền chỉnh sửa reply này không → Chỉ author mới có quyền chỉnh sửa trong vòng 24 giờ
     * 
     * INPUT:
     * - $user: User object
     * 
     * RETURN:
     * - Boolean: true nếu user có quyền chỉnh sửa, false nếu không
     * 
     * LƯU Ý:
     * - Chỉ author (user_id) mới có quyền chỉnh sửa
     * - Chỉ có thể chỉnh sửa trong vòng 24 giờ kể từ khi tạo
     */
    public function canBeEditedBy($user)
    {
        if ($this->user_id === $user->id) { // Nếu user là author của reply
            return $this->created_at->diffInHours(now()) <= 24; // Kiểm tra xem đã quá 24 giờ chưa → Chỉ cho phép chỉnh sửa trong 24 giờ
        }
        return false; // Nếu không phải author → Không có quyền chỉnh sửa
    }

    /**
     * Method: canBeDeletedBy - Kiểm tra user có thể xóa reply không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem user có quyền xóa reply này không → Author của reply hoặc author của review mới có quyền xóa
     * 
     * INPUT:
     * - $user: User object
     * 
     * RETURN:
     * - Boolean: true nếu user có quyền xóa, false nếu không
     * 
     * LƯU Ý:
     * - Author của reply (user_id) có quyền xóa
     * - Author của review (review->tenant_id) cũng có quyền xóa
     */
    public function canBeDeletedBy($user)
    {
        return $this->user_id === $user->id || $this->review->tenant_id === $user->id; // Author của reply hoặc author của review → Cả hai đều có quyền xóa
    }
}
