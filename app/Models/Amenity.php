<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;

class Amenity extends Model
{
    use HasSoftDeletesWithUser;
    protected $table = 'amenities';

    public $timestamps = true;

    protected $fillable = [
        'key_code',
        'name',
        'category',
        'deleted_by',
    ];

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_amenities', 'amenity_id', 'unit_id');
    }
}

