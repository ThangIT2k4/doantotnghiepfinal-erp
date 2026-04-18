<?php

namespace App\Observers;

use App\Models\Organization;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class OrganizationObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        try {
            Log::info('OrganizationObserver::created triggered', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'code' => $organization->code,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($organization);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationObserver::created: ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        try {
            Log::info('OrganizationObserver::updated triggered', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'changes' => $organization->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($organization);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationObserver::updated: ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        try {
            Log::info('OrganizationObserver::deleted triggered', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($organization);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationObserver::deleted: ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

