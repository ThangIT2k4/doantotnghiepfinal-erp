<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class PropertyObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(AuditLogService $auditLogService, BusinessRulesService $businessRulesService)
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the Property "created" event.
     */
    public function created(Property $property): void
    {
        try {
            Log::info('PropertyObserver::created triggered', [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'organization_id' => $property->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($property);

        } catch (\Exception $e) {
            Log::error('Error in PropertyObserver::created: ' . $e->getMessage(), [
                'property_id' => $property->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Property "updated" event.
     */
    public function updated(Property $property): void
    {
        try {
            Log::info('PropertyObserver::updated triggered', [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'changes' => $property->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($property);

        } catch (\Exception $e) {
            Log::error('Error in PropertyObserver::updated: ' . $e->getMessage(), [
                'property_id' => $property->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Property "deleting" event.
     * This fires BEFORE the model is deleted, allowing us to prevent deletion.
     */
    public function deleting(Property $property): void
    {
        // Validate business rules for soft delete only
        if (!$property->isForceDeleting()) {
            try {
                Log::info('PropertyObserver::deleting - Validating business rules', [
                    'property_id' => $property->id,
                    'property_name' => $property->name
                ]);
                
                $this->businessRulesService->validate($property, 'deleting');
                
            } catch (\Exception $e) {
                Log::error('PropertyObserver::deleting - Validation failed', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage()
                ]);
                throw $e; // Re-throw to prevent deletion
            }
        }
    }

    /**
     * Handle the Property "deleted" event.
     * This fires AFTER the model is deleted.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Property $property): void
    {
        try {
            $isForceDelete = $property->isForceDeleting();
            
            Log::info('PropertyObserver::deleted triggered', [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'is_force_deleting' => $isForceDelete,
                'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
            ]);

            // Chuyển tất cả master_lease liên quan về trạng thái "chấm dứt" (terminated)
            // Lưu property_id trước khi xóa để query
            $propertyId = $property->id;
            
            $masterLeases = \App\Models\MasterLease::where('property_id', $propertyId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['active', 'draft'])
                ->get();

            if ($masterLeases->count() > 0) {
                Log::info('PropertyObserver::deleted - Terminating master leases', [
                    'property_id' => $propertyId,
                    'master_lease_count' => $masterLeases->count()
                ]);

                foreach ($masterLeases as $masterLease) {
                    $oldStatus = $masterLease->status;
                    
                    $masterLease->update([
                        'status' => 'terminated',
                    ]);

                    Log::info('PropertyObserver::deleted - Master lease terminated', [
                        'master_lease_id' => $masterLease->id,
                        'contract_no' => $masterLease->contract_no,
                        'old_status' => $oldStatus,
                        'new_status' => 'terminated'
                    ]);
                }
            }

            // Log audit trail
            $this->auditLogService->logDeleted($property);

        } catch (\Exception $e) {
            Log::error('Error in PropertyObserver::deleted: ' . $e->getMessage(), [
                'property_id' => $property->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Property "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Property $property): void
    {
        Log::info('Property force deleted (permanent delete completed)', [
            'property_id' => $property->id,
            'property_name' => $property->name ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }
}

