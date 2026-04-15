<?php

namespace App\Listeners;

use App\Events\CommissionEventNotification;
use App\Services\CommissionNotificationService;
use Illuminate\Support\Facades\Log;

class SendCommissionNotification
{
    protected $commissionNotificationService;

    /**
     * Create the event listener.
     */
    public function __construct(CommissionNotificationService $commissionNotificationService)
    {
        $this->commissionNotificationService = $commissionNotificationService;
    }

    /**
     * Handle the CommissionEvent.
     */
    public function handle(CommissionEventNotification $event): void
    {
        try {
            $commissionEvent = $event->commissionEvent;
            $eventType = $event->eventType;
            
            Log::info('Processing commission notification', [
                'commission_event_id' => $commissionEvent->id,
                'event_type' => $eventType,
                'agent_id' => $commissionEvent->agent_id,
                'amount' => $commissionEvent->commission_total,
                'status' => $commissionEvent->status
            ]);

            // Send notifications using CommissionNotificationService
            $success = $this->commissionNotificationService->notifyCommissionEvent($commissionEvent, $eventType);
            
            if ($success) {
                Log::info('Commission notifications sent successfully', [
                    'commission_event_id' => $commissionEvent->id,
                    'event_type' => $eventType
                ]);
            } else {
                Log::warning('Failed to send commission notifications', [
                    'commission_event_id' => $commissionEvent->id,
                    'event_type' => $eventType
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in SendCommissionNotification listener', [
                'commission_event_id' => $event->commissionEvent->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }
}
