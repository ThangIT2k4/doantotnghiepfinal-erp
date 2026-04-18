<?php

namespace App\Observers;

use App\Models\SubscriptionInvoice;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class SubscriptionInvoiceObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the SubscriptionInvoice "created" event.
     */
    public function created(SubscriptionInvoice $invoice): void
    {
        try {
            Log::info('SubscriptionInvoiceObserver::created triggered', [
                'subscription_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'organization_subscription_id' => $invoice->organization_subscription_id,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($invoice);

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionInvoiceObserver::created: ' . $e->getMessage(), [
                'subscription_invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SubscriptionInvoice "updated" event.
     */
    public function updated(SubscriptionInvoice $invoice): void
    {
        try {
            Log::info('SubscriptionInvoiceObserver::updated triggered', [
                'subscription_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'changes' => $invoice->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($invoice);

            // Nếu invoice chuyển sang trạng thái paid, tự động kích hoạt subscription
            if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
                $this->activateSubscription($invoice);
            }

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionInvoiceObserver::updated: ' . $e->getMessage(), [
                'subscription_invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Activate subscription when invoice is paid
     * 
     * Logic mới:
     * 1. Chuyển subscription từ suspended → active
     * 2. Tính thời hạn từ ngày đăng ký ban đầu + số ngày hiệu lực
     * 3. Cancel tất cả subscription cũ (trial/active) của organization
     * 4. Đánh dấu organization đã thanh toán
     * 
     * Chỉ có 1 subscription active tại một thời điểm
     */
    protected function activateSubscription(SubscriptionInvoice $invoice): void
    {
        try {
            $subscription = $invoice->subscription;
            
            if (!$subscription) {
                Log::warning('Cannot activate subscription: subscription not found', [
                    'invoice_id' => $invoice->id,
                ]);
                return;
            }

            // Chỉ kích hoạt nếu subscription đang ở trạng thái suspended
            if ($subscription->status === 'active') {
                Log::info('Subscription already active, skipping activation', [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                ]);
                return;
            }

            // Chỉ xử lý subscription suspended (subscription đăng ký mới chưa thanh toán)
            if ($subscription->status !== 'suspended') {
                Log::warning('Subscription is not suspended, cannot activate', [
                    'subscription_id' => $subscription->id,
                    'current_status' => $subscription->status,
                    'invoice_id' => $invoice->id,
                ]);
                return;
            }

            // Lấy ngày đăng ký ban đầu (current_period_start)
            // Nếu không có, dùng ngày hiện tại (fallback)
            $registrationDate = $subscription->current_period_start ?: now();
            
            // Tính thời hạn từ ngày đăng ký ban đầu + số ngày hiệu lực
            // KHÔNG tính từ ngày thanh toán
            // Nếu current_period_end đã là null (gói không giới hạn thời gian) → giữ nguyên null
            $periodEnd = null;
            if ($subscription->current_period_end !== null) {
                // Chỉ tính period_end nếu gói có giới hạn thời gian
                if ($subscription->payment_cycle === 'yearly') {
                    $periodEnd = $registrationDate->copy()->addYear();
                } else {
                    // Monthly: 30 ngày
                    $periodEnd = $registrationDate->copy()->addDays(30);
                }
            }
            // Nếu current_period_end = null → periodEnd = null (gói không giới hạn)

            // Kích hoạt subscription
            // Giữ nguyên current_period_start (ngày đăng ký ban đầu)
            $subscription->update([
                'status' => 'active',
                'current_period_start' => $registrationDate, // Giữ nguyên ngày đăng ký
                'current_period_end' => $periodEnd, // Tính từ ngày đăng ký + số ngày hiệu lực
            ]);

            // ============================================
            // CANCEL TẤT CẢ SUBSCRIPTION CŨ (TRIAL/ACTIVE)
            // ============================================
            // Sau khi subscription mới active, cancel tất cả subscription cũ
            // Đảm bảo mỗi organization chỉ có 1 subscription active
            $cancelledSubscriptions = \App\Models\OrganizationSubscription::where('organization_id', $subscription->organization_id)
                ->where('id', '!=', $subscription->id) // Loại trừ subscription mới
                ->whereIn('status', ['trial', 'active']) // Chỉ cancel trial/active
                ->get();

            foreach ($cancelledSubscriptions as $oldSub) {
                $oldSub->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                Log::info('Cancelled old subscription after activating new subscription', [
                    'old_subscription_id' => $oldSub->id,
                    'old_status' => $oldSub->status,
                    'new_subscription_id' => $subscription->id,
                    'organization_id' => $subscription->organization_id,
                ]);
            }

            // ============================================
            // Đánh dấu organization đã thanh toán
            // ============================================
            $organization = $subscription->organization;
            if ($organization) {
                $organization->markPaid();
                Log::info('Organization marked as paid', [
                    'organization_id' => $organization->id,
                    'subscription_id' => $subscription->id,
                    'paid_subscriptions_count' => $organization->paid_subscriptions_count,
                ]);
            }

            Log::info('Subscription activated from suspended to active after invoice payment', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'organization_id' => $subscription->organization_id,
                'registration_date' => $registrationDate->toDateTimeString(),
                'period_end' => $periodEnd ? $periodEnd->toDateTimeString() : 'unlimited (null)',
                'payment_cycle' => $subscription->payment_cycle,
                'cancelled_old_subscriptions_count' => $cancelledSubscriptions->count(),
                'is_unlimited' => $periodEnd === null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating subscription: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the SubscriptionInvoice "deleted" event.
     */
    public function deleted(SubscriptionInvoice $invoice): void
    {
        try {
            Log::info('SubscriptionInvoiceObserver::deleted triggered', [
                'subscription_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($invoice);

        } catch (\Exception $e) {
            Log::error('Error in SubscriptionInvoiceObserver::deleted: ' . $e->getMessage(), [
                'subscription_invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

