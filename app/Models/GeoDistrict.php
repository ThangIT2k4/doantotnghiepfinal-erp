<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoDistrict extends Model
{
    protected $table = 'geo_districts';
    
    protected $fillable = [
        'code',
        'name',
        'province_code',
        'type',
        'status'
    ];

    public function province()
    {
        return $this->belongsTo(GeoProvince::class, 'province_code', 'code');
    }

    public function wards()
    {
        return $this->hasMany(GeoWard::class, 'district_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'district_code', 'code');
    }
}
