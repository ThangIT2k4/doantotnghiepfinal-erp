<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\BookingDeposit;
use App\Models\DepositRefund;
use App\Models\TicketLog;
use App\Services\CommissionEventService;
use App\Services\AuditLogService;
use App\Services\BookingDepositNotificationService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\InvoiceIssued;
use App\Events\InvoiceUpdated;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    protected $auditLogService;
    protected $bookingDepositNotificationService;
    protected $businessRulesService;

    public function __construct(
        AuditLogService $auditLogService,
        BookingDepositNotificationService $bookingDepositNotificationService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->auditLogService = $auditLogService;
        $this->bookingDepositNotificationService = $bookingDepositNotificationService;
        $this->businessRulesService = $businessRulesService;
    }
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($invoice, 'creating');
        
        try {
            Log::info('InvoiceObserver::created triggered', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'status' => $invoice->status,
                'invoice_type' => $invoice->invoice_type,
                'total_amount' => $invoice->total_amount
            ]);

            // Only trigger notification if invoice is issued immediately
            if ($invoice->status === 'issued') {
                // Gửi email hóa đơn cho Lead nếu invoice từ booking deposit
                $this->sendBookingDepositInvoiceEmailIfNeeded($invoice);
                
                event(new InvoiceIssued($invoice));
            }

            // Log audit trail
            $this->auditLogService->logCreated($invoice);

        } catch (\Exception $e) {
            Log::error('Error in InvoiceObserver::created: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($invoice, 'updating');
        
        try {
            // 1) Recalculate related deposit_refund if exists and positive
            if ($invoice->lease_id) {
                $lease = Lease::find($invoice->lease_id);
                if ($lease) {
                    $refund = DepositRefund::where('lease_id', $lease->id)
                        ->whereIn('status', [DepositRefund::STATUS_PENDING, DepositRefund::STATUS_APPROVED])
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($refund) {
                        $depositAmount = (float) ($lease->deposit_amount ?? 0);
                        $unpaidTotal = (float) Invoice::where('lease_id', $lease->id)
                            ->whereIn('status', ['issued', 'overdue'])
                            ->whereNull('deleted_at')
                            ->get()
                            ->sum(function ($inv) { return (float) $inv->remaining_amount; });
                        $ticketDepositTotal = (float) TicketLog::whereHas('ticket', function($q) use ($lease) {
                                $q->where('unit_id', $lease->unit_id);
                            })
                            ->where('charge_to', 'tenant_deposit')
                            ->where('cost_amount', '>', 0)
                            ->sum('cost_amount');
                        $net = $depositAmount - $unpaidTotal - $ticketDepositTotal;
                        if ($net > 0) {
                            $refund->update([
                                'original_deposit_amount' => $depositAmount,
                                'deducted_amount' => ($unpaidTotal + $ticketDepositTotal),
                                'refund_amount' => $net,
                                'deduction_details' => json_encode([
                                    'unpaid_invoices' => $unpaidTotal,
                                    'ticket_deposit' => $ticketDepositTotal,
                                ])
                            ]);
                        } else {
                            $refund->update(['status' => DepositRefund::STATUS_CANCELLED]);
                        }
                    }
                }
            }

            // 2) Create commission events when invoice is paid
            if ($invoice->isDirty('status') && $invoice->status === 'paid') {
                $originalStatus = $invoice->getOriginal('status');
                
                // Cập nhật booking deposit nếu invoice liên quan đến booking deposit
                if ($invoice->booking_deposit_id) {
                    $this->updateBookingDepositStatus($invoice);
                }
                
                // Chỉ tạo commission events cho tenant invoices có lease
                if ($invoice->invoice_type === 'tenant' && $invoice->lease_id) {
                    try {
                        $commissionService = app(CommissionEventService::class);
                        $commissionService->createInvoicePaidEvents($invoice);
                        
                        Log::info('Commission events created for paid invoice', [
                            'invoice_id' => $invoice->id,
                            'invoice_no' => $invoice->invoice_no,
                            'lease_id' => $invoice->lease_id
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error creating commission events for paid invoice: ' . $e->getMessage(), [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getTraceAsString()
                        ]);
                    }
                }
            }

            // 3) Existing notification logic
            $changes = $invoice->getDirty();
            
            // Only trigger notification if status changed to 'issued'
            if ($invoice->isDirty('status') && $invoice->status === 'issued') {
                $originalStatus = $invoice->getOriginal('status');
                
                Log::info('InvoiceObserver::updated triggered - status changed to issued', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'original_status' => $originalStatus,
                    'new_status' => $invoice->status,
                    'invoice_type' => $invoice->invoice_type,
                    'total_amount' => $invoice->total_amount,
                    'booking_deposit_id' => $invoice->booking_deposit_id
                ]);

                // Gửi email hóa đơn cho Lead nếu invoice từ booking deposit
                $this->sendBookingDepositInvoiceEmailIfNeeded($invoice);

                // Dispatch InvoiceIssued event
                event(new InvoiceIssued($invoice));
            }
            
            // Trigger notification for other important invoice updates
            $importantFields = ['status', 'total_amount', 'due_date', 'note'];
            $hasImportantChanges = false;
            
            foreach ($importantFields as $field) {
                if (isset($changes[$field])) {
                    $hasImportantChanges = true;
                    break;
                }
            }
            
            if ($hasImportantChanges) {
                Log::info('InvoiceObserver::updated triggered for important changes', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'changes' => $changes
                ]);
                event(new InvoiceUpdated($invoice, $changes));
            }

            // Log audit trail for all changes
            $this->auditLogService->logUpdated($invoice, $changes);

        } catch (\Exception $e) {
            Log::error('Error in InvoiceObserver::updated: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Gửi email hóa đơn cho Lead khi invoice đặt cọc được phát hành
     * CHỈ gửi khi:
     * 1. Invoice type là booking_deposit
     * 2. Invoice có booking_deposit_id
     * 3. Booking đã được duyệt (payment_status = 'pending')
     * 4. Invoice status = 'issued'
     */
    protected function sendBookingDepositInvoiceEmailIfNeeded(Invoice $invoice): void
    {
        // Chỉ xử lý invoice đặt cọc
        if ($invoice->invoice_type !== Invoice::TYPE_BOOKING_DEPOSIT) {
            return;
        }

        // Phải có booking_deposit_id
        if (!$invoice->booking_deposit_id) {
            Log::info('Invoice email NOT sent - invoice không có booking_deposit_id', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'invoice_type' => $invoice->invoice_type
            ]);
            return;
        }

        try {
            $bookingDeposit = BookingDeposit::find($invoice->booking_deposit_id);
            
            if (!$bookingDeposit) {
                Log::warning('Invoice email NOT sent - không tìm thấy booking deposit', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'booking_deposit_id' => $invoice->booking_deposit_id
                ]);
                return;
            }

            // Kiểm tra booking đã được duyệt chưa (payment_status = 'pending')
            if ($bookingDeposit->payment_status !== 'pending') {
                Log::info('Invoice email NOT sent - booking chưa được duyệt', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'booking_deposit_id' => $bookingDeposit->id,
                    'payment_status' => $bookingDeposit->payment_status,
                    'expected_status' => 'pending'
                ]);
                return;
            }

            // Kiểm tra invoice status phải là 'issued'
            if ($invoice->status !== 'issued') {
                Log::info('Invoice email NOT sent - invoice chưa được phát hành', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'invoice_status' => $invoice->status,
                    'expected_status' => 'issued'
                ]);
                return;
            }

            // Tất cả điều kiện đều thỏa mãn, gửi email
            $this->bookingDepositNotificationService->sendInvoiceEmailForLead($invoice);
            
            Log::info('Invoice email sent to Lead successfully', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'booking_deposit_id' => $bookingDeposit->id,
                'payment_status' => $bookingDeposit->payment_status,
                'invoice_status' => $invoice->status,
                'invoice_type' => $invoice->invoice_type
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending invoice email to Lead: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'booking_deposit_id' => $invoice->booking_deposit_id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Cập nhật trạng thái booking deposit khi invoice được thanh toán thành công
     */
    protected function updateBookingDepositStatus(Invoice $invoice): void
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
                
                Log::info("Booking deposit #{$bookingDeposit->reference_number} automatically marked as paid via InvoiceObserver", [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'old_payment_status' => $oldStatus,
                    'new_payment_status' => 'paid',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error updating booking deposit status when invoice paid in InvoiceObserver: " . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'booking_deposit_id' => $invoice->booking_deposit_id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Invoice $invoice): void
    {
        // Validate business rules first (soft delete only)
        if (!$invoice->isForceDeleting()) {
            $this->businessRulesService->validate($invoice, 'deleting');
        }
        
        try {
            $isForceDelete = $invoice->isForceDeleting();
            
            Log::info('InvoiceObserver::deleted triggered', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'status' => $invoice->status,
                'is_force_deleting' => $isForceDelete,
                'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($invoice);

        } catch (\Exception $e) {
            Log::error('Error in InvoiceObserver::deleted: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Invoice "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Invoice $invoice): void
    {
        Log::info('Invoice force deleted (permanent delete completed)', [
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }
}
