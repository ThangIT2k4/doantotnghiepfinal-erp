<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Viewing
 * 
 * MỤC ĐÍCH:
 * Model đại diện cho viewing (lịch hẹn xem phòng) trong hệ thống - lưu trữ thông tin về lịch hẹn xem phòng, khách hàng (lead hoặc tenant), property, unit, agent, trạng thái (requested, confirmed, done, no_show, cancelled), và các thông tin liên quan
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Relationships: lead, tenant, property, unit, agent, organization, bookingDeposits, deletedBy, documents
 * 2. Scopes: byStatus, byAgent, byProperty, byTenant, upcoming, today
 * 3. Methods: isConfirmed, isCompleted, getStatusBadgeClass, getStatusText, getCustomerNameAttribute, getCustomerTypeAttribute, getCustomerTypeBadgeClass, getCustomerTypeText, leadHasUserAccount, getCustomerTypeIcon, getPhotosAttribute, viewingPhotos
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng viewings: Lưu trữ thông tin viewings
 * - Bảng leads: Relationship với Lead (khách hàng tiềm năng)
 * - Bảng users: Relationship với User (tenant, agent)
 * - Bảng properties: Relationship với Property
 * - Bảng units: Relationship với Unit
 * - Bảng organizations: Relationship với Organization
 * - Bảng booking_deposits: Relationship với BookingDeposit
 * - Bảng documents: Relationship với Document (photos)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng viewings: Tạo, cập nhật, xóa viewings (soft delete)
 * 
 * LƯU Ý:
 * - Sử dụng SoftDeletes để xóa mềm (ghi deleted_by và deleted_at)
 * - Customer có thể là Lead (khách hàng tiềm năng) hoặc Tenant (khách thuê)
 * - Status: requested, confirmed, done, no_show, cancelled
 * - Hỗ trợ virtual viewing (is_virtual, virtual_viewing_link)
 * - Hỗ trợ route optimization (route_optimized, route_data)
 * - Photos được lưu trong documents table (morphMany relationship)
 * - Có backward compatibility với cột photos cũ trong database
 */
class Viewing extends Model
{
    use HasFactory, SoftDeletes; // Trait HasFactory và SoftDeletes → Dùng để tạo factory và soft delete

    protected $table = 'viewings'; // Tên bảng → Bảng viewings trong database

    protected $fillable = [
        'lead_id', // Lead ID → Liên kết với Lead (khách hàng tiềm năng)
        'tenant_id', // Tenant ID → Liên kết với User (khách thuê)
        'property_id', // Property ID → Liên kết với Property
        'agent_id', // Agent ID → Liên kết với User (agent phụ trách)
        'organization_id', // Organization ID → Liên kết với Organization
        'unit_id', // Unit ID → Liên kết với Unit
        'lead_name', // Tên lead → Lưu tên lead (nếu không có lead_id)
        'lead_phone', // SĐT lead → Lưu SĐT lead (nếu không có lead_id)
        'lead_email', // Email lead → Lưu email lead (nếu không có lead_id)
        'schedule_at', // Thời gian hẹn → Lưu thời gian hẹn xem phòng
        'status', // Trạng thái → Lưu trạng thái viewing (requested, confirmed, done, no_show, cancelled)
        'result_note', // Ghi chú kết quả → Lưu ghi chú về kết quả viewing
        'note', // Ghi chú → Lưu ghi chú về viewing
        'checklist', // Checklist → Lưu checklist (JSON array)
        'feedback_notes', // Ghi chú feedback → Lưu ghi chú feedback từ khách hàng
        'feedback_rating', // Đánh giá feedback → Lưu đánh giá feedback từ khách hàng
        'photos', // Photos → Lưu danh sách photos (JSON array, backward compatibility)
        'virtual_viewing_link', // Link virtual viewing → Lưu link virtual viewing
        'is_virtual', // Có phải virtual viewing không → Boolean flag
        'route_optimized', // Route đã được tối ưu chưa → Boolean flag
        'route_data', // Dữ liệu route → Lưu dữ liệu route (JSON array)
        'deleted_by', // User xóa → Lưu user đã xóa viewing
    ];

