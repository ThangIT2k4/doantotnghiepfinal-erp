<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OrganizationSubscription extends Model
{
    protected $table = 'organization_subscriptions';

    protected $fillable = [
        'organization_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'payment_cycle',
        'payment_gateway',
        'gateway_subscription_id',
        'gateway_customer_id',
        'auto_renew',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'auto_renew' => 'boolean',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the subscription.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the plan that the subscription belongs to.
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get the invoices for the subscription.
     */
    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'organization_subscription_id');
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include trial subscriptions.
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope a query to only include cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include valid subscriptions (trial or active).
     * Valid means: subscription is not expired (current_period_end is in future, or null).
     * Trial subscriptions sử dụng current_period_end để lưu thời hạn trial.
     */
    public function scopeValid($query)
    {
        $now = now();
        
        return $query->whereIn('status', ['trial', 'active'])
            ->where(function ($q) use ($now) {
                // For both trial and active subscriptions: check current_period_end
                $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', $now);
            });
    }

    /**
     * Check if subscription is on trial.
     * Trial subscriptions sử dụng current_period_end để lưu thời hạn trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' 
            && $this->current_period_end 
            && $this->current_period_end->isFuture();
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && (!$this->current_period_end || $this->current_period_end->isFuture());
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if subscription is valid (active or on trial).
     */
    public function isValid(): bool
    {
        return $this->isActive() || $this->isOnTrial();
    }

    /**
     * Check if organization can use a specific feature.
     * For boolean features: returns true if feature is enabled.
     * For limit features: returns true if feature exists (limit checking is done separately via isLimitExceeded).
     */
    public function canUseFeature(string $key): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Ensure plan relationship is loaded
        if (!$this->relationLoaded('plan')) {
            $this->load('plan');
        }

        if (!$this->plan) {
            return false;
        }

        $feature = $this->plan->getFeature($key);
        
        if (!$feature) {
            return false;
        }

        // For boolean features: return the enabled value
        if ($feature->isBoolean()) {
            return $feature->getValue();
        }

        // For limit features: return true if feature exists (limit > 0 or unlimited)
        // The actual limit checking should be done via isLimitExceeded()
        if ($feature->isLimit()) {
            $limit = $feature->getValue();
            // -1 means unlimited, 0 means not allowed, > 0 means limited
            return $limit === -1 || $limit > 0;
        }

        // For other feature types (json, etc.): feature exists means it can be used
        return true;
    }

    /**
     * Get remaining limit for a feature.
     */
    public function getRemainingLimit(string $key, int $currentUsage = 0)
    {
        if (!$this->isValid()) {
            return 0;
        }

        $feature = $this->plan->getFeature($key);
        
        if (!$feature || !$feature->isLimit()) {
            return 0;
        }

        $limit = $feature->getValue();
        
        // -1 means unlimited
        if ($limit === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get the limit value for a feature.
     */
    public function getLimit(string $key)
    {
        if (!$this->isValid()) {
            return 0;
        }

        $feature = $this->plan->getFeature($key);
        
        if (!$feature || !$feature->isLimit()) {
            return 0;
        }

        return $feature->getValue();
    }

    /**
     * Check if limit is exceeded.
     */
    public function isLimitExceeded(string $key, int $currentUsage): bool
    {
        $limit = $this->getLimit($key);
        
        // -1 means unlimited
        if ($limit === -1) {
            return false;
        }

        return $currentUsage >= $limit;
    }

    /**
     * Get days until expiry.
     * Sử dụng current_period_end cho cả trial và active subscriptions.
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->current_period_end) {
            return max(0, Carbon::now()->diffInDays($this->current_period_end, false));
        }

        return null;
    }

    /**
     * Get subscription status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'trial' => 'Dùng thử',
            'active' => 'Hoạt động',
            'expired' => 'Hết hạn',
            'cancelled' => 'Đã hủy',
            'suspended' => 'Tạm dừng',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get subscription status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            'trial' => 'info',
            'active' => 'success',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            'suspended' => 'warning',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}

