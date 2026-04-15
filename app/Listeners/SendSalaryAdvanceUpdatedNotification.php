<?php

namespace App\Listeners;

use App\Events\SalaryAdvanceUpdated;
use App\Services\SalaryAdvanceNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSalaryAdvanceUpdatedNotification
{
    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(SalaryAdvanceNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(SalaryAdvanceUpdated $event): void
    {
        $this->notificationService->notifySalaryAdvanceUpdated(
            $event->salaryAdvance, 
            $event->oldStatus, 
            $event->newStatus
        );
    }
}
