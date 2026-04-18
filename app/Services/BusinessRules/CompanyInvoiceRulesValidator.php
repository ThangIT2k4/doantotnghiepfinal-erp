<?php

namespace App\Services\BusinessRules;

use App\Models\CompanyInvoice;
use App\Models\CashOutflow;
use Illuminate\Validation\ValidationException;

class CompanyInvoiceRulesValidator
{
    public function validateCreating(CompanyInvoice $invoice): void
    {
        $this->validateDates($invoice);
        $this->validateAmounts($invoice);
        $this->validateTypeReference($invoice);
    }

    public function validateUpdating(CompanyInvoice $invoice): void
    {
        $this->validateDates($invoice);
        $this->validateAmounts($invoice);
        $this->validateStatusTransition($invoice);
        $this->validateTypeReference($invoice);
    }

    public function validateDeleting(CompanyInvoice $invoice): void
    {
        // Kiểm tra có cash_outflows không
        $hasCashOutflows = CashOutflow::where('company_invoice_id', $invoice->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasCashOutflows) {
            throw ValidationException::withMessages([
                'invoice' => 'Không thể xóa hóa đơn đã có dòng tiền chi ra'
            ]);
        }

        // Kiểm tra status
        if (in_array($invoice->status, ['approved', 'paid', 'overdue'])) {
            throw ValidationException::withMessages([
                'invoice' => 'Không thể xóa hóa đơn ở trạng thái: ' . $invoice->status
            ]);
        }
    }

    protected function validateDates(CompanyInvoice $invoice): void
    {
        if ($invoice->issue_date && $invoice->due_date) {
            if ($invoice->issue_date->gt($invoice->due_date)) {
                throw ValidationException::withMessages([
                    'due_date' => 'Ngày đến hạn phải sau hoặc bằng ngày phát hành'
                ]);
            }
        }
    }

    protected function validateAmounts(CompanyInvoice $invoice): void
    {
        // Validate non-negative
        if ($invoice->subtotal < 0 || $invoice->tax_amount < 0 || $invoice->discount_amount < 0) {
            throw ValidationException::withMessages([
                'amounts' => 'Các khoản tiền không được âm'
            ]);
        }

        // Validate total calculation
        $calculatedTotal = $invoice->subtotal + $invoice->tax_amount - $invoice->discount_amount;
        if (abs($invoice->total_amount - $calculatedTotal) > 0.01) {
            throw ValidationException::withMessages([
                'total_amount' => 'Tổng tiền không khớp với công thức: subtotal + tax - discount'
            ]);
        }

        if ($invoice->total_amount < 0) {
            throw ValidationException::withMessages([
                'total_amount' => 'Tổng tiền không được âm'
            ]);
        }
    }

    protected function validateTypeReference(CompanyInvoice $invoice): void
    {
        switch ($invoice->invoice_type) {
            case 'master_lease':
                if (!$invoice->master_lease_id) {
                    throw ValidationException::withMessages([
                        'master_lease_id' => 'Hóa đơn loại master_lease cần có master_lease_id'
                    ]);
                }
                break;
                
            case 'ticket_cost':
                if (!$invoice->ticket_id && !$invoice->ticket_log_id) {
                    throw ValidationException::withMessages([
                        'ticket_id' => 'Hóa đơn loại ticket_cost cần có ticket_id hoặc ticket_log_id'
                    ]);
                }
                break;
                
            case 'deposit_refund':
                if (!$invoice->deposit_refund_id) {
                    throw ValidationException::withMessages([
                        'deposit_refund_id' => 'Hóa đơn loại deposit_refund cần có deposit_refund_id'
                    ]);
                }
                break;
                
            case 'payroll_payslip':
                if (!$invoice->payroll_payslip_id) {
                    throw ValidationException::withMessages([
                        'payroll_payslip_id' => 'Hóa đơn loại payroll_payslip cần có payroll_payslip_id'
                    ]);
                }
                break;
                
            // Các loại khác (landlord_payout, user_payout, utility, maintenance, service, supply, other) không bắt buộc
        }
    }

    protected function validateStatusTransition(CompanyInvoice $invoice): void
    {
        if (!$invoice->isDirty('status')) {
            return;
        }

        $oldStatus = $invoice->getOriginal('status');
        $newStatus = $invoice->status;

        // pending → approved: có thể cần kiểm tra thêm (tùy nghiệp vụ)
        // approved → paid: bắt buộc có cash_outflow
        if ($oldStatus === 'approved' && $newStatus === 'paid') {
            $hasCashOutflow = CashOutflow::where('company_invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->where('status', 'success')
                ->exists();

            if (!$hasCashOutflow) {
                throw ValidationException::withMessages([
                    'status' => 'Cần có dòng tiền chi ra thành công trước khi chuyển sang paid'
                ]);
            }
        }

        // pending → cancelled: chưa có cash_outflow
        if ($oldStatus === 'pending' && $newStatus === 'cancelled') {
            $hasCashOutflow = CashOutflow::where('company_invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasCashOutflow) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể hủy hóa đơn đã có dòng tiền chi ra'
                ]);
            }
        }

        // approved → overdue: chỉ được set sau due_date
        if ($newStatus === 'overdue') {
            if ($invoice->due_date && $invoice->due_date->isFuture()) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể đặt trạng thái quá hạn trước ngày đến hạn'
                ]);
            }
        }
    }

    public function validateSaving(CompanyInvoice $invoice): void
    {
        if ($invoice->exists) {
            $this->validateUpdating($invoice);
        } else {
            $this->validateCreating($invoice);
        }
    }
}

