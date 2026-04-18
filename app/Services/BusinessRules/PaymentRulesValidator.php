<?php

namespace App\Services\BusinessRules;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class PaymentRulesValidator
{
    public function validateCreating(Payment $payment): void
    {
        $this->validateAmount($payment);
        $this->validateInvoiceExists($payment);
        $this->validatePaymentTotal($payment);
        $this->validatePayerOrLead($payment);
    }

    public function validateUpdating(Payment $payment): void
    {
        $this->validateAmount($payment);
        
        // Nếu thay đổi amount hoặc invoice_id, kiểm tra lại tổng
        if ($payment->isDirty(['amount', 'invoice_id'])) {
            $this->validatePaymentTotal($payment);
        }
        
        // Nếu thay đổi payer_user_id hoặc lead_id, kiểm tra lại ràng buộc
        if ($payment->isDirty(['payer_user_id', 'lead_id'])) {
            $this->validatePayerOrLead($payment);
        }
    }

    public function validateDeleting(Payment $payment): void
    {
        // Không cho xóa payment đã thành công
        if ($payment->status === 'success') {
            throw ValidationException::withMessages([
                'payment' => 'Không thể xóa thanh toán đã thành công'
            ]);
        }
    }

    protected function validateAmount(Payment $payment): void
    {
        if ($payment->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền thanh toán phải lớn hơn 0'
            ]);
        }
    }

    protected function validateInvoiceExists(Payment $payment): void
    {
        if (!$payment->invoice_id) {
            return;
        }

        $invoice = Invoice::where('id', $payment->invoice_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$invoice) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Hóa đơn không tồn tại hoặc đã bị xóa'
            ]);
        }
    }

    protected function validatePaymentTotal(Payment $payment): void
    {
        if (!$payment->invoice_id) {
            return;
        }

        $invoice = Invoice::find($payment->invoice_id);
        if (!$invoice) {
            return;
        }

        // Tính tổng payments thành công (trừ payment hiện tại nếu đang update)
        $paidTotal = Payment::where('invoice_id', $invoice->id)
            ->whereNull('deleted_at')
            ->where('status', 'success')
            ->when($payment->exists, function ($query) use ($payment) {
                return $query->where('id', '!=', $payment->id);
            })
            ->sum('amount');

        $newTotal = $paidTotal + $payment->amount;

        if ($newTotal > $invoice->total_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Tổng thanh toán (' . number_format($newTotal, 0) . ') vượt quá tổng hóa đơn (' . number_format($invoice->total_amount, 0) . ')'
            ]);
        }
    }

    public function validateSaving(Payment $payment): void
    {
        if ($payment->exists) {
            $this->validateUpdating($payment);
        } else {
            $this->validateCreating($payment);
        }
    }

    /**
     * Validate that at least one of payer_user_id or lead_id is not null
     */
    protected function validatePayerOrLead(Payment $payment): void
    {
        if (empty($payment->payer_user_id) && empty($payment->lead_id)) {
            throw ValidationException::withMessages([
                'payer_user_id' => 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.',
                'lead_id' => 'Phải có ít nhất một trong hai: payer_user_id hoặc lead_id.'
            ]);
        }
    }
}

