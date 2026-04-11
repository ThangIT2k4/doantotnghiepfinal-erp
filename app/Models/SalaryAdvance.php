<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToOrganization;
use App\Traits\HasSoftDeletesWithUser;

class SalaryAdvance extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization, HasSoftDeletesWithUser;

    protected $fillable = [
        'organization_id',
        'user_id',
        'amount',
        'currency',
        'advance_date',
        'expected_repayment_date',
        'reason',
        'status',
        'repaid_amount',
        'remaining_amount',
        'repayment_method',
        'installment_months',
        'monthly_deduction',
        'note',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'deleted_by',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'expected_repayment_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount' => 'decimal:2',
        'repaid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'monthly_deduction' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRepaid($query)
    {
        return $query->where('status', 'repaid');
    }

    public function scopePartiallyRepaid($query)
    {
        return $query->where('status', 'partially_repaid');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPayrollDeduction($query)
    {
        return $query->where('repayment_method', 'payroll_deduction')
                    ->whereIn('status', ['approved', 'partially_repaid']);
    }

    // Accessors & Mutators
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Đã từ chối',
            'repaid' => 'Đã hoàn trả',
            'partially_repaid' => 'Hoàn trả một phần'
        ];

        return $labels[$this->status] ?? 'Không xác định';
    }

    public function getRepaymentMethodLabelAttribute()
    {
        $labels = [
            'payroll_deduction' => 'Trừ lương',
            'direct_payment' => 'Thanh toán trực tiếp',
            'installment' => 'Trả góp'
        ];

        return $labels[$this->repayment_method] ?? 'Không xác định';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'repaid' => 'info',
            'partially_repaid' => 'primary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Methods
    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    public function canBeRejected()
    {
        return $this->status === 'pending';
    }

    public function canBeRepaid()
    {
        return in_array($this->status, ['approved', 'partially_repaid']);
    }

    public function canBeDeleted()
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    public function approve($approvedBy)
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function reject($rejectedBy, $reason = null)
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public function addRepayment($amount)
    {
        if (!$this->canBeRepaid()) {
            return false;
        }

        $newRepaidAmount = $this->repaid_amount + $amount;
        $newRemainingAmount = $this->amount - $newRepaidAmount;

        $status = $newRemainingAmount <= 0 ? 'repaid' : 'partially_repaid';

        $this->update([
            'repaid_amount' => $newRepaidAmount,
            'remaining_amount' => max(0, $newRemainingAmount),
            'status' => $status,
        ]);

        return true;
    }

    public function calculateMonthlyDeduction()
    {
        try {
            if ($this->repayment_method !== 'payroll_deduction') {
                return 0;
            }

            // Check if remaining_amount is valid
            if (!$this->remaining_amount || $this->remaining_amount <= 0) {
                return 0;
            }

            if ($this->monthly_deduction && $this->monthly_deduction > 0) {
                return min($this->monthly_deduction, $this->remaining_amount);
            }

            // Auto calculate based on remaining amount and months
            if (!$this->expected_repayment_date) {
                \Illuminate\Support\Facades\Log::warning('SalaryAdvance has no expected_repayment_date', [
                    'salary_advance_id' => $this->id,
                    'user_id' => $this->user_id,
                    'remaining_amount' => $this->remaining_amount
                ]);
                // Default: return remaining amount (pay all at once)
                return $this->remaining_amount;
            }

            $monthsRemaining = \Carbon\Carbon::parse($this->expected_repayment_date)->diffInMonths(now());
            if ($monthsRemaining <= 0) {
                return $this->remaining_amount; // Pay all remaining
            }

            return round($this->remaining_amount / $monthsRemaining, 2);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error calculating monthly deduction', [
                'salary_advance_id' => $this->id ?? null,
                'user_id' => $this->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return 0 to not block payslip generation
            return 0;
        }
    }
}