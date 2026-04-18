<?php

namespace App\Services\BusinessRules;

use App\Models\Property;
use Illuminate\Validation\ValidationException;

class PropertyRulesValidator
{
    /**
     * Validate property before creating
     */
    public function validateCreating(Property $property): void
    {
        // No specific rules for creating
    }

    /**
     * Validate property before updating
     */
    public function validateUpdating(Property $property): void
    {
        // No specific rules for updating
    }

    /**
     * Validate property before deleting (soft delete)
     */
    public function validateDeleting(Property $property): void
    {
        // Kiểm tra xem property có units nào có booking deposit tồn tại (chưa xóa mềm) không
        $unitsWithBookingDeposits = $property->units()
            ->whereHas('bookingDeposits', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
        
        if ($unitsWithBookingDeposits > 0) {
            throw ValidationException::withMessages([
                'property' => "Không thể xóa bất động sản này vì có {$unitsWithBookingDeposits} phòng đang có đặt cọc tồn tại."
            ]);
        }

        // Kiểm tra xem property có units nào có lease tồn tại (chưa xóa mềm) không
        $unitsWithLeases = $property->units()
            ->whereHas('leases', function($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
        
        if ($unitsWithLeases > 0) {
            throw ValidationException::withMessages([
                'property' => "Không thể xóa bất động sản này vì có {$unitsWithLeases} phòng đang có hợp đồng tồn tại."
            ]);
        }
    }

    /**
     * Check if property can be soft deleted
     */
    public function canSoftDelete(Property $property): bool
    {
        try {
            $this->validateDeleting($property);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate property before saving
     */
    public function validateSaving(Property $property): void
    {
        if ($property->exists) {
            $this->validateUpdating($property);
        } else {
            $this->validateCreating($property);
        }
    }
}

