<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoProvince extends Model
{
    protected $table = 'geo_provinces';
    
    protected $fillable = [
        'code',
        'name',
        'country_code',
        'type',
        'status'
    ];

    public function country()
    {
        return $this->belongsTo(GeoCountry::class, 'country_code', 'code');
    }

    public function districts()
    {
        return $this->hasMany(GeoDistrict::class, 'province_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'province_code', 'code');
    }
}
