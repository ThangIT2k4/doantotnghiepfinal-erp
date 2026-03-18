<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;

/**
 * Model: OrganizationUser
 * 
 * MỤC ĐÍCH:
 * Model quản lý mối quan hệ giữa User và Organization - lưu trữ role của user trong organization và quản lý capabilities
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Relationships: organization, user, role, capabilityOverrides, activeCapabilityOverrides, capabilities
 * 2. Soft Deletes: Sử dụng HasSoftDeletesWithUser trait để soft delete với tracking deleted_by
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng organization_users: Thông tin mối quan hệ User-Organization
 * - Bảng organizations: Thông tin organization qua relationship
 * - Bảng users: Thông tin user qua relationship
 * - Bảng roles: Thông tin role qua relationship
 * - Bảng organization_user_capabilities: Capability overrides qua relationship
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng organization_users: Tạo, cập nhật, xóa (soft delete) mối quan hệ User-Organization
 * 
 * LƯU Ý:
 * - Một user có thể thuộc nhiều organizations với roles khác nhau
 * - Sử dụng soft delete để lưu trữ lịch sử
 * - Capabilities được quản lý qua organization_user_capabilities table
 */
class OrganizationUser extends Model
{
    use HasSoftDeletesWithUser; // Trait hỗ trợ soft delete với tracking deleted_by → Lưu trữ lịch sử xóa

    protected $table = 'organization_users'; // Tên bảng → organization_users

    protected $fillable = [
        'organization_id', // Organization ID → Liên kết với Organization
        'user_id', // User ID → Liên kết với User
        'role_id', // Role ID → Liên kết với Role
        'status', // Status → active/inactive
        'deleted_by', // User ID đã xóa → Track ai xóa (từ HasSoftDeletesWithUser trait)
    ];

    /**
     * Relationship: Organization
     * 
     * MỤC ĐÍCH:
     * Lấy organization mà user thuộc về
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class); // BelongsTo relationship → Một OrganizationUser thuộc về một Organization
    }

    /**
     * Relationship: User
     * 
     * MỤC ĐÍCH:
     * Lấy user của OrganizationUser này
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo User
     */
    public function user()
    {
        return $this->belongsTo(User::class); // BelongsTo relationship → Một OrganizationUser thuộc về một User
    }

    /**
     * Relationship: Role
     * 
     * MỤC ĐÍCH:
     * Lấy role của user trong organization này
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo BelongsTo Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class); // BelongsTo relationship → Một OrganizationUser có một Role
    }

    /**
     * Relationship: Capability Overrides
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả capability overrides của user trong organization này (bao gồm cả granted và denied)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany HasMany OrganizationUserCapability
     */
    public function capabilityOverrides()
    {
        return $this->hasMany(OrganizationUserCapability::class, 'organization_user_id'); // HasMany relationship → Một OrganizationUser có nhiều Capability Overrides
    }

    /**
     * Relationship: Active Capability Overrides
     * 
     * MỤC ĐÍCH:
     * Lấy chỉ các capability overrides đang active (granted = true và chưa revoked)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany HasMany OrganizationUserCapability (filtered)
     */
    public function activeCapabilityOverrides()
    {
        return $this->hasMany(OrganizationUserCapability::class, 'organization_user_id') // HasMany relationship → Một OrganizationUser có nhiều Capability Overrides
            ->where('granted', true) // Chỉ lấy granted = true → Users có quyền qua override
            ->whereNull('revoked_at'); // Chỉ lấy chưa bị revoked → Quyền vẫn còn hiệu lực
    }

    /**
     * Relationship: Capabilities (Many-to-Many)
     * 
     * MỤC ĐÍCH:
     * Lấy capabilities của user trong organization này qua pivot table organization_user_capabilities
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany BelongsToMany Capability
     */
    public function capabilities()
    {
        return $this->belongsToMany(
            Capability::class, // Model liên quan → Capability
            'organization_user_capabilities', // Tên pivot table → organization_user_capabilities
            'organization_user_id', // Foreign key của OrganizationUser trong pivot table
            'capability_id' // Foreign key của Capability trong pivot table
        )
        ->withPivot(['granted', 'granted_by', 'granted_at', 'revoked_at']) // Lấy các pivot columns → Dùng để kiểm tra override
        ->withTimestamps(); // Lấy created_at và updated_at từ pivot table → Track thay đổi
    }
}

