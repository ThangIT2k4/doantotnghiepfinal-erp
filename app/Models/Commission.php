<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSoftDeletesWithUser;

class Commission extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $fillable = [
        'organization_id',
        'contract_id',
        'user_id',
        'type',
        'amount',
        'percentage',
        'status',
        'paid_date',
        'notes',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'paid_date' => 'date',
    ];

    /**
     * Get the organization that owns the commission.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the contract that owns the commission.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user that owns the commission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent for the commission (alias for user).
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the lease for the commission (through contract).
     */
    public function lease()
    {
        return $this->hasOneThrough(Lease::class, Contract::class, 'id', 'id', 'contract_id', 'id');
    }

    /**
     * Scope a query to only include pending commissions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid commissions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include cancelled commissions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
