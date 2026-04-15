<?php

namespace App\Events;

use App\Models\Review;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $review;

    /**
     * Create a new event instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
    }
}
