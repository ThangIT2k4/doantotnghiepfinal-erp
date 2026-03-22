<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoStreet extends Model
{
    protected $table = 'geo_streets';

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'ward_code',
        'name',
        'name_local',
    ];

    public function ward()
    {
        return $this->belongsTo(GeoWard::class, 'ward_code', 'code');
    }
}