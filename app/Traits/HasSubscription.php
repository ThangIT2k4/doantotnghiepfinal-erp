<?php

namespace App\Traits;

use App\Models\OrganizationSubscription;
use Illuminate\Support\Facades\DB;

trait HasSubscription
{
    /**
     * Get the current subscription for the organization.
     */
    public function subscription()
    {
        return $this->hasOne(OrganizationSubscription::class, 'organization_id')->latest();
    }

    /**
     * Get the active subscription for the organization.
     * Only returns subscription that is valid (not expired).
     * 
     * Logic:
     * - Chỉ lấy subscription có status 'trial' hoặc 'active'
     * - Subscription có status 'suspended' KHÔNG được sử dụng (chưa thanh toán)
     * - Mỗi organization chỉ có 1 subscription active tại một thời điểm
     * - Khi subscription mới được thanh toán (suspended → active), các subscription cũ sẽ bị cancel
     */
    public function activeSubscription()
    {
        return $this->hasOne(OrganizationSubscription::class, 'organization_id')
            ->whereIn('status', ['trial', 'active']) // Chỉ trial/active, không bao gồm suspended
            ->valid() // Use scope to filter valid subscriptions
            ->latest();
    }

    /**
     * Get all subscriptions for the organization.
     */
    public function subscriptions()
    {
        return $this->hasMany(OrganizationSubscription::class, 'organization_id');
    }

    /**
     * Check if organization has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        $subscription = $this->activeSubscription;
        return $subscription && $subscription->isValid();
    }

    /**
     * Check if organization is on trial.
     */
    public function isOnTrial(): bool
    {
        $subscription = $this->activeSubscription;
        return $subscription && $subscription->isOnTrial();
    }

    /**
     * Check if organization can use a specific feature.
     */
    public function canUseFeature(string $key): bool
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return false;
        }

        return $subscription->canUseFeature($key);
    }

    /**
     * Check if a boolean feature is enabled.
     */
    public function hasFeatureEnabled(string $key): bool
    {
        return $this->canUseFeature($key);
    }

    /**
     * Get limit value for a feature.
     */
    public function getFeatureLimit(string $key)
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return 0;
        }

        return $subscription->getLimit($key);
    }

    /**
     * Get remaining limit for a feature.
     */
    public function getRemainingFeatureLimit(string $key, int $currentUsage = 0)
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return 0;
        }

        return $subscription->getRemainingLimit($key, $currentUsage);
    }

    /**
     * Check if limit is exceeded for a feature.
     */
    public function isFeatureLimitExceeded(string $key, int $currentUsage): bool
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return true;
        }

        return $subscription->isLimitExceeded($key, $currentUsage);
    }

    /**
     * Get usage statistics for the organization.
     */
    public function getUsageStats(): array
    {
        try {
            // Count properties
            $propertiesCount = DB::table('properties')
                ->where('organization_id', $this->id)
                ->whereNull('deleted_at')
                ->count();

            // Count units
            $unitsCount = DB::table('units')
                ->join('properties', 'units.property_id', '=', 'properties.id')
                ->where('properties.organization_id', $this->id)
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->count();

            // Count users
            $usersCount = DB::table('organization_users')
                ->where('organization_id', $this->id)
                ->where('status', 'active')
                ->count();

            // Count active leases
            $activeLeasesCount = DB::table('leases')
                ->join('units', 'leases.unit_id', '=', 'units.id')
                ->join('properties', 'units.property_id', '=', 'properties.id')
                ->where('properties.organization_id', $this->id)
                ->where('leases.status', 'active')
                ->whereNull('leases.deleted_at')
                ->whereNull('units.deleted_at')
                ->whereNull('properties.deleted_at')
                ->count();

            return [
                'properties' => $propertiesCount,
                'units' => $unitsCount,
                'users' => $usersCount,
                'active_leases' => $activeLeasesCount,
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting usage stats: ' . $e->getMessage());
            
            return [
                'properties' => 0,
                'units' => 0,
                'users' => 0,
                'active_leases' => 0,
            ];
        }
    }

    /**
     * Get subscription status with details.
     */
    public function getSubscriptionStatus(): array
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
                'status_label' => 'Chưa có gói',
                'plan_name' => null,
                'expires_in_days' => null,
            ];
        }

        return [
            'has_subscription' => true,
            'status' => $subscription->status,
            'status_label' => $subscription->getStatusLabel(),
            'plan_name' => $subscription->plan->name ?? null,
            'expires_in_days' => $subscription->daysUntilExpiry(),
            'is_trial' => $subscription->isOnTrial(),
            'is_active' => $subscription->isActive(),
        ];
    }

    /**
     * Can add property based on subscription limit.
     */
    public function canAddProperty(): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $stats = $this->getUsageStats();
        return !$this->isFeatureLimitExceeded('max_properties', $stats['properties']);
    }

    /**
     * Can add unit based on subscription limit.
     */
    public function canAddUnit(): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $stats = $this->getUsageStats();
        return !$this->isFeatureLimitExceeded('max_units', $stats['units']);
    }

    /**
     * Can add user based on subscription limit.
     */
    public function canAddUser(): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $stats = $this->getUsageStats();
        return !$this->isFeatureLimitExceeded('max_users', $stats['users']);
    }
}

