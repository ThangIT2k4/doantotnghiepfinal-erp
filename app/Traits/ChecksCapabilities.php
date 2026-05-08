<?php

namespace App\Traits;

use App\Services\CapabilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait ChecksCapabilities
{
    /**
     * Get current user's organization ID
     * Uses User model's method which checks session first, then falls back to first organization
     */
    protected function getCurrentOrganizationId(): ?int
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        
        // Use User model's method which checks session first, ensuring consistency
        // with global scopes that also use this method
        return $user->getCurrentOrganizationId();
    }

    /**
     * Check if user has capability
     */
    protected function checkCapability(string $capability): bool
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return false;
        }
        
        return CapabilityService::userHas($user->id, $organizationId, $capability);
    }

    /**
     * Abort if user doesn't have capability
     */
    protected function requireCapability(string $capability, string $message = null): void
    {
        if (!$this->checkCapability($capability)) {
            $defaultMessage = "Bạn không có quyền thực hiện thao tác này.";
            $errorMessage = $message ?? $defaultMessage;
            
            // If request expects JSON, return JSON response
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
                response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 403)->send();
                exit;
            }
            
            abort(403, $errorMessage);
        }
    }

    /**
     * Check if user should filter by ownership (view_own capability)
     * Returns true if user has view_own but NOT view (manager has view)
     */
    protected function shouldFilterByOwnership(string $capabilityBase): bool
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId || !$user) {
            return true; // Default to filter if no organization or user
        }
        
        // Manager has '*' capability, so can view all
        if ($this->checkCapability('*')) {
            return false;
        }
        
        // Check if user has view capability (manager can view all)
        $hasView = $this->checkCapability($capabilityBase . '.view');
        
        // If has view, don't filter (can see all)
        // If only has view_own, filter (can only see own)
        return !$hasView;
    }

    /**
     * Auto-assign agent_id for agent when creating records
     * Returns agent_id if user is agent, null if manager
     */
    protected function getAutoAssignedAgentId(): ?int
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId || !$user) {
            return null;
        }
        
        // Manager has '*' capability, so return null (can assign to anyone)
        if ($this->checkCapability('*')) {
            return null;
        }
        
        // Agent: return current user ID
        return $user->id;
    }

    /**
     * Force agent_id to current user for agents (prevent changing)
     * Use this in store/update methods
     * 
     * @param array $data Data array to modify
     * @param string $fieldName Field name to enforce (default: 'agent_id')
     */
    protected function enforceAgentId(array &$data, string $fieldName = 'agent_id'): void
    {
        $autoAgentId = $this->getAutoAssignedAgentId();
        
        if ($autoAgentId !== null) {
            // Agent: force to current user, ignore request value
            $data[$fieldName] = $autoAgentId;
        }
        // Manager: keep request value (can assign to anyone)
    }

    /**
     * Force user_id to current user for agents (prevent changing)
     * Use this in store/update methods for records with user_id field
     * 
     * @param array $data Data array to modify
     * @param string $fieldName Field name to enforce (default: 'user_id')
     */
    protected function enforceUserId(array &$data, string $fieldName = 'user_id'): void
    {
        $autoUserId = $this->getAutoAssignedAgentId();
        
        if ($autoUserId !== null) {
            // Agent: force to current user, ignore request value
            $data[$fieldName] = $autoUserId;
        }
        // Manager: keep request value (can assign to anyone)
    }

    /**
     * Check if record belongs to user's organization
     * Converts both values to int to avoid type mismatch issues
     * 
     * @param mixed $recordOrganizationId Organization ID from record (can be int, string, or null)
     * @param string|null $errorMessage Custom error message (optional)
     * @param string|null $recordType Type of record for logging (e.g., 'commission_event', 'property_type')
     * @param int|null $recordId Record ID for logging
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function checkOrganizationAccess($recordOrganizationId, ?string $errorMessage = null, ?string $recordType = null, ?int $recordId = null): void
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Convert both to int for comparison to avoid type mismatch issues
        $recordOrgId = (int) $recordOrganizationId;
        $userOrgId = (int) $organizationId;
        
        if ($recordOrgId !== $userOrgId) {
            // Log unauthorized access attempt for debugging
            if ($recordType && $recordId) {
                Log::warning("Unauthorized access attempt to {$recordType}", [
                    'user_id' => $user->id ?? null,
                    'user_organization_id' => $userOrgId,
                    'record_id' => $recordId,
                    'record_organization_id' => $recordOrgId,
                    'record_organization_id_raw' => $recordOrganizationId,
                    'getCurrentOrganizationId_result' => $organizationId,
                ]);
            }
            
            $defaultMessage = 'Unauthorized access. Bạn không có quyền truy cập tài nguyên này.';
            abort(403, $errorMessage ?? $defaultMessage);
        }
    }
}

