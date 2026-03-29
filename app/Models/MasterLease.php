<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\BelongsToOrganization;
use App\Models\CompanyInvoice;
use App\Helpers\SequenceGenerator;

class MasterLease extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'landlord_user_id',
        'property_id',
        'contract_no',
        'start_date',
        'end_date',
        'base_rent',
        'rent_currency',
        'deposit_amount',
        'billing_cycle',
        'billing_day',
        'due_in_days',
        'revenue_share_pct',
        'status',
        'note',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'base_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'revenue_share_pct' => 'decimal:2',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_user_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'property_id', 'property_id');
    }


    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function companyInvoices(): HasMany
    {
        return $this->hasMany(CompanyInvoice::class, 'master_lease_id');
    }

    // Scopes
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByLandlord($query, $landlordId)
    {
        return $query->where('landlord_user_id', $landlordId);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
                    ->where('status', 'active');
    }

    // Accessors & Mutators
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now();
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }
        
        $today = now()->startOfDay();
        $endDate = $this->end_date->startOfDay();
        
        if ($endDate->isPast()) {
            return 0; // Already expired
        }
        
        return $today->diffInDays($endDate);
    }

    public function getFormattedBaseRentAttribute(): string
    {
        return number_format($this->base_rent, 0, ',', '.') . ' ' . $this->rent_currency;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'badge bg-secondary',
            'active' => 'badge bg-success',
            'terminated' => 'badge bg-warning',
            'expired' => 'badge bg-danger',
            default => 'badge bg-secondary'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Nháp',
            'active' => 'Hoạt động',
            'terminated' => 'Chấm dứt',
            'expired' => 'Hết hạn',
            default => 'Không xác định'
        };
    }

    public function getBillingCycleLabelAttribute(): string
    {
        $months = $this->billing_cycle;
        
        if ($months == 1) {
            return 'Hàng tháng';
        } elseif ($months == 3) {
            return 'Hàng quý';
        } elseif ($months == 6) {
            return 'Nửa năm';
        } elseif ($months == 12) {
            return 'Hàng năm';
        } else {
            return "Mỗi {$months} tháng";
        }
    }

    // Methods
    /**
     * Generate master lease contract number with format: ML-{org_id}-{year}-{month}-{sequence}
     * 
     * @return string Contract number
     * @throws \Exception If organization ID is not set
     */
    public function generateContractNumber(): string
    {
        $organizationId = $this->organization_id;
        
        if (!$organizationId) {
            throw new \Exception('Organization ID is required to generate master lease contract number');
        }
        
        $year = now()->year;
        $month = now()->format('m');
        $sequenceKey = SequenceGenerator::buildKey('master_lease', $organizationId, $year, $month);
        
        $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month) {
            // Find max from existing master leases
            // Support both old format (ML2025010001) and new format (ML-1-2025-01-0001)
            $existingLeases = self::withTrashed()
                ->where('organization_id', $organizationId)
                ->where(function($query) use ($year, $month) {
                    $pattern = "ML{$year}{$month}";
                    $query->where('contract_no', 'like', "{$pattern}%")
                          ->orWhere('contract_no', 'like', "ML-%-{$year}-{$month}-%");
                })
                ->pluck('contract_no')
                ->toArray();
            
            $maxNumber = 0;
            foreach ($existingLeases as $contractNo) {
                // Parse new format: "ML-1-2025-01-0001" => 1
                // Parse old format: "ML2025010001" => 1
                if (strpos($contractNo, '-') !== false) {
                    // New format: ML-{org_id}-{year}-{month}-{sequence}
                    $parts = explode('-', $contractNo);
                    if (count($parts) >= 5) {
                        $number = (int) preg_replace('/[^0-9]/', '', $parts[4]);
                    } else {
                        $number = 0;
                    }
                } else {
                    // Old format: ML{year}{month}{sequence}
                    $pattern = "ML{$year}{$month}";
                    $numberStr = substr($contractNo, strlen($pattern));
                    $number = (int) preg_replace('/[^0-9]/', '', $numberStr);
                }
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
            return $maxNumber;
        });
        
        // Generate contract number with new format: ML-{org_id}-{year}-{month}-{sequence}
        $contractNumber = "ML-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        
        // Double-check to ensure uniqueness (excluding soft-deleted records)
        $exists = self::where('contract_no', $contractNumber)
            ->whereNull('deleted_at')
            ->where('organization_id', $organizationId)
            ->exists();
        
        if ($exists) {
            // If exists, retry with incremented sequence (max 10 retries)
            $maxRetries = 10;
            $retries = 0;
            
            while ($exists && $retries < $maxRetries) {
                $newSequence++;
                SequenceGenerator::reset($sequenceKey, $newSequence);
                
                $contractNumber = "ML-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
                $exists = self::where('contract_no', $contractNumber)
                    ->whereNull('deleted_at')
                    ->where('organization_id', $organizationId)
                    ->exists();
                $retries++;
            }
            
            if ($exists) {
                // If still exists after retries, use timestamp fallback
                \Illuminate\Support\Facades\Log::warning('Could not generate unique master lease contract number after retries, using timestamp fallback');
                $contractNumber = "ML-{$organizationId}-{$year}-{$month}-" . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
            }
        }
        
        return $contractNumber;
    }

    /**
     * Calculate total rent for the entire lease period
     * 
     * Logic:
     * - base_rent: Tiền thuê cho mỗi billing cycle (ví dụ: 300.000.000 VND/tháng)
     * - billing_cycle: Số tháng trong một chu kỳ thanh toán (ví dụ: 1 = hàng tháng, 3 = hàng quý)
     * - Tính số tháng thực tế từ start_date đến end_date (bao gồm cả 2 ngày)
     * - Tính số chu kỳ thanh toán = số tháng / billing_cycle (làm tròn lên)
     * - Tổng tiền thuê = số chu kỳ * base_rent
     * 
     * Ví dụ:
     * - start_date: 01/01/2025, end_date: 31/12/2026 (24 tháng)
     * - base_rent: 300.000.000 VND, billing_cycle: 1 (hàng tháng)
     * - Số tháng: 24
     * - Số chu kỳ: ceil(24/1) = 24
     * - Tổng tiền thuê: 24 * 300.000.000 = 7.200.000.000 VND
     */
    public function calculateTotalRent(): float
    {
        // Ensure valid billing cycle (default to 1 month if not set)
        $cycleMonths = (int) ($this->billing_cycle ?: 1);
        
        if ($cycleMonths <= 0) {
            $cycleMonths = 1;
        }

        // Calculate number of months between start and end dates (inclusive)
        // diffInMonths returns the difference, we add 1 to include both start and end months
        // Example: 01/01/2025 to 31/01/2025 = 1 month (diffInMonths = 0, we add 1 = 1)
        // Example: 01/01/2025 to 28/02/2025 = 2 months (diffInMonths = 1, we add 1 = 2)
        $startDate = \Carbon\Carbon::parse($this->start_date);
        $endDate = \Carbon\Carbon::parse($this->end_date);
        
        // Calculate total months (inclusive of both start and end months)
        $totalMonths = max(1, $startDate->diffInMonths($endDate) + 1);

        // Calculate number of billing cycles needed
        // Round up to ensure we cover the full period
        // Example: 13 months / 1 month cycle = 13 cycles
        // Example: 13 months / 3 month cycle = ceil(13/3) = 5 cycles
        $cycles = (int) ceil($totalMonths / $cycleMonths);

        // Total rent = number of cycles * rent per cycle
        return (float) ($cycles * $this->base_rent);
    }

    /**
     * Get total outflows (total amount paid to landlord)
     * 
     * Logic:
     * - Lấy tổng tất cả các company invoices đã thanh toán (status = 'paid')
     * - Đây là số tiền đã chi trả cho chủ nhà
     */
    public function getTotalOutflows(): float
    {
        return (float) $this->companyInvoices()
            ->where('status', 'paid')
            ->whereNull('deleted_at')
            ->sum('total_amount');
    }

    /**
     * Get remaining balance (total rent - total outflows)
     * 
     * Logic:
     * - Số tiền còn lại cần chi trả = Tổng tiền thuê - Tổng đã chi trả
     */
    public function getRemainingBalance(): float
    {
        return $this->calculateTotalRent() - $this->getTotalOutflows();
    }

    /**
     * Get expected profit (total rent - total outflows)
     * 
     * Logic:
     * - Lợi nhuận dự kiến = Tổng tiền thuê - Tổng chi trả
     * - Nếu có revenue_share_pct, cần tính thêm phần chia sẻ doanh thu
     */
    public function getExpectedProfit(): float
    {
        $totalRent = $this->calculateTotalRent();
        $totalOutflows = $this->getTotalOutflows();
        
        // Lợi nhuận = Tổng thu - Tổng chi
        $profit = $totalRent - $totalOutflows;
        
        // Nếu có tỷ lệ chia sẻ doanh thu, trừ đi phần chia sẻ
        // (Tuy nhiên, revenue_share_pct thường áp dụng cho doanh thu từ leases, không phải master lease)
        // Nên tạm thời không trừ revenue_share_pct ở đây
        
        return max(0, $profit); // Đảm bảo không âm
    }

    public function isOverdue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $nextDueDate = $this->getNextDueDate();
        return $nextDueDate && $nextDueDate < now();
    }

    public function getNextDueDate(): ?\Carbon\Carbon
    {
        if ($this->status !== 'active') {
            return null;
        }

        $current = now();
        $start = $this->start_date;
        $billingCycleMonths = $this->billing_cycle;
        
        // Calculate the next billing date based on the cycle
        $currentMonth = $current->month;
        $currentYear = $current->year;
        $startMonth = $start->month;

        $diffMonths = ($currentYear - $start->year) * 12 + ($currentMonth - $startMonth);
        $numCycles = floor($diffMonths / $billingCycleMonths);

        $nextDue = $start->copy()->addMonths($numCycles * $billingCycleMonths)->day($this->billing_day);
        
        if ($nextDue <= $current) {
            $nextDue->addMonths($billingCycleMonths);
        }
        
        return $nextDue;
    }

    public function getUnitsInProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->units()->get();
    }

    public function hasUnit(Unit $unit): bool
    {
        return $this->property_id === $unit->property_id;
    }

    /**
     * Get documents for this master lease (truy vấn trực tiếp từ documents table)
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'owner');
    }

    /**
     * Retrieve the model for route model binding.
     * Bypass global scope to allow finding master lease if user belongs to its organization,
     * even if current session organization is different.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (!$user) {
            return null;
        }

        // Admin can access all
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        if ($isAdmin) {
            return $this->withoutGlobalScope('organization')
                ->where($field ?? $this->getRouteKeyName(), $value)
                ->first();
        }

        // Get all organizations user belongs to
        $userOrganizationIds = $user->getAllOrganizationIds();
        
        if (empty($userOrganizationIds)) {
            return null;
        }

        // Find master lease if it belongs to any of user's organizations
        return $this->withoutGlobalScope('organization')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->whereIn('organization_id', $userOrganizationIds)
            ->first();
    }
}
