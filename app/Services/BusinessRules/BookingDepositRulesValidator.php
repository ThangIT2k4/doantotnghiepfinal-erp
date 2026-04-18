<?php

namespace App\Services\BusinessRules;

use App\Models\BookingDeposit;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class BookingDepositRulesValidator
{
    public function validateCreating(BookingDeposit $deposit): void
    {
        $this->validateAmount($deposit);
        $this->validateNoActiveOverlap($deposit);
    }

    public function validateUpdating(BookingDeposit $deposit): void
    {
        $this->validateAmount($deposit);
        
        // Chỉ kiểm tra overlap nếu thay đổi unit_id, payment_status, hoặc hold_until
        if ($deposit->isDirty(['unit_id', 'payment_status', 'hold_until'])) {
            $this->validateNoActiveOverlap($deposit);
        }
    }

    public function validateDeleting(BookingDeposit $deposit): void
    {
        // Không cho xóa nếu đã paid
        if ($deposit->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'deposit' => 'Không thể xóa đặt cọc đã thanh toán'
            ]);
        }

        // Kiểm tra có invoice liên quan không
        $hasInvoice = Invoice::where('booking_deposit_id', $deposit->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasInvoice) {
            throw ValidationException::withMessages([
                'deposit' => 'Không thể xóa đặt cọc đã có hóa đơn'
            ]);
        }
    }

    protected function validateAmount(BookingDeposit $deposit): void
    {
        if ($deposit->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền đặt cọc phải lớn hơn 0'
            ]);
        }
    }

    protected function validateNoActiveOverlap(BookingDeposit $deposit): void
    {
        if (!$deposit->unit_id || !$deposit->hold_until) {
            return;
        }

        // Kiểm tra có booking active khác cho cùng unit không
        $activeBooking = BookingDeposit::where('unit_id', $deposit->unit_id)
            ->whereNull('deleted_at')
            ->whereIn('payment_status', ['pending', 'pending_approval', 'paid'])
            ->where('hold_until', '>=', now())
            ->when($deposit->exists, function ($query) use ($deposit) {
                return $query->where('id', '!=', $deposit->id);
            })
            ->exists();

        if ($activeBooking) {
            throw ValidationException::withMessages([
                'unit_id' => 'Phòng này đang có đặt cọc đang hoạt động'
            ]);
        }
    }

    public function validateSaving(BookingDeposit $deposit): void
    {
        if ($deposit->exists) {
            $this->validateUpdating($deposit);
        } else {
            $this->validateCreating($deposit);
        }
    }
}

