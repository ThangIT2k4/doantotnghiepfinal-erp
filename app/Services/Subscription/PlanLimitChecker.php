<?php

namespace App\Services\Subscription;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class PlanLimitChecker
{
    /**
     * Check if organization can perform an action based on limit.
     */
    public function checkLimit(Organization $organization, string $limitKey, int $currentValue): bool
    {
        if (!$organization->hasActiveSubscription()) {
            return false;
        }

        $subscription = $organization->activeSubscription;
        $limit = $subscription->getLimit($limitKey);

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentValue < $limit;
    }

    /**
     * Check if organization can add a property.
     */
    public function canAddProperty(Organization $organization): bool
    {
        $currentCount = $this->getPropertiesCount($organization);
        return $this->checkLimit($organization, 'max_properties', $currentCount);
    }

    /**
     * Check if organization can add a unit.
     */
    public function canAddUnit(Organization $organization): bool
    {
        $currentCount = $this->getUnitsCount($organization);
        return $this->checkLimit($organization, 'max_units', $currentCount);
    }

    /**
     * Check if organization can add a user.
     */
    public function canAddUser(Organization $organization): bool
    {
        $currentCount = $this->getUsersCount($organization);
        return $this->checkLimit($organization, 'max_users', $currentCount);
    }

    /**
     * Check if organization can add a lease.
     */
    public function canAddLease(Organization $organization): bool
    {
        $currentCount = $this->getLeasesCount($organization);
        return $this->checkLimit($organization, 'max_leases', $currentCount);
    }

    /**
     * Get the limit value for a specific feature.
     */
    public function getLimit(Organization $organization, string $limitKey)
    {
        if (!$organization->hasActiveSubscription()) {
            return 0;
        }

        return $organization->activeSubscription->getLimit($limitKey);
    }

    /**
     * Get remaining limit for a specific feature.
     */
    public function getRemainingLimit(Organization $organization, string $limitKey): int
    {
        if (!$organization->hasActiveSubscription()) {
            return 0;
        }

        $currentValue = $this->getCurrentValue($organization, $limitKey);
        return $organization->activeSubscription->getRemainingLimit($limitKey, $currentValue);
    }

    /**
     * Get current usage value for a limit key.
     */
    protected function getCurrentValue(Organization $organization, string $limitKey): int
    {
        switch ($limitKey) {
            case 'max_properties':
                return $this->getPropertiesCount($organization);
            case 'max_units':
                return $this->getUnitsCount($organization);
            case 'max_users':
                return $this->getUsersCount($organization);
            case 'max_leases':
                return $this->getLeasesCount($organization);
            default:
                return 0;
        }
    }

    /**
     * Get properties count for organization.
     */
    public function getPropertiesCount(Organization $organization): int
    {
        return DB::table('properties')
            ->where('organization_id', $organization->id)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get units count for organization.
     */
    public function getUnitsCount(Organization $organization): int
    {
        return DB::table('units')
            ->join('properties', 'units.property_id', '=', 'properties.id')
            ->where('properties.organization_id', $organization->id)
            ->whereNull('units.deleted_at')
            ->whereNull('properties.deleted_at')
            ->count();
    }

    /**
     * Get users count for organization.
     */
    public function getUsersCount(Organization $organization): int
    {
        return DB::table('organization_users')
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get active leases count for organization.
     */
    public function getLeasesCount(Organization $organization): int
    {
        return DB::table('leases')
            ->join('units', 'leases.unit_id', '=', 'units.id')
            ->join('properties', 'units.property_id', '=', 'properties.id')
            ->where('properties.organization_id', $organization->id)
            ->where('leases.status', 'active')
            ->whereNull('leases.deleted_at')
            ->whereNull('units.deleted_at')
            ->whereNull('properties.deleted_at')
            ->count();
    }


    /**
     * Get all usage statistics with limits.
     */
    public function getUsageWithLimits(Organization $organization): array
    {
        if (!$organization->hasActiveSubscription()) {
            return [
                'properties' => ['current' => 0, 'limit' => 0, 'remaining' => 0],
                'units' => ['current' => 0, 'limit' => 0, 'remaining' => 0],
                'users' => ['current' => 0, 'limit' => 0, 'remaining' => 0],
                'leases' => ['current' => 0, 'limit' => 0, 'remaining' => 0],
            ];
        }

        $propertiesCount = $this->getPropertiesCount($organization);
        $unitsCount = $this->getUnitsCount($organization);
        $usersCount = $this->getUsersCount($organization);
        $leasesCount = $this->getLeasesCount($organization);

        $subscription = $organization->activeSubscription;

        return [
            'properties' => [
                'current' => $propertiesCount,
                'limit' => $subscription->getLimit('max_properties'),
                'remaining' => $subscription->getRemainingLimit('max_properties', $propertiesCount),
                'percentage' => $this->calculatePercentage($propertiesCount, $subscription->getLimit('max_properties')),
            ],
            'units' => [
                'current' => $unitsCount,
                'limit' => $subscription->getLimit('max_units'),
                'remaining' => $subscription->getRemainingLimit('max_units', $unitsCount),
                'percentage' => $this->calculatePercentage($unitsCount, $subscription->getLimit('max_units')),
            ],
            'users' => [
                'current' => $usersCount,
                'limit' => $subscription->getLimit('max_users'),
                'remaining' => $subscription->getRemainingLimit('max_users', $usersCount),
                'percentage' => $this->calculatePercentage($usersCount, $subscription->getLimit('max_users')),
            ],
            'leases' => [
                'current' => $leasesCount,
                'limit' => $subscription->getLimit('max_leases'),
                'remaining' => $subscription->getRemainingLimit('max_leases', $leasesCount),
                'percentage' => $this->calculatePercentage($leasesCount, $subscription->getLimit('max_leases')),
            ],
        ];
    }

    /**
     * Calculate usage percentage.
     */
    protected function calculatePercentage(int $current, int $limit): float
    {
        if ($limit <= 0 || $limit === -1) {
            return 0;
        }

        return min(100, round(($current / $limit) * 100, 2));
    }

    /**
     * Check if organization can use a boolean feature.
     */
    public function canUseFeature(Organization $organization, string $featureKey): bool
    {
        if (!$organization->hasActiveSubscription()) {
            return false;
        }

        return $organization->canUseFeature($featureKey);
    }
}

