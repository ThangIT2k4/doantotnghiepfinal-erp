<?php

namespace App\Observers;

use App\Models\CommissionPolicy;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class CommissionPolicyObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the CommissionPolicy "created" event.
     */
    public function created(CommissionPolicy $commissionPolicy): void
    {
        try {
            Log::info('CommissionPolicyObserver::created triggered', [
                'commission_policy_id' => $commissionPolicy->id,
                'code' => $commissionPolicy->code,
                'organization_id' => $commissionPolicy->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($commissionPolicy);

        } catch (\Exception $e) {
            Log::error('Error in CommissionPolicyObserver::created: ' . $e->getMessage(), [
                'commission_policy_id' => $commissionPolicy->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CommissionPolicy "updated" event.
     */
    public function updated(CommissionPolicy $commissionPolicy): void
    {
        try {
            Log::info('CommissionPolicyObserver::updated triggered', [
                'commission_policy_id' => $commissionPolicy->id,
                'code' => $commissionPolicy->code,
                'changes' => $commissionPolicy->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($commissionPolicy);

        } catch (\Exception $e) {
            Log::error('Error in CommissionPolicyObserver::updated: ' . $e->getMessage(), [
                'commission_policy_id' => $commissionPolicy->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CommissionPolicy "deleted" event.
     */
    public function deleted(CommissionPolicy $commissionPolicy): void
    {
        try {
            Log::info('CommissionPolicyObserver::deleted triggered', [
                'commission_policy_id' => $commissionPolicy->id,
                'code' => $commissionPolicy->code,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($commissionPolicy);

        } catch (\Exception $e) {
            Log::error('Error in CommissionPolicyObserver::deleted: ' . $e->getMessage(), [
                'commission_policy_id' => $commissionPolicy->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

