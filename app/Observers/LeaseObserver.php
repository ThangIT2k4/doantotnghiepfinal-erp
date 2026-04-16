<?php

namespace App\Observers;

use App\Models\Lease;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\CommissionEventService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\LeaseCreated;
use App\Events\LeaseUpdated;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class LeaseObserver
{
    protected $commissionEventService;
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(
        CommissionEventService $commissionEventService,
        AuditLogService $auditLogService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->commissionEventService = $commissionEventService;
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }
    /**
     * Handle the Lease "created" event.
     */
    public function created(Lease $lease)
    {
        // Validate business rules first
        $this->businessRulesService->validate($lease, 'creating');
        
        Log::info('LeaseObserver::created triggered', [
            'lease_id' => $lease->id,
            'rent_amount' => $lease->rent_amount,
            'deposit_amount' => $lease->deposit_amount,
            'status' => $lease->status
        ]);
        
        // Log audit trail
        $this->auditLogService->logCreated($lease);
        
        // BỎ: Không tạo hóa đơn tự động khi tạo hợp đồng
        // Hóa đơn sẽ được tạo thủ công từ trang show hợp đồng
        // $this->createFirstMonthRentInvoice($lease);
        
        // Automatically create commission events when lease is created
        $this->createCommissionEvents($lease);
        
        // Fire LeaseCreated event for notifications only if lease is active
        if ($lease->status === 'active') {
            event(new LeaseCreated($lease));
        }
    }

    /**
     * Handle the Lease "updated" event.
     */
public function updated(Lease $lease)
    {
        // Validate business rules first
        $this->businessRulesService->validate($lease, 'updating');
        
        // Get changed fields before any operations
        $changedFields = $lease->getDirty();
        
        // Only update invoices if rent_amount or deposit_amount changed
        if ($lease->isDirty(['rent_amount', 'deposit_amount', 'status'])) {
            $this->updateRelatedInvoices($lease);
        }
        
        // Update commission events if relevant fields changed
        if ($lease->isDirty(['rent_amount', 'deposit_amount'])) {
            $this->updateCommissionEvents($lease);
        }
        
        // Create commission events if status changed to active
        if ($lease->isDirty('status') && $lease->status === 'active') {
            $originalStatus = $lease->getOriginal('status');
            
            // Create commission events for draft → active
            // BỎ: Không tạo hóa đơn tự động, hóa đơn sẽ được tạo thủ công từ trang show hợp đồng
            if ($originalStatus === 'draft') {
                Log::info('Lease status changed from draft to active, creating commission events', [
                    'lease_id' => $lease->id,
                    'original_status' => $originalStatus,
                    'new_status' => $lease->status
                ]);
                $this->createCommissionEvents($lease);
                // $this->createFirstMonthRentInvoice($lease); // BỎ: Không tạo hóa đơn tự động
            }
            
            // Send notifications when lease is activated (from any status)
            Log::info('Lease activated, sending notifications', [
                'lease_id' => $lease->id,
                'original_status' => $originalStatus,
                'new_status' => $lease->status
            ]);
            event(new LeaseCreated($lease));
        } else {
            // Log audit trail for all changes
            Log::info('Lease updated, logging audit trail', [
                'lease_id' => $lease->id,
                'status' => $lease->status,
                'changed_fields' => array_keys($changedFields),
                'has_changes' => !empty($changedFields)
            ]);
            
            // Always log audit trail
            $this->auditLogService->logUpdated($lease, $changedFields);
            
            // Fire LeaseUpdated event for email notifications
            // Important fields that should trigger notifications
            $importantFields = ['rent_amount', 'deposit_amount', 'start_date', 'end_date', 'tenant_id', 'unit_id', 'status'];
            $hasImportantChanges = false;
            
            foreach ($importantFields as $field) {
                if (isset($changedFields[$field])) {
                    $hasImportantChanges = true;
                    break;
                }
            }
            
            if ($hasImportantChanges && !empty($changedFields)) {
                Log::info('Lease updated with important changes, firing LeaseUpdated event', [
                    'lease_id' => $lease->id,
                    'changed_fields' => array_keys($changedFields)
                ]);
                event(new LeaseUpdated($lease, $changedFields));
            }
        }
    }

    /**
     * Handle the Lease "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Lease $lease)
    {
        // Validate business rules first (soft delete only)
        if (!$lease->isForceDeleting()) {
            $this->businessRulesService->validate($lease, 'deleting');
        }
        
        $isForceDelete = $lease->isForceDeleting();
        
        Log::info('Lease deleted', [
            'lease_id' => $lease->id,
            'contract_no' => $lease->contract_no,
            'is_force_deleting' => $isForceDelete,
            'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
        ]);
        
        // Log audit trail before deletion
        $this->auditLogService->logDeleted($lease);
        
        // When lease is deleted, cancel all related unpaid invoices
        $invoices = Invoice::where('lease_id', $lease->id)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'status' => 'cancelled',
                'note' => $invoice->note . "\n[Hủy tự động do hợp đồng bị xóa]"
            ]);
        }

        if ($invoices->count() > 0) {
            Log::info('Invoices automatically cancelled due to lease deletion', [
                'lease_id' => $lease->id,
                'cancelled_invoices' => $invoices->pluck('id')->toArray()
            ]);
        }
        
        // Delete commission events when lease is deleted
        $this->deleteCommissionEvents($lease);
    }

    /**
     * Handle the Lease "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Lease $lease): void
    {
        Log::info('Lease force deleted (permanent delete completed)', [
            'lease_id' => $lease->id,
            'contract_no' => $lease->contract_no ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }

    /**
     * Create first month rent invoice automatically for lease
     */
    private function createFirstMonthRentInvoice(Lease $lease)
    {
        try {
            Log::info('LeaseObserver::createFirstMonthRentInvoice started', [
                'lease_id' => $lease->id,
                'rent_amount' => $lease->rent_amount,
                'deposit_amount' => $lease->deposit_amount,
                'booking_id' => $lease->booking_id
            ]);
            
            // QUAN TRỌNG: Nếu có booking_id, hóa đơn đã được tạo trong LeaseController::createFirstInvoiceWithDepositDeduction
            // Bỏ qua hoàn toàn để tránh tạo hóa đơn trùng
            if ($lease->booking_id) {
                Log::info('Skipping invoice creation - lease has booking_id, invoice already created in LeaseController', [
                    'lease_id' => $lease->id,
                    'booking_id' => $lease->booking_id
                ]);
                return; // Hóa đơn đã được tạo trong LeaseController, không cần tạo ở đây
            }
            
            // Skip if lease doesn't have rent amount
            if (!$lease->rent_amount || $lease->rent_amount <= 0) {
                Log::info('Skipping invoice creation - no rent amount', [
                    'lease_id' => $lease->id,
                    'rent_amount' => $lease->rent_amount
                ]);
                return;
            }

            // Kiểm tra invoice_timing với priority: Lease > Property > Organization Default Cycle
            $property = $lease->property;
            
            if ($property) {
                $invoiceTiming = $property->getEffectiveInvoiceTiming();
            } else {
                $organization = $lease->organization ?? \App\Models\Organization::find($lease->organization_id);
                $invoiceTiming = $organization ? $organization->getEffectiveInvoiceTiming() : 'end_of_cycle';
            }
            
            // Nếu invoice_timing = 'end_of_cycle' và không có tiền cọc, không cần tạo hóa đơn
            if ($invoiceTiming === 'end_of_cycle' && (!$lease->deposit_amount || $lease->deposit_amount <= 0)) {
                Log::info('Skipping invoice creation - invoice_timing is end_of_cycle and no deposit amount', [
                    'lease_id' => $lease->id,
                    'invoice_timing' => $invoiceTiming,
                    'deposit_amount' => $lease->deposit_amount
                ]);
                return;
            }
            
            // Check if first invoice already exists (cho trường hợp không có booking deposit)
            $existingInvoice = Invoice::where('lease_id', $lease->id)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) {
                    $query->whereHas('items', function($q) {
                        $q->where('item_type', 'deposit')
                          ->orWhere('item_type', 'rent')
                          ->orWhere('description', 'like', '%chu kỳ đầu%')
                          ->orWhere('description', 'like', '%tháng đầu%');
                    });
                })
                ->first();

            if ($existingInvoice) {
                Log::info('First invoice already exists (may include deposit and/or rent), skipping creation', [
                    'lease_id' => $lease->id,
                    'existing_invoice_id' => $existingInvoice->id
                ]);
                return; // Already exists
            }
            
            // Nếu chưa có hóa đơn và invoice_timing = 'start_of_cycle', tạo hóa đơn với tiền cọc + tiền thuê
            // (Nếu có booking deposit, hóa đơn đã được tạo trong LeaseController với tiền cọc + trừ tiền cọc + tiền thuê)
            // (Nếu không có booking deposit, tạo hóa đơn ở đây với tiền cọc + tiền thuê)
            
            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber($lease->organization_id);
            
            // Get unit and property information
            $unit = $lease->unit;
            $property = $unit->property;
            
            // Calculate dates
            $issueDate = $lease->start_date;
            $dueDate = $this->calculateDueDate($lease, $issueDate);
            
            // Calculate totals - bao gồm tiền cọc (không nhân số tháng) + tiền thuê chu kỳ đầu (nếu start_of_cycle)
            $depositAmount = (float)$lease->deposit_amount ?? 0; // Tiền cọc (không nhân số tháng)
            
            $rentTotal = 0;
            $servicesTotal = 0;
            $cycleInfo = null;
            
            // Chỉ tính tiền thuê nếu invoice_timing = 'start_of_cycle' (KHÔNG tính dịch vụ)
            if ($invoiceTiming === 'start_of_cycle') {
                $cycleInfo = $this->calculatePaymentCycle($lease);
                $rentTotal = $cycleInfo['rent_total'] ?? 0;
                // KHÔNG tính dịch vụ trong first invoice
                $servicesTotal = 0;
            }
            
            $totalAmount = $depositAmount + $rentTotal;
            
            // Create invoice
            $invoice = Invoice::create([
                'organization_id' => $lease->organization_id,
                'is_auto_created' => true,
                'lease_id' => $lease->id,
                'invoice_no' => $invoiceNumber,
                'invoice_type' => Invoice::TYPE_FIRST_INVOICE,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'VND',
                'note' => "Hóa đơn đầu tiên" . ($invoiceTiming === 'start_of_cycle' ? ' (bao gồm tiền cọc và tiền thuê chu kỳ đầu)' : ' (chỉ tiền cọc)') . " cho {$property->name} - {$unit->code}",
            ]);
            
            // Add deposit item (tiền cọc - không nhân số tháng)
            if ($depositAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'deposit',
                    'description' => 'Tiền cọc - ' . $property->name . ' - ' . $unit->code,
                    'quantity' => 1,
                    'unit_price' => $depositAmount,
                    'amount' => $depositAmount,
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'type' => 'deposit',
                    ],
                ]);
            }
            
            // Create invoice item for rent (payment cycle) - chỉ nếu invoice_timing = 'start_of_cycle'
            if ($invoiceTiming === 'start_of_cycle' && $rentTotal > 0 && $cycleInfo) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'rent',
                    'description' => $cycleInfo['description'] . " - {$property->name} - {$unit->code}",
                    'quantity' => $cycleInfo['months'],
                    'unit_price' => $lease->rent_amount,
                    'amount' => $rentTotal,
                ]);
            }
            
            // KHÔNG fill service items khi invoice_timing = 'start_of_cycle'
            // Dịch vụ sẽ được tính trong các hóa đơn chu kỳ tiếp theo
            
            // Recalculate totals from items
            $recalculatedSubtotal = $invoice->items()->sum('amount');
            $invoice->update([
                'subtotal' => $recalculatedSubtotal,
                'total_amount' => max(0, $recalculatedSubtotal),
            ]);
            
            $effectiveCycle = $lease->getEffectivePaymentCycle();
            
            Log::info('First invoice created automatically for lease (includes deposit and rent if start_of_cycle)', [
                'lease_id' => $lease->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoiceNumber,
                'payment_cycle_id' => $lease->payment_cycle_id,
                'payment_cycle_type' => $effectiveCycle ? $effectiveCycle->cycle_type : null,
                'invoice_timing' => $invoiceTiming,
                'deposit_amount' => $depositAmount,
                'months' => $cycleInfo['months'],
                'rent_total' => $rentTotal,
                'services_total' => $servicesTotal,
                'total_amount' => $recalculatedSubtotal
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating first month rent invoice for lease: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'rent_amount' => $lease->rent_amount,
                'deposit_amount' => $lease->deposit_amount,
                'error' => $e->getTraceAsString()
            ]);
            
            // Don't re-throw the exception to prevent breaking the lease creation process
            // The invoice can be created manually later if needed
        }
    }
    
    /**
     * Manually create invoice for existing lease (for fixing missing invoices)
     */
    public static function createInvoiceForExistingLease(Lease $lease)
    {
        $observer = new self(
            app(\App\Services\CommissionEventService::class),
            app(\App\Services\AuditLogService::class),
            app(\App\Services\BusinessRules\BusinessRulesService::class)
        );
        return $observer->createFirstMonthRentInvoice($lease);
    }

    /**
     * Update related invoices when lease changes
     */
    private function updateRelatedInvoices(Lease $lease)
    {
        try {
            // Get all unpaid invoices for this lease
            $invoices = Invoice::where('lease_id', $lease->id)
                ->where('status', '!=', 'paid')
                ->get();

            foreach ($invoices as $invoice) {
                $this->updateInvoiceFromLease($invoice, $lease);
            }

            Log::info('Invoices automatically updated due to lease changes', [
                'lease_id' => $lease->id,
                'updated_invoices' => $invoices->pluck('id')->toArray(),
                'changes' => $lease->getDirty()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoices from lease changes: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update individual invoice from lease changes
     */
    private function updateInvoiceFromLease(Invoice $invoice, Lease $lease)
    {
        // Update rent-related invoice items
        $rentItems = $invoice->items()->where('description', 'like', '%tiền thuê%')->get();
        foreach ($rentItems as $item) {
            if ($lease->isDirty('rent_amount')) {
                $item->update([
                    'unit_price' => $lease->rent_amount,
                    'amount' => $lease->rent_amount,
                ]);
            }
        }

        // Update deposit-related invoice items
        $depositItems = $invoice->items()->where('description', 'like', '%cọc%')->get();
        foreach ($depositItems as $item) {
            if ($lease->isDirty('deposit_amount')) {
                $item->update([
                    'unit_price' => $lease->deposit_amount,
                    'amount' => $lease->deposit_amount,
                ]);
            }
        }

        // Recalculate invoice totals
        $subtotal = $invoice->items()->sum('amount');
        $invoice->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $invoice->tax_amount - $invoice->discount_amount,
        ]);
    }

    /**
     * Create commission events for lease
     */
    private function createCommissionEvents(Lease $lease)
    {
        try {
            // Only create commission events for active leases
            if ($lease->status !== 'active') {
                Log::info('Lease not active, skipping commission events creation', [
                    'lease_id' => $lease->id,
                    'status' => $lease->status
                ]);
                return;
            }

            // Check if commission events already exist for this lease
            $existingEvents = \App\Models\CommissionEvent::where('lease_id', $lease->id)->count();
            if ($existingEvents > 0) {
                Log::info('Commission events already exist for lease, skipping creation', [
                    'lease_id' => $lease->id,
                    'existing_events_count' => $existingEvents
                ]);
                return;
            }

            $result = $this->commissionEventService->createCommissionEventsForLease($lease);
            
            if ($result) {
                Log::info('Commission events created successfully via LeaseObserver', [
                    'lease_id' => $lease->id,
                    'created_events_count' => count($result)
                ]);
            } else {
                Log::warning('Failed to create commission events via LeaseObserver', [
                    'lease_id' => $lease->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error creating commission events in LeaseObserver: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update commission events for lease
     */
    private function updateCommissionEvents(Lease $lease)
    {
        try {
            $result = $this->commissionEventService->updateCommissionEventsForLease($lease);
            
            if ($result) {
                Log::info('Commission events updated successfully via LeaseObserver', [
                    'lease_id' => $lease->id
                ]);
            } else {
                Log::warning('Failed to update commission events via LeaseObserver', [
                    'lease_id' => $lease->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating commission events in LeaseObserver: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Delete commission events for lease
     */
    private function deleteCommissionEvents(Lease $lease)
    {
        try {
            $result = $this->commissionEventService->deleteCommissionEventsForLease($lease);
            
            if ($result) {
                Log::info('Commission events deleted successfully via LeaseObserver', [
                    'lease_id' => $lease->id
                ]);
            } else {
                Log::warning('Failed to delete commission events via LeaseObserver', [
                    'lease_id' => $lease->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error deleting commission events in LeaseObserver: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Calculate payment cycle information
     */
    private function calculatePaymentCycle(Lease $lease)
    {
        // Get payment cycle from lease or fallback to property/organization
        $paymentCycle = $lease->getEffectivePaymentCycle();
        
        $cycleMonths = 1; // Default monthly
        
        if ($paymentCycle) {
            switch ($paymentCycle->cycle_type) {
                case 'monthly':
                    $cycleMonths = 1;
                    break;
                case 'quarterly':
                    $cycleMonths = 3;
                    break;
                case 'yearly':
                    $cycleMonths = 12;
                    break;
                case 'custom':
                    $cycleMonths = $paymentCycle->custom_months ?? 1;
                    break;
            }
        }
        
        $rentAmount = (float)$lease->rent_amount;
        $rentTotal = $rentAmount * $cycleMonths;
        
        $startDate = \Carbon\Carbon::parse($lease->start_date);
        $endDate = $startDate->copy()->addMonths($cycleMonths)->subDay();
        $period = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        
        $description = "Tiền thuê " . ($cycleMonths > 1 ? $cycleMonths . " tháng" : "tháng đầu") . " (" . $period . ")";
        
        // Get services from lease service set
        $serviceItems = [];
        $servicesTotal = 0;
        
        $effectiveServiceSet = $lease->getEffectiveLeaseServiceSet();
        if ($effectiveServiceSet && $effectiveServiceSet->items) {
            foreach ($effectiveServiceSet->items as $item) {
                $serviceAmount = (float)$item->price * $cycleMonths;
                $servicesTotal += $serviceAmount;
                
                $serviceItems[] = [
                    'service_id' => $item->service_id,
                    'description' => ($item->service->name ?? 'Dịch vụ') . " " . ($cycleMonths > 1 ? $cycleMonths . " tháng" : "tháng đầu"),
                    'quantity' => $cycleMonths,
                    'unit_price' => (float)$item->price,
                    'amount' => $serviceAmount,
                ];
            }
        }
        
        return [
            'months' => $cycleMonths,
            'rent_total' => $rentTotal,
            'description' => $description,
            'services_total' => $servicesTotal,
            'service_items' => $serviceItems,
        ];
    }

    /**
     * Calculate due date based on payment cycle
     */
    private function calculateDueDate(Lease $lease, $issueDate)
    {
        // Get payment cycle from lease or fallback to property/organization
        $paymentCycle = $lease->getEffectivePaymentCycle();
        
        // If no payment cycle set, default to 30 days
        if (!$paymentCycle) {
            return $issueDate->copy()->addDays(30);
        }
        
        $paymentDay = (int) ($paymentCycle->payment_day ?? 1);
        
        // Calculate due date based on payment day
        $dueDate = $issueDate->copy();
        
        // Set to the payment day of the current month (ensure int)
        $dueDate->day($paymentDay);
        
        // If payment day has passed in current month, move to next month
        if ($dueDate->lt($issueDate)) {
            $dueDate->addMonth();
        }
        
        return $dueDate;
    }

}
