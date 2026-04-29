<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class OrganizationEmailSetting extends Model
{
    use SoftDeletes;

    protected $table = 'organization_email_settings';

    protected $fillable = [
        'organization_id',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_host',
        'mail_port',
        'mail_encryption',
    ];

    protected $casts = [
        'mail_port' => 'integer',
    ];

    /**
     * Get the organization that owns this email setting
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Encrypt mail_password when setting it
     */
    public function setMailPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['mail_password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['mail_password'] = null;
        }
    }

    /**
     * Decrypt mail_password when getting it
     */
    public function getMailPasswordAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails (e.g., old unencrypted data), return as is
            return $value;
        }
    }
}
