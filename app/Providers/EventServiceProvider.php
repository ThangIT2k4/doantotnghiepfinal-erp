<?php

namespace App\Providers;

use App\Events\CommissionEventNotification;
use App\Events\SalaryAdvanceCreated;
use App\Events\SalaryAdvanceUpdated;
use App\Events\PayrollPayslipCreated;
use App\Events\PayrollPayslipUpdated;
use App\Listeners\SendCommissionNotification;
use App\Listeners\SendSalaryAdvanceCreatedNotification;
use App\Listeners\SendSalaryAdvanceUpdatedNotification;
use App\Listeners\SendPayrollPayslipCreatedNotification;
use App\Listeners\SendPayrollPayslipUpdatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     * 
     * Note: Notifications for tenant-related events (Invoice, Payment, Ticket, Review, Lease, Viewing)
     * are now handled automatically by AuditLogObserver via NotificationFromAuditService.
     * Only non-tenant events are kept here.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CommissionEventNotification::class => [
            SendCommissionNotification::class,
        ],
        SalaryAdvanceCreated::class => [
            SendSalaryAdvanceCreatedNotification::class,
        ],
        SalaryAdvanceUpdated::class => [
            SendSalaryAdvanceUpdatedNotification::class,
        ],
        PayrollPayslipCreated::class => [
            SendPayrollPayslipCreatedNotification::class,
        ],
        PayrollPayslipUpdated::class => [
            SendPayrollPayslipUpdatedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
