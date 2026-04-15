<?php

namespace App\Listeners;

use App\Events\PayrollPayslipCreated;
use App\Services\PayrollPayslipNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPayrollPayslipCreatedNotification
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
    public function handle(PayrollPayslipCreated $event): void
    {
        $this->notificationService->notifyPayrollPayslipCreated($event->payrollPayslip);
    }
}
