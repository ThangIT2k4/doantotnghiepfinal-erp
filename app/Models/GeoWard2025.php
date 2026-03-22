<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoWard2025 extends Model
{
    protected $table = 'geo_wards_2025';

    protected $fillable = [
        'code',
        'province_code',
        'name',
        'name_local',
        'kind',
    ];

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships
    public function province()
    {
        return $this->belongsTo(GeoProvince2025::class, 'province_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location2025::class, 'ward_code', 'code');
    }
}
