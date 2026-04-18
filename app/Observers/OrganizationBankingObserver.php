<?php

namespace App\Observers;

use App\Models\OrganizationBanking;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class OrganizationBankingObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the OrganizationBanking "created" event.
     */
    public function created(OrganizationBanking $organizationBanking): void
    {
        try {
            Log::info('OrganizationBankingObserver::created triggered', [
                'organization_banking_id' => $organizationBanking->id,
                'organization_id' => $organizationBanking->organization_id,
                'account_number' => $organizationBanking->account_number,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($organizationBanking);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationBankingObserver::created: ' . $e->getMessage(), [
                'organization_banking_id' => $organizationBanking->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationBanking "updated" event.
     */
    public function updated(OrganizationBanking $organizationBanking): void
    {
        try {
            Log::info('OrganizationBankingObserver::updated triggered', [
                'organization_banking_id' => $organizationBanking->id,
                'organization_id' => $organizationBanking->organization_id,
                'changes' => $organizationBanking->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($organizationBanking);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationBankingObserver::updated: ' . $e->getMessage(), [
                'organization_banking_id' => $organizationBanking->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationBanking "deleted" event.
     */
    public function deleted(OrganizationBanking $organizationBanking): void
    {
        try {
            Log::info('OrganizationBankingObserver::deleted triggered', [
                'organization_banking_id' => $organizationBanking->id,
                'organization_id' => $organizationBanking->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($organizationBanking);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationBankingObserver::deleted: ' . $e->getMessage(), [
                'organization_banking_id' => $organizationBanking->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

