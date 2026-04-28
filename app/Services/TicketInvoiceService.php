<?php

namespace App\Services;

use App\Models\TicketLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Ticket;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TicketInvoiceService
{
    /**
     * Xử lý tự động tạo hóa đơn cho ticket_log
     */
    public function processTicketLogInvoice(TicketLog $ticketLog)
    {
        try {
            // Chỉ xử lý khi có chi phí và hướng hạch toán phù hợp
            if ($ticketLog->cost_amount <= 0) {
                return false;
            }

            switch ($ticketLog->charge_to) {
                case 'tenant_invoice':
                    return $this->handleTenantInvoice($ticketLog);
                case 'landlord':
                    return $this->handleLandlordInvoice($ticketLog);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Error processing ticket log invoice: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'ticket_id' => $ticketLog->ticket_id,
                'charge_to' => $ticketLog->charge_to,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Xử lý trường hợp charge_to = tenant_invoice
     */
    private function handleTenantInvoice(TicketLog $ticketLog)
    {
        try {
            DB::beginTransaction();

            $ticket = $ticketLog->ticket;
            $lease = $this->getTicketLease($ticket);
            
            if (!$lease) {
                Log::warning('No lease found for ticket', [
                    'ticket_id' => $ticket->id,
                    'ticket_log_id' => $ticketLog->id
                ]);
                DB::rollBack();
                return false;
            }

            // Tìm hoặc tạo hóa đơn
            $invoice = $this->findOrCreateInvoice($ticketLog, $lease);
            
            if (!$invoice) {
                DB::rollBack();
                return false;
            }

            // Tạo invoice item cho chi phí ticket
            $this->createTicketInvoiceItem($invoice, $ticketLog);

            // Cập nhật tổng tiền hóa đơn
            $this->updateInvoiceTotals($invoice);

            // Cập nhật linked_invoice_id nếu chưa có
            if (!$ticketLog->linked_invoice_id) {
                $ticketLog->update(['linked_invoice_id' => $invoice->id]);
            }

            DB::commit();

            Log::info('Successfully created tenant invoice for ticket log', [
                'ticket_log_id' => $ticketLog->id,
                'invoice_id' => $invoice->id,
                'cost_amount' => $ticketLog->cost_amount
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling tenant invoice: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Xử lý trường hợp charge_to = landlord
     */
    private function handleLandlordInvoice(TicketLog $ticketLog)
    {
        try {
            DB::beginTransaction();

            $ticket = $ticketLog->ticket;
            $lease = $this->getTicketLease($ticket);
            
            if (!$lease) {
                Log::warning('No lease found for ticket', [
                    'ticket_id' => $ticket->id,
                    'ticket_log_id' => $ticketLog->id
                ]);
                DB::rollBack();
                return false;
            }

            // Tìm hoặc tạo hóa đơn cho landlord
            $invoice = $this->findOrCreateLandlordInvoice($ticketLog, $lease);
            
            if (!$invoice) {
                DB::rollBack();
                return false;
            }

            // Tạo invoice item cho chi phí ticket
            $this->createTicketInvoiceItem($invoice, $ticketLog, 'landlord');

            // Cập nhật tổng tiền hóa đơn
            $this->updateInvoiceTotals($invoice);

            // Cập nhật linked_invoice_id nếu chưa có
            if (!$ticketLog->linked_invoice_id) {
                $ticketLog->update(['linked_invoice_id' => $invoice->id]);
            }

            DB::commit();

            Log::info('Successfully created landlord invoice for ticket log', [
                'ticket_log_id' => $ticketLog->id,
                'invoice_id' => $invoice->id,
                'cost_amount' => $ticketLog->cost_amount
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling landlord invoice: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Lấy lease từ ticket
     */
    private function getTicketLease(Ticket $ticket)
    {
        // Ưu tiên lấy lease_id trực tiếp từ ticket
        if ($ticket->lease_id) {
            $lease = Lease::where('id', $ticket->lease_id)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();
            if ($lease) {
                return $lease;
            }
        }
        
        // Nếu không có lease_id, lấy từ unit_id
        if ($ticket->unit_id) {
            return Lease::where('unit_id', $ticket->unit_id)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();
        }
        
        return null;
    }

    /**
     * Tìm hoặc tạo hóa đơn cho tenant
     */
    private function findOrCreateInvoice(TicketLog $ticketLog, Lease $lease)
    {
        // Nếu đã có linked_invoice_id, sử dụng hóa đơn đó
        if ($ticketLog->linked_invoice_id) {
            $invoice = Invoice::where('id', $ticketLog->linked_invoice_id)
                ->where('lease_id', $lease->id)
                ->whereNull('deleted_at')
                ->first();
            if ($invoice) {
                return $invoice;
            }
        }

        // Tìm hóa đơn chưa issued (draft) của lease để thêm item vào
        $invoice = Invoice::where('lease_id', $lease->id)
            ->where('status', 'draft')
            ->where('invoice_type', Invoice::TYPE_OTHER)
            ->where('is_auto_created', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->first();

        // Nếu không có invoice draft, tạo mới
        if (!$invoice) {
            $invoice = $this->createNewInvoice($lease, 'tenant');
        }

        return $invoice;
    }

    /**
     * Tìm hoặc tạo hóa đơn cho landlord
     */
    private function findOrCreateLandlordInvoice(TicketLog $ticketLog, Lease $lease)
    {
        // Tạo hóa đơn mới cho mỗi ticket_log
        $invoice = $this->createNewInvoice($lease, 'landlord');

        return $invoice;
    }

    /**
     * Tạo hóa đơn mới
     */
    private function createNewInvoice(Lease $lease, string $type = 'tenant')
    {
        $invoiceNo = Invoice::generateInvoiceNumber($lease->organization_id);
        $issueDate = Carbon::now();
        $dueDate = $issueDate->copy()->addDays(30);

        $invoice = Invoice::create([
            'invoice_no' => $invoiceNo,
            'lease_id' => $lease->id,
            'invoice_type' => Invoice::TYPE_OTHER, // Sử dụng TYPE_OTHER cho ticket cost
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'status' => 'draft', // Tạo ở trạng thái draft, có thể issue sau
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'currency' => 'VND', // Set currency mặc định
            'is_auto_created' => true, // Đánh dấu là tự động tạo từ ticket
            'note' => $type === 'landlord' ? 'Hóa đơn chi phí sửa chữa - Chủ trọ (tự động tạo từ ticket)' : 'Hóa đơn chi phí sửa chữa - Khách thuê (tự động tạo từ ticket)',
            'created_by' => Auth::check() ? Auth::id() : null,
        ]);

        return $invoice;
    }

    /**
     * Tạo invoice item cho ticket
     */
    private function createTicketInvoiceItem(Invoice $invoice, TicketLog $ticketLog, string $type = 'tenant')
    {
        $ticket = $ticketLog->ticket;
        $description = $this->generateTicketItemDescription($ticketLog, $ticket, $type);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'ticket_cost',
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $ticketLog->cost_amount,
            'amount' => $ticketLog->cost_amount,
            'meta_json' => [
                'ticket_id' => $ticket->id,
                'ticket_log_id' => $ticketLog->id,
                'ticket_action' => $ticketLog->action,
                'cost_note' => $ticketLog->cost_note,
                'created_at' => $ticketLog->created_at,
            ]
        ]);
    }

    /**
     * Tạo mô tả cho invoice item
     */
    private function generateTicketItemDescription(TicketLog $ticketLog, Ticket $ticket, string $type = 'tenant')
    {
        $ticketInfo = "Ticket #{$ticket->id}";
        if ($ticket->unit) {
            $ticketInfo .= " - {$ticket->unit->property->name} - {$ticket->unit->code}";
        }
        
        $action = $ticketLog->action ?: 'Chi phí sửa chữa';
        $costNote = $ticketLog->cost_note ? " ({$ticketLog->cost_note})" : '';
        
        if ($type === 'landlord') {
            return "Chi phí sửa chữa - {$ticketInfo} - {$action}{$costNote}";
        }
        
        return "Chi phí sửa chữa - {$ticketInfo} - {$action}{$costNote}";
    }

    /**
     * Cập nhật tổng tiền hóa đơn
     */
    private function updateInvoiceTotals(Invoice $invoice)
    {
        $subtotal = $invoice->items()->sum('amount');
        $totalAmount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;

        $invoice->update([
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Xử lý cập nhật ticket_log
     */
    public function processTicketLogUpdate(TicketLog $ticketLog, array $originalData)
    {
        // Nếu chi phí hoặc hướng hạch toán thay đổi
        if (isset($originalData['cost_amount']) || isset($originalData['charge_to'])) {
            // Xóa invoice item cũ nếu có
            $this->removeOldInvoiceItems($ticketLog);
            
            // Xử lý lại với dữ liệu mới
            return $this->processTicketLogInvoice($ticketLog);
        }

        return true;
    }

    /**
     * Xóa invoice items cũ
     */
    private function removeOldInvoiceItems(TicketLog $ticketLog)
    {
        InvoiceItem::where('meta_json->ticket_log_id', $ticketLog->id)->delete();
    }

    /**
     * Xử lý xóa ticket_log
     */
    public function processTicketLogDelete(TicketLog $ticketLog)
    {
        try {
            // Xóa invoice items liên quan
            $this->removeOldInvoiceItems($ticketLog);
            
            // Cập nhật lại tổng tiền các hóa đơn bị ảnh hưởng
            $affectedInvoices = Invoice::whereHas('items', function($query) use ($ticketLog) {
                $query->where('meta_json->ticket_log_id', $ticketLog->id);
            })->get();

            foreach ($affectedInvoices as $invoice) {
                $this->updateInvoiceTotals($invoice);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error processing ticket log deletion: ' . $e->getMessage(), [
                'ticket_log_id' => $ticketLog->id,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
