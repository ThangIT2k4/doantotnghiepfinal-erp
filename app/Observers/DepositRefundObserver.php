<?php

namespace App\Observers;

use App\Models\DepositRefund;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;

class DepositRefundObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(
        AuditLogService $auditLogService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the DepositRefund "created" event.
     */
    public function created(DepositRefund $depositRefund): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($depositRefund, 'creating');
        
        // Log audit trail
        $this->auditLogService->logCreated($depositRefund);
    }

    /**
     * Handle the DepositRefund "updated" event.
     */
    public function updated(DepositRefund $depositRefund): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($depositRefund, 'updating');
        
        // Log audit trail
        $this->auditLogService->logUpdated($depositRefund);
    }

    /**
     * Handle the DepositRefund "deleted" event.
     */
    public function deleted(DepositRefund $depositRefund): void
    {
        // Validate business rules first (soft delete)
        if (!$depositRefund->isForceDeleting()) {
            $this->businessRulesService->validate($depositRefund, 'deleting');
        }
        
        // Log audit trail
        $this->auditLogService->logDeleted($depositRefund);
    }
}

