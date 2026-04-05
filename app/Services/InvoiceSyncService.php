<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\BookingDeposit;
use App\Models\Lease;
use App\Models\PaymentCycle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceSyncService
{
    /**
     * Sync invoice with booking deposit changes
     */
    public function syncWithBookingDeposit(BookingDeposit $bookingDeposit)
    {
        $invoice = $bookingDeposit->invoices()->first();
        if (!$invoice) {
            return false;
        }

        try {
            DB::beginTransaction();

            // Update invoice details
            $invoice->update([
                'due_date' => $bookingDeposit->hold_until,
                'subtotal' => $bookingDeposit->amount,
                'total_amount' => $bookingDeposit->amount,
                'note' => "Hóa đơn đặt cọc cho {$bookingDeposit->unit->property->name} - {$bookingDeposit->unit->code}. Loại: " . ucfirst($bookingDeposit->deposit_type),
            ]);

            // Update or create invoice item
            $invoiceItem = $invoice->items()->first();
            if ($invoiceItem) {
                $invoiceItem->update([
                    'description' => "Đặt cọc {$bookingDeposit->deposit_type} - {$bookingDeposit->unit->property->name} - {$bookingDeposit->unit->code}",
                    'unit_price' => $bookingDeposit->amount,
                    'amount' => $bookingDeposit->amount,
                ]);
            } else {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'deposit',
                    'description' => "Đặt cọc {$bookingDeposit->deposit_type} - {$bookingDeposit->unit->property->name} - {$bookingDeposit->unit->code}",
                    'quantity' => 1,
                    'unit_price' => $bookingDeposit->amount,
                    'amount' => $bookingDeposit->amount,
                ]);
            }

            DB::commit();

            Log::info('Invoice synced with booking deposit', [
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_id' => $invoice->id
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing invoice with booking deposit: ' . $e->getMessage(), [
                'booking_deposit_id' => $bookingDeposit->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sync invoice with lease changes
     */
    public function syncWithLease(Lease $lease)
    {
        try {
            DB::beginTransaction();

            // Get all unpaid invoices for this lease
            $invoices = Invoice::where('lease_id', $lease->id)
                ->where('status', '!=', 'paid')
                ->get();

            foreach ($invoices as $invoice) {
                $this->updateInvoiceFromLease($invoice, $lease);
            }

            DB::commit();

            Log::info('Invoices synced with lease', [
                'lease_id' => $lease->id,
                'updated_invoices' => $invoices->pluck('id')->toArray()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing invoices with lease: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sync invoice with lease service set changes
     * Note: This method is deprecated. Use syncWithLease() instead.
     * Lease service sets are now managed at organization/property/lease level.
     */
    public function syncWithLeaseServiceSet($leaseId)
    {
        try {
            $lease = Lease::find($leaseId);
            if (!$lease) {
                return false;
            }

            return $this->syncWithLease($lease);

        } catch (\Exception $e) {
            Log::error('Error syncing invoices with lease service set: ' . $e->getMessage(), [
                'lease_id' => $leaseId,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update invoice from lease changes
     */
    private function updateInvoiceFromLease(Invoice $invoice, Lease $lease)
    {
        // Update rent-related invoice items based on payment cycle
        $rentItems = $invoice->items()->where('item_type', 'rent')->get();
        foreach ($rentItems as $item) {
            if ($lease->isDirty('rent_amount') || $lease->isDirty('payment_cycle_id')) {
                // Get payment cycle to calculate correct amount
                $paymentCycle = $lease->getEffectivePaymentCycle();
                $months = $this->getPaymentCycleMonths($paymentCycle);
                
                $item->update([
                    'unit_price' => $lease->rent_amount,
                    'quantity' => $months,
                    'amount' => $lease->rent_amount * $months,
                ]);
            }
        }

        // Update deposit-related invoice items
        $depositItems = $invoice->items()->where('item_type', 'deposit')->get();
        foreach ($depositItems as $item) {
            if ($lease->isDirty('deposit_amount')) {
                $item->update([
                    'unit_price' => $lease->deposit_amount,
                    'amount' => $lease->deposit_amount,
                ]);
            }
        }

        // Update due date if payment cycle changed
        if ($lease->isDirty('payment_cycle_id')) {
            $paymentCycle = $lease->getEffectivePaymentCycle();
            if ($paymentCycle && $paymentCycle->payment_day) {
                $dueDate = \Carbon\Carbon::parse($invoice->issue_date);
                $dueDate->day($paymentCycle->payment_day);
                if ($dueDate->lt($invoice->issue_date)) {
                    $dueDate->addMonth();
                }
                $invoice->due_date = $dueDate;
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
     * Get payment cycle in months
     */
    private function getPaymentCycleMonths($paymentCycle)
    {
        if (!$paymentCycle) {
            return 1; // Default to monthly
        }

        switch ($paymentCycle->cycle_type) {
            case 'monthly':
                return 1;
            case 'quarterly':
                return 3;
            case 'yearly':
                return 12;
            case 'custom':
                return $paymentCycle->custom_months ?? 1;
            default:
                return 1; // Default to monthly
        }
    }


    /**
     * Recalculate all invoice totals
     */
    public function recalculateInvoiceTotals(Invoice $invoice)
    {
        try {
            $subtotal = $invoice->items()->sum('amount');
            $totalAmount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;

            $invoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
            ]);

            Log::info('Invoice totals recalculated', [
                'invoice_id' => $invoice->id,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error recalculating invoice totals: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sync all pending invoices
     */
    public function syncAllPendingInvoices()
    {
        try {
            $pendingInvoices = Invoice::where('status', '!=', 'paid')
                ->where('status', '!=', 'cancelled')
                ->get();

            $syncedCount = 0;
            foreach ($pendingInvoices as $invoice) {
                if ($this->recalculateInvoiceTotals($invoice)) {
                    $syncedCount++;
                }
            }

            Log::info('Bulk invoice sync completed', [
                'total_invoices' => $pendingInvoices->count(),
                'synced_invoices' => $syncedCount
            ]);

            return $syncedCount;

        } catch (\Exception $e) {
            Log::error('Error in bulk invoice sync: ' . $e->getMessage(), [
                'error' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }
}
