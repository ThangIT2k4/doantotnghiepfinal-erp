<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToOrganization;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SequenceGenerator;

class CompanyInvoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization, HasSoftDeletesWithUser;

    protected $table = 'company_invoices';

    protected $fillable = [
        'organization_id',
        'invoice_no',
        'vendor_id',
        'user_id',
        'invoice_type',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'description',
        'note',
        // 'attachment_url', // Removed - use document attachments instead
        'created_by',
        'master_lease_id',
        'ticket_id',
        'ticket_log_id',
        'deposit_refund_id',
        'payroll_payslip_id',
        'deleted_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2', // Database column is now decimal(15,2) to support large values
        'tax_amount' => 'decimal:2', // Database column is now decimal(15,2) to support large values
        'discount_amount' => 'decimal:2', // Database column is now decimal(15,2) to support large values
        'total_amount' => 'decimal:2', // Database column is now decimal(15,2) to support large values
    ];

    // Constants for invoice status
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    // Constants for invoice types
    const TYPE_MASTER_LEASE = 'master_lease';
    const TYPE_TICKET_COST = 'ticket_cost';
    const TYPE_DEPOSIT_REFUND = 'deposit_refund';
    const TYPE_PAYROLL_PAYSLIP = 'payroll_payslip';
    const TYPE_UTILITY = 'utility';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_SERVICE = 'service';
    const TYPE_SUPPLY = 'supply';
    const TYPE_OTHER = 'other';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_no)) {
                $invoice->invoice_no = $invoice->generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate unique invoice number
     */
    /**
     * Generate company invoice number with format: CI-{org_id}-{year}-{month}-{sequence}
     * 
     * @return string Invoice number
     * @throws \Exception If organization ID is not set
     */
    public function generateInvoiceNumber()
    {
        $organizationId = $this->organization_id;
        
        if (!$organizationId) {
            throw new \Exception('Organization ID is required to generate company invoice number');
        }
        
        $year = date('Y');
        $month = date('m');
        $sequenceKey = SequenceGenerator::buildKey('company_invoice', $organizationId, $year, $month);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month) {
            // Find max from existing company invoices
            // Support both old format (CI-2025-000123) and new format (CI-1-2025-11-000123)
            $existingInvoices = self::withTrashed()
                ->where('organization_id', $organizationId)
                ->where(function($query) use ($year, $month) {
                    $query->where('invoice_no', 'like', "CI-{$year}-%")
                          ->orWhere('invoice_no', 'like', "CI-%-{$year}-{$month}-%");
                })
                ->pluck('invoice_no')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingInvoices as $invoiceNo) {
                // Parse new format: "CI-1-2025-11-000123" => 123
                // Parse old format: "CI-2025-000123" => 123
                $parts = explode('-', $invoiceNo);
                if (count($parts) >= 3) {
                    // New format: CI-{org_id}-{year}-{month}-{sequence}
                    if (count($parts) >= 5) {
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[4]);
                    } else {
                        // Old format: CI-{year}-{sequence}
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[2]);
                    }
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            return $maxNumber;
        });
        
        // Generate invoice number with new format: CI-{org_id}-{year}-{month}-{sequence}
        $invoiceNumber = "CI-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records)
        $exists = self::where('invoice_no', $invoiceNumber)
            ->whereNull('deleted_at')
            ->exists();
        
        if ($exists) {
            // If exists, retry with incremented sequence (max 10 retries)
            $maxRetries = 10;
            $retries = 0;
            
            while ($exists && $retries < $maxRetries) {
                $newSequence++;
                SequenceGenerator::reset($sequenceKey, $newSequence);
                
                $invoiceNumber = "CI-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
                $exists = self::where('invoice_no', $invoiceNumber)
                    ->whereNull('deleted_at')
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique company invoice number after retries, using timestamp fallback');
                $invoiceNumber = "CI-{$organizationId}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
            }
        }
        
        return $invoiceNumber;
    }

    /**
     * Get the vendor that owns the invoice.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user that owns the invoice (for payroll and user payouts).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization that owns the invoice.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function masterLease(): BelongsTo
    {
        return $this->belongsTo(MasterLease::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketLog(): BelongsTo
    {
        return $this->belongsTo(TicketLog::class);
    }

    public function depositRefund(): BelongsTo
    {
        return $this->belongsTo(DepositRefund::class);
    }

    public function payrollPayslip(): BelongsTo
    {
        return $this->belongsTo(PayrollPayslip::class);
    }

    /**
     * Company invoice items (line items)
     */
    public function items(): HasMany
    {
        return $this->hasMany(CompanyInvoiceItem::class);
    }

    /**
     * Get the cash outflows for this invoice.
     */
    public function cashOutflows()
    {
        return $this->hasMany(CashOutflow::class, 'company_invoice_id');
    }

    /**
     * Get the total amount paid for this invoice.
     */
    public function getTotalPaidAttribute()
    {
        return $this->cashOutflows()
            ->where('status', 'success')
            ->sum('amount');
    }

    /**
     * Get the outstanding amount for this invoice.
     */
    public function getOutstandingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->outstanding_amount <= 0;
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && 
               $this->status !== 'cancelled' && 
               $this->due_date < now()->toDateString();
    }

    /**
     * Scope for filtering by organization
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('invoice_type', $type);
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled')
                    ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for invoices due soon
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /**
     * Create invoice from master lease
     */
    public static function createFromMasterLease(MasterLease $masterLease, $data = [])
    {
        return self::create(array_merge([
            'organization_id' => $masterLease->organization_id,
            'vendor_id' => null,
            'user_id' => $masterLease->landlord_user_id,
            'invoice_type' => self::TYPE_MASTER_LEASE,
            'master_lease_id' => $masterLease->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $masterLease->base_rent,
            'total_amount' => $masterLease->base_rent,
            'description' => "Hóa đơn thuê tài sản - Hợp đồng {$masterLease->contract_no}",
            'created_by' => Auth::id(),
        ], $data));
    }

    /**
     * Create invoice from ticket cost
     */
    public static function createFromTicketLog(TicketLog $ticketLog, $data = [])
    {
        if ($ticketLog->cost_amount <= 0) {
            return null;
        }

        return self::create(array_merge([
            'organization_id' => $ticketLog->ticket->organization_id,
            'vendor_id' => 1, // Default vendor for maintenance costs
            'invoice_type' => self::TYPE_TICKET_COST,
            'ticket_log_id' => $ticketLog->id,
            'ticket_id' => $ticketLog->ticket_id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => $ticketLog->cost_amount,
            'total_amount' => $ticketLog->cost_amount,
            'description' => "Chi phí bảo trì - Ticket #{$ticketLog->ticket_id}: {$ticketLog->cost_note}",
            'created_by' => $ticketLog->actor_id ?? Auth::id(),
        ], $data));
    }

    /**
     * Create invoice from deposit refund
     */
    public static function createFromDepositRefund(DepositRefund $depositRefund, $data = [])
    {
        return self::create(array_merge([
            'organization_id' => $depositRefund->organization_id,
            'vendor_id' => 1, // Default vendor for refunds
            'invoice_type' => self::TYPE_DEPOSIT_REFUND,
            'deposit_refund_id' => $depositRefund->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $depositRefund->refund_amount,
            'total_amount' => $depositRefund->refund_amount,
            'description' => "Hoàn tiền cọc - Hợp đồng {$depositRefund->lease_id}",
            'created_by' => $depositRefund->created_by,
        ], $data));
    }

    /**
     * Create invoice from payroll payslip
     */
    public static function createFromPayrollPayslip(PayrollPayslip $payslip, $data = [])
    {
        return self::create(array_merge([
            'organization_id' => $payslip->payrollCycle->organization_id,
            'vendor_id' => 1, // Default vendor for payroll
            'invoice_type' => self::TYPE_PAYROLL_PAYSLIP,
            'payroll_payslip_id' => $payslip->id,
            'user_id' => $payslip->user_id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(1)->toDateString(),
            'subtotal' => $payslip->net_amount,
            'total_amount' => $payslip->net_amount,
            'description' => "Lương nhân viên - Tháng {$payslip->payrollCycle->period_month}",
            'created_by' => Auth::id(),
        ], $data));
    }

    /**
     * Get documents for this company invoice (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get attachment URL (backward compatibility)
     */
    public function getAttachmentUrlAttribute()
    {
        // Nếu cột attachment_url vẫn còn trong database, trả về nó
        if (isset($this->attributes['attachment_url'])) {
            return $this->attributes['attachment_url'];
        }

        // Lấy từ documents trực tiếp
        $attachment = $this->documents()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        return $attachment ? $attachment->file_url : null;
    }
}