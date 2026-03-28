<?php

namespace App\Console\Commands;

use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyPendingSubscriptionDowngrades extends Command
{
    protected $signature = 'subscriptions:apply-pending-downgrades';

    protected $description = 'Áp dụng hạ gói đã đặt lịch khi đến cuối chu kỳ (metadata.pending_downgrade)';

    public function handle(): int
    {
        $processed = 0;

        $candidates = OrganizationSubscription::query()
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('metadata')
            ->get();

        foreach ($candidates as $subscription) {
            $pending = $subscription->metadata['pending_downgrade'] ?? null;
            if (!is_array($pending)) {
                continue;
            }

            $scheduledAt = isset($pending['scheduled_at']) ? Carbon::parse($pending['scheduled_at']) : null;
            if (!$scheduledAt || $scheduledAt->isFuture()) {
                continue;
            }

            $targetPlanId = (int) ($pending['target_plan_id'] ?? 0);
            $newPlan = SubscriptionPlan::find($targetPlanId);
            if (!$newPlan) {
                Log::warning('Pending downgrade: target plan missing', [
                    'subscription_id' => $subscription->id,
                    'target_plan_id' => $targetPlanId,
                ]);
                continue;
            }

            $newCycle = $pending['target_payment_cycle'] ?? $subscription->payment_cycle;
            if (!in_array($newCycle, ['monthly', 'yearly'], true)) {
                $newCycle = 'monthly';
            }
            $newGateway = $pending['target_payment_gateway'] ?? $subscription->payment_gateway;

            try {
                $applied = DB::transaction(function () use ($subscription, $newPlan, $newCycle, $newGateway, $scheduledAt) {
                    $subscription->refresh();

                    $meta = $subscription->metadata ?? [];
                    if (empty($meta['pending_downgrade'])) {
                        return false;
                    }

                    $metadata = $meta;
                    unset($metadata['pending_downgrade']);

                    if ($subscription->status === 'active') {
                        $start = $scheduledAt->copy();
                        $periodEnd = $newCycle === 'yearly'
                            ? $start->copy()->addYear()
                            : $start->copy()->addDays(30);

                        $subscription->update([
                            'plan_id' => $newPlan->id,
                            'payment_cycle' => $newCycle,
                            'payment_gateway' => $newGateway,
                            'status' => 'active',
                            'current_period_start' => $start,
                            'current_period_end' => $periodEnd,
                            'metadata' => $metadata ?: null,
                        ]);

                        Log::info('Applied scheduled downgrade (active → lower plan)', [
                            'subscription_id' => $subscription->id,
                            'new_plan_id' => $newPlan->id,
                        ]);

                        return true;
                    }

                    if ($subscription->status === 'trial') {
                        $registrationDate = Carbon::now();
                        $trialDays = (int) ($newPlan->trial_days ?? 0);
                        $trialEndsAt = $trialDays > 0 ? $registrationDate->copy()->addDays($trialDays) : null;
                        $amount = (float) $newPlan->getPrice($newCycle);

                        $subscription->update([
                            'plan_id' => $newPlan->id,
                            'payment_cycle' => $newCycle,
                            'payment_gateway' => $newGateway,
                            'status' => 'suspended',
                            'current_period_start' => $registrationDate,
                            'current_period_end' => $trialEndsAt,
                            'metadata' => $metadata ?: null,
                        ]);

                        $invoiceNumber = 'SUB' . $registrationDate->format('Ymd') . str_pad((string) $subscription->id, 4, '0', STR_PAD_LEFT);
                        SubscriptionInvoice::create([
                            'organization_subscription_id' => $subscription->id,
                            'invoice_number' => $invoiceNumber,
                            'amount' => $amount,
                            'currency' => $newPlan->currency ?? 'VND',
                            'status' => 'pending',
                            'due_date' => $registrationDate->copy()->addDays(7),
                            'payment_method' => $newGateway,
                            'metadata' => [
                                'source' => 'scheduled_downgrade_from_trial',
                            ],
                        ]);

                        Log::info('Applied scheduled downgrade (trial → suspended + invoice)', [
                            'subscription_id' => $subscription->id,
                            'new_plan_id' => $newPlan->id,
                        ]);

                        return true;
                    }

                    return false;
                });

                if ($applied) {
                    $processed++;
                }
            } catch (\Throwable $e) {
                Log::error('Apply pending downgrade failed: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Processed {$processed} downgrade(s).");

        return self::SUCCESS;
    }
}