    protected $casts = [
        'schedule_at' => 'datetime', // schedule_at: cast sang datetime → Dùng để format và query datetime
        'checklist' => 'array', // checklist: cast sang array → Dùng để lưu và lấy checklist
        'photos' => 'array', // photos: cast sang array → Dùng để lưu và lấy photos (backward compatibility)
        'route_data' => 'array', // route_data: cast sang array → Dùng để lưu và lấy route data
        'is_virtual' => 'boolean', // is_virtual: cast sang boolean → Dùng để check virtual viewing
        'route_optimized' => 'boolean', // route_optimized: cast sang boolean → Dùng để check route đã được tối ưu chưa
    ];

    /**
     * Relationship: Lead
     * 
     * MỤC ĐÍCH:
     * Lấy Lead (khách hàng tiềm năng) liên kết với viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với Lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id'); // BelongsTo relationship → Lấy Lead qua lead_id
    }

    /**
     * Relationship: Tenant
     * 
     * MỤC ĐÍCH:
     * Lấy User (khách thuê) liên kết với viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với User (tenant)
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id'); // BelongsTo relationship → Lấy User (tenant) qua tenant_id
    }

    /**
     * Relationship: Property
     * 
     * MỤC ĐÍCH:
     * Lấy Property (bất động sản) liên kết với viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với Property
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id'); // BelongsTo relationship → Lấy Property qua property_id
    }


    /**
     * Relationship: Unit
     * 
     * MỤC ĐÍCH:
     * Lấy Unit (phòng) liên kết với viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với Unit
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id'); // BelongsTo relationship → Lấy Unit qua unit_id
    }

    /**
     * Relationship: Agent
     * 
     * MỤC ĐÍCH:
     * Lấy User (agent) phụ trách viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với User (agent)
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id'); // BelongsTo relationship → Lấy User (agent) qua agent_id
    }

    /**
     * Relationship: Organization
     * 
     * MỤC ĐÍCH:
     * Lấy Organization (tổ chức) mà viewing thuộc về
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class); // BelongsTo relationship → Lấy Organization qua organization_id
    }

    /**
     * Relationship: Booking Deposits
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách BookingDeposit (đặt cọc) liên kết với viewing
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Relationship với BookingDeposit
     */
    public function bookingDeposits()
    {
        return $this->hasMany(BookingDeposit::class, 'viewing_id'); // HasMany relationship → Lấy danh sách BookingDeposit qua viewing_id
    }

    /**
     * Relationship: Deleted By User
     * 
     * MỤC ĐÍCH:
     * Lấy User đã xóa viewing (soft delete)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship với User (deleted_by)
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by'); // BelongsTo relationship → Lấy User (deleted_by) qua deleted_by
    }

    /**
     * Relationship: Documents
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách Document (tài liệu, photos) liên kết với viewing (polymorphic relationship)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany Relationship với Document (polymorphic)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner'); // MorphMany relationship → Lấy danh sách Document qua polymorphic relationship
    }

    /**
     * Relationship: Viewing Photos
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách photos (Document với document_type = 'photo') của viewing, sắp xếp theo sort_order và created_at
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany Relationship với Document (photos only)
     */
    public function viewingPhotos()
    {
        return $this->documents() // Query từ documents relationship → Lấy documents của viewing
            ->where('document_type', 'photo') // Filter: chỉ lấy photos → Chỉ lấy documents có document_type = 'photo'
            ->orderBy('sort_order') // Sort theo sort_order → Sắp xếp photos theo thứ tự
            ->orderBy('created_at'); // Sort theo created_at → Sắp xếp photos theo thời gian tạo
    }

