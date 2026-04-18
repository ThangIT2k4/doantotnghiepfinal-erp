<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        try {
            Log::info('ServiceObserver::created triggered', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'key_code' => $service->key_code,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($service);

        } catch (\Exception $e) {
            Log::error('Error in ServiceObserver::created: ' . $e->getMessage(), [
                'service_id' => $service->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        try {
            Log::info('ServiceObserver::updated triggered', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'changes' => $service->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($service);

        } catch (\Exception $e) {
            Log::error('Error in ServiceObserver::updated: ' . $e->getMessage(), [
                'service_id' => $service->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        // Validate business rules first (soft delete)
        if (!$service->isForceDeleting()) {
            $this->businessRulesService->validate($service, 'deleting');
        }
        
        try {
            Log::info('ServiceObserver::deleted triggered', [
                'service_id' => $service->id,
                'service_name' => $service->name,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($service);

        } catch (\Exception $e) {
            Log::error('Error in ServiceObserver::deleted: ' . $e->getMessage(), [
                'service_id' => $service->id,
                'error' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to prevent deletion if validation fails
        }
    }
}

