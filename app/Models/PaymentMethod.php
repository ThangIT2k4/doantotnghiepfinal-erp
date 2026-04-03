<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    public $timestamps = false;

    protected $fillable = [
        'key_code',
        'name',
    ];

    /**
     * Get the payments for the payment method.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'method_id');
    }
}

