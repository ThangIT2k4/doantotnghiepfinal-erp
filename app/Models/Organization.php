<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSoftDeletesWithUser;
use App\Traits\HasSubscription;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes, HasSoftDeletesWithUser, HasSubscription;

    protected $table = 'organizations';

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'tax_code',
        'address',
        'status',
        'deleted_by',
        'first_trial_at',
        'has_ever_paid',
        'paid_subscriptions_count',
    ];

    protected $casts = [
        'status' => 'boolean',
        'has_ever_paid' => 'boolean',
        'paid_subscriptions_count' => 'integer',
        'first_trial_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * Get the users for the organization.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot('role_id', 'status', 'deleted_at', 'deleted_by')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * Get the roles for the organization through organization_users pivot table.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'organization_users', 'organization_id', 'role_id')
            ->withPivot('user_id', 'status')
            ->withTimestamps();
    }

    /**
     * Get the properties for the organization.
     */
    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get the salary contracts for the organization.
     */
    public function salaryContracts()
    {
        return $this->hasMany(SalaryContract::class);
    }

    /**
     * Get the commission policies for the organization.
     */
    public function commissionPolicies()
    {
        return $this->hasMany(CommissionPolicy::class);
    }

    /**
     * Get all lease service sets for this organization
     */
    public function leaseServiceSets()
    {
        return $this->hasMany(LeaseServiceSet::class, 'organization_id');
    }

    /**
     * Get the default payment cycle for the organization.
     */
    public function defaultPaymentCycle()
    {
        return $this->hasOne(PaymentCycle::class, 'organization_id')
            ->where('is_default', true);
    }

    /**
     * Get or create default payment cycle for the organization.
     * Ensures there is always a default payment cycle.
     * 
     * @return PaymentCycle|null
     */
    public function getOrCreateDefaultPaymentCycle()
    {
        // Try to get existing default
        $defaultCycle = $this->defaultPaymentCycle;
        
        if ($defaultCycle) {
            return $defaultCycle;
        }
        
        // If no default exists, check if there are any cycles for this organization
        $latestCycle = PaymentCycle::where('organization_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($latestCycle) {
            // Set the latest cycle as default
            $latestCycle->update(['is_default' => true]);
            return $latestCycle->fresh();
        }
        
        // If no cycles exist at all, create a default one
        $defaultCycle = PaymentCycle::create([
            'organization_id' => $this->id,
            'cycle_type' => 'monthly',
            'billing_day' => 1,
            'notes' => 'Chu kỳ thanh toán mặc định',
            'name' => 'Hàng tháng - Ngày 1',
            'is_default' => true,
            'payment_due_hours' => 4320, // 72 hours
            'invoice_timing' => 'end_of_cycle',
            'invoice_payment_days' => 30,
        ]);
        
        return $defaultCycle;
    }

    /**
     * Get payment_due_hours from default payment cycle
     * 
     * @return int Minutes (default: 4320 = 72 hours)
     */
    public function getEffectivePaymentDueHours()
    {
        $cycle = $this->getOrCreateDefaultPaymentCycle();
        return $cycle?->payment_due_hours ?? 4320;
    }

    /**
     * Get invoice_timing from default payment cycle
     * 
     * @return string 'start_of_cycle' or 'end_of_cycle'
     */
    public function getEffectiveInvoiceTiming()
    {
        $cycle = $this->getOrCreateDefaultPaymentCycle();
        return $cycle?->invoice_timing ?? 'end_of_cycle';
    }

    /**
     * Get invoice_payment_days from default payment cycle
     * 
     * @return int Days (default: 30)
     */
    public function getEffectiveInvoicePaymentDays()
    {
        $cycle = $this->getOrCreateDefaultPaymentCycle();
        return $cycle?->invoice_payment_days ?? 30;
    }

    /**
     * Get the default lease service set for the organization.
     */
    public function defaultLeaseServiceSet()
    {
        return $this->hasOne(LeaseServiceSet::class, 'organization_id')
            ->where('is_default', true);
    }

    /**
     * Get the email settings for the organization (1-1 relationship)
     */
    public function emailSetting()
    {
        return $this->hasOne(OrganizationEmailSetting::class, 'organization_id');
    }

    /**
     * Scope a query to only include active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Kiểm tra xem organization có đủ điều kiện sử dụng trial không
     * 
     * Logic:
     * - Nếu chưa bao giờ dùng trial (first_trial_at = null): CHO PHÉP trial
     * - Nếu đã dùng trial nhưng chưa thanh toán lần nào (has_ever_paid = false):
     *   + Cho phép nếu chỉ có subscription mặc định (FREE plan) từ system
     *   + Không cho phép nếu đã có subscription do user đăng ký VÀ đã được sử dụng (trial/active)
     *   + Cho phép nếu subscription là suspended (chưa thanh toán, chưa được sử dụng)
     *   + Lưu ý: đổi gói (nâng/hạ) qua SubscriptionController::store không dùng gate này (xử lý riêng).
     * - Nếu đã thanh toán ít nhất 1 lần (has_ever_paid = true): CHO PHÉP trial khi đổi gói
     * 
     * @return bool
     */
    public function canUseTrial(): bool
    {
        // Trường hợp 1: Chưa bao giờ dùng trial - cho phép
        if ($this->first_trial_at === null) {
            return true;
        }
        
        // Trường hợp 2: Đã dùng trial nhưng chưa thanh toán
        if (!$this->has_ever_paid) {
            // Kiểm tra xem có subscription nào được đăng ký bởi user VÀ đã được sử dụng không
            // (không phải subscription mặc định từ system)
            // CHỈ chặn nếu subscription có status 'trial' hoặc 'active' (đã được sử dụng)
            // KHÔNG chặn nếu subscription là 'suspended' (chưa thanh toán, chưa được sử dụng)
            $hasUsedUserSubscription = $this->subscriptions()
                ->whereIn('status', ['trial', 'active']) // CHỈ kiểm tra trial/active (đã được sử dụng)
                ->whereHas('plan', function($query) {
                    // Loại trừ FREE plan (gói mặc định từ system)
                    $query->where('code', '!=', 'FREE');
                })
                ->exists();
            
            // Nếu chỉ có subscription mặc định (FREE) từ system → cho phép trial
            // Nếu có subscription suspended (chưa thanh toán) → cho phép trial
            // Nếu đã có subscription trial/active do user đăng ký → không cho phép
            return !$hasUsedUserSubscription;
        }
        
        // Trường hợp 3: Đã thanh toán - cho phép trial khi đổi gói
        return true;
    }

    /**
     * Lý do không thể dùng trial
     * 
     * @return string|null
     */
    public function getTrialDenialReason(): ?string
    {
        if ($this->canUseTrial()) {
            return null;
        }
        
        if ($this->first_trial_at !== null && !$this->has_ever_paid) {
            return 'Bạn đã sử dụng gói dùng thử. Vui lòng thanh toán để tiếp tục sử dụng dịch vụ.';
        }
        
        return 'Không thể sử dụng gói dùng thử.';
    }

    /**
     * Đánh dấu organization đã sử dụng trial lần đầu
     * 
     * @return void
     */
    public function markTrialUsed(): void
    {
        if ($this->first_trial_at === null) {
            $this->update([
                'first_trial_at' => now(),
            ]);
        }
    }

    /**
     * Đánh dấu organization đã thanh toán thành công
     * 
     * @return void
     */
    public function markPaid(): void
    {
        $this->increment('paid_subscriptions_count');
        
        if (!$this->has_ever_paid) {
            $this->update([
                'has_ever_paid' => true,
            ]);
        }
    }

    /**
     * Get mail_username from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_username
     */
    public function getMailUsernameAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_username : null;
    }

    /**
     * Get mail_password from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_password
     */
    public function getMailPasswordAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_password : null;
    }

    /**
     * Get mail_from_address from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_from_address
     */
    public function getMailFromAddressAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_from_address : null;
    }

    /**
     * Get mail_host from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_host
     */
    public function getMailHostAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_host : null;
    }

    /**
     * Get mail_port from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_port
     */
    public function getMailPortAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_port : null;
    }

    /**
     * Get mail_encryption from email setting
     * Returns null if email setting doesn't exist or doesn't have mail_encryption
     */
    public function getMailEncryptionAttribute()
    {
        $emailSetting = $this->emailSetting;
        return $emailSetting ? $emailSetting->mail_encryption : null;
    }
}
