<?php

namespace App\Services\BusinessRules;

use App\Models\Service;
use Illuminate\Validation\ValidationException;

class ServiceRulesValidator
{
    /**
     * Validate service before creating
     */
    public function validateCreating(Service $service): void
    {
        // No specific rules for creating
    }

    /**
     * Validate service before updating
     */
    public function validateUpdating(Service $service): void
    {
        // No specific rules for updating
    }

    /**
     * Validate service before deleting (soft delete)
     */
    public function validateDeleting(Service $service): void
    {
        // Check if service is being used by meters
        $metersCount = $service->meters()->count();
        if ($metersCount > 0) {
            throw ValidationException::withMessages([
                'service' => "Không thể xóa dịch vụ này vì đang có {$metersCount} đồng hồ đang sử dụng."
            ]);
        }

        // Check if service is being used in lease service sets
        $leaseServiceSetItemsCount = $service->leaseServiceSetItems()->count();
        if ($leaseServiceSetItemsCount > 0) {
            throw ValidationException::withMessages([
                'service' => "Không thể xóa dịch vụ này vì đang được sử dụng trong {$leaseServiceSetItemsCount} bộ dịch vụ."
            ]);
        }
    }

    /**
     * Check if service can be soft deleted
     */
    public function canSoftDelete(Service $service): bool
    {
        try {
            $this->validateDeleting($service);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate service before saving
     */
    public function validateSaving(Service $service): void
    {
        if ($service->exists) {
            $this->validateUpdating($service);
        } else {
            $this->validateCreating($service);
        }
    }
}

