<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'unit_id',
        'service_id',
        'serial_no',
        'installed_at',
        'status',
        'deleted_by'
    ];

    protected $casts = [
        'installed_at' => 'date',
        'status' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function readings()
    {
        return $this->hasMany(MeterReading::class);
    }

    public function latestReading()
    {
        return $this->hasOne(MeterReading::class)->latest('reading_date');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
