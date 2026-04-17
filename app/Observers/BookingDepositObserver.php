<?php

namespace App\Observers;

use App\Models\BookingDeposit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Unit;
use App\Services\CommissionEventService;
use App\Services\AuditLogService;
use App\Services\BookingDepositNotificationService;
use App\Services\BusinessRules\BusinessRulesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BookingDepositObserver
{
    protected $commissionEventService;
    protected $auditLogService;
    protected $notificationService;
    protected $businessRulesService;

    public function __construct(
        CommissionEventService $commissionEventService, 
        AuditLogService $auditLogService,
        BookingDepositNotificationService $notificationService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->commissionEventService = $commissionEventService;
        $this->auditLogService = $auditLogService;
        $this->notificationService = $notificationService;
        $this->businessRulesService = $businessRulesService;
    }
    /**
     * Handle the BookingDeposit "created" event.
     */
    public function created(BookingDeposit $bookingDeposit)
    {
        // Validate business rules first
        $this->businessRulesService->validate($bookingDeposit, 'creating');
        
        // Only create invoice when payment_status is 'pending' (approved or created with pending status)
        if ($bookingDeposit->payment_status === 'pending') {
            $this->createInvoiceForBookingDeposit($bookingDeposit);
        }
        
        // Log audit trail
        $this->auditLogService->logCreated($bookingDeposit);

        // Update unit status based on payment_status
        $this->updateUnitStatusBasedOnPaymentStatus($bookingDeposit);

        // KHÔNG gửi email ở đây - email sẽ được gửi khi invoice được phát hành (status = issued)
        // trong InvoiceObserver, và chỉ khi booking đã được duyệt (payment_status = pending)
    }

    /**
     * Handle the BookingDeposit "updated" event.
     */
    public function updated(BookingDeposit $bookingDeposit)
    {
        // Validate business rules first
        $this->businessRulesService->validate($bookingDeposit, 'updating');
        
        // Only update invoice if amount, deposit_type, or hold_until changed
        if ($bookingDeposit->isDirty(['amount', 'deposit_type', 'hold_until', 'notes'])) {
            $this->updateRelatedInvoice($bookingDeposit);
        }
        
        // If payment_status changed, handle status transitions
        if ($bookingDeposit->isDirty('payment_status')) {
            $oldStatus = $bookingDeposit->getOriginal('payment_status');
            $newStatus = $bookingDeposit->payment_status;
            
            // When approved (pending_approval → pending), create invoice
            if ($oldStatus === 'pending_approval' && $newStatus === 'pending') {
                // Create invoice if not exists
                if (!$bookingDeposit->invoices()->exists()) {
                    $this->createInvoiceForBookingDeposit($bookingDeposit);
                    $bookingDeposit->refresh();
                }
                
                // KHÔNG gửi email ở đây - email sẽ được gửi khi invoice được phát hành (status = issued)
                // trong InvoiceObserver, và chỉ khi booking đã được duyệt (payment_status = pending)
            }
            
            // When payment status changes to paid, send success email
            if ($newStatus === 'paid' && $oldStatus !== 'paid') {
                $this->sendPaymentSuccessEmail($bookingDeposit);
            }
            
            // When cancelled (any status → cancelled), cancel related invoice if not paid
            // Không cho phép hủy booking deposit đã thanh toán
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                // Ngăn chặn việc hủy booking deposit đã thanh toán
                if ($oldStatus === 'paid') {
                    Log::warning('Attempted to cancel paid booking deposit - this should not be allowed', [
                        'booking_deposit_id' => $bookingDeposit->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                    // Không xử lý cancel invoice nếu đã thanh toán
                } else {
                    $this->cancelRelatedInvoice($bookingDeposit);
                }
            }
            
            // When expired (any status → expired), do NOT cancel related invoice
            // Invoice should remain active even if deposit is expired
            
            // Update unit status based on payment_status change
            $this->updateUnitStatusBasedOnPaymentStatus($bookingDeposit);
        }
        
        // Log audit trail
        $this->auditLogService->logUpdated($bookingDeposit);
        
        // Commission events for booking deposits are no longer needed
        // Viewing commission events are handled by ViewingObserver
    }

    /**
     * Handle the BookingDeposit "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(BookingDeposit $bookingDeposit)
    {
        // Validate business rules first (soft delete only)
        if (!$bookingDeposit->isForceDeleting()) {
            $this->businessRulesService->validate($bookingDeposit, 'deleting');
        }
        
        $isForceDelete = $bookingDeposit->isForceDeleting();
        
        Log::info('BookingDeposit deleted', [
            'booking_deposit_id' => $bookingDeposit->id,
            'is_force_deleting' => $isForceDelete,
            'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
        ]);
        
        // Save unit_id before deletion for unit status update
        $unitId = $bookingDeposit->unit_id;
        
        // When booking deposit is deleted, cancel related invoices
        $invoices = $bookingDeposit->invoices;
        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'paid') {
                $invoice->update([
                    'status' => 'cancelled',
                    'note' => ($invoice->note ?? '') . "\n[Hủy tự động do đặt cọc bị xóa]"
                ]);
                
                Log::info('Invoice automatically cancelled due to booking deposit deletion', [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'invoice_id' => $invoice->id
                ]);
            }
        }
        
        // Update unit status after deletion
        if ($unitId) {
            $this->updateUnitStatusAfterDeletion($unitId);
        }
        
        // Log audit trail
        $this->auditLogService->logDeleted($bookingDeposit);
    }

    /**
     * Handle the BookingDeposit "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(BookingDeposit $bookingDeposit): void
    {
        Log::info('BookingDeposit force deleted (permanent delete completed)', [
            'booking_deposit_id' => $bookingDeposit->id,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }

    /**
     * Create invoice automatically for booking deposit
     */
    private function createInvoiceForBookingDeposit(BookingDeposit $bookingDeposit)
    {
        try {
            // Skip if invoice already exists
            if ($bookingDeposit->invoices()->exists()) {
                return;
            }

            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber($bookingDeposit->organization_id);
            
            // Get unit and property information
            $unit = $bookingDeposit->unit;
            $property = $unit->property;
            
            // Create invoice with due_date = payment_due_date of booking deposit
            // Convert datetime to date format
            $dueDate = null;
            if ($bookingDeposit->payment_due_date) {
                $dueDate = \Carbon\Carbon::parse($bookingDeposit->payment_due_date)->format('Y-m-d');
            } elseif ($bookingDeposit->hold_until) {
                $dueDate = \Carbon\Carbon::parse($bookingDeposit->hold_until)->format('Y-m-d');
            } else {
                $dueDate = now()->addDays(3)->format('Y-m-d');
            }
            
            $invoice = Invoice::create([
                'organization_id' => $bookingDeposit->organization_id,
                'is_auto_created' => true,
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_no' => $invoiceNumber,
                'invoice_type' => Invoice::TYPE_BOOKING_DEPOSIT,
                'issue_date' => now()->format('Y-m-d'), // Ensure date format, not datetime
                'due_date' => $dueDate, // Already formatted as date
                'status' => 'draft',
                'subtotal' => $bookingDeposit->amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $bookingDeposit->amount,
                'currency' => 'VND',
                'note' => "Hóa đơn đặt cọc cho {$property->name} - {$unit->code}. Loại: " . ucfirst($bookingDeposit->deposit_type),
            ]);
            
            // Create invoice item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'deposit',
                'description' => "Đặt cọc {$bookingDeposit->deposit_type} - {$property->name} - {$unit->code}",
                'quantity' => 1,
                'unit_price' => $bookingDeposit->amount,
                'amount' => $bookingDeposit->amount,
            ]);
            
            // Invoice is already linked via booking_deposit_id in invoices table
            // No need to update booking_deposits table anymore
            // Refresh the model to load the relationship
            $bookingDeposit->refresh();
            
            Log::info('Invoice created automatically for booking deposit', [
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoiceNumber,
                'amount' => $bookingDeposit->amount,
                'status' => 'draft' // Invoice được tạo với status draft, chờ phát hành thủ công
            ]);

            // Không tự động phát hành hóa đơn
            // Hóa đơn sẽ được phát hành thủ công bởi staff
            // Email sẽ được gửi khi invoice status thay đổi từ 'draft' → 'issued' (trong InvoiceObserver)
            
        } catch (\Exception $e) {
            Log::error('Error creating invoice for booking deposit: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update related invoice when booking deposit changes
     */
    private function updateRelatedInvoice(BookingDeposit $bookingDeposit)
    {
        $invoice = $bookingDeposit->invoices()->first();
        if (!$invoice) {
            return;
        }

        try {

            // Update invoice details with due_date = payment_due_date of booking deposit
            // Convert datetime to date format
            $dueDate = null;
            if ($bookingDeposit->payment_due_date) {
                $dueDate = \Carbon\Carbon::parse($bookingDeposit->payment_due_date)->format('Y-m-d');
            } elseif ($bookingDeposit->hold_until) {
                $dueDate = \Carbon\Carbon::parse($bookingDeposit->hold_until)->format('Y-m-d');
            } else {
                $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : now()->addDays(3)->format('Y-m-d');
            }
            
            $invoice->update([
                'due_date' => $dueDate, // Already formatted as date
                'subtotal' => $bookingDeposit->amount,
                'total_amount' => $bookingDeposit->amount,
                'note' => "Hóa đơn đặt cọc cho {$bookingDeposit->unit->property->name} - {$bookingDeposit->unit->code}. Loại: " . ucfirst($bookingDeposit->deposit_type),
            ]);

            // Update invoice item
            $invoiceItem = $invoice->items()->first();
            if ($invoiceItem) {
                $invoiceItem->update([
                    'description' => "Đặt cọc {$bookingDeposit->deposit_type} - {$bookingDeposit->unit->property->name} - {$bookingDeposit->unit->code}",
                    'unit_price' => $bookingDeposit->amount,
                    'amount' => $bookingDeposit->amount,
                ]);
            }

            Log::info('Invoice automatically updated due to booking deposit changes', [
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_id' => $invoice->id,
                'changes' => $bookingDeposit->getDirty()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoice from booking deposit changes: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Cancel related invoice when booking deposit is cancelled
     */
    private function cancelRelatedInvoice(BookingDeposit $bookingDeposit)
    {
        $invoices = $bookingDeposit->invoices;
        if ($invoices->isEmpty()) {
            return;
        }

        foreach ($invoices as $invoice) {
            try {
                // Only cancel if invoice is not paid (thanh toán thành công)
                if ($invoice->status !== 'paid') {
                    $invoice->update([
                        'status' => 'cancelled',
                        'note' => ($invoice->note ?? '') . "\n[Hủy tự động do đặt cọc bị hủy]"
                    ]);
                    
                    Log::info('Invoice automatically cancelled due to booking deposit cancellation', [
                        'booking_deposit_id' => $bookingDeposit->id,
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_status' => $invoice->getOriginal('status'),
                        'old_payment_status' => $bookingDeposit->getOriginal('payment_status'),
                        'new_payment_status' => $bookingDeposit->payment_status
                    ]);
                } else {
                    Log::info('Invoice not cancelled because it is already paid', [
                        'booking_deposit_id' => $bookingDeposit->id,
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_status' => $invoice->status
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error cancelling invoice when booking deposit cancelled: ' . $e->getMessage(), [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'invoice_id' => $invoice->id ?? null,
                    'invoice_no' => $invoice->invoice_no ?? null,
                    'error' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Update unit status after booking deposit deletion
     */
    private function updateUnitStatusAfterDeletion($unitId)
    {
        try {
            $unit = \App\Models\Unit::find($unitId);
            if (!$unit) {
                return;
            }

            // Check if unit has active lease
            $hasActiveLease = $unit->leases()
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->exists();

            // If unit has active lease, don't change status
            if ($hasActiveLease) {
                return;
            }

            // Don't change status if unit is in maintenance or occupied
            if (in_array($unit->status, ['maintenance', 'occupied'])) {
                return;
            }

            // Check all remaining booking deposits for this unit
            $activeDeposits = BookingDeposit::where('unit_id', $unitId)
                ->whereNull('deleted_at')
                ->get();

            // Check if there are any deposits with payment_status = 'pending' or 'paid'
            $hasActiveDeposit = $activeDeposits->contains(function ($deposit) {
                return in_array($deposit->payment_status, ['pending', 'paid']);
            });

            // Determine target status
            $targetStatus = $hasActiveDeposit ? 'reserved' : 'available';

            // Only update if status needs to change
            if ($unit->status !== $targetStatus) {
                $oldStatus = $unit->status;
                $unit->update(['status' => $targetStatus]);

                Log::info('Unit status updated after booking deposit deletion', [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'has_active_deposit' => $hasActiveDeposit,
                    'unit_old_status' => $oldStatus,
                    'unit_new_status' => $targetStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating unit status after deletion: ' . $e->getMessage(), [
                'unit_id' => $unitId,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update unit status based on booking deposit payment_status
     * Only set reserved when payment_status is 'pending' or 'paid'
     * Set available for any other status
     */
    private function updateUnitStatusBasedOnPaymentStatus(BookingDeposit $bookingDeposit)
    {
        try {
            $unit = $bookingDeposit->unit;
            if (!$unit) {
                return;
            }

            // Check if unit has active lease
            $hasActiveLease = $unit->leases()
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->exists();

            // If unit has active lease, don't change status
            if ($hasActiveLease) {
                return;
            }

            // Don't change status if unit is in maintenance or occupied
            if (in_array($unit->status, ['maintenance', 'occupied'])) {
                return;
            }

            // Check all booking deposits for this unit
            $activeDeposits = BookingDeposit::where('unit_id', $unit->id)
                ->whereNull('deleted_at')
                ->get();

            // Check if there are any deposits with payment_status = 'pending' or 'paid'
            $hasActiveDeposit = $activeDeposits->contains(function ($deposit) {
                return in_array($deposit->payment_status, ['pending', 'paid']);
            });

            // Determine target status
            $targetStatus = $hasActiveDeposit ? 'reserved' : 'available';

            // Only update if status needs to change
            if ($unit->status !== $targetStatus) {
                $oldStatus = $unit->status;
                $unit->update(['status' => $targetStatus]);

                Log::info('Unit status updated based on booking deposit payment_status', [
                    'booking_deposit_id' => $bookingDeposit->id,
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'payment_status' => $bookingDeposit->payment_status,
                    'has_active_deposit' => $hasActiveDeposit,
                    'unit_old_status' => $oldStatus,
                    'unit_new_status' => $targetStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating unit status based on payment_status: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'unit_id' => $bookingDeposit->unit_id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send payment link email to lead/tenant
     */
    private function sendPaymentLinkEmail(BookingDeposit $bookingDeposit)
    {
        try {
            // Check if invoice exists before sending email
            if (!$bookingDeposit->invoices()->exists()) {
                Log::warning('Cannot send payment link email: invoice not found', [
                    'booking_deposit_id' => $bookingDeposit->id,
                ]);
                return;
            }
            
            $this->notificationService->sendPaymentLinkEmail($bookingDeposit);
        } catch (\Exception $e) {
            Log::error('Error sending payment link email: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send payment success email to lead/tenant
     */
    private function sendPaymentSuccessEmail(BookingDeposit $bookingDeposit)
    {
        try {
            // Check if invoice exists before sending email (optional for success email)
            // Success email doesn't require invoice, but we log if invoice is missing
            if (!$bookingDeposit->invoices()->exists()) {
                Log::warning('Sending payment success email without invoice', [
                    'booking_deposit_id' => $bookingDeposit->id,
                ]);
            }
            
            $this->notificationService->sendPaymentSuccessEmail($bookingDeposit);
        } catch (\Exception $e) {
            Log::error('Error sending payment success email: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    // Commission events logic moved to ViewingObserver for viewing_done trigger
}
