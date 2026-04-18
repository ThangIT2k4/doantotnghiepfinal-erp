<?php

namespace App\Observers;

use App\Models\LeaseResident;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class LeaseResidentObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the LeaseResident "created" event.
     */
    public function created(LeaseResident $leaseResident): void
    {
        try {
            Log::info('LeaseResidentObserver::created triggered', [
                'lease_resident_id' => $leaseResident->id,
                'lease_id' => $leaseResident->lease_id,
                'user_id' => $leaseResident->user_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($leaseResident);

        } catch (\Exception $e) {
            Log::error('Error in LeaseResidentObserver::created: ' . $e->getMessage(), [
                'lease_resident_id' => $leaseResident->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseResident "updated" event.
     */
    public function updated(LeaseResident $leaseResident): void
    {
        try {
            Log::info('LeaseResidentObserver::updated triggered', [
                'lease_resident_id' => $leaseResident->id,
                'lease_id' => $leaseResident->lease_id,
                'changes' => $leaseResident->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($leaseResident);

        } catch (\Exception $e) {
            Log::error('Error in LeaseResidentObserver::updated: ' . $e->getMessage(), [
                'lease_resident_id' => $leaseResident->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseResident "deleted" event.
     */
    public function deleted(LeaseResident $leaseResident): void
    {
        try {
            Log::info('LeaseResidentObserver::deleted triggered', [
                'lease_resident_id' => $leaseResident->id,
                'lease_id' => $leaseResident->lease_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($leaseResident);

        } catch (\Exception $e) {
            Log::error('Error in LeaseResidentObserver::deleted: ' . $e->getMessage(), [
                'lease_resident_id' => $leaseResident->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

