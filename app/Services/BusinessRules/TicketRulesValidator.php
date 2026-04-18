<?php

namespace App\Services\BusinessRules;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\CompanyInvoice;
use Illuminate\Validation\ValidationException;

class TicketRulesValidator
{
    public function validateCreating(Ticket $ticket): void
    {
        $this->validateOrganization($ticket);
    }

    public function validateUpdating(Ticket $ticket): void
    {
        $this->validateOrganization($ticket);
    }

    public function validateDeleting(Ticket $ticket): void
    {
        // Kiểm tra có ticket_logs không
        $hasTicketLogs = TicketLog::where('ticket_id', $ticket->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasTicketLogs) {
            throw ValidationException::withMessages([
                'ticket' => 'Không thể xóa ticket đang có ticket logs'
            ]);
        }

        // Kiểm tra có company_invoices không
        $hasInvoices = CompanyInvoice::where('ticket_id', $ticket->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasInvoices) {
            throw ValidationException::withMessages([
                'ticket' => 'Không thể xóa ticket đã có hóa đơn công ty'
            ]);
        }
    }

    public function canSoftDelete(Ticket $ticket): bool
    {
        try {
            $this->validateDeleting($ticket);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    protected function validateOrganization(Ticket $ticket): void
    {
        if (!$ticket->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => 'Ticket phải thuộc một tổ chức'
            ]);
        }
    }

    public function validateSaving(Ticket $ticket): void
    {
        if ($ticket->exists) {
            $this->validateUpdating($ticket);
        } else {
            $this->validateCreating($ticket);
        }
    }
}

