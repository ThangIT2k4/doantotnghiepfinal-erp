<?php

namespace App\Listeners;

use App\Events\SalaryAdvanceCreated;
use App\Services\SalaryAdvanceNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSalaryAdvanceCreatedNotification
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
    public function handle(SalaryAdvanceCreated $event): void
    {
        $this->notificationService->notifySalaryAdvanceCreated($event->salaryAdvance);
    }
}
