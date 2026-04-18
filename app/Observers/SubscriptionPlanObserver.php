<?php

namespace App\Observers;

use App\Models\SubscriptionPlan;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class SubscriptionPlanObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the SubscriptionPlan "created" event.
     */
    public function created(SubscriptionPlan $plan): void
    {
        try {
            Log::info('SubscriptionPlanObserver::created triggered', [
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'plan_name' => $plan->name,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($plan);

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionPlanObserver::created: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SubscriptionPlan "updated" event.
     */
    public function updated(SubscriptionPlan $plan): void
    {
        try {
            Log::info('SubscriptionPlanObserver::updated triggered', [
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'changes' => $plan->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($plan);

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionPlanObserver::updated: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SubscriptionPlan "deleted" event.
     */
    public function deleted(SubscriptionPlan $plan): void
    {
        try {
            Log::info('SubscriptionPlanObserver::deleted triggered', [
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($plan);

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionPlanObserver::deleted: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

