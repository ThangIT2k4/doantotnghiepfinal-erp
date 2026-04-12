<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'entity_type',
        'email_enabled',
        'in_app_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default preferences for a user
     */
    public static function getDefaults(): array
    {
        return [
            'lease' => ['email_enabled' => true, 'in_app_enabled' => true],
            'invoice' => ['email_enabled' => true, 'in_app_enabled' => true],
            'payment' => ['email_enabled' => true, 'in_app_enabled' => true],
            'ticket' => ['email_enabled' => true, 'in_app_enabled' => true],
            'ticketlog' => ['email_enabled' => true, 'in_app_enabled' => true],
            'depositrefund' => ['email_enabled' => true, 'in_app_enabled' => true],
            'review' => ['email_enabled' => true, 'in_app_enabled' => true],
            'reviewreply' => ['email_enabled' => true, 'in_app_enabled' => true],
        ];
    }
}
