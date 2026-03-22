<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasSoftDeletesWithUser;

class Service extends Model
{
    use HasSoftDeletesWithUser;

    protected $fillable = [
        'organization_id',
        'key_code',
        'name',
        'pricing_type',
        'unit_label',
        'description',
    ];

    /**
     * Relationship: Service belongs to an organization (nullable for global services)
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function meters()
    {
        return $this->hasMany(Meter::class);
    }

    /**
     * @deprecated REMOVED - Use leaseServiceSetItems() instead. LeaseService model and table have been removed.
     */
    // public function leaseServices()
    // {
    //     return $this->hasMany(LeaseService::class);
    // }
    
    /**
     * Get lease service set items for this service
     */
    public function leaseServiceSetItems()
    {
        return $this->hasMany(LeaseServiceSetItem::class, 'service_id');
    }

    /**
     * Scope: Get services available for a specific organization
     * Includes both organization-specific services and global services (organization_id = NULL)
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
     * Scope: Get only global services (created by system admin)
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeGlobal(Builder $query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Scope: Get only organization-specific services
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
     * Check if service is global (available to all organizations)
     * 
     * @return bool
     */
    public function isGlobal()
    {
        return is_null($this->organization_id);
    }

    /**
     * Check if service belongs to a specific organization
     * 
     * @return bool
     */
    public function isOrganizationSpecific()
    {
        return !is_null($this->organization_id);
    }
}