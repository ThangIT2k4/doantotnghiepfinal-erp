<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSoftDeletesWithUser;

class PayrollPayslip extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'payroll_payslips';

    protected $fillable = [
        'payroll_cycle_id',
        'user_id',
        'gross_amount',
        'deduction_amount',
        'net_amount',
        'status',
        'paid_at',
        'payment_method',
        'note',
        'deleted_by',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the payroll cycle that owns the payslip.
     */
    public function payrollCycle()
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }

    /**
     * Get the user that owns the payslip.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for this payslip.
     */
    public function items()
    {
        return $this->hasMany(PayrollPayslipItem::class, 'payroll_payslip_id');
    }
}

