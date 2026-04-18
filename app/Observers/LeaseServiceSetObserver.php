<?php

namespace App\Observers;

use App\Models\LeaseServiceSet;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class LeaseServiceSetObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the LeaseServiceSet "created" event.
     */
    public function created(LeaseServiceSet $leaseServiceSet): void
    {
        try {
            Log::info('LeaseServiceSetObserver::created triggered', [
                'lease_service_set_id' => $leaseServiceSet->id,
                'name' => $leaseServiceSet->name,
                'organization_id' => $leaseServiceSet->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($leaseServiceSet);

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetObserver::created: ' . $e->getMessage(), [
                'lease_service_set_id' => $leaseServiceSet->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseServiceSet "updated" event.
     */
    public function updated(LeaseServiceSet $leaseServiceSet): void
    {
        try {
            $changes = $leaseServiceSet->getDirty();

            if (!empty($changes)) {
                Log::info('LeaseServiceSetObserver::updated triggered', [
                    'lease_service_set_id' => $leaseServiceSet->id,
                    'name' => $leaseServiceSet->name,
                    'changes' => $changes
                ]);

                // Log audit trail for all changes
                $this->auditLogService->logUpdated($leaseServiceSet, $changes);
            }

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetObserver::updated: ' . $e->getMessage(), [
                'lease_service_set_id' => $leaseServiceSet->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseServiceSet "deleted" event.
     */
    public function deleted(LeaseServiceSet $leaseServiceSet): void
    {
        // Validate business rules first (soft delete)
        if (!$leaseServiceSet->isForceDeleting()) {
            $this->businessRulesService->validate($leaseServiceSet, 'deleting');
        }
        
        try {
            Log::info('LeaseServiceSetObserver::deleted triggered', [
                'lease_service_set_id' => $leaseServiceSet->id,
                'name' => $leaseServiceSet->name,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($leaseServiceSet);

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetObserver::deleted: ' . $e->getMessage(), [
                'lease_service_set_id' => $leaseServiceSet->id,
                'error' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to prevent deletion if validation fails
        }
    }
}

