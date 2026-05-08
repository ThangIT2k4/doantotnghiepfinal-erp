<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToOrganization
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToOrganization()
    {
        // Tự động scope queries theo organization (trừ admin và tenant)
        static::addGlobalScope('organization', function (Builder $builder) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            
            if (!$user) {
                return;
            }

            try {
                // Admin có quyền xem tất cả
                $isAdmin = $user->userRoles()
                    ->where('key_code', 'admin')
                    ->exists();
                if ($isAdmin) {
                    return;
                }

                // Tenant xem TẤT CẢ organizations mà họ thuộc về - KHÔNG filter
                $isTenant = $user->userRoles()
                    ->where('key_code', 'tenant')
                    ->exists();
                if ($isTenant) {
                    // Tenant có thể xem data từ TẤT CẢ organizations
                    // Chỉ filter theo organizations mà user thuộc về
                    $userOrgIds = $user->organizations()->pluck('organizations.id')->toArray();
                    if (!empty($userOrgIds)) {
                        $builder->whereIn($builder->getModel()->getTable() . '.organization_id', $userOrgIds);
                    }
                    return;
                }

                // Các role khác (manager, agent, staff): Lọc theo organization context
                $organizationId = $user->getCurrentOrganizationId();
                if ($organizationId) {
                    $builder->where($builder->getModel()->getTable() . '.organization_id', $organizationId);
                }
            } catch (\Exception $e) {
                // Nếu có lỗi với relationships, bỏ qua global scope
                \Illuminate\Support\Facades\Log::debug('BelongsToOrganization global scope error: ' . $e->getMessage());
            }
        });
    }

    /**
     * Relationship to organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to filter by organization
     */
    public function scopeForOrganization(Builder $query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Check if user can access this record
     */
    public function canAccess($user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        // Admin có quyền truy cập tất cả
        // Sử dụng whereHas để tránh lỗi pivot
        $isAdmin = $user->userRoles()
            ->where('key_code', 'admin')
            ->exists();
        if ($isAdmin) {
            return true;
        }

        // Kiểm tra cùng organization (sử dụng organization context)
        $userOrganizationId = $user->getCurrentOrganizationId();
        return $userOrganizationId && $this->organization_id == $userOrganizationId;
    }
}
