<?php

namespace App\Events;

use App\Models\CommissionEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommissionEventNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commissionEvent;
    public $eventType; // 'created', 'updated', 'paid', 'cancelled'

    /**
     * Create a new event instance.
     */
    public function __construct(CommissionEvent $commissionEvent, string $eventType = 'created')
    {
        $this->commissionEvent = $commissionEvent;
        $this->eventType = $eventType;
    }
}
