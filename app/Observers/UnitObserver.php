<?php

namespace App\Observers;

use App\Models\Unit;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class UnitObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the Unit "created" event.
     */
    public function created(Unit $unit): void
    {
        try {
            Log::info('UnitObserver::created triggered', [
                'unit_id' => $unit->id,
                'unit_code' => $unit->code,
                'property_id' => $unit->property_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($unit);

        } catch (\Exception $e) {
            Log::error('Error in UnitObserver::created: ' . $e->getMessage(), [
                'unit_id' => $unit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Unit "updated" event.
     */
    public function updated(Unit $unit): void
    {
        try {
            Log::info('UnitObserver::updated triggered', [
                'unit_id' => $unit->id,
                'unit_code' => $unit->code,
                'changes' => $unit->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($unit);

        } catch (\Exception $e) {
            Log::error('Error in UnitObserver::updated: ' . $e->getMessage(), [
                'unit_id' => $unit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Unit "deleting" event.
     * This fires BEFORE the model is deleted, allowing us to prevent deletion.
     */
    public function deleting(Unit $unit): void
    {
        // Validate business rules for soft delete only
        if (!$unit->isForceDeleting()) {
            try {
                Log::info('UnitObserver::deleting - Validating business rules', [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code
                ]);
                
                $this->businessRulesService->validate($unit, 'deleting');
                
            } catch (\Exception $e) {
                Log::error('UnitObserver::deleting - Validation failed', [
                    'unit_id' => $unit->id,
                    'error' => $e->getMessage()
                ]);
                throw $e; // Re-throw to prevent deletion
            }
        }
    }

    /**
     * Handle the Unit "deleted" event.
     * This fires AFTER the model is deleted.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Unit $unit): void
    {
        try {
            $isForceDelete = $unit->isForceDeleting();
            
            Log::info('UnitObserver::deleted triggered', [
                'unit_id' => $unit->id,
                'unit_code' => $unit->code,
                'is_force_deleting' => $isForceDelete,
                'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($unit);

        } catch (\Exception $e) {
            Log::error('Error in UnitObserver::deleted: ' . $e->getMessage(), [
                'unit_id' => $unit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Unit "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Unit $unit): void
    {
        Log::info('Unit force deleted (permanent delete completed)', [
            'unit_id' => $unit->id,
            'unit_code' => $unit->code ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }
}

