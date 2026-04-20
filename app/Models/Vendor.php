<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToOrganization;
use App\Models\CashOutflow;

/**
 * Model: Vendor
 *
 * MỤC ĐÍCH:
 * Quản lý thông tin nhà cung cấp (vendors) trong module Finance - lưu trữ thông tin cơ bản, ngân hàng, liên hệ và tích hợp với hệ thống thanh toán SePay
 *
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Relationships: organization, sepayBank, cashOutflows (qua company_invoices), companyInvoices, deletedBy
 * 2. Scopes: byOrganization, search, byType, byStatus, active
 * 3. Accessors: total_payments, formatted_total_payments, status_label, status_badge_class, vendor_type_label, banking_info, contact_info, last_payment_date
 * 4. Methods: getContactInfo(), hasValidContactInfo(), getDisplayName()
 *
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng vendors: Thông tin nhà cung cấp
 * - Bảng sepay_banks: Thông tin ngân hàng qua relationship
 * - Bảng company_invoices: Hóa đơn của vendor
 * - Bảng cash_outflows: Các khoản thanh toán qua company_invoices
 *
 * DỮ LIỆU GHI VÀO:
 * - Bảng vendors: Tạo, cập nhật, xóa (soft delete) vendors
 *
 * LƯU Ý:
 * - Sử dụng BelongsToOrganization trait để tự động scope theo organization
 * - Sử dụng SoftDeletes để soft delete (ghi deleted_by và deleted_at)
 * - Cash outflows được lấy qua company_invoices (không có direct relationship)
 * - Hỗ trợ 2 loại vendor: individual (cá nhân) và company (công ty)
 * - Status: active, inactive, suspended
 */
