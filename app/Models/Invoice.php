<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SequenceGenerator;

class Invoice extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, BelongsToOrganization;

    protected $table = 'invoices';

    protected $fillable = [
        'organization_id',
        'is_auto_created',
        'lease_id',
        'booking_deposit_id',
        'invoice_no',
        'invoice_type',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'note',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_auto_created' => 'boolean',
    ];

    /**
     * Invoice type constants
     */
    const TYPE_MONTHLY_RENT = 'monthly_rent';
    const TYPE_FIRST_INVOICE = 'first_invoice';
    const TYPE_BOOKING_DEPOSIT = 'booking_deposit';
    const TYPE_OTHER = 'other';

    /**
     * Get all available invoice types
     */
    public static function getInvoiceTypes()
    {
        return [
            self::TYPE_MONTHLY_RENT => 'Tiền thuê hàng tháng',
            self::TYPE_FIRST_INVOICE => 'Hoá đơn đầu',
            self::TYPE_BOOKING_DEPOSIT => 'Hoá đơn đặt cọc',
            self::TYPE_OTHER => 'Khác',
        ];
    }

    /**
     * Get invoice type label
     */
    public function getInvoiceTypeLabel()
    {
        $types = self::getInvoiceTypes();
        return $types[$this->invoice_type] ?? 'Không xác định';
    }

    /**
     * Check if invoice is monthly rent type
     */
    public function isMonthlyRent()
    {
        return $this->invoice_type === self::TYPE_MONTHLY_RENT;
    }

    /**
     * Check if invoice is first invoice type
     */
    public function isFirstInvoice()
    {
        return $this->invoice_type === self::TYPE_FIRST_INVOICE;
    }

    /**
     * Check if invoice is booking deposit type
     */
    public function isBookingDeposit()
    {
        return $this->invoice_type === self::TYPE_BOOKING_DEPOSIT;
    }

    /**
     * Scope to get invoices by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('invoice_type', $type);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Automatically set organization_id when creating invoice
        static::creating(function ($invoice) {
            if (!$invoice->organization_id) {
                // Try to get organization_id from lease
                if ($invoice->lease_id) {
                    $lease = \App\Models\Lease::find($invoice->lease_id);
                    if ($lease && $lease->organization_id) {
                        $invoice->organization_id = $lease->organization_id;
                    }
                }
                
                // Try to get organization_id from booking deposit
                if (!$invoice->organization_id && $invoice->booking_deposit_id) {
                    $bookingDeposit = \App\Models\BookingDeposit::find($invoice->booking_deposit_id);
                    if ($bookingDeposit && $bookingDeposit->organization_id) {
                        $invoice->organization_id = $bookingDeposit->organization_id;
                    }
                }
                
                // If still no organization_id, try to get from authenticated user
                if (!$invoice->organization_id && Auth::check()) {
                    $user = Auth::user();
                    // Try to get organization from user's organization relationships
                    $userOrganization = \App\Models\OrganizationUser::where('user_id', $user->id)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($userOrganization) {
                        $invoice->organization_id = $userOrganization->organization_id;
                    }
                }
            }
        });
    }

    /**
     * Get the organization that owns the invoice.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the lease that owns the invoice.
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the user (tenant) for this invoice through lease.
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Lease::class, 'id', 'id', 'lease_id', 'tenant_id');
    }

    /**
     * Get the booking deposit that owns the invoice.
     */
    public function bookingDeposit()
    {
        return $this->belongsTo(BookingDeposit::class, 'booking_deposit_id');
    }

    /**
     * Get the user who created this invoice.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the invoice items for the invoice.
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the total paid amount for this invoice.
     */
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get payment tokens for this invoice
     */
    public function paymentTokens()
    {
        return $this->hasMany(PaymentToken::class);
    }

    /**
     * Get active payment token (not used and not expired)
     */
    public function getActivePaymentToken()
    {
        return PaymentToken::where('invoice_id', $this->id)
            ->where('is_used', false)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Generate secure payment token for guest payment
     * For booking deposit invoices, token expires at payment_due_date
     */
    public function generatePaymentToken(int $expiresInHours = 72): PaymentToken
    {
        // If this is a booking deposit invoice, use payment_due_date as expires_at
        $expiresAt = null;
        if ($this->booking_deposit_id && $this->bookingDeposit) {
            $bookingDeposit = $this->bookingDeposit;
            if ($bookingDeposit->payment_due_date) {
                $expiresAt = \Carbon\Carbon::parse($bookingDeposit->payment_due_date);
            }
        }
        
        // If no expires_at from booking deposit, use default hours
        if (!$expiresAt) {
            $expiresAt = now()->addHours($expiresInHours);
        }
        
        return PaymentToken::generateForInvoice($this, $expiresAt);
    }

    /**
     * Get guest payment URL
     */
    public function getGuestPaymentUrl()
    {
        $token = $this->getActivePaymentToken();
        
        if (!$token) {
            $token = $this->generatePaymentToken();
        }
        
        return route('guest.payment.show', ['invoice' => $this->id, 'token' => $token->token]);
    }

    /**
     * Verify payment token
     */
    public function verifyPaymentToken(string $token): bool
    {
        $paymentToken = PaymentToken::findByToken($token);
        
        if (!$paymentToken || $paymentToken->invoice_id !== $this->id) {
            return false;
        }

        return $paymentToken->isValid();
    }

    /**
     * Get the remaining amount for this invoice.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date < now() && !$this->isFullyPaid() && $this->status !== 'cancelled';
    }

    /**
     * Scope to get invoices by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope to get invoices for a specific lease.
     */
    public function scopeForLease($query, $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Check if invoice was created automatically (from lease or booking deposit)
     */
    public function isAutoCreated()
    {
        return $this->is_auto_created;
    }

    /**
     * Get the source type of auto-created invoice
     */
    public function getAutoCreatedSource()
    {
        if ($this->booking_deposit_id) {
            return 'booking_deposit';
        } elseif ($this->lease_id) {
            return 'lease';
        }
        return null;
    }

    /**
     * Get human-readable description of auto-created source
     */
    public function getAutoCreatedDescription()
    {
        $source = $this->getAutoCreatedSource();
        
        switch ($source) {
            case 'booking_deposit':
                return 'Hóa đơn đặt cọc được tạo tự động';
            case 'lease':
                return 'Hóa đơn hợp đồng thuê được tạo tự động';
            default:
                return 'Hóa đơn được tạo thủ công';
        }
    }

    /**
     * Generate a unique invoice number
     * This method is thread-safe and prevents duplicate invoice numbers
     */
    /**
     * Generate invoice number with format: HD-{org_id}-{year}-{month}-{sequence}
     * 
     * @param int|null $organizationId Organization ID (optional, will try to get from context)
     * @return string Invoice number
     * @throws \Exception If organization ID cannot be determined
     */
    public static function generateInvoiceNumber($organizationId = null, $invoiceInstance = null)
    {
        // Get organization ID from parameter, instance, or context
        if (!$organizationId) {
            // Try to get from instance if provided
            if ($invoiceInstance && $invoiceInstance->organization_id) {
                $organizationId = $invoiceInstance->organization_id;
            }
            
            // Try to get from authenticated user
            if (!$organizationId && Auth::check()) {
                $user = Auth::user();
                $organizationId = $user->organization_id ?? null;
            }
            
            // Try to get from lease if available
            if (!$organizationId && $invoiceInstance && $invoiceInstance->lease_id) {
                $lease = Lease::find($invoiceInstance->lease_id);
                $organizationId = $lease->organization_id ?? null;
            }
            
            // Try to get from booking deposit if available
            if (!$organizationId && $invoiceInstance && $invoiceInstance->booking_deposit_id) {
                $bookingDeposit = BookingDeposit::find($invoiceInstance->booking_deposit_id);
                $organizationId = $bookingDeposit->organization_id ?? null;
            }
        }
        
        if (!$organizationId) {
            throw new \Exception('Organization ID is required to generate invoice number');
        }
        
        $year = date('Y');
        $month = date('m');
        $sequenceKey = SequenceGenerator::buildKey('invoice', $organizationId, $year, $month);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month) {
            // Find max from existing invoices
            // Support old formats with dashes and new format without dashes
            $existingInvoices = static::withTrashed()
                ->where('organization_id', $organizationId)
                ->where(function($query) use ($organizationId, $year, $month) {
                    // New format without dashes: HD{org_id}{year}{month}{sequence}
                    $query->where('invoice_no', 'like', "HD{$organizationId}{$year}{$month}%")
                          // Old format with dashes: HD-{org_id}-{year}-{month}-{sequence}
                          ->orWhere('invoice_no', 'like', "HD-{$organizationId}-{$year}-{$month}-%")
                          ->orWhere('invoice_no', 'like', "HD-{$year}-{$month}-%");
                })
                ->pluck('invoice_no')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingInvoices as $invoiceNo) {
                // Remove "HD" prefix and all dashes
                $cleanNo = str_replace(['HD', '-'], '', $invoiceNo);
                
                // Extract sequence number (last 4 digits)
                if (strlen($cleanNo) >= 4) {
                    $sequenceStr = substr($cleanNo, -4);
                    $number = (int) $sequenceStr;
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            return $maxNumber;
        });
        
        // Generate invoice number with new format (no dashes): HD{org_id}{year}{month}{sequence}
        $invoiceNumber = "HD{$organizationId}{$year}{$month}" . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records)
        $exists = static::where('invoice_no', $invoiceNumber)
            ->whereNull('deleted_at')
            ->exists();
        
        if ($exists) {
            // If exists, retry with incremented sequence (max 10 retries)
            $maxRetries = 10;
            $retries = 0;
            
            while ($exists && $retries < $maxRetries) {
                $newSequence++;
                SequenceGenerator::reset($sequenceKey, $newSequence);
                
                $invoiceNumber = "HD{$organizationId}{$year}{$month}" . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
                $exists = static::where('invoice_no', $invoiceNumber)
                    ->whereNull('deleted_at')
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique invoice number after retries, using timestamp fallback');
                $invoiceNumber = "HD{$organizationId}{$year}{$month}" . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
            }
        }
        
        return $invoiceNumber;
    }

    /**
     * Get documents for this invoice (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }
}

