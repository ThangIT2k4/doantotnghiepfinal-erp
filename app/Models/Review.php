<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: Review
 * 
 * MỤC ĐÍCH:
 * Model đại diện cho đánh giá từ khách thuê về phòng/bất động sản. Lưu trữ thông tin về ratings (tổng thể, vị trí, chất lượng, dịch vụ, giá cả), 
 * nội dung đánh giá, highlights, khuyến nghị, và statistics (view count, helpful count).
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. Tenant tạo review sau khi kết thúc lease
 * 2. Review có status: pending (chờ duyệt), approved (đã duyệt), rejected (đã từ chối)
 * 3. Staff có thể xem và phản hồi review (không được chỉnh sửa/xóa)
 * 4. Review có thể có nhiều replies (phản hồi lồng nhau)
 * 5. Review có thể có nhiều documents (hình ảnh)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Bảng reviews: Lưu trữ đánh giá
 * - Bảng units: Thông tin phòng được đánh giá
 * - Bảng properties: Thông tin bất động sản
 * - Bảng leases: Thông tin hợp đồng liên quan
 * - Bảng users: Thông tin tenant đánh giá
 * - Bảng review_replies: Phản hồi của staff
 * - Bảng documents: Hình ảnh review (polymorphic)
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng reviews: Tạo/cập nhật/xóa (soft delete) review
 * 
 * LƯU Ý:
 * - Sử dụng soft deletes (có thể khôi phục)
 * - Ratings được cast thành decimal:1 (1 chữ số thập phân)
 * - Highlights được cast thành array (JSON)
 * - Hỗ trợ polymorphic relationship với Document (hình ảnh)
 * - Chỉ tenant mới có quyền chỉnh sửa/xóa review của mình
 */
