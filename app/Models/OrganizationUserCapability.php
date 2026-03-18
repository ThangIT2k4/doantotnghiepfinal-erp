<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: OrganizationUserCapability
 * 
 * MỤC ĐÍCH:
 * Model quản lý capability overrides của user trong organization - lưu trữ các quyền được cấp hoặc từ chối cho user (ghi đè role default)
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Relationships: organizationUser, capability, grantedByUser
 * 2. Scope: active (lấy chỉ các overrides đang active)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng organization_user_capabilities: Thông tin capability overrides
 * - Bảng organization_users: Thông tin OrganizationUser qua relationship
 * - Bảng capabilities: Thông tin Capability qua relationship
 * - Bảng users: Thông tin User (granted_by) qua relationship
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng organization_user_capabilities: Tạo, cập nhật, xóa capability overrides
 * 
 * LƯU Ý:
 * - granted = true: User có quyền qua override (ghi đè role default)
 * - granted = false hoặc revoked_at != null: User bị từ chối quyền (ghi đè role default)
 * - Override có priority cao hơn role default
 * - Một OrganizationUser có thể có nhiều capability overrides
 */
class OrganizationUserCapability extends Model
{
    protected $table = 'organization_user_capabilities'; // Tên bảng → organization_user_capabilities

    protected $fillable = [
        'organization_user_id', // OrganizationUser ID → Liên kết với OrganizationUser
        'capability_id', // Capability ID → Liên kết với Capability
        'granted', // Granted → true = cấp quyền, false = từ chối quyền
        'granted_by', // User ID đang cấp quyền → Track ai cấp
        'granted_at', // Thời gian cấp quyền → Track khi nào cấp
        'revoked_at', // Thời gian thu hồi quyền → Track khi nào thu hồi
    ];

    protected $casts = [
        'granted' => 'boolean', // Cast granted sang boolean → Dùng để kiểm tra true/false
        'granted_at' => 'datetime', // Cast granted_at sang datetime → Dùng để format ngày giờ
        'revoked_at' => 'datetime', // Cast revoked_at sang datetime → Dùng để format ngày giờ
    ];

    /**
     * Relationship: OrganizationUser
     * 
     * MỤC ĐÍCH:
     * Lấy OrganizationUser mà override này thuộc về
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo OrganizationUser
     */
    public function organizationUser()
    {
        return $this->belongsTo(OrganizationUser::class); // BelongsTo relationship → Một OrganizationUserCapability thuộc về một OrganizationUser
    }

    /**
     * Relationship: Capability
     * 
     * MỤC ĐÍCH:
     * Lấy Capability mà override này áp dụng cho
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo Capability
     */
    public function capability()
    {
        return $this->belongsTo(Capability::class); // BelongsTo relationship → Một OrganizationUserCapability thuộc về một Capability
    }

    /**
     * Relationship: Granted By User
     * 
     * MỤC ĐÍCH:
     * Lấy User đã cấp quyền này (granted_by)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo User
     */
    public function grantedByUser()
    {
        return $this->belongsTo(User::class, 'granted_by'); // BelongsTo relationship → Một OrganizationUserCapability được cấp bởi một User (qua granted_by)
    }

    /**
     * Scope: Active Overrides
     * 
     * MỤC ĐÍCH:
     * Lấy chỉ các overrides đang active (granted = true và chưa revoked)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter active
     */
    public function scopeActive($query)
    {
        return $query->where('granted', true) // Chỉ lấy granted = true → Users có quyền qua override
            ->whereNull('revoked_at'); // Chỉ lấy chưa bị revoked → Quyền vẫn còn hiệu lực
    }
}

