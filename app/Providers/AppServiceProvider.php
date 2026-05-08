<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Date;
use App\Models\BookingDeposit;
use App\Models\Invoice;
use App\Models\CompanyInvoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\Viewing;
use App\Models\SalaryAdvance;
use App\Models\PayrollPayslip;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\MasterLease;
use App\Models\LeaseServiceSet;
use App\Models\LeaseServiceSetItem;
use App\Models\DepositRefund;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lead;
use App\Models\Document;
use App\Models\Commission;
use App\Models\CommissionEvent;
use App\Models\CommissionPolicy;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\CashOutflow;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\Organization;
use App\Models\OrganizationBanking;
use App\Models\OrganizationEmailSetting;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvoice;
use App\Models\PaymentMethod;
use App\Models\PaymentCycle;
use App\Models\PropertyType;
use App\Models\LeaseResident;
use App\Models\SalaryContract;
use App\Models\PayrollCycle;
use App\Models\User;
use App\Observers\BookingDepositObserver;
use App\Observers\InvoiceObserver;
use App\Observers\CompanyInvoiceObserver;
use App\Observers\LeaseObserver;
use App\Observers\MasterLeaseObserver;
use App\Observers\PaymentObserver;
use App\Observers\TicketObserver;
use App\Observers\TicketLogObserver;
use App\Observers\ViewingObserver;
use App\Observers\SalaryAdvanceObserver;
use App\Observers\PayrollPayslipObserver;
use App\Observers\ReviewObserver;
use App\Observers\ReviewReplyObserver;
use App\Observers\LeaseServiceSetObserver;
use App\Observers\LeaseServiceSetItemObserver;
use App\Observers\DepositRefundObserver;
use App\Observers\PropertyObserver;
use App\Observers\UnitObserver;
use App\Observers\LeadObserver;
use App\Observers\DocumentObserver;
use App\Observers\CommissionObserver;
use App\Observers\CommissionEventObserver;
use App\Observers\CommissionPolicyObserver;
use App\Observers\MeterObserver;
use App\Observers\MeterReadingObserver;
use App\Observers\CashOutflowObserver;
use App\Observers\VendorObserver;
use App\Observers\ServiceObserver;
use App\Observers\OrganizationObserver;
use App\Observers\OrganizationBankingObserver;
use App\Observers\OrganizationEmailSettingObserver;
use App\Observers\OrganizationSubscriptionObserver;
use App\Observers\SubscriptionPlanObserver;
use App\Observers\SubscriptionInvoiceObserver;
use App\Observers\PaymentMethodObserver;
use App\Observers\PaymentCycleObserver;
use App\Observers\PropertyTypeObserver;
use App\Observers\LeaseResidentObserver;
use App\Observers\SalaryContractObserver;
use App\Observers\PayrollCycleObserver;
use App\Observers\InvoiceItemObserver;
use App\Observers\AuditLogObserver;
use App\Observers\UserObserver;
use App\Models\AuditLog;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
// CommissionEventObserver removed - no longer creating invoices for commission events

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS for all generated URLs when APP_URL uses https
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Set timezone for Carbon/Date
        Date::setLocale('vi');
        
        // Ensure all dates use the app timezone
        config(['app.timezone' => config('app.timezone', 'Asia/Ho_Chi_Minh')]);
        date_default_timezone_set(config('app.timezone'));
        
        // Register model observers for automatic invoice updates and audit logging
        BookingDeposit::observe(BookingDepositObserver::class);
        Invoice::observe(InvoiceObserver::class);
        CompanyInvoice::observe(CompanyInvoiceObserver::class);
        Lease::observe(LeaseObserver::class);
        // LeaseService observer removed - LeaseService model is deprecated, use LeaseServiceSet instead
        MasterLease::observe(MasterLeaseObserver::class);
        Payment::observe(PaymentObserver::class);
        Ticket::observe(TicketObserver::class);
        TicketLog::observe(TicketLogObserver::class);
        Viewing::observe(ViewingObserver::class);
        SalaryAdvance::observe(SalaryAdvanceObserver::class);
        PayrollPayslip::observe(PayrollPayslipObserver::class);
        Review::observe(ReviewObserver::class);
        ReviewReply::observe(ReviewReplyObserver::class);
        LeaseServiceSet::observe(LeaseServiceSetObserver::class);
        LeaseServiceSetItem::observe(LeaseServiceSetItemObserver::class);
        DepositRefund::observe(DepositRefundObserver::class);
        
        // Register observers for property and unit management
        Property::observe(PropertyObserver::class);
        Unit::observe(UnitObserver::class);
        Lead::observe(LeadObserver::class);
        Document::observe(DocumentObserver::class);
        
        // Register observers for commission management
        Commission::observe(CommissionObserver::class);
        CommissionEvent::observe(CommissionEventObserver::class);
        CommissionPolicy::observe(CommissionPolicyObserver::class);
        
        // Register observers for meter and utility management
        Meter::observe(MeterObserver::class);
        MeterReading::observe(MeterReadingObserver::class);
        CashOutflow::observe(CashOutflowObserver::class);
        Vendor::observe(VendorObserver::class);
        Service::observe(ServiceObserver::class);
        
        // Register observers for organization management
        Organization::observe(OrganizationObserver::class);
        OrganizationBanking::observe(OrganizationBankingObserver::class);
        OrganizationEmailSetting::observe(OrganizationEmailSettingObserver::class);
        OrganizationSubscription::observe(OrganizationSubscriptionObserver::class);
        
        // Register observers for subscription management
        SubscriptionPlan::observe(SubscriptionPlanObserver::class);
        SubscriptionInvoice::observe(SubscriptionInvoiceObserver::class);
        
        // Register observers for payment and billing
        PaymentMethod::observe(PaymentMethodObserver::class);
        PaymentCycle::observe(PaymentCycleObserver::class);
        
        // Register observers for property types
        PropertyType::observe(PropertyTypeObserver::class);
        
        // Register observers for lease and payroll
        LeaseResident::observe(LeaseResidentObserver::class);
        SalaryContract::observe(SalaryContractObserver::class);
        PayrollCycle::observe(PayrollCycleObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        
        // Register AuditLogObserver để tự động tạo notifications từ audit_logs
        AuditLog::observe(AuditLogObserver::class);
        
        // Register UserObserver để theo dõi các thay đổi trong bảng users
        User::observe(UserObserver::class);

        // Blade capability check: @cap('unit.update', orgId)
        Blade::if('cap', function (string $capability, ?int $orgId = null) {
            return Gate::allows('capability', [$capability, $orgId]);
        });
    }
}