    /**
     * Accessor: Photos (backward compatibility)
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách photos từ cột photos (nếu có) hoặc từ documents table (polymorphic relationship), backward compatibility với cột photos cũ
     * 
     * INPUT:
     * - Model attribute: photos (nếu có trong database)
     * - Database: documents (nếu không có photos attribute)
     * 
     * OUTPUT:
     * - Array: Danh sách file_url của photos
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra xem có photos attribute trong database không
     * 2. Nếu có: Trả về photos attribute (JSON array)
     * 3. Nếu không: Lấy từ documents table (document_type = 'photo'), sắp xếp theo sort_order và created_at, lấy file_url
     * 4. Trả về array file_url
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model attribute: photos (nếu có)
     * - Bảng documents: Lấy photos nếu không có photos attribute
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có
     * 
     * LƯU Ý:
     * - Backward compatibility với cột photos cũ trong database
     * - Ưu tiên photos attribute, fallback sang documents table
     * - Trả về array file_url để hiển thị photos
     * 
     * @return array Danh sách file_url của photos
     */
    public function getPhotosAttribute()
    {
        // Nếu cột photos vẫn còn trong database, trả về nó
        if (isset($this->attributes['photos'])) { // Kiểm tra có photos attribute không → Backward compatibility
            return $this->attributes['photos']; // Trả về photos attribute → Dùng cột photos cũ nếu có
        }

        // Lấy từ documents trực tiếp
        return $this->documents() // Query từ documents relationship → Lấy documents của viewing
            ->where('document_type', 'photo') // Filter: chỉ lấy photos → Chỉ lấy documents có document_type = 'photo'
            ->orderBy('sort_order') // Sort theo sort_order → Sắp xếp photos theo thứ tự
            ->orderBy('created_at') // Sort theo created_at → Sắp xếp photos theo thời gian tạo
            ->get() // Lấy tất cả kết quả → Dùng để lấy file_url
            ->pluck('file_url') // Lấy file_url từ mỗi document → Tạo collection file_url
            ->toArray(); // Convert sang array → Trả về array file_url
    }

