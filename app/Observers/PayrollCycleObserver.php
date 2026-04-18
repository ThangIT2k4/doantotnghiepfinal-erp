<?php

namespace App\Observers;

use App\Models\PayrollCycle;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class PayrollCycleObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the PayrollCycle "created" event.
     */
    public function created(PayrollCycle $payrollCycle): void
    {
        try {
            Log::info('PayrollCycleObserver::created triggered', [
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $payrollCycle->organization_id,
                'period_month' => $payrollCycle->period_month,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($payrollCycle);

        } catch (\Exception $e) {
            Log::error('Error in PayrollCycleObserver::created: ' . $e->getMessage(), [
                'payroll_cycle_id' => $payrollCycle->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PayrollCycle "updated" event.
     */
    public function updated(PayrollCycle $payrollCycle): void
    {
        try {
            Log::info('PayrollCycleObserver::updated triggered', [
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $payrollCycle->organization_id,
                'changes' => $payrollCycle->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($payrollCycle);

        } catch (\Exception $e) {
            Log::error('Error in PayrollCycleObserver::updated: ' . $e->getMessage(), [
                'payroll_cycle_id' => $payrollCycle->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PayrollCycle "deleted" event.
     */
    public function deleted(PayrollCycle $payrollCycle): void
    {
        try {
            Log::info('PayrollCycleObserver::deleted triggered', [
                'payroll_cycle_id' => $payrollCycle->id,
                'organization_id' => $payrollCycle->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($payrollCycle);

        } catch (\Exception $e) {
            Log::error('Error in PayrollCycleObserver::deleted: ' . $e->getMessage(), [
                'payroll_cycle_id' => $payrollCycle->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

