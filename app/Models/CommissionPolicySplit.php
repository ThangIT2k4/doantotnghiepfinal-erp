<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionPolicySplit extends Model
{
    protected $table = 'commission_policy_splits';

    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'role_key',
        'percent_share',
    ];

    protected $casts = [
        'percent_share' => 'decimal:2',
    ];

    /**
     * Get the policy that owns the split.
     */
    public function policy()
    {
        return $this->belongsTo(CommissionPolicy::class, 'policy_id');
    }
}

