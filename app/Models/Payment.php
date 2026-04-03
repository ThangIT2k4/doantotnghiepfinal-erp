<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\SequenceGenerator;
use App\Models\PaymentMethod;

class Payment extends Model
{
    use HasFactory, SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'method_id',
        'amount',
        'paid_at',
        'txn_ref',
        'status',
        'payer_user_id',
        'lead_id',
        'note',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Constants for payment status
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';


    /**
     * Get the invoice that owns the payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment method for the payment.
     */
    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    /**
     * Get the payer user for the payment.
     */
    public function payerUser()
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    /**
     * Get the lead for the payment.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Scope a query to only include payments of a given status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include payments for a specific organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->whereHas('invoice.lease', function($q) use ($organizationId) {
            $q->withTrashed()->whereHas('property', function($pq) use ($organizationId) {
                $pq->where('organization_id', $organizationId);
            });
        });
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'Chờ thanh toán',
            self::STATUS_SUCCESS => 'Thành công',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];

        return $statuses[$this->status] ?? 'Không xác định';
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REFUNDED => 'info',
        ];

        return $classes[$this->status] ?? 'secondary';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.') . ' VND';
    }

    /**
     * Get documents for this payment (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get payment proof images
     */
    public function paymentProofImages()
    {
        return $this->documents()
            ->where('document_type', 'image')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * Get image (backward compatibility)
     */
    public function getImageAttribute()
    {
        // Nếu cột image vẫn còn trong database, trả về nó
        if (isset($this->attributes['image'])) {
            return $this->attributes['image'];
        }

        // Lấy từ documents trực tiếp
        $primaryImage = $this->documents()
            ->where('document_type', 'image')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        return $primaryImage ? $primaryImage->file_url : null;
    }

    /**
     * Generate transaction reference with format: PM-{key_code}-{org_id}-{year}-{month}-{sequence}
     * 
     * @param int|null $organizationId Organization ID (optional, will try to get from invoice)
     * @param int|null $paymentMethodId Payment method ID (optional, will use instance method_id)
     * @return string Transaction reference
     * @throws \Exception If organization ID cannot be determined
     */
    public function generateTxnRef($organizationId = null, $paymentMethodId = null)
    {
        // Get organization ID from invoice if not provided
        if (!$organizationId && $this->invoice_id) {
            $invoice = $this->invoice;
            if ($invoice && $invoice->lease_id) {
                $lease = $invoice->lease;
                if ($lease && $lease->property_id) {
                    $property = $lease->property;
                    if ($property) {
                        $organizationId = $property->organization_id;
                    }
                }
            }
        }
        
        if (!$organizationId) {
            throw new \Exception('Organization ID is required to generate transaction reference');
        }
        
        // Get payment method key code
        $paymentMethodKeyCode = null;
        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::find($paymentMethodId);
            $paymentMethodKeyCode = $paymentMethod ? $paymentMethod->key_code : null;
        } elseif ($this->method_id) {
            $paymentMethod = $this->method;
            $paymentMethodKeyCode = $paymentMethod ? $paymentMethod->key_code : null;
        }
        
        $year = date('Y');
        $month = date('m');
        $sequenceKey = SequenceGenerator::buildKey('payment', $organizationId, $year, $month, $paymentMethodKeyCode);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month, $paymentMethodKeyCode) {
            // Find max from existing payments
            // Support both old format and new format: PM-{key_code}-{org_id}-{year}-{month}-{sequence}
            $existingRefs = self::withTrashed()
                ->where(function($query) use ($year, $month, $paymentMethodKeyCode) {
                    if ($paymentMethodKeyCode) {
                        $query->where('txn_ref', 'like', "PM-{$paymentMethodKeyCode}-%-{$year}-{$month}-%")
                              ->orWhere('txn_ref', 'like', "PM-%-{$year}-{$month}-%");
                    } else {
                        $query->where('txn_ref', 'like', "PM-%-{$year}-{$month}-%")
                              ->orWhere('txn_ref', 'like', "PM-{$year}-{$month}-%");
                    }
                })
                ->whereNotNull('txn_ref')
                ->whereHas('invoice.lease.property', function($subQ) use ($organizationId) {
                    $subQ->where('organization_id', $organizationId);
                })
                ->pluck('txn_ref')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingRefs as $ref) {
                // Parse format: "PM-{key_code}-{org_id}-{year}-{month}-{sequence}" or "PM-{org_id}-{year}-{month}-{sequence}"
                $parts = explode('-', $ref);
                $number = null;
                if (count($parts) >= 6) {
                    // New format with key_code: PM-{key_code}-{org_id}-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', end($parts));
                } elseif (count($parts) >= 5) {
                    // Format without key_code: PM-{org_id}-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', end($parts));
                } elseif (count($parts) >= 3) {
                    // Old format: PM-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', $parts[2] ?? '');
                }
                if ($number !== null && $number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
            return $maxNumber;
        });
        
        // Generate transaction reference with new format: PM-{key_code}-{org_id}-{year}-{month}-{sequence}
        $prefix = $paymentMethodKeyCode ? "PM-{$paymentMethodKeyCode}-{$organizationId}" : "PM-{$organizationId}";
        $txnRef = "{$prefix}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records)
        $exists = self::where('txn_ref', $txnRef)
            ->whereNull('deleted_at')
            ->whereHas('invoice.lease.property', function($subQ) use ($organizationId) {
                $subQ->where('organization_id', $organizationId);
            })
            ->exists();
        
        if ($exists) {
            // If exists, retry with incremented sequence (max 10 retries)
            $maxRetries = 10;
            $retries = 0;
            
            while ($exists && $retries < $maxRetries) {
                $newSequence++;
                SequenceGenerator::reset($sequenceKey, $newSequence);
                
                $txnRef = "{$prefix}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
                $exists = self::where('txn_ref', $txnRef)
                    ->whereNull('deleted_at')
                    ->whereHas('invoice.lease.property', function($subQ) use ($organizationId) {
                        $subQ->where('organization_id', $organizationId);
                    })
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique payment transaction reference after retries, using timestamp fallback');
                $txnRef = "{$prefix}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
            }
        }
        
        return $txnRef;
    }
}

