<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasSoftDeletesWithUser;

class PropertyType extends Model
{
    use HasSoftDeletesWithUser;
    protected $table = 'property_types';

    protected $fillable = [
        'organization_id',
        'key_code',
        'name',
        'icon',
        'description',
        'status',
        'deleted_by',
    ];

    /**
     * Relationship: PropertyType belongs to an organization (nullable for global property types)
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Scope: Get property types available for a specific organization
     * Includes both organization-specific property types and global property types (organization_id = NULL)
     * 
     * @param Builder $query
     * @param int|null $organizationId
     * @return Builder
     */
    public function scopeForOrganization(Builder $query, $organizationId)
    {
        return $query->where(function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->orWhereNull('organization_id');
        });
    }

    /**
     * Scope: Get only global property types (created by system admin)
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeGlobal(Builder $query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Scope: Get only organization-specific property types
     * 
     * @param Builder $query
     * @param int|null $organizationId
     * @return Builder
     */
    public function scopeOrganizationOnly(Builder $query, $organizationId = null)
    {
        if ($organizationId) {
            return $query->where('organization_id', $organizationId);
        }
        return $query->whereNotNull('organization_id');
    }

    /**
     * Check if property type is global (available to all organizations)
     * 
     * @return bool
     */
    public function isGlobal()
    {
        return is_null($this->organization_id);
    }

    /**
     * Check if property type belongs to a specific organization
     * 
     * @return bool
     */
    public function isOrganizationSpecific()
    {
        return !is_null($this->organization_id);
    }
}

