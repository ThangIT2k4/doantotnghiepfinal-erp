<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use Illuminate\Support\Facades\Log;

class TicketObserver
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
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($ticket, 'creating');
        
        try {
            Log::info('TicketObserver::created triggered', [
                'ticket_id' => $ticket->id,
                'ticket_title' => $ticket->title,
                'organization_id' => $ticket->organization_id,
                'unit_id' => $ticket->unit_id,
                'lease_id' => $ticket->lease_id
            ]);

            // Dispatch TicketCreated event
            event(new TicketCreated($ticket));

            // Log audit trail
            $this->auditLogService->logCreated($ticket);

        } catch (\Exception $e) {
            Log::error('Error in TicketObserver::created: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($ticket, 'updating');
        
        try {
            // Only trigger notification if important fields changed
            if ($ticket->isDirty(['status', 'priority_id', 'assigned_to', 'title', 'description'])) {
                Log::info('TicketObserver::updated triggered', [
                    'ticket_id' => $ticket->id,
                    'ticket_title' => $ticket->title,
                    'changes' => $ticket->getDirty(),
                    'original' => $ticket->getOriginal()
                ]);

                // Dispatch TicketUpdated event
                event(new TicketUpdated($ticket, $ticket->getDirty()));

                // Log audit trail for all changes (not just important ones)
                $this->auditLogService->logUpdated($ticket);
            } else {
                // Log audit trail even for non-important changes
                $this->auditLogService->logUpdated($ticket);
            }

        } catch (\Exception $e) {
            Log::error('Error in TicketObserver::updated: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     * This is called for both soft delete and force delete.
     */
    public function deleted(Ticket $ticket): void
    {
        // Validate business rules first (soft delete only)
        if (!$ticket->isForceDeleting()) {
            $this->businessRulesService->validate($ticket, 'deleting');
        }
        
        try {
            $isForceDelete = $ticket->isForceDeleting();
            
            Log::info('TicketObserver::deleted triggered', [
                'ticket_id' => $ticket->id,
                'ticket_title' => $ticket->title,
                'is_force_deleting' => $isForceDelete,
                'delete_type' => $isForceDelete ? 'force_delete' : 'soft_delete'
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($ticket);

            // You can add a TicketDeleted event here if needed
            // event(new TicketDeleted($ticket));

        } catch (\Exception $e) {
            Log::error('Error in TicketObserver::deleted: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Ticket "force deleted" event.
     * This is called AFTER force delete (model is already deleted from database).
     */
    public function forceDeleted(Ticket $ticket): void
    {
        Log::info('Ticket force deleted (permanent delete completed)', [
            'ticket_id' => $ticket->id,
            'ticket_title' => $ticket->title ?? null,
            'is_force_deleting' => true
        ]);

        // Note: Audit log should already be created in deleted() method
        // This method is for any additional cleanup or logging needed after force delete
    }
}
