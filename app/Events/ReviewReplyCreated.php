<?php

namespace App\Events;

use App\Models\ReviewReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewReplyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply;

    /**
     * Create a new event instance.
     */
    public function __construct(ReviewReply $reply)
    {
        $this->reply = $reply;
    }
}
