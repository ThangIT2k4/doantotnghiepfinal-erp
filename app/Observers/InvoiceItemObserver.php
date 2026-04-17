<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class InvoiceItemObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        try {
            Log::info('InvoiceItemObserver::created triggered', [
                'invoice_item_id' => $invoiceItem->id,
                'invoice_id' => $invoiceItem->invoice_id,
                'item_type' => $invoiceItem->item_type,
                'amount' => $invoiceItem->amount,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($invoiceItem);

        } catch (\Exception $e) {
            Log::error('Error in InvoiceItemObserver::created: ' . $e->getMessage(), [
                'invoice_item_id' => $invoiceItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        try {
            $changes = $invoiceItem->getDirty();

            if (!empty($changes)) {
                Log::info('InvoiceItemObserver::updated triggered', [
                    'invoice_item_id' => $invoiceItem->id,
                    'invoice_id' => $invoiceItem->invoice_id,
                    'changes' => $changes
                ]);

                // Log audit trail for all changes
                $this->auditLogService->logUpdated($invoiceItem, $changes);
            }

        } catch (\Exception $e) {
            Log::error('Error in InvoiceItemObserver::updated: ' . $e->getMessage(), [
                'invoice_item_id' => $invoiceItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        try {
            Log::info('InvoiceItemObserver::deleted triggered', [
                'invoice_item_id' => $invoiceItem->id,
                'invoice_id' => $invoiceItem->invoice_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($invoiceItem);

        } catch (\Exception $e) {
            Log::error('Error in InvoiceItemObserver::deleted: ' . $e->getMessage(), [
                'invoice_item_id' => $invoiceItem->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

