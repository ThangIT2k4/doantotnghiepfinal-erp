<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoCountry extends Model
{
    protected $table = 'geo_countries';
    
    protected $fillable = [
        'code',
        'name',
        'status'
    ];

    public function provinces()
    {
        return $this->hasMany(GeoProvince::class, 'country_code', 'code');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'country_code', 'code');
    }
}
