<?php

namespace App\Observers;

use App\Models\Meter;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class MeterObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Meter "created" event.
     */
    public function created(Meter $meter): void
    {
        try {
            Log::info('MeterObserver::created triggered', [
                'meter_id' => $meter->id,
                'serial_no' => $meter->serial_no,
                'unit_id' => $meter->unit_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($meter);

        } catch (\Exception $e) {
            Log::error('Error in MeterObserver::created: ' . $e->getMessage(), [
                'meter_id' => $meter->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Meter "updated" event.
     */
    public function updated(Meter $meter): void
    {
        try {
            Log::info('MeterObserver::updated triggered', [
                'meter_id' => $meter->id,
                'serial_no' => $meter->serial_no,
                'changes' => $meter->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($meter);

        } catch (\Exception $e) {
            Log::error('Error in MeterObserver::updated: ' . $e->getMessage(), [
                'meter_id' => $meter->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Meter "deleted" event.
     */
    public function deleted(Meter $meter): void
    {
        try {
            Log::info('MeterObserver::deleted triggered', [
                'meter_id' => $meter->id,
                'serial_no' => $meter->serial_no,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($meter);

        } catch (\Exception $e) {
            Log::error('Error in MeterObserver::deleted: ' . $e->getMessage(), [
                'meter_id' => $meter->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

