<?php

namespace App\Services\BusinessRules;

use App\Models\PayrollPayslip;
use App\Models\CompanyInvoice;
use Illuminate\Validation\ValidationException;

class PayrollRulesValidator
{
    public function validateCreating(PayrollPayslip $payslip): void
    {
        $this->validateAmounts($payslip);
    }

    public function validateUpdating(PayrollPayslip $payslip): void
    {
        $this->validateAmounts($payslip);
    }

    public function validateDeleting(PayrollPayslip $payslip): void
    {
        // Không cho xóa nếu đã paid
        if ($payslip->status === 'paid') {
            throw ValidationException::withMessages([
                'payslip' => 'Không thể xóa phiếu lương đã thanh toán'
            ]);
        }

        // Kiểm tra có company_invoice liên quan không
        $hasInvoice = CompanyInvoice::where('payroll_payslip_id', $payslip->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasInvoice) {
            throw ValidationException::withMessages([
                'payslip' => 'Không thể xóa phiếu lương đã có hóa đơn công ty'
            ]);
        }
    }

    protected function validateAmounts(PayrollPayslip $payslip): void
    {
        // Validate non-negative
        if ($payslip->gross_amount < 0 || $payslip->deduction_amount < 0 || $payslip->net_amount < 0) {
            throw ValidationException::withMessages([
                'amounts' => 'Các khoản tiền không được âm'
            ]);
        }

        // Validate net calculation
        $calculatedNet = $payslip->gross_amount - $payslip->deduction_amount;
        if (abs($payslip->net_amount - $calculatedNet) > 0.01) {
            throw ValidationException::withMessages([
                'net_amount' => 'Lương thực lĩnh không khớp: gross - deduction = ' . number_format($calculatedNet, 0)
            ]);
        }
    }

    public function validateSaving(PayrollPayslip $payslip): void
    {
        if ($payslip->exists) {
            $this->validateUpdating($payslip);
        } else {
            $this->validateCreating($payslip);
        }
    }
}

