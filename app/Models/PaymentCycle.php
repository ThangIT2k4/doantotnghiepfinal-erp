<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCycle extends Model
{
    use SoftDeletes;

    protected $table = 'payment_cycles';

    protected $fillable = [
        'organization_id',
        'cycle_type',
        'billing_day',
        'custom_months',
        'notes',
        'name',
        'is_default',
        'payment_due_hours',
        'invoice_timing',
        'invoice_payment_days',
    ];

    protected $casts = [
        'billing_day' => 'integer',
        'custom_months' => 'integer',
        'is_default' => 'boolean',
        'payment_due_hours' => 'integer',
        'invoice_payment_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate name if not provided
        static::saving(function ($model) {
            if (empty($model->name)) {
                $model->name = $model->generateName();
            }

            // Validate custom_months
            if ($model->cycle_type === 'custom' && $model->custom_months !== null) {
                if ($model->custom_months < 1 || $model->custom_months > 60) {
                    throw new \InvalidArgumentException('Số tháng tùy chỉnh phải từ 1 đến 60.');
                }
            }

            // Validate billing_day
            if ($model->billing_day !== null) {
                if ($model->billing_day < 1 || $model->billing_day > 28) {
                    throw new \InvalidArgumentException('Ngày tạo hóa đơn phải từ 1 đến 28.');
                }
            }

            // Ensure only one default per organization
            if ($model->is_default && $model->organization_id) {
                static::where('organization_id', $model->organization_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });

        // After creating a payment cycle, ensure there's always a default
        static::created(function ($model) {
            if ($model->organization_id) {
                // Check if there's any default for this organization
                $hasDefault = static::where('organization_id', $model->organization_id)
                    ->where('is_default', true)
                    ->exists();
                
                // If no default exists, set this newly created one as default
                if (!$hasDefault) {
                    $model->is_default = true;
                    $model->saveQuietly(); // Save without triggering events again
                }
            }
        });
    }

    /**
     * Generate a descriptive name for the payment cycle
     */
    public function generateName(): string
    {
        $cycleNames = [
            'monthly' => 'Hàng tháng',
            'quarterly' => 'Hàng quý',
            'yearly' => 'Hàng năm',
            'custom' => 'Tùy chỉnh',
        ];

        $cycleName = $cycleNames[$this->cycle_type] ?? $this->cycle_type;

        if ($this->cycle_type === 'custom' && $this->custom_months) {
            $cycleName = "{$this->custom_months} tháng";
        }

        return $cycleName;
    }

    /**
     * Get the organization that owns this payment cycle
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all organizations using this payment cycle
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'payment_cycle_id');
    }

    /**
     * Get all properties using this payment cycle
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'payment_cycle_id');
    }

    /**
     * Get all leases using this payment cycle
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'payment_cycle_id');
    }

    /**
     * Get display name for cycle type
     */
    public function getCycleTypeNameAttribute(): string
    {
        return match($this->cycle_type) {
            'monthly' => 'Hàng tháng',
            'quarterly' => 'Hàng quý',
            'yearly' => 'Hàng năm',
            'custom' => $this->custom_months ? "{$this->custom_months} tháng" : 'Tùy chỉnh',
            default => $this->cycle_type,
        };
    }

    /**
     * Get cycle months based on cycle type
     */
    public function getCycleMonthsAttribute(): int
    {
        return match($this->cycle_type) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            'custom' => (int)($this->custom_months ?? 1),
            default => 1,
        };
    }

    /**
     * Scope: Get system-wide payment cycles (not organization-specific)
     */
    public function scopeSystemWide($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Scope: Get organization-specific payment cycles
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Get default payment cycles
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

