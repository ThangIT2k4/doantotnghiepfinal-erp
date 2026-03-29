<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, BelongsToOrganization;

    protected $table = 'leases';

    protected $fillable = [
        'organization_id',
        'unit_id',
        'tenant_id',
        'agent_id',
        'booking_id',
        'start_date',
        'end_date',
        'rent_amount',
        'deposit_amount',
        'payment_cycle_id',
        'lease_services_id',
        'status',
        'contract_no',
        'signed_at',
        'deleted_by',
        'termination_date',
        'termination_reason',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'termination_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Get the payment cycle for this lease
     */
    public function paymentCycle()
    {
        return $this->belongsTo(PaymentCycle::class, 'payment_cycle_id');
    }

    /**
     * Get payment cycle from lease or fallback to property or default payment cycle
     */
    public function getEffectivePaymentCycle()
    {
        if ($this->payment_cycle_id && $this->paymentCycle) {
            return $this->paymentCycle;
        }
        
        $property = $this->property;
        if ($property) {
            $propertyCycle = $property->getEffectivePaymentCycle();
            if ($propertyCycle) {
                return $propertyCycle;
            }
        }
        
        // Get default payment cycle for this organization
        if ($this->organization_id) {
            return PaymentCycle::where('organization_id', $this->organization_id)
                ->where('is_default', true)
                ->first();
        }
        
        return null;
    }


    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function property()
    {
        return $this->hasOneThrough(Property::class, Unit::class, 'id', 'id', 'unit_id', 'property_id');
    }

    /**
     * Get the booking deposit for this lease
     */
    public function bookingDeposit()
    {
        return $this->belongsTo(BookingDeposit::class, 'booking_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function residents()
    {
        return $this->hasMany(LeaseResident::class);
    }

    /**
     * Get the lease service set for this lease
     */
    public function leaseServiceSet()
    {
        return $this->belongsTo(LeaseServiceSet::class, 'lease_services_id');
    }

    /**
     * Get lease service set from lease or fallback to property or default lease service set
     */
    public function getEffectiveLeaseServiceSet()
    {
        if ($this->lease_services_id && $this->leaseServiceSet) {
            // Load items with service if not already loaded
            if (!$this->leaseServiceSet->relationLoaded('items')) {
                $this->leaseServiceSet->load('items.service');
            }
            return $this->leaseServiceSet;
        }
        
        $property = $this->property;
        if ($property) {
            $propertyServiceSet = $property->getEffectiveLeaseServiceSet();
            if ($propertyServiceSet) {
                // Load items with service if not already loaded
                if (!$propertyServiceSet->relationLoaded('items')) {
                    $propertyServiceSet->load('items.service');
                }
                return $propertyServiceSet;
            }
        }
        
        // Get default lease service set for this organization
        if ($this->organization_id) {
            $defaultSet = LeaseServiceSet::where('organization_id', $this->organization_id)
                ->where('is_default', true)
                ->with('items.service')
                ->first();
            return $defaultSet;
        }
        
        return null;
    }

    /**
     * Get lease services from lease or fallback to property or organization (backward compatibility)
     */
    public function getEffectiveLeaseServices()
    {
        return $this->getEffectiveLeaseServiceSet();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bookingDeposits()
    {
        return $this->hasMany(BookingDeposit::class);
    }

    public function commissionEvents()
    {
        return $this->hasMany(CommissionEvent::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function depositRefunds()
    {
        return $this->hasMany(DepositRefund::class);
    }

    public function ticketDeposits()
    {
        return $this->hasMany(Ticket::class, 'unit_id', 'unit_id')
            ->whereHas('logs', function($query) {
                $query->where('charge_to', 'tenant_deposit')
                      ->where('cost_amount', '>', 0);
            });
    }

    /**
     * Get documents attached to this lease (OLD WAY - backward compatibility)
     * Sử dụng polymorphic relationship cũ
     */
    /**
     * Get documents for this lease (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Get tenant information - either from User or Lead
     */
    public function getTenantInfo()
    {
        if ($this->tenant_id) {
            return [
                'type' => 'user',
                'id' => $this->tenant_id,
                'name' => $this->tenant->full_name ?? $this->tenant->name ?? 'N/A',
                'phone' => $this->tenant->phone ?? 'N/A',
                'email' => $this->tenant->email ?? 'N/A',
            ];
        }
        
        return null;
    }


    /**
     * Check if lease has tenant user account
     */
    public function hasTenantAccount()
    {
        return $this->tenant_id !== null;
    }

    /**
     * Check if a user has access to this lease (as tenant or resident)
     * 
     * @param int $userId
     * @return bool
     */
    public function isAccessibleByUser($userId)
    {
        // Check if user is the main tenant
        if ($this->tenant_id == $userId) {
            return true;
        }

        // Check if user is a resident in this lease
        return $this->residents()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Scope to filter leases accessible by a user (as tenant or resident)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleByUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            // User is the main tenant
            $q->where('tenant_id', $userId)
              // Or user is a resident
              ->orWhereHas('residents', function($residentQuery) use ($userId) {
                  $residentQuery->where('user_id', $userId);
              });
        });
    }

    /**
     * Get all lease IDs accessible by a user (as tenant or resident)
     * 
     * @param int $userId
     * @param int|null $organizationId Optional: filter by specific organization. If null, gets leases from ALL organizations user belongs to
     * @return \Illuminate\Support\Collection
     */
    public static function getAccessibleLeaseIds($userId, ?int $organizationId = null)
    {
        $query = static::where(function($q) use ($userId) {
            $q->where('tenant_id', $userId)
              ->orWhereHas('residents', function($subQ) use ($userId) {
                  $subQ->where('user_id', $userId);
              });
        });
        
        // Filter theo organization cụ thể nếu có
        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }
        // KHÔNG filter theo organization nếu null - lấy TẤT CẢ leases từ tất cả organizations
        
        return $query->pluck('id');
    }
}
