<?php

namespace App\Services\BusinessRules;

use App\Models\DepositRefund;
use Illuminate\Validation\ValidationException;

class DepositRefundRulesValidator
{
    public function validateCreating(DepositRefund $refund): void
    {
        $this->validateAmounts($refund);
    }

    public function validateUpdating(DepositRefund $refund): void
    {
        $this->validateAmounts($refund);
        $this->validateStatusTransition($refund);
    }

    public function validateDeleting(DepositRefund $refund): void
    {
        if (in_array($refund->status, ['approved', 'paid'])) {
            throw ValidationException::withMessages([
                'refund' => 'Không thể xóa hoàn cọc đã được phê duyệt hoặc thanh toán'
            ]);
        }
    }

    protected function validateAmounts(DepositRefund $refund): void
    {
        if ($refund->deducted_amount < 0) {
            throw ValidationException::withMessages([
                'deducted_amount' => 'Số tiền trừ không được âm'
            ]);
        }

        $calculatedRefund = $refund->original_deposit_amount - $refund->deducted_amount;
        
        if (abs($refund->refund_amount - $calculatedRefund) > 0.01) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Số tiền hoàn không khớp: original - deducted = ' . number_format($calculatedRefund, 0)
            ]);
        }

        if ($refund->refund_amount < 0) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Số tiền hoàn không được âm'
            ]);
        }

        // Không được hoàn nhiều hơn cọc gốc
        if ($refund->refund_amount > $refund->original_deposit_amount) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Số tiền hoàn không được vượt quá số tiền cọc gốc'
            ]);
        }
    }

    protected function validateStatusTransition(DepositRefund $refund): void
    {
        if (!$refund->isDirty('status')) {
            return;
        }

        $oldStatus = $refund->getOriginal('status');
        $newStatus = $refund->status;

        // approved → paid: cần có thông tin thanh toán
        if ($oldStatus === 'approved' && $newStatus === 'paid') {
            if (!$refund->paid_at || !$refund->paid_by) {
                throw ValidationException::withMessages([
                    'status' => 'Cần có thông tin thanh toán (paid_at, paid_by) trước khi chuyển sang paid'
                ]);
            }
        }
    }

    public function validateSaving(DepositRefund $refund): void
    {
        if ($refund->exists) {
            $this->validateUpdating($refund);
        } else {
            $this->validateCreating($refund);
        }
    }
}

