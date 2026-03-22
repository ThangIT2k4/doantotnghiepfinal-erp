<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;

class Location extends Model
{
    use HasSoftDeletesWithUser;
    protected $table = 'locations';

    protected $fillable = [
        'country_code',
        'province_code',
        'district_code',
        'ward_code',
        'street',
        'country',
        'city',
        'district',
        'ward',
        'lat',
        'lng',
        'postal_code',
        'deleted_by',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    protected $appends = [
        'address'
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    // Relationships with geo tables
    public function province()
    {
        return $this->belongsTo(GeoProvince::class, 'province_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(GeoDistrict::class, 'district_code', 'code');
    }

    public function ward()
    {
        return $this->belongsTo(GeoWard::class, 'ward_code', 'code');
    }

    public function country()
    {
        return $this->belongsTo(GeoCountry::class, 'country_code', 'code');
    }

    /**
     * Get formatted address
     */
    public function getAddressAttribute()
    {
        $parts = array_filter([
            $this->street,
            $this->ward,
            $this->district,
            $this->city,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }
}

