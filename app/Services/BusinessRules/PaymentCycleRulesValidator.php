<?php

namespace App\Services\BusinessRules;

use App\Models\PaymentCycle;
use App\Models\Property;
use App\Models\Lease;
use Illuminate\Validation\ValidationException;

class PaymentCycleRulesValidator
{
    /**
     * Validate payment cycle before creating
     */
    public function validateCreating(PaymentCycle $paymentCycle): void
    {
        // No specific rules for creating
    }

    /**
     * Validate payment cycle before updating
     */
    public function validateUpdating(PaymentCycle $paymentCycle): void
    {
        // No specific rules for updating
    }

    /**
     * Validate payment cycle before deleting (soft delete)
     */
    public function validateDeleting(PaymentCycle $paymentCycle): void
    {
        // Check if payment cycle is being used by properties
        $propertiesCount = Property::where('payment_cycle_id', $paymentCycle->id)->count();
        
        // Check if payment cycle is being used by leases
        $leasesCount = Lease::where('payment_cycle_id', $paymentCycle->id)->count();

        if ($propertiesCount > 0 || $leasesCount > 0) {
            $parts = [];
            if ($propertiesCount > 0) {
                $parts[] = "{$propertiesCount} bất động sản";
            }
            if ($leasesCount > 0) {
                $parts[] = "{$leasesCount} hợp đồng thuê";
            }
            
            $message = "Không thể xóa chu kỳ thanh toán này vì đang được sử dụng bởi " . implode(' và ', $parts) . ".";
            
            throw ValidationException::withMessages([
                'payment_cycle' => $message
            ]);
        }
    }

    /**
     * Check if payment cycle can be soft deleted
     */
    public function canSoftDelete(PaymentCycle $paymentCycle): bool
    {
        try {
            $this->validateDeleting($paymentCycle);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate payment cycle before saving
     */
    public function validateSaving(PaymentCycle $paymentCycle): void
    {
        if ($paymentCycle->exists) {
            $this->validateUpdating($paymentCycle);
        } else {
            $this->validateCreating($paymentCycle);
        }
    }
}

