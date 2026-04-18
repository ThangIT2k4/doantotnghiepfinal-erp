<?php

namespace App\Services\BusinessRules;

use App\Models\TicketLog;
use App\Models\Invoice;
use App\Models\CompanyInvoice;
use Illuminate\Validation\ValidationException;

class TicketLogRulesValidator
{
    public function validateCreating(TicketLog $ticketLog): void
    {
        $this->validateAmount($ticketLog);
        $this->validateTicketExists($ticketLog);
    }

    public function validateUpdating(TicketLog $ticketLog): void
    {
        $this->validateAmount($ticketLog);
    }

    public function validateDeleting(TicketLog $ticketLog): void
    {
        // Kiểm tra có invoice liên quan không
        if ($ticketLog->linked_invoice_id) {
            $hasInvoice = Invoice::where('id', $ticketLog->linked_invoice_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasInvoice) {
                throw ValidationException::withMessages([
                    'ticket_log' => 'Không thể xóa ticket log đã liên kết với hóa đơn'
                ]);
            }
        }

        // Kiểm tra có company_invoice không
        $hasCompanyInvoice = CompanyInvoice::where('ticket_log_id', $ticketLog->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasCompanyInvoice) {
            throw ValidationException::withMessages([
                'ticket_log' => 'Không thể xóa ticket log đã có hóa đơn công ty'
            ]);
        }
    }

    protected function validateAmount(TicketLog $ticketLog): void
    {
        if ($ticketLog->cost_amount && $ticketLog->cost_amount < 0) {
            throw ValidationException::withMessages([
                'cost_amount' => 'Chi phí không được âm'
            ]);
        }
    }

    protected function validateTicketExists(TicketLog $ticketLog): void
    {
        if (!$ticketLog->ticket_id) {
            return;
        }

        $ticket = \App\Models\Ticket::where('id', $ticketLog->ticket_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$ticket) {
            throw ValidationException::withMessages([
                'ticket_id' => 'Ticket không tồn tại hoặc đã bị xóa'
            ]);
        }
    }

    public function validateSaving(TicketLog $ticketLog): void
    {
        if ($ticketLog->exists) {
            $this->validateUpdating($ticketLog);
        } else {
            $this->validateCreating($ticketLog);
        }
    }
}

