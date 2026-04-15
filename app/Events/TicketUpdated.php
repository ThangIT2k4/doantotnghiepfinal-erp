<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, array $changes = [])
    {
        $this->ticket = $ticket;
        $this->changes = $changes;
    }
}
