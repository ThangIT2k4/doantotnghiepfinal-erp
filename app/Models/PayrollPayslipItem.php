<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPayslipItem extends Model
{
    protected $table = 'payroll_payslip_items';

    protected $fillable = [
        'payroll_payslip_id',
        'item_type',
        'item_name',
        'sign',
        'amount',
        'ref_type',
        'ref_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sign' => 'integer',
    ];

    /**
     * Item type constants
     */
    const TYPE_BASIC_SALARY = 'basic_salary';
    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_COMMISSION = 'commission';
    const TYPE_SALARY_ADVANCE_DEDUCTION = 'salary_advance_deduction';
    const TYPE_OTHER = 'other';

    /**
     * Get the payroll payslip that owns this item.
     */
    public function payrollPayslip()
    {
        return $this->belongsTo(PayrollPayslip::class, 'payroll_payslip_id');
    }

    /**
     * Get the reference object (polymorphic relationship)
     */
    public function ref()
    {
        return $this->morphTo('ref', 'ref_type', 'ref_id');
    }
}
