<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;

class Ticket extends Model
{
    use HasSoftDeletesWithUser, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'property_id',
        'unit_id',
        'lease_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'cancelled_at',
        'cancelled_by',
        'priority_id',
    ];

    protected $casts = [
        'status' => 'string',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Priority relation
     */
    public function priorityRelation()
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs()
    {
        return $this->hasMany(TicketLog::class);
    }

    /**
     * Get documents attached to this ticket (OLD WAY - backward compatibility)
     * Sử dụng polymorphic relationship cũ
     */
    /**
     * Get documents for this ticket (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get primary image (backward compatibility)
     */
    public function getImageAttribute()
    {
        // Nếu cột image vẫn còn trong database, trả về nó
        if (isset($this->attributes['image'])) {
            return $this->attributes['image'];
        }

        // Lấy từ documents trực tiếp
        $primaryImage = $this->documents()
            ->where('document_type', 'image')
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        return $primaryImage ? $primaryImage->file_url : null;
    }

    /**
     * Get the image URL (backward compatibility)
     */
    public function getImageUrlAttribute()
    {
        $image = $this->image;
        if ($image) {
            // Nếu đã là full URL, trả về luôn
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                return $image;
            }
            // Nếu là relative path, tạo full URL
            return asset('storage/' . ltrim($image, '/'));
        }
        return null;
    }

    /**
     * Get Vietnamese status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'open' => 'Đang mở',
            'in_progress' => 'Đang xử lý',
            'resolved' => 'Đã giải quyết',
            'closed' => 'Đã đóng',
            'cancelled' => 'Đã hủy',
            default => ucfirst(str_replace('_', ' ', $this->status))
        };
    }

    /**
     * Get Vietnamese priority label
     */
    public function getPriorityLabelAttribute()
    {
        $code = $this->priorityRelation?->key_code ?? 'medium';
        return match($code) {
            'low' => 'Thấp',
            'medium' => 'Trung bình',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
            default => ucfirst((string) $code)
        };
    }

    /**
     * Get priority key_code (backward compatibility accessor)
     */
    public function getPriorityAttribute()
    {
        return $this->priorityRelation?->key_code ?? 'medium';
    }
}