    /**
     * Scope: Filter theo status
     * 
     * MỤC ĐÍCH:
     * Filter viewings theo status (requested, confirmed, done, no_show, cancelled)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $status Status code
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status); // Filter theo status → Chỉ lấy viewings có status này
    }

    /**
     * Scope: Filter theo agent
     * 
     * MỤC ĐÍCH:
     * Filter viewings theo agent_id (agent phụ trách)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param int $agentId Agent ID
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter agent_id
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId); // Filter theo agent_id → Chỉ lấy viewings của agent này
    }

    /**
     * Scope: Filter theo property
     * 
     * MỤC ĐÍCH:
     * Filter viewings theo property_id (bất động sản)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param int $propertyId Property ID
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter property_id
     */
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId); // Filter theo property_id → Chỉ lấy viewings của property này
    }

    /**
     * Scope: Filter theo tenant
     * 
     * MỤC ĐÍCH:
     * Filter viewings theo tenant_id (khách thuê)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param int $tenantId Tenant ID
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter tenant_id
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId); // Filter theo tenant_id → Chỉ lấy viewings của tenant này
    }

    /**
     * Scope: Upcoming viewings
     * 
     * MỤC ĐÍCH:
     * Filter viewings sắp tới (schedule_at >= now() và status = 'requested' hoặc 'confirmed')
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter upcoming
     */
    public function scopeUpcoming($query)
    {
        return $query->where('schedule_at', '>=', now()) // Filter: schedule_at >= now() → Chỉ lấy viewings sắp tới
                    ->whereIn('status', ['requested', 'confirmed']); // Filter: status = 'requested' hoặc 'confirmed' → Chỉ lấy viewings chưa hoàn thành
    }

    /**
     * Scope: Today's viewings
     * 
     * MỤC ĐÍCH:
     * Filter viewings hôm nay (schedule_at = today)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder Query builder với filter today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('schedule_at', today()); // Filter: schedule_at = hôm nay → Chỉ lấy viewings hôm nay
    }

    /**
     * Kiểm tra viewing đã được xác nhận chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem viewing có status = 'confirmed' không
     * 
     * @return bool True nếu status = 'confirmed', false nếu không
     */
    public function isConfirmed()
    {
        return $this->status === 'confirmed'; // Kiểm tra status = 'confirmed' → Trả về true nếu đã xác nhận
    }

    /**
     * Kiểm tra viewing đã hoàn thành chưa
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem viewing có status = 'done' hoặc 'no_show' không (đã hoàn thành)
     * 
     * @return bool True nếu status = 'done' hoặc 'no_show', false nếu không
     */
    public function isCompleted()
    {
        return in_array($this->status, ['done', 'no_show']); // Kiểm tra status = 'done' hoặc 'no_show' → Trả về true nếu đã hoàn thành
    }

    /**
     * Lấy badge class cho status
     * 
     * MỤC ĐÍCH:
     * Lấy CSS class cho badge hiển thị status (dùng trong view)
     * 
     * @return string Badge class (badge-warning, badge-info, badge-success, badge-danger, badge-secondary, badge-light)
     */
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'requested' => 'badge-warning', // requested → Badge màu warning (vàng)
            'confirmed' => 'badge-info', // confirmed → Badge màu info (xanh dương)
            'done' => 'badge-success', // done → Badge màu success (xanh lá)
            'no_show' => 'badge-danger', // no_show → Badge màu danger (đỏ)
            'cancelled' => 'badge-secondary', // cancelled → Badge màu secondary (xám)
            default => 'badge-light' // Mặc định → Badge màu light (trắng)
        }; // Trả về badge class tương ứng → Dùng để hiển thị trong view
    }

    /**
     * Lấy text status bằng tiếng Việt
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi status code sang text tiếng Việt để hiển thị cho user
     * 
     * @return string Text status tiếng Việt
     */
    public function getStatusText()
    {
        return match($this->status) {
            'requested' => 'Chờ xác nhận', // requested → Text tiếng Việt
            'confirmed' => 'Đã xác nhận', // confirmed → Text tiếng Việt
            'done' => 'Hoàn thành', // done → Text tiếng Việt
            'no_show' => 'Không đến', // no_show → Text tiếng Việt
            'cancelled' => 'Đã hủy', // cancelled → Text tiếng Việt
            default => 'Không xác định' // Mặc định → Text tiếng Việt
        }; // Trả về text status tương ứng → Dùng để hiển thị trong view
    }

    /**
     * Accessor: Customer Name
     * 
     * MỤC ĐÍCH:
     * Lấy tên khách hàng, ưu tiên tenant (nếu có), fallback sang lead_name
     * 
     * @return string Tên khách hàng (tenant full_name hoặc lead_name)
     */
    public function getCustomerNameAttribute()
    {
        if ($this->tenant) { // Nếu có tenant
            return $this->tenant->full_name; // Trả về full_name của tenant → Ưu tiên tenant
        }
        return $this->lead_name; // Trả về lead_name → Fallback sang lead_name nếu không có tenant
    }

    /**
     * Accessor: Customer Type
     * 
     * MỤC ĐÍCH:
     * Xác định loại khách hàng (tenant hoặc lead) dựa trên tenant_id
     * 
     * @return string Customer type ('tenant' hoặc 'lead')
     */
    public function getCustomerTypeAttribute()
    {
        return $this->tenant_id ? 'tenant' : 'lead'; // Trả về 'tenant' nếu có tenant_id, 'lead' nếu không → Xác định loại khách hàng
    }

    /**
     * Lấy badge class cho customer type
     * 
     * MỤC ĐÍCH:
     * Lấy CSS class cho badge hiển thị customer type (dùng trong view)
     * 
     * @return string Badge class (badge-info cho tenant, badge-warning cho lead)
     */
    public function getCustomerTypeBadgeClass()
    {
        return $this->customer_type === 'tenant' ? 'badge-info' : 'badge-warning'; // Trả về badge class tương ứng → Dùng để hiển thị trong view
    }

    /**
     * Lấy text customer type bằng tiếng Việt
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi customer type sang text tiếng Việt để hiển thị cho user, kiểm tra lead có user account không
     * 
     * @return string Text customer type tiếng Việt
     */
    public function getCustomerTypeText()
    {
        if ($this->customer_type === 'tenant') { // Nếu customer_type = 'tenant'
            return 'Khách thuê'; // Trả về text tiếng Việt → Hiển thị cho user
        }
        
        // Check if lead has user account
        if ($this->leadHasUserAccount()) { // Nếu lead có user account
            return 'Lead (có tài khoản)'; // Trả về text tiếng Việt → Hiển thị lead có tài khoản
        }
        
        return 'Lead'; // Trả về text tiếng Việt → Hiển thị lead thông thường
    }

    /**
     * Kiểm tra lead có user account không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem lead có user account tương ứng không (dựa trên email)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra có lead_id không
     * 2. Lấy Lead từ database
     * 3. Kiểm tra Lead có email không
     * 4. Tìm User có email trùng với Lead email
     * 5. Trả về true nếu tìm thấy, false nếu không
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng leads: Lấy Lead theo lead_id
     * - Bảng users: Tìm User có email trùng với Lead email
     * 
     * @return bool True nếu lead có user account, false nếu không
     */
    public function leadHasUserAccount(): bool
    {
        if (!$this->lead_id) { // Nếu không có lead_id
            return false; // Trả về false → Không có lead thì không có user account
        }

        // Check if there's a user with the same email as the lead
        $lead = \App\Models\Lead::find($this->lead_id); // Query từ bảng leads → Lấy Lead theo lead_id
        if (!$lead || !$lead->email) { // Nếu không tìm thấy Lead hoặc Lead không có email
            return false; // Trả về false → Không có email thì không thể kiểm tra user account
        }

        return \App\Models\User::where('email', $lead->email) // Query từ bảng users → Tìm User có email trùng với Lead email
            ->whereNull('deleted_at') // Chỉ lấy users chưa bị xóa → Exclude soft-deleted users
            ->exists(); // Kiểm tra có tồn tại không → Trả về true nếu tìm thấy User
    }

    /**
     * Lấy icon cho customer type
     * 
     * MỤC ĐÍCH:
     * Lấy FontAwesome icon class cho customer type (dùng trong view)
     * 
     * @return string Icon class (fa-user cho tenant hoặc lead có user account, fa-user-plus cho lead không có user account)
     */
    public function getCustomerTypeIcon()
    {
        if ($this->customer_type === 'tenant') { // Nếu customer_type = 'tenant'
            return 'fa-user'; // Trả về icon → Hiển thị icon user cho tenant
        }
        
        // If lead has user account, show user icon, otherwise show user-plus icon
        if ($this->leadHasUserAccount()) { // Nếu lead có user account
            return 'fa-user'; // Trả về icon → Hiển thị icon user cho lead có tài khoản
        }
        
        return 'fa-user-plus'; // Trả về icon → Hiển thị icon user-plus cho lead không có tài khoản
    }

    /**
     * Accessor: Status Badge Class
     * 
     * MỤC ĐÍCH:
     * Lấy badge class cho status (accessor để dùng trong view như $viewing->status_badge_class)
     * 
     * @return string Badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->getStatusBadgeClass(); // Gọi method getStatusBadgeClass() → Trả về badge class
    }

    /**
     * Accessor: Status Text
     * 
     * MỤC ĐÍCH:
     * Lấy text status tiếng Việt (accessor để dùng trong view như $viewing->status_text)
     * 
     * @return string Text status tiếng Việt
     */
    public function getStatusTextAttribute()
    {
        return $this->getStatusText(); // Gọi method getStatusText() → Trả về text status
    }
}

