<?php

namespace App\Observers;

use App\Models\OrganizationSubscription;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class OrganizationSubscriptionObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the OrganizationSubscription "created" event.
     */
    public function created(OrganizationSubscription $subscription): void
    {
        try {
            Log::info('OrganizationSubscriptionObserver::created triggered', [
                'subscription_id' => $subscription->id,
                'organization_id' => $subscription->organization_id,
                'plan_id' => $subscription->plan_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($subscription);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationSubscriptionObserver::created: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationSubscription "updated" event.
     */
    public function updated(OrganizationSubscription $subscription): void
    {
        try {
            Log::info('OrganizationSubscriptionObserver::updated triggered', [
                'subscription_id' => $subscription->id,
                'organization_id' => $subscription->organization_id,
                'changes' => $subscription->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($subscription);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationSubscriptionObserver::updated: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationSubscription "deleted" event.
     */
    public function deleted(OrganizationSubscription $subscription): void
    {
        try {
            Log::info('OrganizationSubscriptionObserver::deleted triggered', [
                'subscription_id' => $subscription->id,
                'organization_id' => $subscription->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($subscription);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationSubscriptionObserver::deleted: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

