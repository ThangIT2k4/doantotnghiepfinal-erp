<?php

namespace App\Services\BusinessRules;

use App\Models\Unit;
use Illuminate\Validation\ValidationException;

class UnitRulesValidator
{
    /**
     * Validate unit before creating
     */
    public function validateCreating(Unit $unit): void
    {
        // No specific rules for creating
    }

    /**
     * Validate unit before updating
     */
    public function validateUpdating(Unit $unit): void
    {
        // No specific rules for updating
    }

    /**
     * Validate unit before deleting (soft delete)
     */
    public function validateDeleting(Unit $unit): void
    {
        // Kiểm tra xem unit có booking deposit tồn tại (chưa xóa mềm) không
        $bookingDepositsCount = $unit->bookingDeposits()
            ->whereNull('deleted_at')
            ->count();
        
        if ($bookingDepositsCount > 0) {
            throw ValidationException::withMessages([
                'unit' => "Không thể xóa phòng này vì đang có {$bookingDepositsCount} đặt cọc tồn tại."
            ]);
        }

        // Kiểm tra xem unit có lease tồn tại (chưa xóa mềm) không
        $leasesCount = $unit->leases()
            ->whereNull('deleted_at')
            ->count();
        
        if ($leasesCount > 0) {
            throw ValidationException::withMessages([
                'unit' => "Không thể xóa phòng này vì đang có {$leasesCount} hợp đồng tồn tại."
            ]);
        }
    }

    /**
     * Check if unit can be soft deleted
     */
    public function canSoftDelete(Unit $unit): bool
    {
        try {
            $this->validateDeleting($unit);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate unit before saving
     */
    public function validateSaving(Unit $unit): void
    {
        if ($unit->exists) {
            $this->validateUpdating($unit);
        } else {
            $this->validateCreating($unit);
        }
    }
}

