<?php

namespace App\Services\BusinessRules;

use App\Models\Viewing;
use App\Models\BookingDeposit;
use Illuminate\Validation\ValidationException;

class ViewingRulesValidator
{
    public function validateCreating(Viewing $viewing): void
    {
        $this->validateScheduleDate($viewing);
        $this->validateOrganization($viewing);
    }

    public function validateUpdating(Viewing $viewing): void
    {
        $this->validateScheduleDate($viewing);
        $this->validateOrganization($viewing);
    }

    public function validateDeleting(Viewing $viewing): void
    {
        // Kiểm tra có booking_deposits active không
        $hasActiveBookingDeposits = BookingDeposit::where('viewing_id', $viewing->id)
            ->whereNull('deleted_at')
            ->whereIn('payment_status', ['pending', 'pending_approval', 'paid'])
            ->exists();

        if ($hasActiveBookingDeposits) {
            throw ValidationException::withMessages([
                'viewing' => 'Không thể xóa lịch xem phòng đang có đặt cọc đang hoạt động'
            ]);
        }
    }

    public function canSoftDelete(Viewing $viewing): bool
    {
        try {
            $this->validateDeleting($viewing);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    protected function validateScheduleDate(Viewing $viewing): void
    {
        if ($viewing->schedule_at && $viewing->schedule_at->isPast() && !in_array($viewing->status, ['done', 'no_show', 'cancelled'])) {
            // Cho phép lịch quá khứ nếu đã hoàn thành hoặc hủy
            // Chỉ cảnh báo nếu lịch quá khứ nhưng chưa hoàn thành
        }
    }

    protected function validateOrganization(Viewing $viewing): void
    {
        // organization_id không được NULL (theo yêu cầu NOT NULL)
        if (!$viewing->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => 'Lịch xem phòng phải thuộc một tổ chức'
            ]);
        }
    }

    public function validateSaving(Viewing $viewing): void
    {
        if ($viewing->exists) {
            $this->validateUpdating($viewing);
        } else {
            $this->validateCreating($viewing);
        }
    }
}

