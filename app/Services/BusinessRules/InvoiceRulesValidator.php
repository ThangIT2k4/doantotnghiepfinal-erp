<?php

namespace App\Services\BusinessRules;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class InvoiceRulesValidator
{
    /**
     * Validate invoice before creating
     */
    public function validateCreating(Invoice $invoice): void
    {
        $this->validateDates($invoice);
        $this->validateAmounts($invoice);
        $this->validateMonthlyRentUniqueness($invoice);
    }

    /**
     * Validate invoice before updating
     */
    public function validateUpdating(Invoice $invoice): void
    {
        $this->validateDates($invoice);
        $this->validateAmounts($invoice);
        $this->validateStatusTransition($invoice);
        $this->validateMonthlyRentUniqueness($invoice);
    }

    /**
     * Validate invoice before deleting (soft delete)
     */
    public function validateDeleting(Invoice $invoice): void
    {
        // Kiểm tra có payments đang active không
        $hasActivePayments = Payment::where('invoice_id', $invoice->id)
            ->whereNull('deleted_at')
            ->where('status', 'success')
            ->exists();

        if ($hasActivePayments) {
            throw ValidationException::withMessages([
                'invoice' => 'Không thể xóa hóa đơn đã có thanh toán thành công'
            ]);
        }

        // Kiểm tra status
        if (in_array($invoice->status, ['issued', 'paid', 'overdue'])) {
            throw ValidationException::withMessages([
                'invoice' => 'Không thể xóa hóa đơn ở trạng thái: ' . $invoice->status
            ]);
        }
    }

    /**
     * Check if invoice can be soft deleted
     */
    public function canSoftDelete(Invoice $invoice): bool
    {
        try {
            $this->validateDeleting($invoice);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate dates
     */
    protected function validateDates(Invoice $invoice): void
    {
        if ($invoice->issue_date && $invoice->due_date) {
            if ($invoice->issue_date->gt($invoice->due_date)) {
                throw ValidationException::withMessages([
                    'due_date' => 'Ngày đến hạn phải sau hoặc bằng ngày phát hành'
                ]);
            }
        }
    }

    /**
     * Validate amounts
     */
    protected function validateAmounts(Invoice $invoice): void
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

    /**
     * Validate monthly rent uniqueness
     */
    protected function validateMonthlyRentUniqueness(Invoice $invoice): void
    {
        if ($invoice->invoice_type !== 'monthly_rent' || !$invoice->lease_id) {
            return; // Skip nếu không phải monthly_rent
        }

        $issueMonth = $invoice->issue_date->format('Y-m');

        $exists = Invoice::where('lease_id', $invoice->lease_id)
            ->where('invoice_type', 'monthly_rent')
            ->whereRaw("DATE_FORMAT(issue_date, '%Y-%m') = ?", [$issueMonth])
            ->whereNull('deleted_at')
            ->when($invoice->exists, function ($query) use ($invoice) {
                return $query->where('id', '!=', $invoice->id);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'invoice_type' => "Đã tồn tại hóa đơn tiền nhà cho tháng {$issueMonth}"
            ]);
        }
    }

    /**
     * Validate status transition
     */
    protected function validateStatusTransition(Invoice $invoice): void
    {
        if (!$invoice->isDirty('status')) {
            return; // Status không thay đổi
        }

        $oldStatus = $invoice->getOriginal('status');
        $newStatus = $invoice->status;

        // draft → issued: phải có ít nhất 1 invoice_item
        if ($oldStatus === 'draft' && $newStatus === 'issued') {
            if ($invoice->items()->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Hóa đơn phải có ít nhất 1 dòng hóa đơn trước khi phát hành'
                ]);
            }
        }

        // issued → paid: phải đủ tiền
        if ($oldStatus === 'issued' && $newStatus === 'paid') {
            $paidTotal = Payment::where('invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->where('status', 'success')
                ->sum('amount');

            if ($paidTotal < $invoice->total_amount) {
                throw ValidationException::withMessages([
                    'status' => 'Chưa thanh toán đủ số tiền. Đã thanh toán: ' . number_format($paidTotal, 0) . ', Cần: ' . number_format($invoice->total_amount, 0)
                ]);
            }
        }

        // issued → cancelled: chưa có payment thành công
        if ($oldStatus === 'issued' && $newStatus === 'cancelled') {
            $hasPayments = Payment::where('invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->where('status', 'success')
                ->exists();

            if ($hasPayments) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể hủy hóa đơn đã có thanh toán. Vui lòng hoàn tiền trước.'
                ]);
            }
        }

        // issued → overdue: chỉ được set sau due_date
        if ($newStatus === 'overdue') {
            if ($invoice->due_date && $invoice->due_date->isFuture()) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể đặt trạng thái quá hạn trước ngày đến hạn'
                ]);
            }
        }
    }

    /**
     * General validation for saving
     */
    public function validateSaving(Invoice $invoice): void
    {
        if ($invoice->exists) {
            $this->validateUpdating($invoice);
        } else {
            $this->validateCreating($invoice);
        }
    }
}

