<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'key_code',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get notifications for this channel.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'channel_id');
    }

    /**
     * Scope for active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get channel by key code.
     */
    public static function getByKeyCode(string $keyCode): ?self
    {
        return static::where('key_code', $keyCode)->first();
    }
}
