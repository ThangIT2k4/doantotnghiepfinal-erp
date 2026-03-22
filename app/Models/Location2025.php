<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSoftDeletesWithUser;

class Location2025 extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'locations_2025';

    protected $fillable = [
        'country_code',
        'province_code',
        'ward_code',
        'street',
        'country',
        'city',
        'district',
        'ward',
        'lat',
        'lng',
        'postal_code',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    protected $appends = [
        'address'
    ];

    // Relationships
    public function country()
    {
        return $this->belongsTo(GeoCountry::class, 'country_code', 'code');
    }

    public function province()
    {
        return $this->belongsTo(GeoProvince2025::class, 'province_code', 'code');
    }

    public function ward()
    {
        return $this->belongsTo(GeoWard2025::class, 'ward_code', 'code');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'location_id_2025');
    }

    /**
     * Get formatted address
     */
    public function getAddressAttribute()
    {
        $parts = array_filter([
            $this->street,
            $this->ward,
            $this->city,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }
}
