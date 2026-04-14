<?php

namespace App\Events;

use App\Models\Lease;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaseUpdated
{
    use Dispatchable, SerializesModels;

    public $lease;
    public $changes; // Array of changed fields

    /**
     * Create a new event instance.
     */
    public function __construct(Lease $lease, array $changes = [])
    {
        $this->lease = $lease;
        $this->changes = $changes;
    }
}

