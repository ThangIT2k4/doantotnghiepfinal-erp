<?php

namespace App\Events;

use App\Models\Review;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $review;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Review $review, array $changes)
    {
        $this->review = $review;
        $this->changes = $changes;
    }
}
