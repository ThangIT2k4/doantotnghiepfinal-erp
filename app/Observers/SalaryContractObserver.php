<?php

namespace App\Observers;

use App\Models\SalaryContract;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class SalaryContractObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the SalaryContract "created" event.
     */
    public function created(SalaryContract $salaryContract): void
    {
        try {
            Log::info('SalaryContractObserver::created triggered', [
                'salary_contract_id' => $salaryContract->id,
                'organization_id' => $salaryContract->organization_id,
                'user_id' => $salaryContract->user_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($salaryContract);

        } catch (\Exception $e) {
            Log::error('Error in SalaryContractObserver::created: ' . $e->getMessage(), [
                'salary_contract_id' => $salaryContract->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SalaryContract "updated" event.
     */
    public function updated(SalaryContract $salaryContract): void
    {
        try {
            Log::info('SalaryContractObserver::updated triggered', [
                'salary_contract_id' => $salaryContract->id,
                'organization_id' => $salaryContract->organization_id,
                'changes' => $salaryContract->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($salaryContract);

        } catch (\Exception $e) {
            Log::error('Error in SalaryContractObserver::updated: ' . $e->getMessage(), [
                'salary_contract_id' => $salaryContract->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SalaryContract "deleted" event.
     */
    public function deleted(SalaryContract $salaryContract): void
    {
        try {
            Log::info('SalaryContractObserver::deleted triggered', [
                'salary_contract_id' => $salaryContract->id,
                'organization_id' => $salaryContract->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($salaryContract);

        } catch (\Exception $e) {
            Log::error('Error in SalaryContractObserver::deleted: ' . $e->getMessage(), [
                'salary_contract_id' => $salaryContract->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

