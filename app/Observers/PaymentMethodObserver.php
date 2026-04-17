<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class PaymentMethodObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the PaymentMethod "created" event.
     */
    public function created(PaymentMethod $paymentMethod): void
    {
        try {
            Log::info('PaymentMethodObserver::created triggered', [
                'payment_method_id' => $paymentMethod->id,
                'key_code' => $paymentMethod->key_code,
                'name' => $paymentMethod->name,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($paymentMethod);

        } catch (\Exception $e) {
            Log::error('Error in PaymentMethodObserver::created: ' . $e->getMessage(), [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PaymentMethod "updated" event.
     */
    public function updated(PaymentMethod $paymentMethod): void
    {
        try {
            Log::info('PaymentMethodObserver::updated triggered', [
                'payment_method_id' => $paymentMethod->id,
                'key_code' => $paymentMethod->key_code,
                'changes' => $paymentMethod->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($paymentMethod);

        } catch (\Exception $e) {
            Log::error('Error in PaymentMethodObserver::updated: ' . $e->getMessage(), [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PaymentMethod "deleted" event.
     */
    public function deleted(PaymentMethod $paymentMethod): void
    {
        try {
            Log::info('PaymentMethodObserver::deleted triggered', [
                'payment_method_id' => $paymentMethod->id,
                'key_code' => $paymentMethod->key_code,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($paymentMethod);

        } catch (\Exception $e) {
            Log::error('Error in PaymentMethodObserver::deleted: ' . $e->getMessage(), [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

