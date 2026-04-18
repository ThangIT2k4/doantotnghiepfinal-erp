<?php

namespace App\Services\BusinessRules;

use App\Models\PropertyType;
use Illuminate\Validation\ValidationException;

class PropertyTypeRulesValidator
{
    /**
     * Validate property type before creating
     */
    public function validateCreating(PropertyType $propertyType): void
    {
        // No specific rules for creating
    }

    /**
     * Validate property type before updating
     */
    public function validateUpdating(PropertyType $propertyType): void
    {
        // No specific rules for updating
    }

    /**
     * Validate property type before deleting (soft delete)
     */
    public function validateDeleting(PropertyType $propertyType): void
    {
        // Check if property type is being used by properties
        $propertiesCount = $propertyType->properties()->count();
        if ($propertiesCount > 0) {
            throw ValidationException::withMessages([
                'property_type' => "Không thể xóa loại bất động sản này vì đang được sử dụng bởi {$propertiesCount} bất động sản."
            ]);
        }
    }

    /**
     * Check if property type can be soft deleted
     */
    public function canSoftDelete(PropertyType $propertyType): bool
    {
        try {
            $this->validateDeleting($propertyType);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate property type before saving
     */
    public function validateSaving(PropertyType $propertyType): void
    {
        if ($propertyType->exists) {
            $this->validateUpdating($propertyType);
        } else {
            $this->validateCreating($propertyType);
        }
    }
}

