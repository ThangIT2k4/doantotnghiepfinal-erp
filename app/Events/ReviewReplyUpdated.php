<?php

namespace App\Events;

use App\Models\ReviewReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewReplyUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(ReviewReply $reply, array $changes)
    {
        $this->reply = $reply;
        $this->changes = $changes;
    }
}
