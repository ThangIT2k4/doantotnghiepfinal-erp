<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\SequenceGenerator;
use App\Models\PaymentMethod;

class CashOutflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'payment_method_id',
        'paid_at',
        'status',
        'transaction_ref',
        'note',
        'company_invoice_id',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Constants for payment methods
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_SEPAY = 'sepay';
    const PAYMENT_METHOD_OTHER = 'other';

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    // Relationships
    /**
     * Get organization through company_invoice
     */
    public function organization()
    {
        if ($this->company_invoice_id && $this->companyInvoice) {
            return $this->companyInvoice->organization();
        }
        return null;
    }

    /**
     * Get organization_id attribute
     */
    public function getOrganizationIdAttribute()
    {
        if ($this->company_invoice_id) {
            if ($this->relationLoaded('companyInvoice') && $this->companyInvoice) {
                return $this->companyInvoice->organization_id;
            }
            $this->load('companyInvoice');
            if ($this->companyInvoice) {
                return $this->companyInvoice->organization_id;
            }
        }
        return null;
    }

    /**
     * Get vendor_id attribute from company_invoice
     */
    public function getVendorIdAttribute()
    {
        if ($this->company_invoice_id) {
            if ($this->relationLoaded('companyInvoice') && $this->companyInvoice) {
                return $this->companyInvoice->vendor_id;
            }
            $this->load('companyInvoice');
            if ($this->companyInvoice) {
                return $this->companyInvoice->vendor_id;
            }
        }
        return null;
    }

    /**
     * Get payer_user_id attribute from company_invoice created_by
     */
    public function getPayerUserIdAttribute()
    {
        if ($this->company_invoice_id) {
            if ($this->relationLoaded('companyInvoice') && $this->companyInvoice) {
                return $this->companyInvoice->created_by;
            }
            $this->load('companyInvoice');
            if ($this->companyInvoice) {
                return $this->companyInvoice->created_by;
            }
        }
        return null;
    }

    public function vendor()
    {
        if ($this->company_invoice_id && $this->companyInvoice) {
            return $this->companyInvoice->vendor();
        }
        return null;
    }

    public function payerUser()
    {
        if ($this->company_invoice_id && $this->companyInvoice) {
            return $this->companyInvoice->creator();
        }
        return null;
    }


    public function companyInvoice(): BelongsTo
    {
        return $this->belongsTo(CompanyInvoice::class, 'company_invoice_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get documents for this cash outflow (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get receipt images
     */
    public function receiptImages()
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
     * Get the payment method for the cash outflow.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    // Scopes
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->whereHas('companyInvoice', function($subQ) use ($organizationId) {
            $subQ->where('organization_id', $organizationId);
        });
    }


    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }


    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('company_invoice_id', $invoiceId);
    }

    /**
     * Create cash outflow from company invoice
     */
    public static function createFromCompanyInvoice(CompanyInvoice $invoice, $data = [])
    {
        $cashOutflow = new self();
        
        // Auto-generate transaction_ref if not provided and not a document URL
        if (empty($data['transaction_ref']) || (isset($data['transaction_ref']) && strpos($data['transaction_ref'], '/storage/') === false)) {
            if (empty($data['transaction_ref'])) {
                $paymentMethodId = $data['payment_method_id'] ?? null;
                $data['transaction_ref'] = $cashOutflow->generateTransactionRef($invoice->organization_id, $paymentMethodId);
            }
        }
        
        return self::create(array_merge([
            'amount' => $invoice->total_amount,
            'paid_at' => now(),
            'status' => self::STATUS_SUCCESS,
            'company_invoice_id' => $invoice->id,
            'note' => "Thanh toán hóa đơn {$invoice->invoice_no}",
        ], $data));
    }

    /**
     * Generate transaction reference with format: CO-{key_code}-{org_id}-{year}-{month}-{sequence}
     * 
     * @param int|null $organizationId Organization ID (optional, will use instance organization_id)
     * @param int|null $paymentMethodId Payment method ID (optional, will use instance payment_method_id)
     * @return string Transaction reference
     * @throws \Exception If organization ID cannot be determined
     */
    public function generateTransactionRef($organizationId = null, $paymentMethodId = null)
    {
        if (!$organizationId) {
            // Try to get from company_invoice
            if ($this->company_invoice_id && $this->companyInvoice) {
                $organizationId = $this->companyInvoice->organization_id;
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
        } elseif ($this->payment_method_id) {
            $paymentMethod = $this->paymentMethod;
            $paymentMethodKeyCode = $paymentMethod ? $paymentMethod->key_code : null;
        }
        
        $year = date('Y');
        $month = date('m');
        $sequenceKey = SequenceGenerator::buildKey('cash_outflow', $organizationId, $year, $month, $paymentMethodKeyCode);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month, $paymentMethodKeyCode) {
            // Find max from existing cash outflows
            // Support both old format and new format: CO-{key_code}-{org_id}-{year}-{month}-{sequence}
            $pattern = $paymentMethodKeyCode 
                ? "CO-{$paymentMethodKeyCode}-%-{$year}-{$month}-%"
                : "CO-%-{$year}-{$month}-%";
            
            $existingRefs = self::withTrashed()
                ->where(function($query) use ($year, $month, $paymentMethodKeyCode) {
                    if ($paymentMethodKeyCode) {
                        $query->where('transaction_ref', 'like', "CO-{$paymentMethodKeyCode}-%-{$year}-{$month}-%")
                              ->orWhere('transaction_ref', 'like', "CO-%-{$year}-{$month}-%");
                    } else {
                        $query->where('transaction_ref', 'like', "CO-%-{$year}-{$month}-%")
                              ->orWhere('transaction_ref', 'like', "CO-{$year}-{$month}-%");
                    }
                })
                ->whereNotNull('transaction_ref')
                ->where('transaction_ref', 'not like', '/storage/%') // Exclude document URLs
                ->whereHas('companyInvoice', function($subQ) use ($organizationId) {
                    $subQ->where('organization_id', $organizationId);
                })
                ->pluck('transaction_ref')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingRefs as $ref) {
                // Parse format: "CO-{key_code}-{org_id}-{year}-{month}-{sequence}" or "CO-{org_id}-{year}-{month}-{sequence}"
                $parts = explode('-', $ref);
                $number = null;
                if (count($parts) >= 6) {
                    // New format with key_code: CO-{key_code}-{org_id}-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', end($parts));
                } elseif (count($parts) >= 5) {
                    // Format without key_code: CO-{org_id}-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', end($parts));
                } elseif (count($parts) >= 3) {
                    // Old format: CO-{year}-{month}-{sequence}
                    $number = (int) preg_replace('/[^0-9]/', '', $parts[2] ?? '');
                }
                if ($number !== null && $number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
            return $maxNumber;
        });
        
        // Generate transaction reference with new format: CO-{key_code}-{org_id}-{year}-{month}-{sequence}
        $prefix = $paymentMethodKeyCode ? "CO-{$paymentMethodKeyCode}-{$organizationId}" : "CO-{$organizationId}";
        $transactionRef = "{$prefix}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records and document URLs)
        $exists = self::where('transaction_ref', $transactionRef)
            ->whereNull('deleted_at')
            ->whereHas('companyInvoice', function($subQ) use ($organizationId) {
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
                
                $transactionRef = "{$prefix}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
                $exists = self::where('transaction_ref', $transactionRef)
                    ->whereNull('deleted_at')
                    ->whereHas('companyInvoice', function($subQ) use ($organizationId) {
                        $subQ->where('organization_id', $organizationId);
                    })
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique cash outflow transaction reference after retries, using timestamp fallback');
                $transactionRef = "{$prefix}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
            }
        }
        
        return $transactionRef;
    }

}