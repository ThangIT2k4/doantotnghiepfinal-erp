<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo policy()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo organization()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo agent()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo lease()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo unit()
 */
class CommissionEvent extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'commission_events';

    protected $fillable = [
        'policy_id',
        'organization_id',
        'trigger_event',
        'ref_type',
        'ref_id',
        'lease_id',
        'unit_id',
        'agent_id',
        'occurred_at',
        'amount_base',
        'commission_total',
        'status',
        'deleted_by',
    ];

    protected $casts = [
        'amount_base' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the policy that owns the commission event.
     */
    public function policy()
    {
        return $this->belongsTo(CommissionPolicy::class, 'policy_id');
    }

    /**
     * Get the organization that owns the commission event.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the agent for the commission event.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the user for the commission event (alias for agent).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the lease for the commission event.
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }


    /**
     * Get the unit for the commission event.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Invoice relationship removed - commission events no longer create invoices
}

