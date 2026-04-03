<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;
use App\Helpers\SequenceGenerator;

class DepositRefund extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, BelongsToOrganization;

    protected $table = 'deposit_refunds';

    protected $fillable = [
        'lease_id',
        'organization_id',
        'unit_id',
        'tenant_id',
        'agent_id',
        'original_deposit_amount',
        'deducted_amount',
        'refund_amount',
        'status',
        'refund_method',
        'refund_reference',
        'notes',
        'deduction_details',
        'approved_at',
        'paid_at',
        'approved_by',
        'paid_by',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'original_deposit_amount' => 'decimal:2',
        'deducted_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'deduction_details' => 'array',
    ];

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    // Constants for refund method
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_WALLET = 'wallet';

    /**
     * Get the lease that owns the deposit refund.
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the unit that owns the deposit refund.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the tenant user for the deposit refund.
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Get the agent user for the deposit refund.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the user who approved the refund.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who paid the refund.
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the user who created the refund.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get refunds by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get refunds for a specific organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to get refunds for a specific lease.
     */
    public function scopeForLease($query, $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Check if refund can be approved.
     */
    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if refund can be paid.
     */
    public function canBePaid()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if refund can be cancelled.
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Chờ phê duyệt',
            self::STATUS_APPROVED => 'Đã phê duyệt',
            self::STATUS_PAID => 'Đã thanh toán',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => $this->status,
        };
    }

    /**
     * Get refund method label.
     */
    public function getRefundMethodLabelAttribute()
    {
        return match($this->refund_method) {
            self::METHOD_CASH => 'Tiền mặt',
            self::METHOD_BANK_TRANSFER => 'Chuyển khoản',
            self::METHOD_WALLET => 'Ví điện tử',
            default => $this->refund_method,
        };
    }

    /**
     * Generate refund reference with format: RF-{org_id}-{year}-{month}-{sequence}
     * 
     * @param int|null $organizationId Organization ID (optional, will use instance organization_id)
     * @return string Refund reference
     * @throws \Exception If organization ID cannot be determined
     */
    public function generateRefundReference($organizationId = null)
    {
        $organizationId = $organizationId ?? $this->organization_id;
        
        if (!$organizationId) {
            throw new \Exception('Organization ID is required to generate refund reference');
        }
        
        $year = date('Y');
        $month = date('m');
        $sequenceKey = SequenceGenerator::buildKey('deposit_refund', $organizationId, $year, $month);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month) {
            // Find max from existing deposit refunds
            // Support both old format (if any) and new format (RF-{org_id}-{year}-{month}-{sequence})
            $existingRefs = self::withTrashed()
                ->where('organization_id', $organizationId)
                ->where(function($query) use ($year, $month) {
                    $query->where('refund_reference', 'like', "RF-%-{$year}-{$month}-%")
                          ->orWhere('refund_reference', 'like', "RF-{$year}-{$month}-%");
                })
                ->whereNotNull('refund_reference')
                ->pluck('refund_reference')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingRefs as $ref) {
                // Parse new format: "RF-1-2025-11-000123" => 123
                // Parse old format: "RF-2025-11-000123" => 123
                $parts = explode('-', $ref);
                if (count($parts) >= 3) {
                    // New format: RF-{org_id}-{year}-{month}-{sequence}
                    if (count($parts) >= 5) {
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[4]);
                    } else {
                        // Old format: RF-{year}-{month}-{sequence}
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[2] ?? '');
                    }
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            return $maxNumber;
        });
        
        // Generate refund reference with new format: RF-{org_id}-{year}-{month}-{sequence}
        $refundRef = "RF-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records)
        $exists = self::where('refund_reference', $refundRef)
            ->whereNull('deleted_at')
            ->where('organization_id', $organizationId)
            ->exists();
        
        if ($exists) {
            // If exists, retry with incremented sequence (max 10 retries)
            $maxRetries = 10;
            $retries = 0;
            
            while ($exists && $retries < $maxRetries) {
                $newSequence++;
                SequenceGenerator::reset($sequenceKey, $newSequence);
                
                $refundRef = "RF-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
                $exists = self::where('refund_reference', $refundRef)
                    ->whereNull('deleted_at')
                    ->where('organization_id', $organizationId)
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique deposit refund reference after retries, using timestamp fallback');
                $refundRef = "RF-{$organizationId}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
            }
        }
        
        return $refundRef;
    }
}
