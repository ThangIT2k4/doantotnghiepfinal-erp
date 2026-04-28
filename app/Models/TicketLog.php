<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSoftDeletesWithUser;

class TicketLog extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser;

    protected $table = 'ticket_logs';
    
    public $timestamps = false; // Only has created_at, no updated_at
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $casts = [
        'cost_amount' => 'decimal:2',
        'warranty_expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'action',
        'detail',
        'cost_amount',
        'cost_note',
        'charge_to',
        'linked_invoice_id',
        'vendor_id',
        'warranty_period_days',
        'warranty_expires_at',
        'deleted_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function linkedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'linked_invoice_id');
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    /**
     * Get the company invoice created for this ticket log (vendor self-pay)
     */
    public function companyInvoice()
    {
        return $this->hasOne(CompanyInvoice::class, 'ticket_log_id');
    }

    /**
     * Check if warranty is active
     */
    public function hasActiveWarranty(): bool
    {
        if (!$this->warranty_expires_at) {
            return false;
        }
        
        $expiresAt = $this->warranty_expires_at instanceof \Carbon\Carbon 
            ? $this->warranty_expires_at 
            : \Carbon\Carbon::parse($this->warranty_expires_at);
        
        return $expiresAt->isFuture();
    }

    /**
     * Get warranty status
     */
    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->warranty_expires_at) {
            return 'none';
        }
        
        $expiresAt = $this->warranty_expires_at instanceof \Carbon\Carbon 
            ? $this->warranty_expires_at 
            : \Carbon\Carbon::parse($this->warranty_expires_at);
        
        if ($expiresAt->isPast()) {
            return 'expired';
        }
        
        $daysUntilExpiry = now()->diffInDays($expiresAt);
        if ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Get days until warranty expires
     */
    public function getDaysUntilWarrantyExpiresAttribute(): ?int
    {
        if (!$this->warranty_expires_at) {
            return null;
        }
        
        $expiresAt = $this->warranty_expires_at instanceof \Carbon\Carbon 
            ? $this->warranty_expires_at 
            : \Carbon\Carbon::parse($this->warranty_expires_at);
        
        return max(0, now()->diffInDays($expiresAt, false));
    }
}