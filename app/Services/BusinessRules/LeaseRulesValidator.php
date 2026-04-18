<?php

namespace App\Services\BusinessRules;

use App\Models\Lease;
use App\Models\Invoice;
use App\Models\DepositRefund;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class LeaseRulesValidator
{
    public function validateCreating(Lease $lease): void
    {
        $this->validateDates($lease);
        $this->validateNoOverlap($lease);
    }

    public function validateUpdating(Lease $lease): void
    {
        $this->validateDates($lease);
        
        // Chỉ kiểm tra overlap nếu thay đổi dates hoặc unit_id
        if ($lease->isDirty(['start_date', 'end_date', 'unit_id'])) {
            $this->validateNoOverlap($lease);
        }
    }

    public function validateDeleting(Lease $lease): void
    {
        // Kiểm tra có invoices đang active
        $hasActiveInvoices = Invoice::where('lease_id', $lease->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'overdue', 'paid'])
            ->exists();

        if ($hasActiveInvoices) {
            throw ValidationException::withMessages([
                'lease' => 'Không thể xóa hợp đồng đang có hóa đơn chưa xử lý xong'
            ]);
        }

        // Kiểm tra có deposit refunds
        $hasRefunds = DepositRefund::where('lease_id', $lease->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasRefunds) {
            throw ValidationException::withMessages([
                'lease' => 'Không thể xóa hợp đồng đã có hoàn cọc'
            ]);
        }
    }

    public function canSoftDelete(Lease $lease): bool
    {
        try {
            $this->validateDeleting($lease);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    protected function validateDates(Lease $lease): void
    {
        if ($lease->start_date && $lease->end_date) {
            if ($lease->start_date->gte($lease->end_date)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Ngày kết thúc phải sau ngày bắt đầu'
                ]);
            }
        }
    }

    protected function validateNoOverlap(Lease $lease): void
    {
        if (!$lease->unit_id || !$lease->start_date || !$lease->end_date) {
            return;
        }

        $overlapping = Lease::where('unit_id', $lease->unit_id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['draft', 'active'])
            ->when($lease->exists, function ($query) use ($lease) {
                return $query->where('id', '!=', $lease->id);
            })
            ->where(function ($query) use ($lease) {
                $query->whereBetween('start_date', [$lease->start_date, $lease->end_date])
                    ->orWhereBetween('end_date', [$lease->start_date, $lease->end_date])
                    ->orWhere(function ($q) use ($lease) {
                        $q->where('start_date', '<=', $lease->start_date)
                          ->where('end_date', '>=', $lease->end_date);
                    });
            })
            ->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'unit_id' => 'Phòng này đã có hợp đồng trùng thời gian hiệu lực'
            ]);
        }
    }

    public function validateSaving(Lease $lease): void
    {
        if ($lease->exists) {
            $this->validateUpdating($lease);
        } else {
            $this->validateCreating($lease);
        }
    }
}

