<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'key_code',
        'name',
    ];

    public $timestamps = true;

    /**
     * Get the users that have this role through organization_users.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users', 'role_id', 'user_id')
            ->withPivot('organization_id', 'status', 'deleted_at', 'deleted_by')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }
}
