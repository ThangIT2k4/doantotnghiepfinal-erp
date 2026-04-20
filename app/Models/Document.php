<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'documents';
    
    // Enable timestamps (đã thêm updated_at trong migration)
    public $timestamps = true;

    protected $fillable = [
        'owner_type', // Giữ lại để backward compatibility
        'owner_id',   // Giữ lại để backward compatibility
        'file_url',
        'file_name',
        'mime_type',
        'file_size',
        'document_type',
        'is_primary',
        'sort_order',
        'description',
        'uploaded_by',
        'deleted_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'file_size' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Override to manually handle created_at và validate mime_type
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            // Validate mime_type format nếu có
            if ($model->mime_type !== null) {
                $model->validateMimeType($model->mime_type);
            }
        });
        
        static::updating(function ($model) {
            // Validate mime_type format nếu có thay đổi
            if ($model->isDirty('mime_type') && $model->mime_type !== null) {
                $model->validateMimeType($model->mime_type);
            }
        });
    }
    
    /**
     * Validate mime_type format
     * Format: type/subtype (e.g., image/jpeg, application/pdf)
     * Pattern: type/subtype where type and subtype match [a-zA-Z0-9][a-zA-Z0-9!#$&-^_.]*
     */
    protected function validateMimeType(?string $mimeType): void
    {
        if ($mimeType === null) {
            return;
        }
        
        // Validate mime_type format
        $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9!#$&\\-^_.]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\\-^_.]*$/';
        
        if (!preg_match($pattern, $mimeType)) {
            throw new \InvalidArgumentException(
                "Invalid mime_type format: {$mimeType}. Expected format: type/subtype (e.g., image/jpeg, application/pdf)"
            );
        }
    }

    /**
     * Get the parent documentable model (lease, property, etc.) - OLD WAY (backward compatibility)
     * Sử dụng polymorphic relationship cũ
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }


    /**
     * Get the user who uploaded this document
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeAttribute()
    {
        // Nếu đã có file_size trong database, sử dụng nó
        if (isset($this->attributes['file_size']) && $this->attributes['file_size']) {
            $bytes = $this->attributes['file_size'];
        } else {
            // Nếu chưa có, tính từ file thực tế
            $path = storage_path('app/public/' . $this->file_url);
            if (file_exists($path)) {
                $bytes = filesize($path);
            } else {
                return 'N/A';
            }
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file size in bytes (raw)
     */
    public function getFileSizeBytesAttribute(): ?int
    {
        return $this->attributes['file_size'] ?? null;
    }

    /**
     * Check if document is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/') 
            || in_array($this->document_type ?? '', ['image', 'avatar', 'photo']);
    }

    /**
     * Get file icon based on mime type
     */
    public function getFileIcon()
    {
        if ($this->isImage()) {
            return 'fa-image';
        }
        
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        
        return match($ext) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            'xls', 'xlsx' => 'fa-file-excel',
            'zip', 'rar' => 'fa-file-archive',
            default => 'fa-file',
        };
    }

    /**
     * Get file URL (full URL)
     */
    public function getFileUrlAttribute($value)
    {
        // Sử dụng StorageHelper để xử lý URL
        return \App\Helpers\StorageHelper::getFileUrl($value);
    }

    /**
     * Check if document belongs to a lease (OLD WAY - backward compatibility)
     */
    public function belongsToLease(): bool
    {
        return $this->owner_type === \App\Models\Lease::class;
    }

    /**
     * Get the lease ID if this document belongs to a lease (OLD WAY)
     */
    public function getLeaseId(): ?int
    {
        return $this->belongsToLease() ? $this->owner_id : null;
    }

    /**
     * Get owner type name (human readable)
     */
    public function getOwnerTypeName(): string
    {
        return match($this->owner_type) {
            \App\Models\Lease::class => 'Hợp đồng',
            \App\Models\Property::class => 'Bất động sản',
            \App\Models\Unit::class => 'Căn hộ',
            \App\Models\Ticket::class => 'Ticket',
            \App\Models\Payment::class => 'Thanh toán',
            \App\Models\CashOutflow::class => 'Chi tiêu',
            \App\Models\Viewing::class => 'Lịch xem',
            \App\Models\UserProfile::class => 'Hồ sơ người dùng',
            default => $this->owner_type ?? 'Không xác định',
        };
    }

    /**
     * Scope to get documents for a specific lease (OLD WAY)
     */
    public function scopeForLease($query, $leaseId)
    {
        return $query->where('owner_type', \App\Models\Lease::class)
                     ->where('owner_id', $leaseId);
    }

    /**
     * Scope to get documents by owner type (OLD WAY)
     */
    public function scopeByOwnerType($query, $ownerType)
    {
        return $query->where('owner_type', $ownerType);
    }

    /**
     * Scope to get documents by document type
     */
    public function scopeByDocumentType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    /**
     * Scope to get primary documents only
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get images only
     */
    public function scopeImages($query)
    {
        return $query->whereIn('document_type', ['image', 'avatar', 'photo']);
    }

    /**
     * Get information about the owner relationship (OLD WAY)
     */
    public function getOwnerInfo(): array
    {
        return [
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'owner_type_name' => $this->getOwnerTypeName(),
            'belongs_to_lease' => $this->belongsToLease(),
            'lease_id' => $this->getLeaseId(),
        ];
    }
}
