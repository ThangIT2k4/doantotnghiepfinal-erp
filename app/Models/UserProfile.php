<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    
    // Use user_id as primary key
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';
    
    // Disable timestamps
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'avatar',
        'dob',
        'gender',
        'id_number',
        'id_issued_at',
        'id_card_place',
        'id_images',
        'address',
        'tax_code',
        'note',
        // Banking information
        'sepay_bank_id',
        'account_number',
        'account_holder_name',
        'branch_name',
        'branch_code',
        'swift_code',
        'banking_notes',
    ];

    protected $casts = [
        'dob' => 'date',
        'id_issued_at' => 'date',
        'id_images' => 'array',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sepay bank for this profile.
     */
    public function sepayBank(): BelongsTo
    {
        return $this->belongsTo(SepayBank::class, 'sepay_bank_id');
    }

    /**
     * Get the formatted gender text.
     */
    public function getGenderTextAttribute(): string
    {
        return match($this->gender) {
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
            default => 'Chưa xác định'
        };
    }

    /**
     * Get the age from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->dob) {
            return null;
        }

        return $this->dob->age;
    }

    /**
     * Get formatted date of birth.
     */
    public function getFormattedDobAttribute(): ?string
    {
        return $this->dob ? $this->dob->format('d/m/Y') : null;
    }

    /**
     * Get formatted ID issued date.
     */
    public function getFormattedIdIssuedAtAttribute(): ?string
    {
        return $this->id_issued_at ? $this->id_issued_at->format('d/m/Y') : null;
    }

    /**
     * Check if profile is complete for KYC.
     */
    public function isKycComplete(): bool
    {
        return !empty($this->dob) &&
               !empty($this->gender) &&
               !empty($this->id_number) &&
               !empty($this->id_issued_at) &&
               !empty($this->address);
    }

    /**
     * Get KYC completion percentage.
     */
    public function getKycCompletionPercentage(): int
    {
        $fields = [
            'dob',
            'gender', 
            'id_number',
            'id_issued_at',
            'address'
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100);
    }

    /**
     * Get missing KYC fields.
     */
    public function getMissingKycFields(): array
    {
        $fields = [
            'dob' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'id_number' => 'Số CMND/CCCD',
            'id_issued_at' => 'Ngày cấp CMND/CCCD',
            'address' => 'Địa chỉ thường trú'
        ];

        $missing = [];
        foreach ($fields as $field => $label) {
            if (empty($this->$field)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Get documents for this user profile (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get avatar (backward compatibility)
     */
    public function getAvatarAttribute()
    {
        // Nếu cột avatar vẫn còn trong database, trả về nó
        if (isset($this->attributes['avatar'])) {
            return $this->attributes['avatar'];
        }

        // Lấy từ documents trực tiếp
        $avatar = $this->documents()
            ->where('document_type', 'avatar')
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        return $avatar ? $avatar->file_url : null;
    }

    /**
     * Get id_images (backward compatibility)
     */
    public function getIdImagesAttribute()
    {
        // Nếu cột id_images vẫn còn trong database, trả về nó
        if (isset($this->attributes['id_images'])) {
            return $this->attributes['id_images'];
        }

        // Lấy từ documents trực tiếp
        return $this->documents()
            ->where('document_type', 'image')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->pluck('file_url')
            ->toArray();
    }
}