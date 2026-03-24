<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;

/**
 * Model: Property
 * 
 * MỤC ĐÍCH:
 * Model đại diện cho bất động sản (property) trong hệ thống. Mỗi property thuộc một organization, có thể có property type, 
 * location (hỗ trợ cả old system và new system 2025), payment cycle, lease service set, và được gán cho staff (managers và agents).
 * Property có nhiều units, master leases, và documents (hình ảnh).
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Property được tạo với organization_id, property_type_id, location (old và/hoặc new system 2025)
 * 2. Property có thể có payment_cycle_id riêng hoặc dùng default của organization
 * 3. Property có thể có lease_services_id riêng hoặc dùng default của organization
 * 4. Property có thể được gán cho staff (managers và agents) qua pivot table properties_user
 * 5. Property có nhiều units, mỗi unit có thể có leases
 * 6. Property có master leases để quản lý chủ nhà (landlord)
 * 7. Property có documents (hình ảnh) qua polymorphic relationship
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng properties: Thông tin chính của property
 * - Bảng property_types: Loại bất động sản
 * - Bảng locations, locations_2025: Địa chỉ (old và new system)
 * - Bảng payment_cycles: Chu kỳ thanh toán
 * - Bảng lease_service_sets: Bộ dịch vụ hợp đồng
 * - Bảng properties_user: Pivot table cho assigned staff
 * - Bảng units: Phòng thuộc property
 * - Bảng master_leases: Hợp đồng chính với chủ nhà
 * - Bảng documents: Hình ảnh/tài liệu (polymorphic)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng properties: Tạo/cập nhật/xóa (soft delete) property
 * - Bảng properties_user: Gán/hủy gán staff cho property
 * 
 * LƯU Ý:
 * - Sử dụng soft deletes (có thể khôi phục)
 * - Hỗ trợ cả old location system (Location) và new system 2025 (Location2025) - có thể có cả 2
 * - Owner (chủ nhà) được quản lý qua master_leases, không còn owner_id trực tiếp
 * - Images được lưu trong documents table (polymorphic), không còn cột images
 * - Payment cycle và lease service set có thể fallback về default của organization
 * - Occupancy rate được tính dựa trên số units có active leases
 */
class Property extends Model
{
    use HasSoftDeletesWithUser, BelongsToOrganization; // Sử dụng traits → Soft delete với user tracking và auto scope theo organization
    protected $table = 'properties'; // Tên bảng trong database

    protected $fillable = [ // Các trường có thể mass assign → Bảo mật: chỉ cho phép assign các trường này
        'organization_id', // ID của organization sở hữu property
        // 'owner_id', // Removed - now managed through master_leases → Chủ nhà được quản lý qua master_leases
        'property_type_id', // ID của loại bất động sản
        'name', // Tên property
        'location_id', // ID của location (old system)
        'location_id_2025', // ID của location2025 (new system 2025)
        'description', // Mô tả property
        // 'images', // Removed - now using documents table → Hình ảnh được lưu trong documents table (polymorphic)
        'total_floors', // Tổng số tầng
        'status', // Trạng thái (1 = active, 0 = inactive)
        'payment_cycle_id', // ID của payment cycle (có thể null, dùng default của organization)
        'lease_services_id', // ID của lease service set (có thể null, dùng default của organization)
        'deleted_by', // ID của user xóa property
    ];