class Review extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, BelongsToOrganization;

    protected $fillable = [ // Các trường có thể mass assign → Bảo mật: chỉ cho phép assign các trường này
        'organization_id', // ID của organization sở hữu review
        'unit_id', // ID của phòng được đánh giá
        'lease_id', // ID của hợp đồng liên quan
        'tenant_id', // ID của tenant đánh giá
        'overall_rating', // Điểm tổng thể (1-5)
        'location_rating', // Điểm vị trí (1-5)
        'quality_rating', // Điểm chất lượng (1-5)
        'service_rating', // Điểm dịch vụ (1-5)
        'price_rating', // Điểm giá cả (1-5)
        'title', // Tiêu đề đánh giá
        'content', // Nội dung đánh giá
        'highlights', // Điểm nổi bật (array)
        'recommend', // Khuyến nghị (yes, maybe, no)
        'helpful_count', // Số lượt đánh giá hữu ích
        'view_count', // Số lượt xem
        'status', // Trạng thái (pending, approved, rejected)
        'deleted_by', // ID của user xóa review
    ];

    protected $casts = [ // Cast các trường khi lấy từ database → Tự động convert kiểu dữ liệu
        'overall_rating' => 'decimal:1', // Cast thành decimal với 1 chữ số thập phân → Đảm bảo format đúng
        'location_rating' => 'decimal:1', // Cast thành decimal với 1 chữ số thập phân
        'quality_rating' => 'decimal:1', // Cast thành decimal với 1 chữ số thập phân
        'service_rating' => 'decimal:1', // Cast thành decimal với 1 chữ số thập phân
        'price_rating' => 'decimal:1', // Cast thành decimal với 1 chữ số thập phân
        'highlights' => 'array', // Cast thành array → Tự động JSON encode/decode
        'helpful_count' => 'integer', // Cast thành integer
        'view_count' => 'integer', // Cast thành integer
    ];

    /**
     * Relationship: organization - Tổ chức sở hữu review
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin organization sở hữu review → Dùng để filter và hiển thị
     * 
     * RETURN:
     * - BelongsTo Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class); // BelongsTo relationship → Một review thuộc một organization
    }

    /**
     * Relationship: unit - Phòng được đánh giá
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin phòng được đánh giá → Dùng để hiển thị thông tin phòng
     * 
     * RETURN:
     * - BelongsTo Unit
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class); // BelongsTo relationship → Một review thuộc một unit
    }

    /**
     * Relationship: lease - Hợp đồng liên quan
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin hợp đồng liên quan đến review → Dùng để hiển thị thông tin hợp đồng
     * 
     * RETURN:
     * - BelongsTo Lease
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class); // BelongsTo relationship → Một review có thể thuộc một lease
    }

    /**
     * Relationship: tenant - Khách thuê đánh giá
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin tenant đánh giá → Dùng để hiển thị thông tin người đánh giá
     * 
     * RETURN:
     * - BelongsTo User (tenant_id)
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id'); // BelongsTo relationship với foreign key tenant_id → Một review thuộc một tenant
    }

    /**
     * Relationship: replies - Phản hồi của review (chỉ parent replies)
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách phản hồi cha (không bao gồm nested replies) → Dùng để hiển thị phản hồi chính
     * 
     * RETURN:
     * - HasMany ReviewReply (chỉ parent_reply_id = null)
     */
    public function replies()
    {
        return $this->hasMany(ReviewReply::class)->whereNull('parent_reply_id'); // HasMany relationship → Một review có nhiều replies, chỉ lấy parent replies
    }

    /**
     * Relationship: allReplies - Tất cả phản hồi (bao gồm nested)
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả phản hồi bao gồm cả nested replies → Dùng để đếm tổng số phản hồi
     * 
     * RETURN:
     * - HasMany ReviewReply (tất cả)
     */
    public function allReplies()
    {
        return $this->hasMany(ReviewReply::class); // HasMany relationship → Một review có nhiều replies (bao gồm cả nested)
    }

    /**
     * Accessor: replies_count - Số lượng phản hồi
     * 
     * MỤC ĐÍCH:
     * Tính số lượng phản hồi (bao gồm cả nested) → Dùng để hiển thị số lượng phản hồi
     * 
     * RETURN:
     * - Integer: Số lượng phản hồi
     */
    public function getRepliesCountAttribute()
    {
        return $this->allReplies()->count(); // Đếm tổng số replies → Dùng để hiển thị
    }

    /**
     * Relationship: deletedBy - User xóa review
     * 
     * MỤC ĐÍCH:
     * Lấy thông tin user xóa review → Dùng để tracking và audit
     * 
     * RETURN:
     * - BelongsTo User (deleted_by)
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by'); // BelongsTo relationship với foreign key deleted_by → User xóa review
    }

    /**
     * Scope: published - Chỉ lấy reviews đã đăng
     * 
     * MỤC ĐÍCH:
     * Filter chỉ lấy reviews có status = 'published' → Dùng để hiển thị reviews đã được duyệt và đăng
     * 
     * INPUT:
     * - $query: Query builder
     * 
     * RETURN:
     * - Query builder với điều kiện status = 'published'
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published'); // Filter chỉ lấy reviews đã đăng → Dùng để hiển thị public reviews
    }

    /**
     * Scope: byTenant - Lấy reviews của tenant cụ thể
     * 
     * MỤC ĐÍCH:
     * Filter chỉ lấy reviews của tenant có ID = $tenantId → Dùng để hiển thị reviews của tenant
     * 
     * INPUT:
     * - $query: Query builder
     * - $tenantId: ID của tenant
     * 
     * RETURN:
     * - Query builder với điều kiện tenant_id = $tenantId
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId); // Filter chỉ lấy reviews của tenant này → Dùng để filter theo tenant
    }

    /**
     * Accessor: average_detail_rating - Điểm trung bình của các ratings chi tiết
     * 
     * MỤC ĐÍCH:
     * Tính điểm trung bình của location_rating, quality_rating, service_rating, price_rating → Dùng để hiển thị điểm trung bình chi tiết
     * 
     * RETURN:
     * - Float: Điểm trung bình (làm tròn 1 chữ số thập phân) hoặc null nếu không có ratings
     */
    public function getAverageDetailRatingAttribute()
    {
        $ratings = array_filter([ // Lọc bỏ các giá trị null/empty → Chỉ tính các ratings có giá trị
            $this->location_rating,
            $this->quality_rating,
            $this->service_rating,
            $this->price_rating,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null; // Tính trung bình và làm tròn 1 chữ số → Trả về null nếu không có ratings
    }

    /**
     * Method: canBeDeletedBy - Kiểm tra user có thể xóa review không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem user có quyền xóa review này không → Chỉ tenant mới có quyền xóa review của mình
     * 
     * INPUT:
     * - $user: User object
     * 
     * RETURN:
     * - Boolean: true nếu user có quyền xóa, false nếu không
     */
    public function canBeDeletedBy($user)
    {
        return $this->tenant_id === $user->id; // Chỉ tenant (người tạo review) mới có quyền xóa → Bảo mật: không cho phép xóa review của người khác
    }

    /**
     * Method: canBeAccessedByAgent - Kiểm tra agent có thể truy cập review không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra xem agent có quyền truy cập review này không → Agent chỉ xem reviews của properties được gán
     * 
     * INPUT:
     * - $user: User object (agent)
     * 
     * RETURN:
     * - Boolean: true nếu agent có quyền truy cập, false nếu không
     */
    public function canBeAccessedByAgent($user)
    {
        $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id')->toArray(); // Lấy danh sách properties được gán cho agent → Dùng để kiểm tra
        return in_array($this->unit->property_id, $assignedPropertyIds); // Kiểm tra property của review có trong danh sách được gán không → Agent chỉ xem reviews của properties được gán
    }

    /**
     * Accessor: status_label - Label tiếng Việt của status
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi status sang label tiếng Việt → Dùng để hiển thị status bằng tiếng Việt
     * 
     * RETURN:
     * - String: Label tiếng Việt của status
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) { // Match expression → Chuyển đổi status sang label
            'published' => 'Đã đăng', // published → Đã đăng
            'hidden' => 'Đã ẩn', // hidden → Đã ẩn
            default => ucfirst($this->status) // Các status khác → Uppercase first letter
        };
    }

    /**
     * Accessor: recommend_label - Label tiếng Việt của recommend
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi recommend sang label tiếng Việt → Dùng để hiển thị recommend bằng tiếng Việt
     * 
     * RETURN:
     * - String: Label tiếng Việt của recommend
     */
    public function getRecommendLabelAttribute()
    {
        return match($this->recommend) { // Match expression → Chuyển đổi recommend sang label
            'yes' => 'Có, tôi sẽ giới thiệu', // yes → Có, tôi sẽ giới thiệu
            'maybe' => 'Có thể', // maybe → Có thể
            'no' => 'Không', // no → Không
            default => 'Chưa chọn' // Các giá trị khác → Chưa chọn
        };
    }

    /**
     * Relationship: documents - Documents của review (polymorphic)
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách documents (hình ảnh) của review → Dùng để hiển thị hình ảnh review
     * 
     * RETURN:
     * - MorphMany Document
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner'); // Polymorphic relationship → Một review có nhiều documents (hình ảnh)
    }

    /**
     * Relationship: reviewImages - Hình ảnh của review
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách hình ảnh của review (chỉ document_type = 'image') → Dùng để hiển thị hình ảnh
     * 
     * RETURN:
     * - Query builder với filter document_type = 'image' và sort theo sort_order, created_at
     */
    public function reviewImages()
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
     * - Array: Mảng URL hình ảnh (đã format với 'storage/' prefix nếu cần)
     */
    public function getImagesAttribute()
    {
        return $this->documents() // Lấy documents relationship
            ->where('document_type', 'image') // Chỉ lấy hình ảnh
            ->orderBy('sort_order') // Sắp xếp theo sort_order
            ->orderBy('created_at') // Sắp xếp theo created_at
            ->get() // Lấy tất cả kết quả
            ->map(function($doc) { // Map mỗi document thành URL → Format URL cho đúng
                return str_starts_with($doc->file_url, 'http://') || str_starts_with($doc->file_url, 'https://') // Kiểm tra nếu đã là URL đầy đủ
                    ? $doc->file_url // Trả về URL đầy đủ
                    : 'storage/' . ltrim($doc->file_url, '/'); // Thêm prefix 'storage/' nếu chưa có
            })
            ->toArray(); // Convert thành array → Dùng để hiển thị
    }
}
