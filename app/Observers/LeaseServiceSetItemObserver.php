<?php

namespace App\Observers;

use App\Models\LeaseServiceSetItem;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class LeaseServiceSetItemObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the LeaseServiceSetItem "created" event.
     */
    public function created(LeaseServiceSetItem $leaseServiceSetItem): void
    {
        try {
            Log::info('LeaseServiceSetItemObserver::created triggered', [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'lease_service_set_id' => $leaseServiceSetItem->lease_service_set_id,
                'service_id' => $leaseServiceSetItem->service_id,
                'price' => $leaseServiceSetItem->price,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($leaseServiceSetItem);

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetItemObserver::created: ' . $e->getMessage(), [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseServiceSetItem "updated" event.
     */
    public function updated(LeaseServiceSetItem $leaseServiceSetItem): void
    {
        try {
            $changes = $leaseServiceSetItem->getDirty();

            if (!empty($changes)) {
                Log::info('LeaseServiceSetItemObserver::updated triggered', [
                    'lease_service_set_item_id' => $leaseServiceSetItem->id,
                    'lease_service_set_id' => $leaseServiceSetItem->lease_service_set_id,
                    'changes' => $changes
                ]);

                // Log audit trail for all changes
                $this->auditLogService->logUpdated($leaseServiceSetItem, $changes);
            }

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetItemObserver::updated: ' . $e->getMessage(), [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the LeaseServiceSetItem "deleted" event.
     */
    public function deleted(LeaseServiceSetItem $leaseServiceSetItem): void
    {
        try {
            Log::info('LeaseServiceSetItemObserver::deleted triggered', [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'lease_service_set_id' => $leaseServiceSetItem->lease_service_set_id,
                'service_id' => $leaseServiceSetItem->service_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($leaseServiceSetItem);

        } catch (\Exception $e) {
            Log::error('Error in LeaseServiceSetItemObserver::deleted: ' . $e->getMessage(), [
                'lease_service_set_item_id' => $leaseServiceSetItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

