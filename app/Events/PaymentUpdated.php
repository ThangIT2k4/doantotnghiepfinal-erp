<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, array $changes)
    {
        $this->payment = $payment;
        $this->changes = $changes;
    }
}
