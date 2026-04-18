<?php

namespace App\Observers;

use App\Models\PropertyType;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class PropertyTypeObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the PropertyType "created" event.
     */
    public function created(PropertyType $propertyType): void
    {
        try {
            Log::info('PropertyTypeObserver::created triggered', [
                'property_type_id' => $propertyType->id,
                'name' => $propertyType->name,
                'key_code' => $propertyType->key_code,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($propertyType);

        } catch (\Exception $e) {
            Log::error('Error in PropertyTypeObserver::created: ' . $e->getMessage(), [
                'property_type_id' => $propertyType->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PropertyType "updated" event.
     */
    public function updated(PropertyType $propertyType): void
    {
        try {
            Log::info('PropertyTypeObserver::updated triggered', [
                'property_type_id' => $propertyType->id,
                'name' => $propertyType->name,
                'changes' => $propertyType->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($propertyType);

        } catch (\Exception $e) {
            Log::error('Error in PropertyTypeObserver::updated: ' . $e->getMessage(), [
                'property_type_id' => $propertyType->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PropertyType "deleted" event.
     */
    public function deleted(PropertyType $propertyType): void
    {
        // Validate business rules first (soft delete)
        if (!$propertyType->isForceDeleting()) {
            $this->businessRulesService->validate($propertyType, 'deleting');
        }
        
        try {
            Log::info('PropertyTypeObserver::deleted triggered', [
                'property_type_id' => $propertyType->id,
                'name' => $propertyType->name,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($propertyType);

        } catch (\Exception $e) {
            Log::error('Error in PropertyTypeObserver::deleted: ' . $e->getMessage(), [
                'property_type_id' => $propertyType->id,
                'error' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to prevent deletion if validation fails
        }
    }
}

