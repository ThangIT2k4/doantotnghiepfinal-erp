<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryContract extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'salary_contracts';

    protected $fillable = [
        'organization_id',
        'user_id',
        'base_salary',
        'currency',
        'pay_cycle',
        'pay_day',
        'allowances_json',
        'kpi_target_json',
        'effective_from',
        'effective_to',
        'status',
        'deleted_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'allowances_json' => 'array',
        'kpi_target_json' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get the organization that owns the salary contract.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that owns the salary contract.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the total compensation (base + allowances).
     */
    public function getTotalCompensationAttribute()
    {
        $total = $this->base_salary;
        
        if ($this->allowances_json) {
            foreach ($this->allowances_json as $allowance) {
                $total += $allowance;
            }
        }
        
        return $total;
    }
}

