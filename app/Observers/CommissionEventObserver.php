<?php

namespace App\Observers;

use App\Models\CommissionEvent;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class CommissionEventObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the CommissionEvent "created" event.
     */
    public function created(CommissionEvent $commissionEvent): void
    {
        try {
            Log::info('CommissionEventObserver::created triggered', [
                'commission_event_id' => $commissionEvent->id,
                'trigger_event' => $commissionEvent->trigger_event,
                'organization_id' => $commissionEvent->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($commissionEvent);

        } catch (\Exception $e) {
            Log::error('Error in CommissionEventObserver::created: ' . $e->getMessage(), [
                'commission_event_id' => $commissionEvent->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CommissionEvent "updated" event.
     */
    public function updated(CommissionEvent $commissionEvent): void
    {
        try {
            Log::info('CommissionEventObserver::updated triggered', [
                'commission_event_id' => $commissionEvent->id,
                'changes' => $commissionEvent->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($commissionEvent);

        } catch (\Exception $e) {
            Log::error('Error in CommissionEventObserver::updated: ' . $e->getMessage(), [
                'commission_event_id' => $commissionEvent->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CommissionEvent "deleted" event.
     */
    public function deleted(CommissionEvent $commissionEvent): void
    {
        try {
            Log::info('CommissionEventObserver::deleted triggered', [
                'commission_event_id' => $commissionEvent->id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($commissionEvent);

        } catch (\Exception $e) {
            Log::error('Error in CommissionEventObserver::deleted: ' . $e->getMessage(), [
                'commission_event_id' => $commissionEvent->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

