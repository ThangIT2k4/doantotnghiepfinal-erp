<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoWard extends Model
{
    protected $table = 'geo_wards';
    
    protected $fillable = [
        'code',
        'name',
        'district_code',
        'type',
        'status'
    ];

    public function district()
    {
        return $this->belongsTo(GeoDistrict::class, 'district_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'ward_code', 'code');
    }
}
