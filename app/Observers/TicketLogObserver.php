<?php

namespace App\Observers;

use App\Models\TicketLog;
use App\Models\CompanyInvoice;
use App\Models\CompanyInvoiceItem;
use Illuminate\Support\Facades\Auth;
use App\Services\TicketInvoiceService;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\TicketLogCreated;
use Illuminate\Support\Facades\Log;

class TicketLogObserver
{
    protected $ticketInvoiceService;
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(
        TicketInvoiceService $ticketInvoiceService,
        AuditLogService $auditLogService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->ticketInvoiceService = $ticketInvoiceService;
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }

    /**
     * Handle the TicketLog "created" event.
     */
    public function created(TicketLog $ticketLog)
    {
        // Validate business rules first
        $this->businessRulesService->validate($ticketLog, 'creating');
        
        try {
            Log::info('>>> TicketLogObserver::created triggered', [
                'ticket_log_id' => $ticketLog->id,
                'ticket_id' => $ticketLog->ticket_id,
                'cost_amount' => $ticketLog->cost_amount,
                'charge_to' => $ticketLog->charge_to,
                'vendor_id' => $ticketLog->vendor_id,
            ]);

            // Tự động xử lý tạo hóa đơn nếu có chi phí
            if ($ticketLog->cost_amount > 0 && in_array($ticketLog->charge_to, ['tenant_invoice', 'landlord'])) {
                Log::info('>>> Processing tenant_invoice or landlord', [
                    'ticket_log_id' => $ticketLog->id,
                ]);
                $this->ticketInvoiceService->processTicketLogInvoice($ticketLog);
            }

            // Tự chi trả (Vendor) -> tạo CompanyInvoice + CompanyInvoiceItem
            $shouldCreateCompanyInvoice = $ticketLog->cost_amount > 0 && $ticketLog->charge_to === 'self_pay_vendor';
            Log::info('>>> Check if should create CompanyInvoice', [
                'ticket_log_id' => $ticketLog->id,
                'cost_amount' => $ticketLog->cost_amount,
                'charge_to' => $ticketLog->charge_to,
                'should_create' => $shouldCreateCompanyInvoice,
            ]);
            
            if ($shouldCreateCompanyInvoice) {
                $this->createCompanyInvoiceForVendorSelfPay($ticketLog);
            }

            // Dispatch TicketLogCreated event for notifications
            event(new TicketLogCreated($ticketLog));

            // Log audit trail
            $this->auditLogService->logCreated($ticketLog);

        } catch (\Exception $e) {
            Log::error('Error in TicketLogObserver::created: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    private function createCompanyInvoiceForVendorSelfPay(TicketLog $ticketLog): void
    {
        try {
            Log::info('>>> START createCompanyInvoiceForVendorSelfPay', [
                'ticket_log_id' => $ticketLog->id,
                'charge_to' => $ticketLog->charge_to,
                'cost_amount' => $ticketLog->cost_amount,
                'vendor_id' => $ticketLog->vendor_id,
            ]);
            
            // Get vendor_id from ticketLog model (already saved)
            $vendorId = $ticketLog->vendor_id;
            if (!$vendorId) {
                Log::warning('>>> FAILED: vendor_id missing', [
                    'ticket_log_id' => $ticketLog->id,
                ]);
                return;
            }

            $ticket = $ticketLog->ticket()->with('unit.property')->first();
            
            if (!$ticket) {
                Log::warning('>>> FAILED: ticket not found', [
                    'ticket_log_id' => $ticketLog->id,
                    'ticket_id' => $ticketLog->ticket_id,
                ]);
                return;
            }

            $invoice = new CompanyInvoice();
            $invoice->organization_id = $ticket->organization_id ?? (Auth::user()->organization_id ?? 1);
            $invoice->vendor_id = $vendorId;
            $invoice->user_id = null;
            $invoice->invoice_type = 'ticket_cost';
            $invoice->issue_date = now()->toDateString();
            $invoice->due_date = now()->addDays(7)->toDateString();
            $invoice->status = CompanyInvoice::STATUS_PENDING;
            $invoice->subtotal = $ticketLog->cost_amount;
            $invoice->tax_amount = 0;
            $invoice->discount_amount = 0;
            $invoice->total_amount = $ticketLog->cost_amount;
            $invoice->currency = 'VND';
            $invoice->description = $ticketLog->cost_note ?: ($ticketLog->detail ?: $ticketLog->action);
            $invoice->note = 'Tự chi trả từ ticket #' . $ticketLog->ticket_id . ' (log #' . $ticketLog->id . ')';
            // attachment_url removed - use document attachments instead
            $invoice->created_by = Auth::id();
            $invoice->ticket_log_id = $ticketLog->id;
            $invoice->ticket_id = $ticketLog->ticket_id;
            $invoice->save();

            CompanyInvoiceItem::create([
                'company_invoice_id' => $invoice->id,
                'item_type' => 'ticket_cost',
                'description' => $ticketLog->cost_note ?: ('Ticket #' . $ticketLog->ticket_id . ' - ' . ($ticketLog->action ?? 'Chi phí')),
                'quantity' => 1,
                'unit_price' => $ticketLog->cost_amount,
                'amount' => $ticketLog->cost_amount,
                'meta_json' => [
                    'ticket_id' => $ticketLog->ticket_id,
                    'ticket_log_id' => $ticketLog->id,
                    'unit_id' => $ticket->unit_id ?? null,
                ],
            ]);

            Log::info('>>> SUCCESS: Created company invoice for vendor self-pay', [
                'ticket_log_id' => $ticketLog->id,
                'company_invoice_id' => $invoice->id,
                'vendor_id' => $vendorId,
                'total_amount' => $invoice->total_amount,
            ]);
        } catch (\Exception $e) {
            Log::error('>>> EXCEPTION in createCompanyInvoiceForVendorSelfPay: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'vendor_id' => $ticketLog->vendor_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw để dễ debug
        }
    }

    /**
     * Handle the TicketLog "updated" event.
     */
    public function updated(TicketLog $ticketLog)
    {
        // Validate business rules first
        $this->businessRulesService->validate($ticketLog, 'updating');
        
        try {
            // Chỉ xử lý khi có thay đổi về chi phí hoặc hướng hạch toán
            if ($ticketLog->isDirty(['cost_amount', 'charge_to', 'linked_invoice_id'])) {
                Log::info('TicketLogObserver::updated triggered', [
                    'ticket_log_id' => $ticketLog->id,
                    'ticket_id' => $ticketLog->ticket_id,
                    'changes' => $ticketLog->getDirty(),
                    'original' => $ticketLog->getOriginal()
                ]);

                $originalData = $ticketLog->getOriginal();
                $this->ticketInvoiceService->processTicketLogUpdate($ticketLog, $originalData);
            }

            // Log audit trail for all changes
            $this->auditLogService->logUpdated($ticketLog);

        } catch (\Exception $e) {
            Log::error('Error in TicketLogObserver::updated: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the TicketLog "deleted" event.
     */
    public function deleted(TicketLog $ticketLog)
    {
        // Validate business rules first (soft delete)
        if (!$ticketLog->isForceDeleting()) {
            $this->businessRulesService->validate($ticketLog, 'deleting');
        }
        
        try {
            Log::info('TicketLogObserver::deleted triggered', [
                'ticket_log_id' => $ticketLog->id,
                'ticket_id' => $ticketLog->ticket_id,
                'cost_amount' => $ticketLog->cost_amount,
                'charge_to' => $ticketLog->charge_to
            ]);

            // Xử lý xóa invoice items liên quan
            $this->ticketInvoiceService->processTicketLogDelete($ticketLog);

            // Log audit trail
            $this->auditLogService->logDeleted($ticketLog);

        } catch (\Exception $e) {
            Log::error('Error in TicketLogObserver::deleted: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}
