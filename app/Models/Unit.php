<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;

class Unit extends Model
{
    use HasSoftDeletesWithUser;

    protected $fillable = [
        'property_id',
        'code',
        'floor',
        'area_m2',
        'unit_type',
        'base_rent',
        'deposit_amount',
        'max_occupancy',
        'status',
        'note',
        'images',
    ];

    protected $casts = [
        'area_m2' => 'decimal:2',
        'base_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'max_occupancy' => 'integer',
        'floor' => 'integer',
        'images' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }


    public function bookingDeposits()
    {
        return $this->hasMany(BookingDeposit::class);
    }

    /**
     * Get the unit's name (alias for code)
     */
    public function getNameAttribute()
    {
        return $this->code;
    }

    public function meters()
    {
        return $this->hasMany(Meter::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'unit_amenities');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Helper methods
    public function getCurrentLeaseAttribute()
    {
        return $this->leases()
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();
    }

    public function getIsRentedAttribute()
    {
        return $this->leases()
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'available' && !$this->is_rented;
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('unit_type', $type);
    }

    /**
     * Get documents for this unit (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get unit images
     */
    public function unitImages()
    {
        return $this->documents()
            ->where('document_type', 'image')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * Get images (backward compatibility)
     */
    public function getImagesAttribute()
    {
        // Nếu cột images vẫn còn trong database, trả về nó
        if (isset($this->attributes['images'])) {
            return $this->attributes['images'];
        }

        // Lấy từ documents trực tiếp
        return $this->documents()
            ->where('document_type', 'image')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(function($document) {
                // Lấy file_url gốc từ database (đã là relative path không có storage/ prefix)
                $fileUrl = $document->getRawOriginal('file_url') ?? $document->file_url;
                
                // Nếu đã là full URL, trả về trực tiếp
                if (str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://')) {
                    return $fileUrl;
                }
                
                // Trả về path như đã lưu (ImageService đã trả về path đúng format)
                return $fileUrl;
            })
            ->toArray();
    }
}