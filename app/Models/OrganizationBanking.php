<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class OrganizationBanking extends Model
{
    use SoftDeletes;

    protected $table = 'organization_banking';

    protected $fillable = [
        'organization_id',
        'sepay_bank_id',
        'account_number',
        'account_name',
        'branch',
        'is_active',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the organization that owns the banking account.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the SepayBank for this banking account.
     */
    public function sepayBank()
    {
        return $this->belongsTo(SepayBank::class, 'sepay_bank_id');
    }

    /**
     * Scope to get active banking accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default banking account
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get default banking account for an organization
     */
    public static function getDefaultForOrganization($organizationId)
    {
        return static::with('sepayBank')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get active banking accounts for an organization
     */
    public static function getActiveForOrganization($organizationId)
    {
        return static::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Set as default banking account
     * This will unset other default accounts for the same organization
     */
    public function setAsDefault()
    {
        DB::transaction(function () {
            // Unset other default accounts
            static::where('organization_id', $this->organization_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            // Set this as default
            $this->update(['is_default' => true]);
        });
    }

    /**
     * Get bank name from SepayBank (accessor)
     */
    public function getBankNameAttribute()
    {
        if ($this->sepayBank) {
            return $this->sepayBank->sepay_name ?? $this->sepayBank->short_name ?? $this->sepayBank->name;
        }
        return null;
    }

    /**
     * Get bank config array for SePay QR code
     */
    public function getBankConfigArray()
    {
        return [
            'bank_name' => $this->bank_name, // Uses accessor
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'branch' => $this->branch,
        ];
    }
}
