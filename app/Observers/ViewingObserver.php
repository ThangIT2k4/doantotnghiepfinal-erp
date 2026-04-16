<?php

namespace App\Observers;

use App\Models\Viewing;
use App\Events\ViewingUpdated;
use App\Services\CommissionEventService;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class ViewingObserver
{
    protected $commissionEventService;
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(
        CommissionEventService $commissionEventService,
        AuditLogService $auditLogService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->commissionEventService = $commissionEventService;
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the Viewing "created" event.
     */
    public function created(Viewing $viewing): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($viewing, 'creating');
        
        // Không tạo sự kiện hoa hồng khi tạo viewing, chỉ khi hoàn thành
        Log::info('Viewing created', [
            'viewing_id' => $viewing->id,
            'agent_id' => $viewing->agent_id,
            'status' => $viewing->status
        ]);

        // Log audit trail
        $this->auditLogService->logCreated($viewing);
    }

    /**
     * Handle the Viewing "updated" event.
     */
    public function updated(Viewing $viewing): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($viewing, 'updating');
        
        // Get the changes that were made
        $changes = $viewing->getChanges();
        
        Log::info('Viewing updated', [
            'viewing_id' => $viewing->id,
            'agent_id' => $viewing->agent_id,
            'status' => $viewing->status,
            'changes' => $changes
        ]);

        // Dispatch ViewingUpdated event for notifications
        event(new ViewingUpdated($viewing, $changes));

        // Log audit trail
        $this->auditLogService->logUpdated($viewing, $viewing->getDirty());

        // Tạo sự kiện hoa hồng khi viewing được hoàn thành (status = 'done')
        if ($viewing->isDirty('status') && $viewing->status === 'done') {
            $this->createCommissionEvents($viewing);
        }
    }

    /**
     * Handle the Viewing "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Viewing $viewing): void
    {
        // Validate business rules first (soft delete only)
        if (!$viewing->isForceDeleting()) {
            $this->businessRulesService->validate($viewing, 'deleting');
        }
        
        $isForceDelete = $viewing->isForceDeleting();
        
        Log::info('Viewing deleted', [
            'viewing_id' => $viewing->id,
            'agent_id' => $viewing->agent_id,
            'is_force_deleting' => $isForceDelete,
            'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
        ]);

        // Log audit trail for both soft delete and force delete
        // The audit log will record the deletion action
        $this->auditLogService->logDeleted($viewing);
    }

    /**
     * Handle the Viewing "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     * Note: Model attributes are still available in memory, but the record is gone from DB.
     */
    public function forceDeleted(Viewing $viewing): void
    {
        Log::info('Viewing force deleted (permanent delete completed)', [
            'viewing_id' => $viewing->id,
            'agent_id' => $viewing->agent_id,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }

    /**
     * Create commission events for viewing when completed
     */
    private function createCommissionEvents(Viewing $viewing)
    {
        try {
            $result = $this->commissionEventService->createCommissionEventsForViewing($viewing);
            
            if ($result) {
                Log::info('Commission events created successfully via ViewingObserver', [
                    'viewing_id' => $viewing->id,
                    'created_events_count' => count($result)
                ]);
            } else {
                Log::warning('Failed to create commission events via ViewingObserver', [
                    'viewing_id' => $viewing->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error creating commission events in ViewingObserver: ' . $e->getMessage(), [
                'viewing_id' => $viewing->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}
