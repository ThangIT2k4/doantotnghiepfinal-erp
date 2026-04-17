<?php

namespace App\Observers;

use App\Models\PaymentCycle;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class PaymentCycleObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the PaymentCycle "created" event.
     */
    public function created(PaymentCycle $paymentCycle): void
    {
        try {
            Log::info('PaymentCycleObserver::created triggered', [
                'payment_cycle_id' => $paymentCycle->id,
                'organization_id' => $paymentCycle->organization_id,
                'cycle_type' => $paymentCycle->cycle_type,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($paymentCycle);

        } catch (\Exception $e) {
            Log::error('Error in PaymentCycleObserver::created: ' . $e->getMessage(), [
                'payment_cycle_id' => $paymentCycle->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PaymentCycle "updated" event.
     */
    public function updated(PaymentCycle $paymentCycle): void
    {
        try {
            Log::info('PaymentCycleObserver::updated triggered', [
                'payment_cycle_id' => $paymentCycle->id,
                'organization_id' => $paymentCycle->organization_id,
                'changes' => $paymentCycle->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($paymentCycle);

        } catch (\Exception $e) {
            Log::error('Error in PaymentCycleObserver::updated: ' . $e->getMessage(), [
                'payment_cycle_id' => $paymentCycle->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PaymentCycle "deleted" event.
     */
    public function deleted(PaymentCycle $paymentCycle): void
    {
        // Validate business rules first (soft delete)
        if (!$paymentCycle->isForceDeleting()) {
            $this->businessRulesService->validate($paymentCycle, 'deleting');
        }
        
        try {
            Log::info('PaymentCycleObserver::deleted triggered', [
                'payment_cycle_id' => $paymentCycle->id,
                'organization_id' => $paymentCycle->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($paymentCycle);

        } catch (\Exception $e) {
            Log::error('Error in PaymentCycleObserver::deleted: ' . $e->getMessage(), [
                'payment_cycle_id' => $paymentCycle->id,
                'error' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to prevent deletion if validation fails
        }
    }
}

