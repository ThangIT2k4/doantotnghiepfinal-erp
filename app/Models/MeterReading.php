<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    use HasFactory;

    protected $table = 'meter_readings';
    
    public $timestamps = false;

    protected $fillable = [
        'meter_id',
        'reading_date',
        'value',
        'image_url',
        'taken_by',
        'note',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'value' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function meter()
    {
        return $this->belongsTo(Meter::class);
    }

    public function takenBy()
    {
        return $this->belongsTo(User::class, 'taken_by');
    }
}
