<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoProvince2025 extends Model
{
    protected $table = 'geo_provinces_2025';

    protected $fillable = [
        'code',
        'country_code',
        'name',
        'name_local',
        'kind',
    ];

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships
    public function country()
    {
        return $this->belongsTo(GeoCountry::class, 'country_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location2025::class, 'province_code', 'code');
    }
}
