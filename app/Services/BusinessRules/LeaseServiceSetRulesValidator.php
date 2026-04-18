<?php

namespace App\Services\BusinessRules;

use App\Models\LeaseServiceSet;
use App\Models\Property;
use App\Models\Lease;
use Illuminate\Validation\ValidationException;

class LeaseServiceSetRulesValidator
{
    /**
     * Validate lease service set before creating
     */
    public function validateCreating(LeaseServiceSet $leaseServiceSet): void
    {
        // No specific rules for creating
    }

    /**
     * Validate lease service set before updating
     */
    public function validateUpdating(LeaseServiceSet $leaseServiceSet): void
    {
        // No specific rules for updating
    }

    /**
     * Validate lease service set before deleting (soft delete)
     */
    public function validateDeleting(LeaseServiceSet $leaseServiceSet): void
    {
        // Check if lease service set is being used by properties
        $propertiesCount = Property::where('lease_services_id', $leaseServiceSet->id)->count();
        
        // Check if lease service set is being used by leases
        $leasesCount = Lease::where('lease_services_id', $leaseServiceSet->id)->count();

        if ($propertiesCount > 0 || $leasesCount > 0) {
            $parts = [];
            if ($propertiesCount > 0) {
                $parts[] = "{$propertiesCount} bất động sản";
            }
            if ($leasesCount > 0) {
                $parts[] = "{$leasesCount} hợp đồng thuê";
            }
            
            $message = "Không thể xóa bộ dịch vụ này vì đang được sử dụng bởi " . implode(' và ', $parts) . ".";
            
            throw ValidationException::withMessages([
                'lease_service_set' => $message
            ]);
        }
    }

    /**
     * Check if lease service set can be soft deleted
     */
    public function canSoftDelete(LeaseServiceSet $leaseServiceSet): bool
    {
        try {
            $this->validateDeleting($leaseServiceSet);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate lease service set before saving
     */
    public function validateSaving(LeaseServiceSet $leaseServiceSet): void
    {
        if ($leaseServiceSet->exists) {
            $this->validateUpdating($leaseServiceSet);
        } else {
            $this->validateCreating($leaseServiceSet);
        }
    }
}

