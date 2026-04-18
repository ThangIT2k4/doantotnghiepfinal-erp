<?php

namespace App\Observers;

use App\Models\Vendor;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class VendorObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Vendor "created" event.
     */
    public function created(Vendor $vendor): void
    {
        try {
            Log::info('VendorObserver::created triggered', [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'organization_id' => $vendor->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($vendor);

        } catch (\Exception $e) {
            Log::error('Error in VendorObserver::created: ' . $e->getMessage(), [
                'vendor_id' => $vendor->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Vendor "updated" event.
     */
    public function updated(Vendor $vendor): void
    {
        try {
            Log::info('VendorObserver::updated triggered', [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'changes' => $vendor->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($vendor);

        } catch (\Exception $e) {
            Log::error('Error in VendorObserver::updated: ' . $e->getMessage(), [
                'vendor_id' => $vendor->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Vendor "deleted" event.
     */
    public function deleted(Vendor $vendor): void
    {
        try {
            Log::info('VendorObserver::deleted triggered', [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($vendor);

        } catch (\Exception $e) {
            Log::error('Error in VendorObserver::deleted: ' . $e->getMessage(), [
                'vendor_id' => $vendor->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

