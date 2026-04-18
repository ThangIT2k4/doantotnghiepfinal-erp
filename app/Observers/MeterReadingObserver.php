<?php

namespace App\Observers;

use App\Models\MeterReading;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class MeterReadingObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the MeterReading "created" event.
     */
    public function created(MeterReading $meterReading): void
    {
        try {
            Log::info('MeterReadingObserver::created triggered', [
                'meter_reading_id' => $meterReading->id,
                'meter_id' => $meterReading->meter_id,
                'reading_date' => $meterReading->reading_date,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($meterReading);

        } catch (\Exception $e) {
            Log::error('Error in MeterReadingObserver::created: ' . $e->getMessage(), [
                'meter_reading_id' => $meterReading->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the MeterReading "updated" event.
     */
    public function updated(MeterReading $meterReading): void
    {
        try {
            Log::info('MeterReadingObserver::updated triggered', [
                'meter_reading_id' => $meterReading->id,
                'meter_id' => $meterReading->meter_id,
                'changes' => $meterReading->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($meterReading);

        } catch (\Exception $e) {
            Log::error('Error in MeterReadingObserver::updated: ' . $e->getMessage(), [
                'meter_reading_id' => $meterReading->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the MeterReading "deleted" event.
     */
    public function deleted(MeterReading $meterReading): void
    {
        try {
            Log::info('MeterReadingObserver::deleted triggered', [
                'meter_reading_id' => $meterReading->id,
                'meter_id' => $meterReading->meter_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($meterReading);

        } catch (\Exception $e) {
            Log::error('Error in MeterReadingObserver::deleted: ' . $e->getMessage(), [
                'meter_reading_id' => $meterReading->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

