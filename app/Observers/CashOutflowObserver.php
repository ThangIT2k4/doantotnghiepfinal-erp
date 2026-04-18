<?php

namespace App\Observers;

use App\Models\CashOutflow;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class CashOutflowObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the CashOutflow "created" event.
     */
    public function created(CashOutflow $cashOutflow): void
    {
        try {
            Log::info('CashOutflowObserver::created triggered', [
                'cash_outflow_id' => $cashOutflow->id,
                'amount' => $cashOutflow->amount,
                'status' => $cashOutflow->status,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($cashOutflow);

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowObserver::created: ' . $e->getMessage(), [
                'cash_outflow_id' => $cashOutflow->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CashOutflow "updated" event.
     */
    public function updated(CashOutflow $cashOutflow): void
    {
        try {
            Log::info('CashOutflowObserver::updated triggered', [
                'cash_outflow_id' => $cashOutflow->id,
                'changes' => $cashOutflow->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($cashOutflow);

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowObserver::updated: ' . $e->getMessage(), [
                'cash_outflow_id' => $cashOutflow->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CashOutflow "deleted" event.
     */
    public function deleted(CashOutflow $cashOutflow): void
    {
        try {
            Log::info('CashOutflowObserver::deleted triggered', [
                'cash_outflow_id' => $cashOutflow->id,
                'amount' => $cashOutflow->amount,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($cashOutflow);

        } catch (\Exception $e) {
            Log::error('Error in CashOutflowObserver::deleted: ' . $e->getMessage(), [
                'cash_outflow_id' => $cashOutflow->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

