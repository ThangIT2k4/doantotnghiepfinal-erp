<?php

namespace App\Events;

use App\Models\Viewing;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViewingUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $viewing;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Viewing $viewing, array $changes = [])
    {
        $this->viewing = $viewing;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('viewing-notifications'),
        ];
    }
}
