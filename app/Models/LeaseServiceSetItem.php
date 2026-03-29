<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseServiceSetItem extends Model
{
    protected $table = 'lease_service_set_items';

    protected $fillable = [
        'lease_service_set_id',
        'service_id',
        'price',
        'meta_json',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'meta_json' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get the lease service set that owns this item
     */
    public function leaseServiceSet(): BelongsTo
    {
        return $this->belongsTo(LeaseServiceSet::class, 'lease_service_set_id');
    }

    /**
     * Get the service for this item
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
