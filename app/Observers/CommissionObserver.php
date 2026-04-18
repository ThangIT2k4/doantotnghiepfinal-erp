<?php

namespace App\Observers;

use App\Models\Commission;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class CommissionObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Commission "created" event.
     */
    public function created(Commission $commission): void
    {
        try {
            Log::info('CommissionObserver::created triggered', [
                'commission_id' => $commission->id,
                'user_id' => $commission->user_id,
                'organization_id' => $commission->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($commission);

        } catch (\Exception $e) {
            Log::error('Error in CommissionObserver::created: ' . $e->getMessage(), [
                'commission_id' => $commission->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Commission "updated" event.
     */
    public function updated(Commission $commission): void
    {
        try {
            Log::info('CommissionObserver::updated triggered', [
                'commission_id' => $commission->id,
                'changes' => $commission->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($commission);

        } catch (\Exception $e) {
            Log::error('Error in CommissionObserver::updated: ' . $e->getMessage(), [
                'commission_id' => $commission->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Commission "deleted" event.
     */
    public function deleted(Commission $commission): void
    {
        try {
            Log::info('CommissionObserver::deleted triggered', [
                'commission_id' => $commission->id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($commission);

        } catch (\Exception $e) {
            Log::error('Error in CommissionObserver::deleted: ' . $e->getMessage(), [
                'commission_id' => $commission->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

