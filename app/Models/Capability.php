<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_code',
        'name',
        'description',
        'category',
        'display_order',
    ];

    /**
     * Get organization users with this capability
     */
    public function organizationUsers()
    {
        return $this->belongsToMany(
            OrganizationUser::class,
            'organization_user_capabilities',
            'capability_id',
            'organization_user_id'
        )
        ->withPivot(['granted', 'granted_by', 'granted_at', 'revoked_at'])
        ->withTimestamps();
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}

