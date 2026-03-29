<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaseResident extends Model
{
    protected $table = 'lease_residents';

    public $timestamps = false;

    protected $fillable = [
        'lease_id',
        'user_id',
        'name',
        'phone',
        'id_number',
        'note',
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

