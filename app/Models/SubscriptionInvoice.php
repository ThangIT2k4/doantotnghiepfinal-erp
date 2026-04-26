<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SubscriptionInvoice extends Model
{
    protected $table = 'subscription_invoices';

    protected $fillable = [
        'organization_subscription_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'gateway_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate organization_subscription_id before saving
        // Cho phép null để hỗ trợ hóa đơn đăng ký (chưa có subscription)
        static::saving(function ($invoice) {
            if ($invoice->organization_subscription_id) {
                // Kiểm tra xem organization_subscription_id có tồn tại không
                $subscriptionExists = \App\Models\OrganizationSubscription::where('id', $invoice->organization_subscription_id)->exists();
                
                if (!$subscriptionExists) {
                    Log::error('Invalid organization_subscription_id in SubscriptionInvoice', [
                        'invoice_id' => $invoice->id,
                        'organization_subscription_id' => $invoice->organization_subscription_id,
                        'invoice_number' => $invoice->invoice_number ?? 'N/A',
                    ]);
                    
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                        "OrganizationSubscription with id {$invoice->organization_subscription_id} does not exist."
                    );
                }
            }
            // Cho phép organization_subscription_id = null (cho hóa đơn đăng ký chưa có subscription)
        });
    }

    /**
     * Get the subscription that owns the invoice.
     * Có thể null nếu đây là hóa đơn đăng ký chưa có subscription.
     */
    public function subscription()
    {
        return $this->belongsTo(OrganizationSubscription::class, 'organization_subscription_id');
    }

    /**
     * Kiểm tra xem đây có phải là hóa đơn đăng ký chưa có subscription không
     */
    public function isRegistrationInvoice(): bool
    {
        return $this->organization_subscription_id === null;
    }

    /**
     * Get documents for this invoice (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed invoices.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include refunded invoices.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invoice is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if invoice is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date < now();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(string $paymentMethod = null, string $transactionId = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'gateway_transaction_id' => $transactionId ?? $this->gateway_transaction_id,
        ]);

        return $this;
    }

    /**
     * Mark invoice as failed.
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
        return $this;
    }

    /**
     * Mark invoice as refunded.
     */
    public function markAsRefunded()
    {
        $this->update(['status' => 'refunded']);
        return $this;
    }

    /**
     * Get invoice status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thất bại',
            'refunded' => 'Đã hoàn tiền',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get invoice status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' ' . $this->currency;
    }
}

