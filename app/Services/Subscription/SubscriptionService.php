<?php

namespace App\Services\Subscription;

use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Assign a plan to an organization.
     * 
     * @param bool $startTrial - Bắt đầu với trial
     * @param bool $forceTrial - Bỏ qua kiểm tra canUseTrial() (dùng cho admin gán gói)
     */
    public function assignPlan(
        Organization $organization,
        SubscriptionPlan $plan,
        string $paymentCycle = 'monthly',
        bool $autoRenew = false,
        bool $startTrial = true,
        string $paymentGateway = 'manual',
        bool $forceTrial = false
    ): OrganizationSubscription {
        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $currentPeriodStart = $now;
            $currentPeriodEnd = null;
            $status = 'active';

            // If starting trial and plan has trial days
            // trial_days được lấy từ subscription_plans.trial_days trong database
            // Lưu thời hạn trial vào current_period_end (không cần trial_ends_at nữa)
            // Nếu trial_days = 0 → Gói không giới hạn thời gian (FREE plan) → current_period_end = null
            if ($startTrial && $plan->trial_days >= 0) {
                // Kiểm tra xem organization có thể dùng trial không
                // Nếu forceTrial = true (admin gán gói) → Bỏ qua kiểm tra canUseTrial()
                $canUseTrial = $forceTrial || $organization->canUseTrial();
                
                if (!$canUseTrial) {
                    // Nếu không thể dùng trial, tạo subscription active ngay
                    // (Đối với các trường hợp được gọi từ user đăng ký gói mới)
                    Log::warning('Organization cannot use trial, creating active subscription instead', [
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'force_trial' => $forceTrial,
                    ]);
                    $status = 'active';
                    // Calculate period based on payment cycle
                    // Nếu trial_days = 0 → unlimited (null), ngược lại tính theo cycle
                    if ($plan->trial_days === 0) {
                        $currentPeriodEnd = null; // Gói không giới hạn
                    } elseif ($paymentCycle === 'yearly') {
                        $currentPeriodEnd = $now->copy()->addYear();
                    } else {
                        $currentPeriodEnd = $now->copy()->addMonth();
                    }
                } else {
                    $status = 'trial';
                    // Sử dụng trial_days từ subscription_plans table
                    // Nếu trial_days = 0 → unlimited (null)
                    if ($plan->trial_days === 0) {
                        $currentPeriodEnd = null; // Gói không giới hạn
                    } else {
                        $currentPeriodEnd = $now->copy()->addDays($plan->trial_days);
                    }
                    
                    // Đánh dấu organization đã sử dụng trial
                    $organization->markTrialUsed();
                    
                    Log::info('Trial subscription created', [
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'trial_days' => $plan->trial_days,
                        'force_trial' => $forceTrial,
                    ]);
                }
            } else {
                // startTrial = false → Tạo subscription active ngay (không trial)
                // Nếu trial_days = 0 → Gói không giới hạn thời gian → current_period_end = null
                if ($plan->trial_days === 0) {
                    $currentPeriodEnd = null; // Gói không giới hạn (FREE plan)
                } else {
                    // Calculate period based on payment cycle
                    if ($paymentCycle === 'yearly') {
                        $currentPeriodEnd = $now->copy()->addYear();
                    } else {
                        $currentPeriodEnd = $now->copy()->addMonth();
                    }
                }
            }

            // TẠO SUBSCRIPTION MỚI TRƯỚC (để tránh mất gói đăng ký)
            $subscription = OrganizationSubscription::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
                'payment_cycle' => $paymentCycle,
                'payment_gateway' => $paymentGateway,
                'auto_renew' => $autoRenew,
            ]);

            // Create initial invoice if not on trial
            if ($status === 'active') {
                $this->createInvoice($subscription);
            }

            // SAU KHI TẠO THÀNH CÔNG, MỚI HỦY CÁC SUBSCRIPTION CŨ
            // Hủy tất cả subscription cũ (không quan trọng thời hạn) để tránh conflict
            // Loại trừ subscription vừa tạo
            $this->cancelOldSubscriptions($organization, $subscription->id);

            DB::commit();

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Start trial for an organization.
     */
    public function startTrial(Organization $organization, SubscriptionPlan $plan): OrganizationSubscription
    {
        if ($plan->trial_days <= 0) {
            throw new \Exception('This plan does not support trial period.');
        }

        return $this->assignPlan($organization, $plan, 'monthly', false, true);
    }

    /**
     * Activate a subscription (e.g., after trial ends or payment received).
     */
    public function activateSubscription(OrganizationSubscription $subscription): bool
    {
        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $currentPeriodEnd = $subscription->payment_cycle === 'yearly' 
                ? $now->copy()->addYear()
                : $now->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'current_period_start' => $now,
                'current_period_end' => $currentPeriodEnd,
            ]);

            // Create invoice for the new period
            $this->createInvoice($subscription);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(
        OrganizationSubscription $subscription,
        bool $immediately = false
    ): bool {
        DB::beginTransaction();

        try {
            if ($immediately) {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                    'auto_renew' => false,
                ]);
            } else {
                // Cancel at end of period
                $subscription->update([
                    'cancelled_at' => Carbon::now(),
                    'auto_renew' => false,
                ]);
            }

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Renew a subscription.
     */
    public function renewSubscription(OrganizationSubscription $subscription): bool
    {
        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $currentPeriodEnd = $subscription->payment_cycle === 'yearly' 
                ? $now->copy()->addYear()
                : $now->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'current_period_start' => $now,
                'current_period_end' => $currentPeriodEnd,
                'cancelled_at' => null,
            ]);

            // Create invoice for the new period
            $this->createInvoice($subscription);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Extend subscription manually (for admin use).
     */
    public function extendSubscription(OrganizationSubscription $subscription, int $days): bool
    {
        DB::beginTransaction();

        try {
            $currentEnd = $subscription->current_period_end ?? Carbon::now();
            $newEnd = Carbon::parse($currentEnd)->addDays($days);

            $subscription->update([
                'current_period_end' => $newEnd,
                'status' => 'active',
            ]);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check and expire subscriptions (for scheduled job).
     */
    public function checkAndExpireSubscriptions(): int
    {
        $expiredCount = 0;
        $now = Carbon::now();

        // Find subscriptions that should be expired
        $subscriptions = OrganizationSubscription::whereIn('status', ['trial', 'active'])
            ->where('current_period_end', '<', $now)
            ->where(function($query) {
                $query->where('auto_renew', false)
                      ->orWhereNotNull('cancelled_at');
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            DB::beginTransaction();

            try {
                $subscription->update(['status' => 'expired']);
                $expiredCount++;

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error expiring subscription: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id
                ]);
            }
        }

        return $expiredCount;
    }

    /**
     * Create an invoice for a subscription.
     */
    protected function createInvoice(OrganizationSubscription $subscription): SubscriptionInvoice
    {
        $plan = $subscription->plan;
        $amount = $subscription->payment_cycle === 'yearly' 
            ? $plan->price_yearly 
            : $plan->price_monthly;

        $invoiceNumber = $this->generateInvoiceNumber();

        return SubscriptionInvoice::create([
            'organization_subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $plan->currency,
            'status' => 'pending',
            'due_date' => $subscription->current_period_end ?? Carbon::now()->addDays(7),
            'payment_method' => $subscription->payment_gateway,
        ]);
    }

    /**
     * Generate unique invoice number.
     * Format: SUB{YYYYMMDD}{random} (không có dấu gạch để dễ so sánh với sepay)
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'SUB';
        $date = Carbon::now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        // Loại bỏ dấu gạch để tránh lỗi so sánh với sepay
        return "{$prefix}{$date}{$random}";
    }

    /**
     * Cancel existing active subscriptions for an organization.
     * DEPRECATED: Chỉ hủy subscription đã hết hạn, không hủy subscription còn hiệu lực.
     */
    protected function cancelExistingSubscriptions(Organization $organization): void
    {
        // Chỉ hủy các subscription đã hết hạn
        $this->cancelExpiredSubscriptions($organization);
    }

    /**
     * Chỉ hủy các subscription đã hết hạn (trial hoặc period).
     * KHÔNG hủy subscription còn hiệu lực khi chưa đăng ký gói mới thành công.
     * 
     * Logic:
     * - Chỉ hủy subscription có current_period_end < now (đã hết hạn)
     * - Chỉ hủy nếu không có auto_renew hoặc đã bị cancelled_at
     * - Trial và Active đều sử dụng current_period_end để lưu thời hạn
     */
    protected function cancelExpiredSubscriptions(Organization $organization): void
    {
        $now = Carbon::now();
        
        // Chỉ hủy các subscription đã hết hạn:
        // 1. Status = 'trial' hoặc 'active'
        // 2. current_period_end < now (đã hết hạn)
        // 3. Không có auto_renew hoặc đã bị cancelled_at
        OrganizationSubscription::where('organization_id', $organization->id)
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $now)
            ->where(function($query) {
                // Chỉ hủy nếu không có auto_renew hoặc đã bị cancelled
                $query->where('auto_renew', false)
                      ->orWhereNotNull('cancelled_at');
            })
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);
    }

    /**
     * Hủy tất cả subscription cũ sau khi đã gắn gói mới thành công.
     * Không quan trọng thời hạn - hủy luôn để tránh conflict.
     * Loại trừ subscription mới vừa tạo.
     */
    protected function cancelOldSubscriptions(Organization $organization, int $excludeSubscriptionId): void
    {
        OrganizationSubscription::where('organization_id', $organization->id)
            ->where('id', '!=', $excludeSubscriptionId) // Loại trừ subscription mới
            ->whereIn('status', ['trial', 'active'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);
    }

    /**
     * Tạo subscription mới cho auto_renew (tương tự đăng ký mới).
     * Logic:
     * 1. Tạo subscription mới với status = 'trial'
     * 2. Thời hạn trial = ngày tạo + 10 ngày (cố định cho auto_renew)
     * 3. Tạo invoice để thanh toán
     * 4. Khi thanh toán invoice, subscription mới sẽ chuyển sang 'active' với thời hạn mới
     * 
     * @param OrganizationSubscription $oldSubscription Subscription cũ cần gia hạn
     * @return OrganizationSubscription Subscription mới được tạo
     */
    public function createRenewalSubscription(OrganizationSubscription $oldSubscription): OrganizationSubscription
    {
        DB::beginTransaction();

        try {
            $organization = $oldSubscription->organization;
            $plan = $oldSubscription->plan;
            
            if (!$organization || !$plan) {
                throw new \Exception('Organization hoặc Plan không tồn tại.');
            }

            // Ngày tạo subscription mới (giữ nguyên để tính thời hạn sau này)
            $registrationDate = Carbon::now();
            
            // Tính thời hạn trial: ngày tạo + 10 ngày (cố định cho auto_renew)
            $trialDays = 10; // Auto_renew luôn dùng 10 ngày trial
            $trialEndsAt = $registrationDate->copy()->addDays($trialDays);
            
            // TẠO SUBSCRIPTION MỚI với trạng thái trial (tương tự đăng ký mới)
            $newSubscription = OrganizationSubscription::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'status' => 'trial', // Trạng thái trial, sẽ chuyển sang active khi thanh toán
                'payment_cycle' => $oldSubscription->payment_cycle,
                'payment_gateway' => $oldSubscription->payment_gateway,
                'current_period_start' => $registrationDate, // Ngày tạo (giữ nguyên khi active)
                'current_period_end' => $trialEndsAt, // Thời hạn trial (sẽ được cập nhật khi thanh toán)
                'auto_renew' => $oldSubscription->auto_renew, // Giữ nguyên auto_renew từ subscription cũ
            ]);

            // Tính giá dựa trên payment cycle
            $amount = $plan->getPrice($oldSubscription->payment_cycle);
            
            // Tạo invoice để thanh toán
            // Format: SUB{YYYYMMDD}{subscription_id} (không có dấu gạch để dễ so sánh với sepay)
            $invoiceNumber = 'SUB' . date('Ymd') . str_pad($newSubscription->id, 4, '0', STR_PAD_LEFT);
            $invoice = SubscriptionInvoice::create([
                'organization_subscription_id' => $newSubscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => $plan->currency ?? 'VND',
                'status' => 'pending',
                'due_date' => $oldSubscription->current_period_end ?? Carbon::now()->addDays(7), // Hạn thanh toán = ngày hết hạn subscription cũ
                'payment_method' => $oldSubscription->payment_gateway,
            ]);

            Log::info('Đã tạo subscription mới cho auto_renew', [
                'old_subscription_id' => $oldSubscription->id,
                'new_subscription_id' => $newSubscription->id,
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'invoice_id' => $invoice->id,
                'trial_days' => $trialDays,
                'trial_ends_at' => $trialEndsAt->toDateTimeString(),
            ]);

            DB::commit();

            return $newSubscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo subscription mới cho auto_renew: ' . $e->getMessage(), [
                'old_subscription_id' => $oldSubscription->id,
                'error' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

