<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Invoice $invoice, array $changes)
    {
        $this->invoice = $invoice;
        $this->changes = $changes;
    }
}
