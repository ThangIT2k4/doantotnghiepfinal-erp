<?php

namespace App\Observers;

use App\Models\CompanyInvoice;
use App\Models\DepositRefund;
use App\Models\PayrollPayslip;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CompanyInvoiceObserver
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

    public function created(CompanyInvoice $invoice): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($invoice, 'creating');
        
        // Log audit trail
        $this->auditLogService->logCreated($invoice);
    }
    public function updated(CompanyInvoice $invoice): void
    {
        try {
            // Skip if model is not fully loaded (e.g., during bulk updates)
            if (!$invoice->exists || !$invoice->id) {
                return;
            }
            
            // Validate business rules - catch and log but don't fail the update
            try {
                $this->businessRulesService->validate($invoice, 'updating');
            } catch (\Illuminate\Validation\ValidationException $validationException) {
                // Log validation error but don't fail the update
                // Business rules validation should be done in controller before update
                Log::warning('Business rule validation failed in Observer (non-blocking)', [
                    'invoice_id' => $invoice->id,
                    'errors' => $validationException->errors()
                ]);
                // Don't throw - let the update proceed
            }

            // Update deposit refund if invoice is linked
            if ($invoice->deposit_refund_id) {
                try {
                    $refund = DepositRefund::withoutGlobalScopes()->find($invoice->deposit_refund_id);
                    if ($refund) {
                        switch ($invoice->status) {
                            case CompanyInvoice::STATUS_APPROVED:
                            case CompanyInvoice::STATUS_PENDING:
                                if ($refund->status === DepositRefund::STATUS_PENDING) {
                                    $refund->update(['status' => DepositRefund::STATUS_APPROVED, 'approved_at' => now(), 'approved_by' => Auth::id()]);
                                }
                                break;
                            case CompanyInvoice::STATUS_PAID:
                                if ($refund->status !== DepositRefund::STATUS_PAID) {
                                    $refund->update(['status' => DepositRefund::STATUS_PAID, 'paid_at' => now(), 'paid_by' => Auth::id()]);
                                }
                                break;
                            case CompanyInvoice::STATUS_CANCELLED:
                                if ($refund->status !== DepositRefund::STATUS_CANCELLED) {
                                    $refund->update(['status' => DepositRefund::STATUS_CANCELLED]);
                                }
                                break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('CompanyInvoiceObserver: Error updating deposit refund', [
                        'invoice_id' => $invoice->id,
                        'refund_id' => $invoice->deposit_refund_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update payroll payslip if invoice is linked and status changed to paid
            // Note: PayrollPayslipObserver will automatically check and update cycle status
            if ($invoice->isDirty('status') && $invoice->status === CompanyInvoice::STATUS_PAID && $invoice->payroll_payslip_id) {
                try {
                    $payslip = PayrollPayslip::withoutGlobalScopes()->find($invoice->payroll_payslip_id);
                    if ($payslip && $payslip->status !== 'paid') {
                        $payslip->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                        
                        Log::info('Payroll payslip automatically marked as paid via CompanyInvoiceObserver', [
                            'payslip_id' => $payslip->id,
                            'invoice_id' => $invoice->id,
                            'invoice_no' => $invoice->invoice_no,
                        ]);
                        // PayrollPayslipObserver will automatically check and update cycle status
                    }
                } catch (\Exception $e) {
                    Log::debug('CompanyInvoiceObserver: Error updating payroll payslip', [
                        'invoice_id' => $invoice->id,
                        'payslip_id' => $invoice->payroll_payslip_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log audit trail - refresh model to ensure all relationships are loaded
            try {
                // Refresh the model to ensure it has all necessary data
                $invoice->refresh();
                $this->auditLogService->logUpdated($invoice);
            } catch (\Exception $auditException) {
                // Log audit error but don't fail the update
                Log::error('CompanyInvoiceObserver audit log error: '.$auditException->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'error' => $auditException->getTraceAsString()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('CompanyInvoiceObserver update error: '.$e->getMessage(), [
                'invoice_id' => $invoice->id ?? null,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    public function deleted(CompanyInvoice $invoice): void
    {
        // Validate business rules first (soft delete)
        if (!$invoice->isForceDeleting()) {
            $this->businessRulesService->validate($invoice, 'deleting');
        }
        
        // Log audit trail
        $this->auditLogService->logDeleted($invoice);
    }
}


