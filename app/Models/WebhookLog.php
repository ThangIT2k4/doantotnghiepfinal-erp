<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'sepay_transaction_id',
        'gateway',
        'transaction_date',
        'account_number',
        'transfer_type',
        'amount',
        'content',
        'reference_code',
        'invoice_id',
        'payment_id',
        'status',
        'error_message',
        'raw_data',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'processed_at' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the invoice associated with this webhook log.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment associated with this webhook log.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending webhooks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter processed webhooks
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Check if webhook is processed
     */
    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    /**
     * Check if webhook is failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if webhook is duplicate
     */
    public function isDuplicate()
    {
        return $this->status === 'duplicate';
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed($invoiceId = null, $paymentId = null)
    {
        $this->update([
            'status' => 'processed',
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark webhook as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark webhook as duplicate
     */
    public function markAsDuplicate()
    {
        $this->update([
            'status' => 'duplicate',
            'processed_at' => now(),
        ]);
    }
}

