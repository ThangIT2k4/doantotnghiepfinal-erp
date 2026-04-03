<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentToken extends Model
{
    protected $table = 'payment_tokens';

    protected $fillable = [
        'invoice_id',
        'token',
        'expires_at',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Get the invoice that owns the payment token.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Check if token is valid (not used and not expired)
     */
    public function isValid(): bool
    {
        if ($this->is_used) {
            return false;
        }

        if ($this->expires_at) {
            // Use Carbon to compare with current time (respects timezone)
            $now = now();
            if ($this->expires_at->isPast()) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Get validation error message
     */
    public function getValidationError(): ?string
    {
        if ($this->is_used) {
            return 'Token đã được sử dụng';
        }
        
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Token đã hết hạn';
        }
        
        return null;
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Generate a new payment token for invoice
     * @param Invoice $invoice
     * @param int|\Carbon\Carbon|null $expiresAtOrHours - Either Carbon instance for expires_at or hours (default 72)
     */
    public static function generateForInvoice(Invoice $invoice, $expiresAtOrHours = 72): self
    {
        // If invoice is linked to booking deposit, use payment_due_date as expires_at
        if ($invoice->booking_deposit_id && $invoice->bookingDeposit) {
            $bookingDeposit = $invoice->bookingDeposit;
            if ($bookingDeposit->payment_due_date) {
                $expiresAtOrHours = \Carbon\Carbon::parse($bookingDeposit->payment_due_date);
            }
        }

        // Check if there's an existing valid token
        $existingToken = self::where('invoice_id', $invoice->id)
            ->where('is_used', false)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existingToken) {
            // Update expires_at if provided and different
            if ($expiresAtOrHours instanceof \Carbon\Carbon) {
                if (!$existingToken->expires_at || $existingToken->expires_at->ne($expiresAtOrHours)) {
                    $existingToken->update(['expires_at' => $expiresAtOrHours]);
                }
            }
            return $existingToken;
        }

        // Generate new token
        $token = hash('sha256', $invoice->id . time() . rand(1000, 9999) . config('app.key'));

        // Determine expires_at
        $expiresAt = null;
        if ($expiresAtOrHours instanceof \Carbon\Carbon) {
            $expiresAt = $expiresAtOrHours;
        } elseif (is_int($expiresAtOrHours)) {
            $expiresAt = now()->addHours($expiresAtOrHours);
        } else {
            $expiresAt = now()->addHours(72); // Default
        }

        return self::create([
            'invoice_id' => $invoice->id,
            'token' => $token,
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);
    }

    /**
     * Find token by token string
     */
    public static function findByToken(string $token): ?self
    {
        // Normalize token (trim whitespace)
        $token = trim($token);
        
        if (empty($token)) {
            return null;
        }
        
        return self::where('token', $token)->first();
    }
}

