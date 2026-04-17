<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\BookingDeposit;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\PaymentCreated;
use App\Events\PaymentUpdated;
use Illuminate\Support\Facades\Log;

class PaymentObserver
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
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($payment, 'creating');
        
        $this->updateInvoiceStatus($payment);
        
        // Trigger notification for new payment
        Log::info('PaymentObserver::created triggered', [
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'status' => $payment->status
        ]);
        
        // Log audit trail
        $this->auditLogService->logCreated($payment);
        
        event(new PaymentCreated($payment));
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($payment, 'updating');
        
        // Handle refunded status - cancel invoice
        if ($payment->isDirty('status') && $payment->status === Payment::STATUS_REFUNDED) {
            $this->handleRefundedPayment($payment);
        } else {
            $this->updateInvoiceStatus($payment);
        }
        
        // Trigger notification for payment updates (status changes)
        $changes = $payment->getDirty();
        if (isset($changes['status'])) {
            Log::info('PaymentObserver::updated triggered for status change', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'old_status' => $payment->getOriginal('status'),
                'new_status' => $payment->status,
                'changes' => $changes
            ]);
            
            // Log audit trail
            $this->auditLogService->logUpdated($payment, $changes);
            
            event(new PaymentUpdated($payment, $changes));
        } else {
            // Log audit trail even for non-status changes
            $this->auditLogService->logUpdated($payment, $changes);
        }
    }

    /**
     * Cập nhật trạng thái hóa đơn dựa trên tổng số tiền đã thanh toán
     */
    protected function updateInvoiceStatus(Payment $payment): void
    {
        if (!$payment->invoice) {
            return;
        }

        $invoice = $payment->invoice;

        // Chỉ xử lý nếu thanh toán thành công
        if ($payment->status !== 'success') {
            return;
        }

        // Lấy tổng số tiền đã thanh toán thành công
        $totalPaid = $invoice->payments()
            ->where('status', 'success')
            ->sum('amount');

        $previousStatus = $invoice->status;

        // Kiểm tra xem đã thanh toán đủ chưa
        if ($totalPaid >= $invoice->total_amount) {
            // Đã thanh toán đủ, cập nhật status sang 'paid'
            if ($invoice->status !== 'paid') {
                $invoice->update(['status' => 'paid']);
                
                Log::info("Invoice #{$invoice->invoice_no} automatically marked as paid", [
                    'invoice_id' => $invoice->id,
                    'total_amount' => $invoice->total_amount,
                    'total_paid' => $totalPaid,
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                ]);
                
                // Cập nhật booking deposit nếu invoice liên quan đến booking deposit
                $this->updateBookingDepositStatus($invoice);
            }
        } elseif ($totalPaid > 0 && $totalPaid < $invoice->total_amount) {
            // Đã thanh toán một phần
            if (in_array($invoice->status, ['draft', 'issued', 'overdue'])) {
                // Giữ nguyên status hiện tại nhưng log thông tin
                Log::info("Invoice #{$invoice->invoice_no} partially paid", [
                    'invoice_id' => $invoice->id,
                    'total_amount' => $invoice->total_amount,
                    'total_paid' => $totalPaid,
                    'remaining' => $invoice->total_amount - $totalPaid,
                    'payment_id' => $payment->id,
                ]);
            }
        }
    }

    /**
     * Handle the Payment "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Payment $payment): void
    {
        // Validate business rules first (soft delete only)
        if (!$payment->isForceDeleting()) {
            $this->businessRulesService->validate($payment, 'deleting');
        }
        
        $isForceDelete = $payment->isForceDeleting();
        
        Log::info('Payment deleted', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'is_force_deleting' => $isForceDelete,
            'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
        ]);
        
        // Khi xóa payment, kiểm tra lại trạng thái invoice
        if (!$payment->invoice) {
            // Log audit trail even if no invoice
            $this->auditLogService->logDeleted($payment);
            return;
        }

        $invoice = $payment->invoice;

        // Lấy tổng số tiền đã thanh toán thành công (trừ payment đã xóa)
        $totalPaid = $invoice->payments()
            ->where('status', 'success')
            ->where('id', '!=', $payment->id)
            ->sum('amount');

        // Nếu chưa thanh toán đủ và invoice đang ở status 'paid', chuyển về 'issued'
        if ($totalPaid < $invoice->total_amount && $invoice->status === 'paid') {
            $invoice->update(['status' => 'issued']);
            
            Log::info("Invoice #{$invoice->invoice_no} status reverted from paid to issued", [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
                'total_paid' => $totalPaid,
                'deleted_payment_id' => $payment->id,
            ]);
        }

        // Log audit trail
        $this->auditLogService->logDeleted($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Payment $payment): void
    {
        Log::info('Payment force deleted (permanent delete completed)', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }

    /**
     * Xử lý khi payment chuyển thành refunded - hủy invoice
     */
    protected function handleRefundedPayment(Payment $payment): void
    {
        if (!$payment->invoice) {
            return;
        }

        $invoice = $payment->invoice;

        // Chuyển invoice thành cancelled khi payment bị refunded
        if ($invoice->status !== 'cancelled') {
            $previousStatus = $invoice->status;
            $invoice->update(['status' => 'cancelled']);
            
            Log::info("Invoice #{$invoice->invoice_no} automatically cancelled due to refunded payment", [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'payment_id' => $payment->id,
                'previous_status' => $previousStatus,
                'new_status' => 'cancelled',
            ]);
        }
    }

    /**
     * Cập nhật trạng thái booking deposit khi invoice được thanh toán thành công
     */
    protected function updateBookingDepositStatus($invoice): void
    {
        if (!$invoice->booking_deposit_id) {
            return;
        }

        try {
            $bookingDeposit = BookingDeposit::find($invoice->booking_deposit_id);
            
            if (!$bookingDeposit) {
                return;
            }

            // Chỉ cập nhật nếu booking deposit chưa được đánh dấu là paid
            if ($bookingDeposit->payment_status !== 'paid') {
                $oldStatus = $bookingDeposit->payment_status;
                
                $bookingDeposit->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
                
                Log::info("Booking deposit #{$bookingDeposit->reference_number} automatically marked as paid", [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'old_payment_status' => $oldStatus,
                    'new_payment_status' => 'paid',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error updating booking deposit status when invoice paid: " . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'booking_deposit_id' => $invoice->booking_deposit_id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

