<?php

namespace App\Traits;

use App\Services\CapabilityService;
use Illuminate\Support\Facades\Auth;

trait FiltersByOwnership
{
    /**
     * Check if user can view all records or only own records
     * 
     * @param string $capabilityBase Base capability like 'crm.lead', 'contract.lease'
     * @return bool True if can view all, false if only own
     */
    protected function canViewAll(string $capabilityBase): bool
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId || !$user) {
            return false;
        }
        
        // Check for view_all capability
        $viewAllCapability = $capabilityBase . '.view_all';
        if (CapabilityService::userHas($user->id, $organizationId, $viewAllCapability)) {
            return true;
        }
        
        // If has view_own, return false (only own)
        $viewOwnCapability = $capabilityBase . '.view_own';
        if (CapabilityService::userHas($user->id, $organizationId, $viewOwnCapability)) {
            return false;
        }
        
        // Default: check base view capability (backward compatibility)
        $baseCapability = $capabilityBase . '.view';
        if (CapabilityService::userHas($user->id, $organizationId, $baseCapability)) {
            // If manager (has wildcard), return true
            // Otherwise, return false (only own)
            $roleKey = session('auth_role_key');
            if (!$roleKey) {
                // Try to get from database
                $orgUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->with('role')
                    ->first();
                $roleKey = $orgUser?->role?->key_code;
            }
            return $roleKey === 'manager';
        }
        
        return false;
    }
    
    /**
     * Apply ownership filter to query based on capability
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $capabilityBase Base capability
     * @param array $filterConfig Configuration for filtering
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOwnershipFilter($query, string $capabilityBase, array $filterConfig = [])
    {
        $canViewAll = $this->canViewAll($capabilityBase);
        
        if ($canViewAll) {
            // Can view all, no filter needed
            return $query;
        }
        
        // Only view own - apply filter based on config
        $user = Auth::user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No user, no results
        }
        
        // Use custom filter if provided
        if (isset($filterConfig['filter']) && is_callable($filterConfig['filter'])) {
            $filterConfig['filter']($query, $user);
            return $query;
        }
        
        // Default filter methods based on entity type
        $defaultFilters = [
            'crm.lead' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id'); // Lấy danh sách property IDs được assign cho user → Dùng để filter viewings
                if ($assignedPropertyIds->isNotEmpty()) { // Nếu user có assigned properties
                    $q->whereHas('viewings', function($subQ) use ($assignedPropertyIds) { // Filter: lead phải có viewings → Chỉ lấy leads có viewings của assigned properties
                        $subQ->whereIn('property_id', $assignedPropertyIds); // Filter viewings theo assigned properties → Agent chỉ xem leads có viewings của properties được assign
                    });
                } else { // Nếu user không có assigned properties
                    $q->whereRaw('1 = 0'); // No results → Không trả về kết quả nào (Agent không có assigned properties thì không xem được leads)
                }
            },
            'crm.appointment' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->whereIn('property_id', $assignedPropertyIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'asset.property' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->whereIn('properties.id', $assignedPropertyIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'asset.unit' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->whereIn('units.property_id', $assignedPropertyIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'contract.lease' => function($q, $user) {
                $q->where('leases.agent_id', $user->id);
            },
            'contract.booking_deposit' => function($q, $user) {
                $q->where('booking_deposits.agent_id', $user->id);
            },
            'contract.deposit_refund' => function($q, $user) {
                $q->whereHas('lease', function($subQ) use ($user) {
                    $subQ->where('agent_id', $user->id);
                });
            },
            'billing.invoice' => function($q, $user) {
                $q->where(function($subQ) use ($user) {
                    $subQ->whereHas('lease', function($leaseQ) use ($user) {
                        $leaseQ->where('agent_id', $user->id);
                    })->orWhereHas('bookingDeposit', function($bookingQ) use ($user) {
                        $bookingQ->where('agent_id', $user->id);
                    });
                });
            },
            'billing.payment' => function($q, $user) {
                $q->where(function($subQ) use ($user) {
                    $subQ->whereHas('invoice', function($invoiceQ) use ($user) {
                        $invoiceQ->where(function($invSubQ) use ($user) {
                            $invSubQ->whereHas('lease', function($leaseQ) use ($user) {
                                $leaseQ->where('agent_id', $user->id);
                            })->orWhereHas('bookingDeposit', function($bookingQ) use ($user) {
                                $bookingQ->where('agent_id', $user->id);
                            });
                        });
                    });
                });
            },
            'work.ticket' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->where(function($subQ) use ($assignedPropertyIds, $user) {
                        $subQ->whereIn('tickets.property_id', $assignedPropertyIds)
                            ->orWhereHas('unit', function($unitQ) use ($assignedPropertyIds) {
                                $unitQ->whereIn('property_id', $assignedPropertyIds);
                            })
                            ->orWhere('tickets.created_by', $user->id)
                            ->orWhere('tickets.assigned_to', $user->id);
                    });
                } else {
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('tickets.created_by', $user->id)
                            ->orWhere('tickets.assigned_to', $user->id);
                    });
                }
            },
            'party.user' => function($q, $user) {
                // Agent chỉ xem Users có role "tenant" (người thuê) trong organization của mình
                $organizationId = $this->getCurrentOrganizationId();
                if ($organizationId) {
                    // Get tenant role ID
                    $tenantRoleId = \App\Models\Role::where('key_code', 'tenant')->value('id');
                    
                    if ($tenantRoleId) {
                        $q->whereHas('organizationUsers', function($subQ) use ($organizationId, $tenantRoleId) {
                            $subQ->where('organization_id', $organizationId)
                                ->where('role_id', $tenantRoleId)
                                ->where('status', 'active');
                        });
                    } else {
                        // If tenant role doesn't exist, return no results
                        $q->whereRaw('1 = 0');
                    }
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'finance.commission' => function($q, $user) {
                $q->where('commission_events.agent_id', $user->id);
            },
            'finance.payroll' => function($q, $user) {
                $q->where('payroll_payslips.user_id', $user->id);
            },
            'finance.salary_advance' => function($q, $user) {
                $q->where('salary_advances.user_id', $user->id);
            },
            'party.salary_contract' => function($q, $user) {
                $q->where('salary_contracts.user_id', $user->id);
            },
            'asset.meter' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->whereHas('unit.property', function($subQ) use ($assignedPropertyIds) {
                        $subQ->whereIn('properties.id', $assignedPropertyIds);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'asset.meter_reading' => function($q, $user) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('properties.id');
                if ($assignedPropertyIds->isNotEmpty()) {
                    $q->whereHas('meter.unit.property', function($subQ) use ($assignedPropertyIds) {
                        $subQ->whereIn('properties.id', $assignedPropertyIds);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            },
            'crm.review' => function($q, $user) {
                $q->whereHas('lease', function($subQ) use ($user) {
                    $subQ->where('agent_id', $user->id);
                });
            },
        ];
        
        // Apply default filter if exists
        if (isset($defaultFilters[$capabilityBase])) {
            $defaultFilters[$capabilityBase]($query, $user);
        }
        
        return $query;
    }
}

