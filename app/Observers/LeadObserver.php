<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        try {
            Log::info('LeadObserver::created triggered', [
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'organization_id' => $lead->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($lead);

        } catch (\Exception $e) {
            Log::error('Error in LeadObserver::created: ' . $e->getMessage(), [
                'lead_id' => $lead->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        try {
            Log::info('LeadObserver::updated triggered', [
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'changes' => $lead->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($lead);

        } catch (\Exception $e) {
            Log::error('Error in LeadObserver::updated: ' . $e->getMessage(), [
                'lead_id' => $lead->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Lead "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Lead $lead): void
    {
        try {
            $isForceDelete = $lead->isForceDeleting();
            
            Log::info('LeadObserver::deleted triggered', [
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'is_force_deleting' => $isForceDelete,
                'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($lead);

        } catch (\Exception $e) {
            Log::error('Error in LeadObserver::deleted: ' . $e->getMessage(), [
                'lead_id' => $lead->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Lead "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Lead $lead): void
    {
        Log::info('Lead force deleted (permanent delete completed)', [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }
}

