<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'code',
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'trial_days',
        'is_active',
        'is_custom',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the features for the plan.
     */
    public function features()
    {
        return $this->hasMany(PlanFeature::class, 'plan_id');
    }

    /**
     * Get the subscriptions for the plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(OrganizationSubscription::class, 'plan_id');
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include custom plans.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    /**
     * Scope a query to only include standard plans.
     */
    public function scopeStandard($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Get a specific feature by key.
     */
    public function getFeature(string $key)
    {
        return $this->features()->where('feature_key', $key)->first();
    }

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $key): bool
    {
        return $this->features()->where('feature_key', $key)->exists();
    }

    /**
     * Get feature value by key.
     */
    public function getFeatureValue(string $key, $default = null)
    {
        $feature = $this->getFeature($key);
        return $feature ? $feature->getValue() : $default;
    }

    /**
     * Check if this is a custom plan.
     */
    public function isCustom(): bool
    {
        return $this->is_custom;
    }

    /**
     * Check if plan is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the price based on payment cycle.
     */
    public function getPrice(string $cycle = 'monthly')
    {
        return $cycle === 'yearly' ? $this->price_yearly : $this->price_monthly;
    }

    /**
     * Get organizations count using this plan.
     */
    public function getActiveSubscriptionsCount()
    {
        return $this->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->count();
    }

    /**
     * Get the route key for the model.
     * This allows Laravel to use 'id' for route model binding
     * even when route parameter is named differently.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }
}

