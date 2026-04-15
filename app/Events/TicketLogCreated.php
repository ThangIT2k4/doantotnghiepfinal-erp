<?php

namespace App\Events;

use App\Models\TicketLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketLogCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticketLog;

    /**
     * Create a new event instance.
     */
    public function __construct(TicketLog $ticketLog)
    {
        $this->ticketLog = $ticketLog;
    }
}
