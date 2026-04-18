<?php

namespace App\Observers;

use App\Models\SalaryAdvance;
use App\Services\AuditLogService;
use App\Events\SalaryAdvanceCreated;
use App\Events\SalaryAdvanceUpdated;

class SalaryAdvanceObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    /**
     * Handle the SalaryAdvance "created" event.
     */
    public function created(SalaryAdvance $salaryAdvance): void
    {
        event(new SalaryAdvanceCreated($salaryAdvance));
        
        // Log audit trail
        $this->auditLogService->logCreated($salaryAdvance);
    }

    /**
     * Handle the SalaryAdvance "updated" event.
     */
    public function updated(SalaryAdvance $salaryAdvance): void
    {
        // Check if status changed
        if ($salaryAdvance->isDirty('status')) {
            $oldStatus = $salaryAdvance->getOriginal('status');
            $newStatus = $salaryAdvance->status;
            
            event(new SalaryAdvanceUpdated($salaryAdvance, $oldStatus, $newStatus));
        }
        
        // Log audit trail for all changes
        $this->auditLogService->logUpdated($salaryAdvance);
    }

    /**
     * Handle the SalaryAdvance "deleted" event.
     */
    public function deleted(SalaryAdvance $salaryAdvance): void
    {
        // Log audit trail
        $this->auditLogService->logDeleted($salaryAdvance);
    }

    /**
     * Handle the SalaryAdvance "restored" event.
     */
    public function restored(SalaryAdvance $salaryAdvance): void
    {
        // Handle restoration if needed
    }

    /**
     * Handle the SalaryAdvance "force deleted" event.
     */
    public function forceDeleted(SalaryAdvance $salaryAdvance): void
    {
        // Handle force deletion if needed
    }
}