class Vendor extends Model
{
    use HasFactory, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'tax_code',
        'phone',
        'email',
        'address',
        'sepay_bank_id',
        'account_number',
        'account_holder_name',
        'branch_name',
        'branch_code',
        'swift_code',
        'banking_notes',
        'contact_person',
        'contact_phone',
        'contact_email',
        'business_license',
        'vendor_type',
        'status',
        'deleted_by',
    ];

    // Relationships
    /**
     * Relationship với Organization
     *
     * MỤC ĐÍCH:
     * Lấy organization mà vendor thuộc về
     *
     * RETURN:
     * BelongsTo Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class); // BelongsTo Organization → Mỗi vendor thuộc một organization
    }

    /**
     * Relationship với SepayBank
     *
     * MỤC ĐÍCH:
     * Lấy thông tin ngân hàng SePay của vendor
     *
     * RETURN:
     * BelongsTo SepayBank
     */
    public function sepayBank(): BelongsTo
    {
        return $this->belongsTo(SepayBank::class); // BelongsTo SepayBank → Mỗi vendor có thể có một ngân hàng SePay
    }

    /**
     * Lấy cash outflows nơi vendor này là người nhận
     *
     * MỤC ĐÍCH:
     * Lấy các khoản thanh toán cho vendor qua company_invoices (không có direct relationship)
     *
     * RETURN:
     * Query builder của CashOutflow
     *
     * LƯU Ý:
     * - Cash outflows được liên kết với company_invoices, và chúng ta lấy chúng qua company_invoices nơi vendor này là vendor (vendor_id)
     * - Đây là query builder, không phải relationship, nên cần gọi ->get() để lấy kết quả
     */
    public function cashOutflows()
    {
        return CashOutflow::whereHas('companyInvoice', function($query) { // Tìm cash outflows có company_invoice
            $query->where('vendor_id', $this->id); // Mà company_invoice có vendor_id = vendor này
        }); // Trả về query builder → Có thể thêm where, orderBy, get(), ...
    }

    /**
     * Relationship với CompanyInvoice
     *
     * MỤC ĐÍCH:
     * Lấy danh sách hóa đơn công ty của vendor
     *
     * RETURN:
     * HasMany CompanyInvoice
     */
    public function companyInvoices(): HasMany
    {
        return $this->hasMany(CompanyInvoice::class); // HasMany CompanyInvoice → Mỗi vendor có nhiều hóa đơn
    }

    /**
     * Relationship với User (người xóa)
     *
     * MỤC ĐÍCH:
     * Lấy user đã xóa vendor này (nếu có)
     *
     * RETURN:
     * BelongsTo User
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by'); // BelongsTo User qua deleted_by → Lấy user đã xóa vendor
    }

    // Scopes
    /**
     * Scope lọc theo organization
     *
     * MỤC ĐÍCH:
     * Lọc vendors theo organization ID
     *
     * INPUT:
     * - $query: Query builder
     * - $organizationId: ID của organization
     *
     * RETURN:
     * Query builder với where organization_id
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId); // Lọc theo organization_id → Dùng để filter vendors theo organization
    }

    /**
     * Scope tìm kiếm vendors
     *
     * MỤC ĐÍCH:
     * Tìm kiếm vendors theo name, tax_code, phone, email
     *
     * INPUT:
     * - $query: Query builder
     * - $search: Từ khóa tìm kiếm
     *
     * RETURN:
     * Query builder với where conditions
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) { // Tạo group where → Tìm kiếm trong nhiều fields
            $q->where('name', 'like', "%{$search}%") // Tìm trong name
              ->orWhere('tax_code', 'like', "%{$search}%") // Hoặc tax_code
              ->orWhere('phone', 'like', "%{$search}%") // Hoặc phone
              ->orWhere('email', 'like', "%{$search}%"); // Hoặc email
        }); // Trả về query builder → Dùng để tìm kiếm vendors
    }

    // Accessors & Mutators
    /**
     * Accessor: Tổng số tiền đã thanh toán cho vendor
     *
     * MỤC ĐÍCH:
     * Tính tổng số tiền đã thanh toán thành công cho vendor qua cash outflows
     *
     * RETURN:
     * float - Tổng số tiền đã thanh toán
     *
     * LƯU Ý:
     * - Chỉ tính các cash outflows có status = 'success'
     * - Lấy qua company_invoices nơi vendor này là vendor
     */
    public function getTotalPaymentsAttribute(): float
    {
        return CashOutflow::whereHas('companyInvoice', function($query) { // Tìm cash outflows có company_invoice
                $query->where('vendor_id', $this->id); // Mà company_invoice có vendor_id = vendor này
            })
            ->where('status', 'success') // Chỉ lấy các thanh toán thành công
            ->sum('amount'); // Tính tổng amount → Trả về tổng số tiền đã thanh toán
    }

    /**
     * Accessor: Tổng số tiền đã thanh toán (đã format)
     *
     * MỤC ĐÍCH:
     * Format tổng số tiền đã thanh toán thành chuỗi với định dạng VND
     *
     * RETURN:
     * string - Tổng số tiền đã format (ví dụ: "1.000.000 VND")
     */
    public function getFormattedTotalPaymentsAttribute(): string
    {
        return number_format($this->total_payments, 0, ',', '.') . ' VND'; // Format số với dấu phẩy và chấm → Hiển thị dễ đọc
    }

    protected $casts = [
        'vendor_type' => 'string', // Cast vendor_type thành string
        'status' => 'string', // Cast status thành string
    ];

    // Scopes
    /**
     * Scope lọc vendors đang hoạt động
     *
     * MỤC ĐÍCH:
     * Lọc chỉ lấy vendors có status = 'active'
     *
     * INPUT:
     * - $query: Query builder
     *
     * RETURN:
     * Query builder với where status = 'active'
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active'); // Lọc theo status = 'active' → Dùng để lấy vendors đang hoạt động
    }

    /**
     * Scope lọc theo loại vendor
     *
     * MỤC ĐÍCH:
     * Lọc vendors theo loại (individual hoặc company)
     *
     * INPUT:
     * - $query: Query builder
     * - $type: Loại vendor ('individual' hoặc 'company')
     *
     * RETURN:
     * Query builder với where vendor_type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type); // Lọc theo vendor_type → Dùng để filter vendors theo loại
    }

    /**
     * Scope lọc theo trạng thái
     *
     * MỤC ĐÍCH:
     * Lọc vendors theo trạng thái (active, inactive, suspended)
     *
     * INPUT:
     * - $query: Query builder
     * - $status: Trạng thái ('active', 'inactive', 'suspended')
     *
     * RETURN:
     * Query builder với where status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status); // Lọc theo status → Dùng để filter vendors theo trạng thái
    }

    // Accessors & Mutators
    /**
     * Accessor: Label trạng thái (tiếng Việt)
     *
     * MỤC ĐÍCH:
     * Chuyển đổi status code sang label tiếng Việt để hiển thị
     *
     * RETURN:
     * string - Label trạng thái (ví dụ: "Hoạt động", "Không hoạt động", "Tạm ngưng")
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) { // Match status → Chuyển đổi sang label tiếng Việt
            'active' => 'Hoạt động',
            'inactive' => 'Không hoạt động',
            'suspended' => 'Tạm ngưng',
            default => 'Không xác định'
        };
    }

    /**
     * Accessor: CSS class cho badge trạng thái
     *
     * MỤC ĐÍCH:
     * Trả về CSS class phù hợp cho badge trạng thái để hiển thị màu sắc
     *
     * RETURN:
     * string - CSS class (ví dụ: "bg-success", "bg-secondary", "bg-warning")
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) { // Match status → Trả về CSS class phù hợp
            'active' => 'bg-success', // Màu xanh cho active
            'inactive' => 'bg-secondary', // Màu xám cho inactive
            'suspended' => 'bg-warning', // Màu vàng cho suspended
            default => 'bg-secondary'
        };
    }

    /**
     * Accessor: Label loại vendor (tiếng Việt)
     *
     * MỤC ĐÍCH:
     * Chuyển đổi vendor_type code sang label tiếng Việt để hiển thị
     *
     * RETURN:
     * string - Label loại vendor (ví dụ: "Cá nhân", "Công ty")
     */
    public function getVendorTypeLabelAttribute(): string
    {
        return match($this->vendor_type) { // Match vendor_type → Chuyển đổi sang label tiếng Việt
            'individual' => 'Cá nhân',
            'company' => 'Công ty',
            default => 'Không xác định'
        };
    }

    /**
     * Accessor: Thông tin ngân hàng
     *
     * MỤC ĐÍCH:
     * Trả về mảng chứa tất cả thông tin ngân hàng của vendor
     *
     * RETURN:
     * array - Mảng chứa bank_name, bank_code, account_number, account_holder_name, branch_name, branch_code, swift_code
     */
    public function getBankingInfoAttribute(): array
    {
        return [
            'bank_name' => $this->sepayBank?->name, // Tên ngân hàng
            'bank_code' => $this->sepayBank?->code, // Mã ngân hàng
            'bank_short_name' => $this->sepayBank?->short_name, // Tên viết tắt ngân hàng
            'account_number' => $this->account_number, // Số tài khoản
            'account_holder_name' => $this->account_holder_name, // Tên chủ tài khoản
            'branch_name' => $this->branch_name, // Tên chi nhánh
            'branch_code' => $this->branch_code, // Mã chi nhánh
            'swift_code' => $this->swift_code, // Mã SWIFT
        ]; // Trả về mảng thông tin ngân hàng → Dùng để hiển thị hoặc tích hợp thanh toán
    }

    /**
     * Accessor: Thông tin liên hệ
     *
     * MỤC ĐÍCH:
     * Trả về mảng chứa thông tin liên hệ của vendor
     *
     * RETURN:
     * array - Mảng chứa contact_person, contact_phone, contact_email
     */
    public function getContactInfoAttribute(): array
    {
        return [
            'contact_person' => $this->contact_person, // Người liên hệ
            'contact_phone' => $this->contact_phone, // Số điện thoại liên hệ
            'contact_email' => $this->contact_email, // Email liên hệ
        ]; // Trả về mảng thông tin liên hệ → Dùng để hiển thị
    }

    /**
     * Accessor: Ngày thanh toán cuối cùng
     *
     * MỤC ĐÍCH:
     * Lấy ngày thanh toán cuối cùng thành công cho vendor (format d/m/Y)
     *
     * RETURN:
     * string|null - Ngày thanh toán cuối cùng (format d/m/Y) hoặc null nếu chưa có thanh toán
     */
    public function getLastPaymentDateAttribute(): ?string
    {
        $lastPayment = CashOutflow::whereHas('companyInvoice', function($query) { // Tìm cash outflows có company_invoice
                $query->where('vendor_id', $this->id); // Mà company_invoice có vendor_id = vendor này
            })
            ->where('status', 'success') // Chỉ lấy các thanh toán thành công
            ->orderBy('paid_at', 'desc') // Sắp xếp theo paid_at giảm dần → Lấy thanh toán mới nhất
            ->first(); // Lấy bản ghi đầu tiên → Thanh toán cuối cùng
        
        return $lastPayment ? $lastPayment->paid_at->format('d/m/Y') : null; // Format ngày hoặc trả về null → Hiển thị ngày thanh toán cuối cùng
    }

    // Methods
    /**
     * Lấy thông tin liên hệ cơ bản
     *
     * MỤC ĐÍCH:
     * Trả về mảng chứa thông tin liên hệ cơ bản (phone, email, address)
     *
     * RETURN:
     * array - Mảng chứa phone, email, address
     */
    public function getContactInfo(): array
    {
        return [
            'phone' => $this->phone, // Số điện thoại
            'email' => $this->email, // Email
            'address' => $this->address, // Địa chỉ
        ]; // Trả về mảng thông tin liên hệ cơ bản → Dùng để hiển thị
    }

    /**
     * Kiểm tra vendor có thông tin liên hệ hợp lệ không
     *
     * MỤC ĐÍCH:
     * Kiểm tra vendor có ít nhất phone hoặc email không
     *
     * RETURN:
     * bool - true nếu có phone hoặc email, false nếu không có cả hai
     */
    public function hasValidContactInfo(): bool
    {
        return !empty($this->phone) || !empty($this->email); // Kiểm tra có phone hoặc email không → Dùng để validate
    }

    /**
     * Lấy tên hiển thị của vendor
     *
     * MỤC ĐÍCH:
     * Trả về tên vendor kèm mã số thuế (nếu có) để hiển thị
     *
     * RETURN:
     * string - Tên vendor kèm mã số thuế (ví dụ: "Công ty ABC (0123456789)")
     */
    public function getDisplayName(): string
    {
        $name = $this->name; // Lấy tên vendor
        if ($this->tax_code) { // Nếu có mã số thuế
            $name .= " ({$this->tax_code})"; // Thêm mã số thuế vào tên
        }
        return $name; // Trả về tên kèm mã số thuế → Dùng để hiển thị
    }

    /**
     * Retrieve the model for route model binding.
     * Đảm bảo chỉ resolve vendor thuộc organization của user hiện tại
     * 
     * MỤC ĐÍCH:
     * Kiểm tra vendor thuộc organization của user trước khi resolve route binding
     * Tránh lỗi 403 bằng cách trả về 404 nếu vendor không thuộc organization
     *
     * INPUT:
     * - $value: Giá trị route parameter (vendor ID)
     * - $field: Field name để tìm kiếm (mặc định là route key)
     *
     * RETURN:
     * - Vendor model instance nếu thuộc organization của user
     * - null nếu không thuộc (sẽ trả về 404)
     *
     * LƯU Ý:
     * - Admin có quyền truy cập tất cả vendors
     * - Non-admin chỉ truy cập được vendors thuộc organization hiện tại (từ session)
     */
    public function resolveRouteBinding($value, $field = null)
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (!$user) {
            return null; // Không có user → trả về null (404)
        }

        // Admin có quyền truy cập tất cả
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        if ($isAdmin) {
            return $this->withoutGlobalScope('organization')
                ->where($field ?? $this->getRouteKeyName(), $value)
                ->first();
        }

        // Lấy organization ID hiện tại từ session
        $organizationId = $user->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return null; // Không có organization → trả về null (404)
        }

        // Tìm vendor thuộc organization hiện tại
        return $this->withoutGlobalScope('organization')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('organization_id', $organizationId)
            ->first();
    }
}
