<?php

namespace App\Services\Subscription;

use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class PlanChangeService
{
    /**
     * So sánh giá gói theo chu kỳ thanh toán đang chọn.
     *
     * @return 'upgrade'|'downgrade'|'lateral'
     */
    public function classifyChange(
        SubscriptionPlan $oldPlan,
        string $oldCycle,
        SubscriptionPlan $newPlan,
        string $newCycle
    ): string {
        $old = (float) $oldPlan->getPrice($oldCycle);
        $new = (float) $newPlan->getPrice($newCycle);

        if ($new > $old) {
            return 'upgrade';
        }
        if ($new < $old) {
            return 'downgrade';
        }

        return 'lateral';
    }

    /**
     * Giá trị chưa dùng của chu kỳ hiện tại (để trừ vào hóa đơn upgrade).
     * Tính theo tỷ lệ thời gian còn lại từ now đến current_period_end.
     */
    public function prorationCredit(OrganizationSubscription $active, SubscriptionPlan $oldPlan, string $oldCycle): float
    {
        $oldPrice = (float) $oldPlan->getPrice($oldCycle);
        if ($oldPrice <= 0) {
            return 0.0;
        }

        $periodStart = $active->current_period_start;
        $periodEnd = $active->current_period_end;
        $now = Carbon::now();

        if (!$periodStart || !$periodEnd || $periodEnd->lte($now)) {
            return 0.0;
        }

        $totalSeconds = max(1, abs($periodEnd->getTimestamp() - $periodStart->getTimestamp()));
        $remainingSeconds = max(0, $periodEnd->getTimestamp() - $now->getTimestamp());

        return $oldPrice * ($remainingSeconds / $totalSeconds);
    }

    /**
     * @return array{amount: float, metadata: array<string, mixed>}
     */
    public function buildUpgradeInvoice(
        OrganizationSubscription $active,
        SubscriptionPlan $oldPlan,
        string $oldCycle,
        SubscriptionPlan $newPlan,
        string $newCycle
    ): array {
        $newPrice = (float) $newPlan->getPrice($newCycle);
        $credit = $this->prorationCredit($active, $oldPlan, $oldCycle);
        $amount = max(0.0, $newPrice - $credit);

        return [
            'amount' => $amount,
            'metadata' => [
                'proration' => [
                    'old_plan_id' => $oldPlan->id,
                    'old_cycle' => $oldCycle,
                    'old_price' => $oldPlan->getPrice($oldCycle),
                    'new_plan_id' => $newPlan->id,
                    'new_cycle' => $newCycle,
                    'new_price' => $newPlan->getPrice($newCycle),
                    'credit_applied' => round($credit, 2),
                    'amount_due' => round($amount, 2),
                ],
            ],
        ];
    }
}