    protected $casts = [ // Cast các trường khi lấy từ database → Tự động convert kiểu dữ liệu
        'status' => 'integer', // Cast thành integer
        'total_floors' => 'integer', // Cast thành integer
        // 'images' => 'array', // Removed - now using documents table → Hình ảnh được lưu trong documents table
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Relationship: paymentCycle - Chu kỳ thanh toán của property
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin payment cycle được gán cho property → Dùng để tính toán hóa đơn và thanh toán
     * 
     * RETURN:
     * - BelongsTo PaymentCycle (payment_cycle_id)
     */
    public function paymentCycle()
    {
        return $this->belongsTo(PaymentCycle::class, 'payment_cycle_id'); // BelongsTo relationship → Một property có thể có một payment cycle riêng
    }

    /**
     * Method: getEffectivePaymentCycle - Lấy payment cycle hiệu quả (property cycle hoặc default của organization)
     * 
     * MỤC ĐÍCH:
     * Lấy payment cycle được sử dụng cho property → Ưu tiên property cycle, nếu không có thì dùng default của organization
     * 
     * RETURN:
     * - PaymentCycle object hoặc null
     * 
     * LƯU Ý:
     * - Priority: Property Cycle > Organization Default Cycle
     */
    public function getEffectivePaymentCycle()
    {
        if ($this->payment_cycle_id && $this->paymentCycle) { // Nếu property có payment cycle riêng
            return $this->paymentCycle; // Trả về payment cycle của property → Ưu tiên property cycle
        }
        
        if ($this->organization_id) { // Nếu có organization ID
            return PaymentCycle::where('organization_id', $this->organization_id) // Tìm default payment cycle của organization → Fallback về default
                ->where('is_default', true)
                ->first();
        }
        
        return null; // Không có payment cycle → Trả về null
    }

    /**
     * Method: getEffectivePaymentDueHours - Lấy số giờ đến hạn thanh toán
     * 
     * MỤC ĐÍCH:
     * Lấy số giờ đến hạn thanh toán từ effective payment cycle → Dùng để tính toán thời hạn thanh toán
     * 
     * RETURN:
     * - Integer: Số giờ (mặc định: 4320 = 72 giờ)
     * 
     * LƯU Ý:
     * - Priority: Property Cycle > Organization Default Cycle
     */
    public function getEffectivePaymentDueHours()
    {
        $cycle = $this->getEffectivePaymentCycle(); // Lấy effective payment cycle → Dùng để lấy payment_due_hours
        return $cycle?->payment_due_hours ?? 4320; // Trả về payment_due_hours hoặc mặc định 4320 giờ (72 giờ)
    }

    /**
     * Method: getEffectiveInvoiceTiming - Lấy thời điểm tạo hóa đơn
     * 
     * MỤC ĐÍCH:
     * Lấy thời điểm tạo hóa đơn từ effective payment cycle → Dùng để xác định khi nào tạo hóa đơn
     * 
     * RETURN:
     * - String: 'start_of_cycle' hoặc 'end_of_cycle' (mặc định: 'end_of_cycle')
     * 
     * LƯU Ý:
     * - Priority: Property Cycle > Organization Default Cycle
     */
    public function getEffectiveInvoiceTiming()
    {
        $cycle = $this->getEffectivePaymentCycle(); // Lấy effective payment cycle → Dùng để lấy invoice_timing
        return $cycle?->invoice_timing ?? 'end_of_cycle'; // Trả về invoice_timing hoặc mặc định 'end_of_cycle'
    }

    /**
     * Method: getEffectiveInvoicePaymentDays - Lấy số ngày thanh toán hóa đơn
     * 
     * MỤC ĐÍCH:
     * Lấy số ngày thanh toán hóa đơn từ effective payment cycle → Dùng để tính toán thời hạn thanh toán
     * 
     * RETURN:
     * - Integer: Số ngày (mặc định: 30)
     * 
     * LƯU Ý:
     * - Priority: Property Cycle > Organization Default Cycle
     */
    public function getEffectiveInvoicePaymentDays()
    {
        $cycle = $this->getEffectivePaymentCycle(); // Lấy effective payment cycle → Dùng để lấy invoice_payment_days
        return $cycle?->invoice_payment_days ?? 30; // Trả về invoice_payment_days hoặc mặc định 30 ngày
    }

    /**
     * Relationship: leaseServiceSet - Bộ dịch vụ hợp đồng của property
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin lease service set được gán cho property → Dùng để quản lý dịch vụ trong hợp đồng
     * 
     * RETURN:
     * - BelongsTo LeaseServiceSet (lease_services_id)
     */
    public function leaseServiceSet()
    {
        return $this->belongsTo(LeaseServiceSet::class, 'lease_services_id'); // BelongsTo relationship → Một property có thể có một lease service set riêng
    }

    /**
     * Method: getEffectiveLeaseServiceSet - Lấy lease service set hiệu quả (property set hoặc default của organization)
     * 
     * MỤC ĐÍCH:
     * Lấy lease service set được sử dụng cho property → Ưu tiên property set, nếu không có thì dùng default của organization
     * 
     * RETURN:
     * - LeaseServiceSet object với items.service đã load hoặc null
     * 
     * LƯU Ý:
     * - Priority: Property Set > Organization Default Set
     * - Tự động load items.service để tránh N+1 queries
     */
    public function getEffectiveLeaseServiceSet()
    {
        if ($this->lease_services_id && $this->leaseServiceSet) { // Nếu property có lease service set riêng
            if (!$this->leaseServiceSet->relationLoaded('items')) { // Nếu chưa load items
                $this->leaseServiceSet->load('items.service'); // Load items với service → Tránh N+1 queries
            }
            return $this->leaseServiceSet; // Trả về lease service set của property → Ưu tiên property set
        }
        
        if ($this->organization_id) { // Nếu có organization ID
            $defaultSet = LeaseServiceSet::where('organization_id', $this->organization_id) // Tìm default lease service set của organization → Fallback về default
                ->where('is_default', true)
                ->with('items.service') // Eager load items với service → Tránh N+1 queries
                ->first();
            return $defaultSet;
        }
        
        return null; // Không có lease service set → Trả về null
    }

    /**
     * Method: getEffectiveLeaseServices - Alias của getEffectiveLeaseServiceSet (backward compatibility)
     * 
     * MỤC ĐÍCH:
     * Alias method để tương thích với code cũ → Dùng để lấy lease services
     * 
     * RETURN:
     * - LeaseServiceSet object hoặc null
     */
    public function getEffectiveLeaseServices()
    {
        return $this->getEffectiveLeaseServiceSet(); // Gọi getEffectiveLeaseServiceSet → Backward compatibility
    }

    /**
     * Relationship: organization - Tổ chức sở hữu property
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin organization sở hữu property → Dùng để filter và hiển thị
     * 
     * RETURN:
     * - BelongsTo Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class); // BelongsTo relationship → Một property thuộc một organization
    }

    // Owner relationship removed - now get from master_leases → Chủ nhà được quản lý qua master_leases

    /**
     * Method: getCurrentLandlord - Lấy chủ nhà hiện tại từ master lease active
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin chủ nhà hiện tại từ master lease đang active → Dùng để hiển thị thông tin chủ nhà
     * 
     * RETURN:
     * - User object (landlord) hoặc null
     * 
     * LƯU Ý:
     * - Chỉ lấy từ master lease có status = 'active' và chưa bị xóa
     */
    public function getCurrentLandlord()
    {
        return $this->masterLeases() // Lấy master leases relationship
            ->where('status', 'active') // Chỉ lấy master lease đang active → Chỉ lấy hợp đồng hiện tại
            ->whereNull('deleted_at') // Chỉ lấy chưa bị xóa
            ->with('landlord') // Eager load landlord → Tránh N+1 queries
            ->first()?->landlord; // Lấy landlord từ master lease đầu tiên → Trả về chủ nhà hiện tại
    }

    /**
     * Relationship: masterLeases - Tất cả master leases của property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách master leases của property → Dùng để quản lý hợp đồng với chủ nhà
     * 
     * RETURN:
     * - HasMany MasterLease
     */
    public function masterLeases()
    {
        return $this->hasMany(MasterLease::class); // HasMany relationship → Một property có nhiều master leases
    }

    /**
     * Relationship: propertyType - Loại bất động sản
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin loại bất động sản → Dùng để hiển thị và filter
     * 
     * RETURN:
     * - BelongsTo PropertyType
     */
    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class); // BelongsTo relationship → Một property thuộc một loại BĐS
    }

    /**
     * Relationship: location - Địa chỉ (old system)
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin địa chỉ (old system) → Dùng để hiển thị và filter
     * 
     * RETURN:
     * - BelongsTo Location
     */
    public function location()
    {
        return $this->belongsTo(Location::class); // BelongsTo relationship → Một property có một location (old system)
    }

    /**
     * Relationship: location2025 - Địa chỉ (new system 2025)
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin địa chỉ (new system 2025) → Dùng để hiển thị và filter
     * 
     * RETURN:
     * - BelongsTo Location2025 (location_id_2025)
     */
    public function location2025()
    {
        return $this->belongsTo(Location2025::class, 'location_id_2025'); // BelongsTo relationship với foreign key location_id_2025 → Một property có một location2025 (new system)
    }

    /**
     * Relationship: units - Phòng thuộc property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách phòng thuộc property → Dùng để hiển thị và quản lý units
     * 
     * RETURN:
     * - HasMany Unit
     */
    public function units()
    {
        return $this->hasMany(Unit::class); // HasMany relationship → Một property có nhiều units
    }

    /**
     * Relationship: documents - Documents của property (polymorphic)
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách documents (hình ảnh, tài liệu) của property → Dùng để hiển thị hình ảnh và tài liệu
     * 
     * RETURN:
     * - MorphMany Document
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner'); // Polymorphic relationship → Một property có nhiều documents (hình ảnh, tài liệu)
    }

    /**
     * Relationship: propertyImages - Hình ảnh của property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách hình ảnh của property (chỉ document_type = 'image') → Dùng để hiển thị hình ảnh
     * 
     * RETURN:
     * - Query builder với filter document_type = 'image' và sort theo sort_order, created_at
     */
    public function propertyImages()
    {
        return $this->documents() // Lấy documents relationship
            ->where('document_type', 'image') // Chỉ lấy hình ảnh → Filter document_type = 'image'
            ->orderBy('sort_order') // Sắp xếp theo sort_order → Hiển thị theo thứ tự
            ->orderBy('created_at'); // Sắp xếp theo created_at → Hiển thị theo thời gian tạo
    }

    /**
     * Accessor: images - Mảng URL hình ảnh (backward compatibility)
     * 
     * MỤC ĐÍCH:
     * Lấy mảng URL hình ảnh từ documents → Dùng để hiển thị hình ảnh (backward compatibility)
     * 
     * RETURN:
     * - Array: Mảng URL hình ảnh (đã format đúng)
     */
    public function getImagesAttribute()
    {
        if (isset($this->attributes['images'])) { // Nếu cột images vẫn còn trong database (backward compatibility)
            return $this->attributes['images']; // Trả về giá trị cũ → Backward compatibility
        }

        return $this->documents() // Lấy documents relationship
            ->where('document_type', 'image') // Chỉ lấy hình ảnh
            ->orderBy('sort_order') // Sắp xếp theo sort_order
            ->orderBy('created_at') // Sắp xếp theo created_at
            ->get() // Lấy tất cả kết quả
            ->map(function($document) { // Map mỗi document thành URL → Format URL cho đúng
                $fileUrl = $document->getRawOriginal('file_url') ?? $document->file_url; // Lấy file_url gốc từ database → Đảm bảo lấy đúng path
                
                if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) { // Nếu đã là full URL
                    return $fileUrl; // Trả về URL đầy đủ → Không cần format thêm
                }
                
                return $fileUrl; // Trả về path như đã lưu → ImageService đã trả về path đúng format
            })
            ->toArray(); // Convert thành array → Dùng để hiển thị
    }

    /**
     * Relationship: assignedUsers - Staff được gán cho property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách staff (managers và agents) được gán cho property → Dùng để hiển thị và quản lý assigned staff
     * 
     * RETURN:
     * - BelongsToMany User (pivot table: properties_user)
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'properties_user', 'property_id', 'user_id') // BelongsToMany relationship → Một property có nhiều assigned users
            ->withPivot('role_key', 'assigned_at', 'updated_by', 'deleted_by') // Lấy thêm các trường từ pivot table → Dùng để hiển thị role và thời gian gán
            ->withTimestamps() // Tự động quản lý created_at và updated_at
            ->whereNull('properties_user.deleted_at'); // Chỉ lấy assigned users chưa bị xóa → Loại bỏ soft deleted records
    }

    /**
     * Method: getPrimaryManager - Lấy manager chính được gán cho property
     * 
     * MỤC ĐÍCH:
     * Lấy manager đầu tiên được gán cho property → Dùng để hiển thị manager chính
     * 
     * RETURN:
     * - User object (manager) hoặc null
     */
    public function getPrimaryManager()
    {
        return $this->assignedUsers() // Lấy assigned users relationship
            ->wherePivot('role_key', 'manager') // Chỉ lấy users có role_key = 'manager' → Filter managers
            ->first(); // Lấy manager đầu tiên → Manager chính
    }

    /**
     * Method: getManagers - Lấy tất cả managers được gán cho property
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tất cả managers được gán cho property → Dùng để hiển thị danh sách managers
     * 
     * RETURN:
     * - Collection: Danh sách User objects (managers)
     */
    public function getManagers()
    {
        return $this->assignedUsers() // Lấy assigned users relationship
            ->wherePivot('role_key', 'manager') // Chỉ lấy users có role_key = 'manager' → Filter managers
            ->get(); // Lấy tất cả managers → Danh sách managers
    }

    /**
     * Scope: active - Chỉ lấy properties đang hoạt động
     * 
     * MỤC ĐÍCH:
     * Filter chỉ lấy properties có status = 1 (active) → Dùng để hiển thị properties đang hoạt động
     * 
     * INPUT:
     * - $query: Query builder
     * 
     * RETURN:
     * - Query builder với điều kiện status = 1
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1); // Filter chỉ lấy properties đang hoạt động → Dùng để filter active properties
    }

    /**
     * Scope: withType - Lấy properties theo loại BĐS
     * 
     * MỤC ĐÍCH:
     * Filter chỉ lấy properties có property_type_id = $typeId → Dùng để filter theo loại BĐS
     * 
     * INPUT:
     * - $query: Query builder
     * - $typeId: ID của property type
     * 
     * RETURN:
     * - Query builder với điều kiện property_type_id = $typeId
     */
    public function scopeWithType($query, $typeId)
    {
        return $query->where('property_type_id', $typeId); // Filter chỉ lấy properties thuộc loại này → Dùng để filter theo loại BĐS
    }

    /**
     * Method: getOccupiedUnitsCount - Đếm số phòng đã được thuê
     * 
     * MỤC ĐÍCH:
     * Đếm số phòng có active leases → Dùng để tính occupancy rate và available units
     * 
     * RETURN:
     * - Integer: Số phòng đã được thuê
     * 
     * LƯU Ý:
     * - Đếm dựa trên active leases, không phải units.status
     */
    public function getOccupiedUnitsCount()
    {
        return $this->units()->whereHas('leases', function($query) { // Tìm units có leases → Đếm units đã được thuê
            $query->where('status', 'active') // Chỉ lấy leases đang active → Chỉ tính leases đang hoạt động
                  ->whereNull('deleted_at'); // Chỉ lấy leases chưa bị xóa
        })->count(); // Đếm số units → Số phòng đã được thuê
    }

    /**
     * Method: getAvailableUnitsCount - Đếm số phòng còn trống
     * 
     * MỤC ĐÍCH:
     * Tính số phòng còn trống = tổng - đã thuê - đã đặt cọc - bảo trì → Dùng để hiển thị số phòng còn trống
     * 
     * RETURN:
     * - Integer: Số phòng còn trống (tối thiểu 0)
     */
    public function getAvailableUnitsCount()
    {
        $total = $this->getTotalUnitsCount(); // Lấy tổng số phòng → Dùng để tính available
        $occupied = $this->getOccupiedUnitsCount(); // Lấy số phòng đã thuê → Dùng để tính available
        $reserved = $this->getReservedUnitsCount(); // Lấy số phòng đã đặt cọc → Dùng để tính available
        $maintenance = $this->getMaintenanceUnitsCount(); // Lấy số phòng đang bảo trì → Dùng để tính available
        
        return max(0, $total - $occupied - $reserved - $maintenance); // Tính available = total - occupied - reserved - maintenance → Trả về tối thiểu 0
    }

    /**
     * Method: getReservedUnitsCount - Đếm số phòng đã đặt cọc
     * 
     * MỤC ĐÍCH:
     * Đếm số phòng có pending leases → Dùng để tính available units
     * 
     * RETURN:
     * - Integer: Số phòng đã đặt cọc
     */
    public function getReservedUnitsCount()
    {
        return $this->units()->whereHas('leases', function($query) { // Tìm units có leases → Đếm units đã đặt cọc
            $query->where('status', 'pending') // Chỉ lấy leases đang pending → Chỉ tính leases đang chờ duyệt
                  ->whereNull('deleted_at'); // Chỉ lấy leases chưa bị xóa
        })->count(); // Đếm số units → Số phòng đã đặt cọc
    }

    /**
     * Method: getMaintenanceUnitsCount - Đếm số phòng đang bảo trì
     * 
     * MỤC ĐÍCH:
     * Đếm số phòng có status = 'maintenance' → Dùng để tính available units
     * 
     * RETURN:
     * - Integer: Số phòng đang bảo trì
     */
    public function getMaintenanceUnitsCount()
    {
        return $this->units()->where('status', 'maintenance')->count(); // Đếm units có status = 'maintenance' → Số phòng đang bảo trì
    }

    /**
     * Method: getTotalUnitsCount - Đếm tổng số phòng
     * 
     * MỤC ĐÍCH:
     * Đếm tổng số phòng của property → Dùng để tính occupancy rate và available units
     * 
     * RETURN:
     * - Integer: Tổng số phòng
     */
    public function getTotalUnitsCount()
    {
        return $this->units()->count(); // Đếm tổng số units → Tổng số phòng
    }

    /**
     * Method: getOccupancyRate - Tính tỷ lệ lấp đầy (%)
     * 
     * MỤC ĐÍCH:
     * Tính tỷ lệ lấp đầy = (số phòng đã thuê / tổng số phòng) * 100 → Dùng để hiển thị occupancy rate
     * 
     * RETURN:
     * - Float: Tỷ lệ lấp đầy (%, làm tròn 2 chữ số thập phân)
     */
    public function getOccupancyRate()
    {
        $totalUnits = $this->getTotalUnitsCount(); // Lấy tổng số phòng → Dùng để tính occupancy rate
        if ($totalUnits == 0) { // Nếu không có phòng nào
            return 0; // Trả về 0% → Tránh chia cho 0
        }
        
        $occupiedUnits = $this->getOccupiedUnitsCount(); // Lấy số phòng đã thuê → Dùng để tính occupancy rate
        return round(($occupiedUnits / $totalUnits) * 100, 2); // Tính occupancy rate = (occupied / total) * 100 → Làm tròn 2 chữ số thập phân
    }

    /**
     * Method: getOccupancyRateByTotalRooms - Tính tỷ lệ lấp đầy (sử dụng units collection)
     * 
     * MỤC ĐÍCH:
     * Tính tỷ lệ lấp đầy sử dụng units collection đã load → Dùng để tính occupancy rate khi units đã được eager load
     * 
     * RETURN:
     * - Float: Tỷ lệ lấp đầy (%, làm tròn 2 chữ số thập phân)
     */
    public function getOccupancyRateByTotalRooms()
    {
        $totalUnits = $this->units->count(); // Đếm từ units collection đã load → Tối ưu khi units đã được eager load
        if ($totalUnits == 0) { // Nếu không có phòng nào
            return 0; // Trả về 0% → Tránh chia cho 0
        }
        
        $occupiedUnits = $this->getOccupiedUnitsCount(); // Lấy số phòng đã thuê → Dùng để tính occupancy rate
        return round(($occupiedUnits / $totalUnits) * 100, 2); // Tính occupancy rate = (occupied / total) * 100 → Làm tròn 2 chữ số thập phân
    }

    /**
     * Accessor: full_address - Địa chỉ đầy đủ từ location (old system) - backward compatible
     * 
     * MỤC ĐÍCH:
     * Lấy địa chỉ đầy đủ từ location (old system) → Dùng để hiển thị địa chỉ
     * 
     * RETURN:
     * - String: Địa chỉ đầy đủ (street, ward, district, city, country) hoặc 'Địa chỉ chưa cập nhật'
     */
    public function getFullAddressAttribute()
    {
        if (!$this->location) { // Nếu không có location
            return 'Địa chỉ chưa cập nhật'; // Trả về message mặc định
        }

        $addressParts = []; // Khởi tạo mảng chứa các phần địa chỉ → Dùng để build địa chỉ đầy đủ
        
        if ($this->location->street) { // Nếu có tên đường
            $addressParts[] = $this->location->street; // Thêm tên đường vào mảng
        }
        
        if ($this->location->ward) { // Nếu có phường/xã
            $addressParts[] = $this->location->ward; // Thêm phường/xã vào mảng
        }
        
        if ($this->location->district) { // Nếu có quận/huyện
            $addressParts[] = $this->location->district; // Thêm quận/huyện vào mảng
        }
        
        if ($this->location->city) { // Nếu có thành phố
            $addressParts[] = $this->location->city; // Thêm thành phố vào mảng
        }
        
        if ($this->location->country && $this->location->country !== 'Vietnam') { // Nếu có quốc gia và không phải Vietnam
            $addressParts[] = $this->location->country; // Thêm quốc gia vào mảng
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : 'Địa chỉ chưa cập nhật'; // Nối các phần địa chỉ bằng dấu phẩy → Trả về địa chỉ đầy đủ
    }

    /**
     * Accessor: formatted_occupancy_rate - Tỷ lệ lấp đầy đã format (%)
     * 
     * MỤC ĐÍCH:
     * Lấy tỷ lệ lấp đầy đã format với dấu % → Dùng để hiển thị occupancy rate
     * 
     * RETURN:
     * - String: Tỷ lệ lấp đầy với dấu % (ví dụ: "85.5%")
     */
    public function getFormattedOccupancyRateAttribute()
    {
        return $this->getOccupancyRate() . '%'; // Lấy occupancy rate và thêm dấu % → Format cho hiển thị
    }

    /**
     * Accessor: occupancy_status - Trạng thái lấp đầy (full/high/medium/low)
     * 
     * MỤC ĐÍCH:
     * Xác định trạng thái lấp đầy dựa trên occupancy rate → Dùng để hiển thị status badge
     * 
     * RETURN:
     * - String: 'full' (>=90%), 'high' (>=70%), 'medium' (>=50%), 'low' (<50%)
     */
    public function getOccupancyStatusAttribute()
    {
        $rate = $this->getOccupancyRate(); // Lấy occupancy rate → Dùng để xác định status
        
        if ($rate >= 90) { // Nếu >= 90%
            return 'full'; // Trạng thái: Đầy
        } elseif ($rate >= 70) { // Nếu >= 70%
            return 'high'; // Trạng thái: Cao
        } elseif ($rate >= 50) { // Nếu >= 50%
            return 'medium'; // Trạng thái: Trung bình
        } else { // Nếu < 50%
            return 'low'; // Trạng thái: Thấp
        }
    }

    /**
     * Accessor: occupancy_status_text - Trạng thái lấp đầy bằng tiếng Việt
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi occupancy_status sang text tiếng Việt → Dùng để hiển thị status bằng tiếng Việt
     * 
     * RETURN:
     * - String: 'Đầy', 'Cao', 'Trung bình', 'Thấp', hoặc 'Không xác định'
     */
    public function getOccupancyStatusTextAttribute()
    {
        $status = $this->getOccupancyStatusAttribute(); // Lấy occupancy status → Dùng để chuyển đổi sang text
        
        return match($status) { // Match expression → Chuyển đổi status sang text tiếng Việt
            'full' => 'Đầy', // full → Đầy
            'high' => 'Cao', // high → Cao
            'medium' => 'Trung bình', // medium → Trung bình
            'low' => 'Thấp', // low → Thấp
            default => 'Không xác định' // Các status khác → Không xác định
        };
    }

    /**
     * Accessor: old_address - Địa chỉ cũ (location - old system)
     * 
     * MỤC ĐÍCH:
     * Lấy địa chỉ từ location (old system) → Dùng để hiển thị địa chỉ cũ
     * 
     * RETURN:
     * - String: Địa chỉ cũ (street, ward, district, city) hoặc 'Chưa có địa chỉ cũ'
     */
    public function getOldAddressAttribute()
    {
        if (!$this->location) { // Nếu không có location
            return 'Chưa có địa chỉ cũ'; // Trả về message mặc định
        }

        $parts = []; // Khởi tạo mảng chứa các phần địa chỉ → Dùng để build địa chỉ
        
        if ($this->location->street) { // Nếu có tên đường
            $parts[] = $this->location->street; // Thêm tên đường vào mảng
        }
        
        if ($this->location->ward) { // Nếu có phường/xã
            $parts[] = $this->location->ward; // Thêm phường/xã vào mảng
        }
        
        if ($this->location->district) { // Nếu có quận/huyện
            $parts[] = $this->location->district; // Thêm quận/huyện vào mảng
        }
        
        if ($this->location->city) { // Nếu có thành phố
            $parts[] = $this->location->city; // Thêm thành phố vào mảng
        }

        return empty($parts) ? 'Chưa có địa chỉ cũ' : implode(', ', $parts); // Nối các phần địa chỉ bằng dấu phẩy → Trả về địa chỉ cũ
    }

    /**
     * Accessor: new_address - Địa chỉ mới (location2025 - new system 2025)
     * 
     * MỤC ĐÍCH:
     * Lấy địa chỉ từ location2025 (new system 2025) → Dùng để hiển thị địa chỉ mới
     * 
     * RETURN:
     * - String: Địa chỉ mới (street, ward, district, city) hoặc 'Chưa có địa chỉ mới'
     */
    public function getNewAddressAttribute()
    {
        if (!$this->location2025) { // Nếu không có location2025
            return 'Chưa có địa chỉ mới'; // Trả về message mặc định
        }

        $parts = []; // Khởi tạo mảng chứa các phần địa chỉ → Dùng để build địa chỉ
        
        if ($this->location2025->street) { // Nếu có tên đường
            $parts[] = $this->location2025->street; // Thêm tên đường vào mảng
        }
        
        if ($this->location2025->ward) { // Nếu có phường/xã
            $parts[] = $this->location2025->ward; // Thêm phường/xã vào mảng
        }
        
        if ($this->location2025->district) { // Nếu có quận/huyện (có thể có trong location2025)
            $parts[] = $this->location2025->district; // Thêm quận/huyện vào mảng
        }
        
        if ($this->location2025->city) { // Nếu có thành phố
            $parts[] = $this->location2025->city; // Thêm thành phố vào mảng
        }

        return empty($parts) ? 'Chưa có địa chỉ mới' : implode(', ', $parts); // Nối các phần địa chỉ bằng dấu phẩy → Trả về địa chỉ mới
    }

    /**
     * Accessor: owner_name - Tên chủ nhà từ master lease
     * 
     * MỤC ĐÍCH:
     * Lấy tên chủ nhà từ master lease active → Dùng để hiển thị tên chủ nhà
     * 
     * RETURN:
     * - String: Tên chủ nhà hoặc 'Chưa có thông tin chủ trọ'
     * 
     * LƯU Ý:
     * - Chủ nhà được quản lý qua master_leases, không còn owner_id trực tiếp
     */
    public function getOwnerNameAttribute()
    {
        $landlord = $this->getCurrentLandlord(); // Lấy chủ nhà hiện tại từ master lease → Dùng để lấy tên
        return $landlord ? $landlord->full_name : 'Chưa có thông tin chủ trọ'; // Trả về tên chủ nhà hoặc message mặc định
    }
}

