<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaseServiceSet extends Model
{
    use SoftDeletes;

    protected $table = 'lease_service_sets';

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Get the organization that owns this lease service set
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the organization that owns this lease service set
     * (already defined above, keeping for clarity)
     */


    /**
     * Get all properties using this lease service set
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'lease_services_id');
    }

    /**
     * Get all leases using this lease service set
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'lease_services_id');
    }

    /**
     * Get all items (services) in this set
     */
    public function items(): HasMany
    {
        return $this->hasMany(LeaseServiceSetItem::class, 'lease_service_set_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Get all services in this set (through items)
     */
    public function services()
    {
        return $this->hasManyThrough(
            Service::class,
            LeaseServiceSetItem::class,
            'lease_service_set_id',
            'id',
            'id',
            'service_id'
        );
    }

    /**
     * Scope: Get system-wide lease service sets (not organization-specific)
     */
    public function scopeSystemWide($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Scope: Get organization-specific lease service sets
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Find or create matching lease service set based on services
     * 
     * @param array $services Array of services with service_id and price
     * @param int $organizationId Organization ID
     * @param string|null $name Optional name for new set
     * @param string|null $description Optional description for new set
     * @return int Lease service set ID
     */
    public static function findOrCreateMatching($services, $organizationId, $name = null, $description = null)
    {
        if (empty($services) || !is_array($services)) {
            return null;
        }

        // Build a unique key based on services
        $serviceIds = collect($services)
            ->pluck('service_id')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
        
        if (empty($serviceIds)) {
            return null;
        }
        
        $servicePrices = collect($services)
            ->filter(function($service) {
                return !empty($service['service_id']);
            })
            ->mapWithKeys(function($service) {
                return [$service['service_id'] => floatval($service['price'] ?? 0)];
            })
            ->toArray();
        
        // Try to find existing lease service set with same services
        $existingSet = static::where('organization_id', $organizationId)
            ->whereHas('items', function($q) use ($serviceIds) {
                $q->whereIn('service_id', $serviceIds);
            }, '=', count($serviceIds))
            ->with('items')
            ->get()
            ->first(function($set) use ($serviceIds, $servicePrices) {
                $setServiceIds = $set->items->pluck('service_id')->sort()->values()->toArray();
                if ($setServiceIds !== $serviceIds) {
                    return false;
                }
                
                // Check if prices match (with tolerance for floating point)
                foreach ($set->items as $item) {
                    if (!isset($servicePrices[$item->service_id]) || 
                        abs($item->price - $servicePrices[$item->service_id]) > 0.01) {
                        return false;
                    }
                }
                
                return true;
            });
        
        if ($existingSet) {
            return $existingSet->id;
        }
        
        // Create new lease service set
        $leaseServiceSet = static::create([
            'organization_id' => $organizationId,
            'name' => $name ?? 'Dịch vụ tùy chỉnh #' . time(),
            'description' => $description ?? 'Dịch vụ tùy chỉnh',
            'is_default' => false,
        ]);

        // Add services to the set
        foreach ($services as $index => $serviceData) {
            if (!empty($serviceData['service_id']) && isset($serviceData['price'])) {
                \App\Models\LeaseServiceSetItem::create([
                    'lease_service_set_id' => $leaseServiceSet->id,
                    'service_id' => $serviceData['service_id'],
                    'price' => floatval($serviceData['price']),
                    'sort_order' => $index,
                ]);
            }
        }

        return $leaseServiceSet->id;
    }

}
