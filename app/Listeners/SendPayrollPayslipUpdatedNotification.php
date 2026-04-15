<?php

namespace App\Listeners;

use App\Events\PayrollPayslipUpdated;
use App\Services\PayrollPayslipNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPayrollPayslipUpdatedNotification
{
    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(PayrollPayslipNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(PayrollPayslipUpdated $event): void
    {
        $this->notificationService->notifyPayrollPayslipUpdated(
            $event->payrollPayslip, 
            $event->oldStatus, 
            $event->newStatus
        );
    }
}
