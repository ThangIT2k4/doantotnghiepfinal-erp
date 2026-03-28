<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: Lead
 * 
 * MỤC ĐÍCH:
 * Model đại diện cho lead (khách hàng tiềm năng) trong hệ thống CRM - lưu trữ thông tin khách hàng tiềm năng, theo dõi viewings, booking deposits, và leases
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Lưu trữ thông tin lead: source, name, phone, email, desired_city, budget, note, status
 * 2. Quan hệ với organization, tenant, viewings, bookingDeposits, leases
 * 3. Kiểm tra lead đã convert thành user account chưa (hasUserAccount, getUserAccount)
 * 4. Kiểm tra lead đã link với tenant chưa (isLinkedToTenant)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng leads: Lưu trữ thông tin leads
 * - Bảng organizations: Quan hệ với organization
 * - Bảng users: Quan hệ với tenant
 * - Bảng viewings: Quan hệ với viewings
 * - Bảng booking_deposits: Quan hệ với booking deposits
 * - Bảng leases: Quan hệ với leases
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng leads: Tạo, cập nhật, xóa leads
 * 
 * LƯU Ý:
 * - Sử dụng SoftDeletes để soft delete (ghi deleted_at và deleted_by)
 * - Sử dụng BelongsToOrganization trait để tự động scope theo organization
 * - Sử dụng HasSoftDeletesWithUser trait để track deleted_by
 * - Budget được cast thành decimal:2 (2 chữ số thập phân)
 * - Lead có thể được convert thành tenant (tenant_id)
 * - Lead có thể có nhiều viewings, bookingDeposits, và leases
 */
class Lead extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, BelongsToOrganization; // Traits: SoftDeletes (soft delete), HasSoftDeletesWithUser (track deleted_by), BelongsToOrganization (auto scope theo organization)

    protected $table = 'leads'; // Tên bảng → Bảng leads trong database

    protected $fillable = [
        'organization_id', // Organization ID → Gán lead vào organization
        'tenant_id', // Tenant ID → Lead đã convert thành tenant (nếu có)
        'source', // Nguồn lead → Nguồn khách hàng tiềm năng
        'name', // Tên khách hàng → Tên lead
        'phone', // Số điện thoại → SĐT lead
        'email', // Email → Email lead
        'desired_city', // Thành phố mong muốn → Thành phố lead muốn thuê
        'budget_min', // Ngân sách tối thiểu → Budget tối thiểu của lead
        'budget_max', // Ngân sách tối đa → Budget tối đa của lead
        'note', // Ghi chú → Ghi chú về lead
        'status', // Trạng thái → Trạng thái lead (new, contacted, qualified, converted, lost)
        'deleted_by', // User ID xóa → Track user đã xóa lead
    ];

    protected $casts = [
        'budget_min' => 'decimal:2', // Cast budget_min thành decimal với 2 chữ số thập phân → Format số tiền
        'budget_max' => 'decimal:2', // Cast budget_max thành decimal với 2 chữ số thập phân → Format số tiền
    ];

    /**
     * Lấy organization của lead
     * 
     * MỤC ĐÍCH:
     * Quan hệ belongsTo với Organization - lead thuộc về một organization
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class); // BelongsTo Organization → Lead thuộc về một organization
    }

    /**
     * Lấy viewings của lead
     * 
     * MỤC ĐÍCH:
     * Quan hệ hasMany với Viewing - lead có thể có nhiều viewings (lịch hẹn xem nhà)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relationship với Viewing
     */
    public function viewings()
    {
        return $this->hasMany(Viewing::class); // HasMany Viewing → Lead có thể có nhiều viewings
    }

    /**
     * Lấy booking deposits của lead
     * 
     * MỤC ĐÍCH:
     * Quan hệ hasMany với BookingDeposit - lead có thể có nhiều booking deposits (đặt cọc)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relationship với BookingDeposit
     */
    public function bookingDeposits()
    {
        return $this->hasMany(BookingDeposit::class); // HasMany BookingDeposit → Lead có thể có nhiều booking deposits
    }

    /**
     * Lấy tenant user của lead
     * 
     * MỤC ĐÍCH:
     * Quan hệ belongsTo với User (tenant_id) - lead có thể được convert thành tenant
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với User (tenant)
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id'); // BelongsTo User (tenant_id) → Lead có thể được convert thành tenant
    }

    /**
     * Lấy leases của lead
     * 
     * MỤC ĐÍCH:
     * Quan hệ hasMany với Lease - lead có thể có nhiều leases (hợp đồng thuê)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relationship với Lease
     */
    public function leases()
    {
        return $this->hasMany(Lease::class); // HasMany Lease → Lead có thể có nhiều leases
    }

    /**
     * Kiểm tra lead đã được convert thành user account chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra lead đã được convert thành tenant chưa (có tenant_id hoặc có lease với tenant_id)
     * 
     * OUTPUT:
     * - bool: true nếu lead đã convert, false nếu chưa
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra lead có tenant_id không
     * 2. Nếu không, kiểm tra lead có lease với tenant_id không
     * 3. Trả về true nếu có một trong hai điều kiện trên
     * 
     * @return bool true nếu lead đã convert thành tenant, false nếu chưa
     */
    public function hasUserAccount()
    {
        return $this->tenant_id !== null || $this->leases()->whereNotNull('tenant_id')->exists(); // Kiểm tra tenant_id hoặc lease có tenant_id → Lead đã convert thành tenant
    }

    /**
     * Lấy user account nếu lead đã được convert
     * 
     * MỤC ĐÍCH:
     * Lấy user account (tenant) nếu lead đã được convert thành tenant
     * 
     * OUTPUT:
     * - User|null: User account nếu đã convert, null nếu chưa
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra lead có tenant_id không (ưu tiên)
     * 2. Nếu không, kiểm tra lead có lease với tenant_id không
     * 3. Trả về tenant user nếu có
     * 
     * @return \App\Models\User|null User account nếu đã convert, null nếu chưa
     */
    public function getUserAccount()
    {
        // First check if lead has direct tenant_id
        if ($this->tenant_id) { // Nếu lead có tenant_id
            return $this->tenant; // Trả về tenant → Lead đã convert trực tiếp
        }
        
        // Fallback to check through leases
        $lease = $this->leases()->whereNotNull('tenant_id')->first(); // Tìm lease có tenant_id → Lead đã convert qua lease
        return $lease ? $lease->tenant : null; // Trả về tenant từ lease nếu có, null nếu không → Lead chưa convert
    }

    /**
     * Kiểm tra lead đã link với tenant user chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra lead đã được link trực tiếp với tenant user chưa (có tenant_id)
     * 
     * OUTPUT:
     * - bool: true nếu lead đã link với tenant, false nếu chưa
     * 
     * LƯU Ý:
     * - Chỉ kiểm tra tenant_id trực tiếp, không kiểm tra qua leases
     * - Khác với hasUserAccount() - method này chỉ kiểm tra tenant_id trực tiếp
     * 
     * @return bool true nếu lead đã link với tenant, false nếu chưa
     */
    public function isLinkedToTenant()
    {
        return $this->tenant_id !== null; // Kiểm tra tenant_id → Lead đã link trực tiếp với tenant
    }
}

