<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo organization()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany events()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany splits()
 */
class CommissionPolicy extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'commission_policies';

    protected $fillable = [
        'organization_id',
        'code',
        'title',
        'trigger_event',
        'basis',
        'calc_type',
        'percent_value',
        'flat_amount',
        'apply_limit_months',
        'min_amount',
        'cap_amount',
        'filters_json',
        'active',
        'deleted_by',
    ];

    protected $casts = [
        'percent_value' => 'decimal:2',
        'flat_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'cap_amount' => 'decimal:2',
        'filters_json' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Get the organization that owns the commission policy.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the commission events for the policy.
     */
    public function events()
    {
        return $this->hasMany(CommissionEvent::class, 'policy_id');
    }


    /**
     * Scope a query to only include active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Calculate commission amount based on base amount.
     */
    public function calculateCommission($baseAmount)
    {
        switch ($this->calc_type) {
            case 'percent':
                $commission = $baseAmount * ($this->percent_value / 100);
                break;
            case 'flat':
                $commission = $this->flat_amount;
                break;
            case 'tiered':
                // Implement tiered calculation logic here
                $commission = 0;
                break;
            default:
                $commission = 0;
        }

        // Apply min and cap
        if ($this->min_amount && $commission < $this->min_amount) {
            $commission = $this->min_amount;
        }
        if ($this->cap_amount && $commission > $this->cap_amount) {
            $commission = $this->cap_amount;
        }

        return $commission;
    }
}

