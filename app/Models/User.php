<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasSoftDeletesWithUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserProfile;
use App\Models\CashOutflow;
use App\Models\CompanyInvoice;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany organizations()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany organizationUsers()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany userRoles()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany commissionEvents()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany assignedProperties()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany salaryContracts()
 * @method \Illuminate\Database\Eloquent\Relations\HasOne activeSalaryContract()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany leasesAsTenant()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany leasesAsAgent()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany viewingsAsAgent()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany payments()
 * @method \Illuminate\Database\Eloquent\Relations\HasManyThrough invoices()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany bookingDepositsAsTenant()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany bookingDeposits()
 * @method \Illuminate\Database\Eloquent\Relations\HasOne userProfile()
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasSoftDeletesWithUser;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'phone',
        'password_hash',
        'google_id',
        'status',
        'email_verified_at',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Check if user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the user's name (from user profile)
     */
    public function getNameAttribute()
    {
        return $this->userProfile?->full_name;
    }

    /**
     * Get full_name from user profile
     */
    public function getFullNameAttribute()
    {
        return $this->userProfile?->full_name;
    }

    /**
     * Prevent setting full_name directly on User model
     * It should be set on UserProfile instead
     */
    public function setFullNameAttribute($value)
    {
        // Do not set full_name on User model
        // It should be set on UserProfile instead
        // This prevents accidental database insert errors
        if (!$this->exists) {
            // If user doesn't exist yet, store it temporarily
            // and create profile after user is saved
            $this->attributes['_temp_full_name'] = $value;
        } else {
            // If user exists, update profile
            $profile = $this->getOrCreateProfile();
            $profile->full_name = $value;
            $profile->save();
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // After user is created, create profile if _temp_full_name exists
        static::created(function ($user) {
            if (isset($user->attributes['_temp_full_name'])) {
                $fullName = $user->attributes['_temp_full_name'];
                UserProfile::create([
                    'user_id' => $user->id,
                    'full_name' => $fullName,
                ]);
                unset($user->attributes['_temp_full_name']);
            }
        });

        // Before soft deleting, append "cancel_{timestamp}" to email and phone to allow reuse
        static::deleting(function ($user) {
            if (!$user->isForceDeleting()) {
                $timestamp = now()->timestamp;
                
                // Update email if exists and doesn't already have cancel prefix
                if ($user->email && !preg_match('/^cancel_\d+_/', $user->email)) {
                    $user->email = 'cancel_' . $timestamp . '_' . $user->email;
                }
                
                // Update phone if exists and doesn't already have cancel prefix
                if ($user->phone && !preg_match('/^cancel_\d+_/', $user->phone)) {
                    $user->phone = 'cancel_' . $timestamp . '_' . $user->phone;
                }
                
                // Save quietly to avoid triggering events
                if ($user->isDirty(['email', 'phone'])) {
                    $user->saveQuietly();
                }
            }
        });

        // When restoring, remove "cancel_{timestamp}_" prefix from email and phone
        static::restoring(function ($user) {
            // Restore email
            if ($user->email && preg_match('/^cancel_\d+_(.+)$/', $user->email, $matches)) {
                $user->email = $matches[1];
            }
            
            // Restore phone
            if ($user->phone && preg_match('/^cancel_\d+_(.+)$/', $user->phone, $matches)) {
                $user->phone = $matches[1];
            }
        });
    }

    /**
     * Get avatar from user profile
     */
    public function getAvatarAttribute()
    {
        return $this->userProfile?->avatar;
    }

    /**
     * Get the roles that belong to the user through organization_users.
     */
    public function organizationRoles($organizationId = null)
    {
        $query = $this->belongsToMany(Role::class, 'organization_users', 'user_id', 'role_id')
            ->withPivot('organization_id', 'status', 'deleted_at', 'deleted_by')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
        
        if ($organizationId) {
            $query->wherePivot('organization_id', $organizationId);
        }
        
        return $query;
    }

    /**
     * Alias for organizationRoles() for backward compatibility.
     */
    public function roles()
    {
        return $this->organizationRoles();
    }

    /**
     * Legacy method - redirects to organizationRoles for backward compatibility.
     */
    public function userRoles()
    {
        return $this->organizationRoles();
    }

    /**
     * Get the user's primary role.
     */
    public function primaryRole()
    {
        return $this->organizationRoles()->first();
    }

    /**
     * Get the salary contracts for the user.
     */
    public function salaryContracts()
    {
        return $this->hasMany(SalaryContract::class);
    }

    /**
     * Get the active salary contract for the user.
     */
    public function activeSalaryContract()
    {
        return $this->hasOne(SalaryContract::class)->where('status', 'active')->latest('effective_from');
    }

    /**
     * Get the properties assigned to the user.
     */
    public function assignedProperties()
    {
        return $this->belongsToMany(Property::class, 'properties_user')
            ->withPivot('role_key', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get the commission events for the user.
     */
    public function commissionEvents()
    {
        return $this->hasMany(CommissionEvent::class, 'agent_id');
    }

    /**
     * Get the organizations the user belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot('role_id', 'status', 'deleted_at', 'deleted_by')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * Get the organization users pivot records.
     */
    public function organizationUsers()
    {
        return $this->hasMany(\App\Models\OrganizationUser::class);
    }

    /**
     * Get the leases where user is tenant.
     */
    public function leasesAsTenant()
    {
        return $this->hasMany(Lease::class, 'tenant_id');
    }

    /**
     * Get the leases where user is agent.
     */
    public function leasesAsAgent()
    {
        return $this->hasMany(Lease::class, 'agent_id');
    }

    /**
     * Get the viewings where user is agent.
     */
    public function viewingsAsAgent()
    {
        return $this->hasMany(Viewing::class, 'agent_id');
    }

    /**
     * Get the payments made by user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'payer_user_id');
    }

    /**
     * Get the invoices for user through leases.
     */
    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Lease::class, 'tenant_id', 'lease_id', 'id', 'id');
    }

    /**
     * Get the booking deposits where user is tenant.
     */
    public function bookingDepositsAsTenant()
    {
        return $this->hasMany(BookingDeposit::class, 'tenant_user_id');
    }

    /**
     * Get the booking deposits where user is agent.
     */
    public function bookingDeposits()
    {
        return $this->hasMany(BookingDeposit::class, 'agent_id');
    }

    /**
     * Get the salary advances for the user.
     */
    public function salaryAdvances()
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    /**
     * Get the payslips for the user.
     */
    public function payslips()
    {
        return $this->hasMany(PayrollPayslip::class);
    }

    /**
     * Get the user's profile.
     */
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the email OTPs for the user.
     */
    public function emailOtps()
    {
        return $this->hasMany(EmailOtp::class);
    }

    /**
     * Get or create user profile.
     */
    public function getOrCreateProfile()
    {
        return $this->userProfile ?: $this->userProfile()->create([]);
    }

    /**
     * Get the chat conversations this user participates in.
     */
  

    /**
     * Get unread chat message count for this user.
     */
    public function getUnreadChatCount()
    {
        $conversations = $this->chatConversations()
            ->wherePivotNull('left_at')
            ->get();

        $totalUnread = 0;
        foreach ($conversations as $conversation) {
            $totalUnread += $conversation->getUnreadCountForUser($this->id);
        }

        return $totalUnread;
    }

    /**
     * Get ALL organization IDs that the user belongs to.
     * 
     * @return array
     */
    public function getAllOrganizationIds(): array
    {
        return $this->organizations()->pluck('organizations.id')->toArray();
    }

    /**
     * Get the user's current organization ID.
     * Ưu tiên: session > first organization
     * 
     * @return int|null
     */
    public function getCurrentOrganizationId()
    {
        try {
            // Lấy từ session nếu có (cho phép user switch organization)
            $sessionOrgId = session('current_organization_id');
            if ($sessionOrgId) {
                // Validate: user phải thuộc organization này
                if ($this->organizations()->where('organizations.id', $sessionOrgId)->exists()) {
                    return (int) $sessionOrgId;
                } else {
                    // Session có organization ID nhưng user không thuộc organization này
                    \Illuminate\Support\Facades\Log::warning('User has invalid organization ID in session', [
                        'user_id' => $this->id,
                        'session_org_id' => $sessionOrgId,
                    ]);
                    // Clear invalid session data
                    session()->forget('current_organization_id');
                }
            }
            
            // Fallback: lấy organization đầu tiên
            $firstOrg = $this->organizations()->first();
            if ($firstOrg) {
                // Auto-set session if not set
                if (!session('current_organization_id')) {
                    session(['current_organization_id' => $firstOrg->id]);
                }
                return (int) $firstOrg->id;
            }
            
            // User không thuộc organization nào
            \Illuminate\Support\Facades\Log::warning('User has no organizations', [
                'user_id' => $this->id,
            ]);
            return null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting current organization ID', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get the user's current organization.
     * 
     * @return \App\Models\Organization|null
     */
    public function getCurrentOrganization()
    {
        $orgId = $this->getCurrentOrganizationId();
        return $orgId ? Organization::find($orgId) : null;
    }

    /**
     * Switch to a different organization.
     * 
     * @param int $organizationId
     * @return bool True if successful, false if user doesn't belong to this organization
     */
    public function switchOrganization(int $organizationId): bool
    {
        // Validate user thuộc organization này
        if (!$this->organizations()->where('organizations.id', $organizationId)->exists()) {
            return false;
        }
        
        // Lưu vào session
        session(['current_organization_id' => $organizationId]);
        return true;
    }

    // ==================== BANKING RELATIONSHIPS ====================

    /**
     * Get the user's bank information (from user profile).
     */
    public function sepayBank()
    {
        return $this->hasOneThrough(SepayBank::class, UserProfile::class, 'user_id', 'id', 'id', 'sepay_bank_id');
    }

    /**
     * Get sepay_bank_id from user profile.
     */
    public function getSepayBankIdAttribute()
    {
        return $this->userProfile?->sepay_bank_id;
    }

    /**
     * Get account_number from user profile.
     */
    public function getAccountNumberAttribute()
    {
        return $this->userProfile?->account_number;
    }

    /**
     * Get account_holder_name from user profile.
     */
    public function getAccountHolderNameAttribute()
    {
        return $this->userProfile?->account_holder_name;
    }

    /**
     * Get branch_name from user profile.
     */
    public function getBranchNameAttribute()
    {
        return $this->userProfile?->branch_name;
    }

    /**
     * Get branch_code from user profile.
     */
    public function getBranchCodeAttribute()
    {
        return $this->userProfile?->branch_code;
    }

    /**
     * Get swift_code from user profile.
     */
    public function getSwiftCodeAttribute()
    {
        return $this->userProfile?->swift_code;
    }

    /**
     * Get banking_notes from user profile.
     */
    public function getBankingNotesAttribute()
    {
        return $this->userProfile?->banking_notes;
    }

    /**
     * Get company invoices where this user is the recipient.
     */
    public function companyInvoices()
    {
        return $this->hasMany(CompanyInvoice::class);
    }

    /**
     * Get cash outflows where this user is the recipient.
     * Cash outflows are linked to company_invoices, and we get them through company_invoices where this user is the recipient (user_id).
     */
    public function cashOutflows()
    {
        return $this->hasManyThrough(
            CashOutflow::class,
            CompanyInvoice::class,
            'user_id', // Foreign key on company_invoices table
            'company_invoice_id', // Foreign key on cash_outflows table
            'id', // Local key on users table
            'id' // Local key on company_invoices table
        );
    }

    // ==================== BANKING METHODS ====================

    /**
     * Get banking information array.
     */
    public function getBankingInfoAttribute(): array
    {
        $profile = $this->userProfile;
        $sepayBank = $profile?->sepayBank;
        return [
            'bank_name' => $sepayBank?->name,
            'bank_code' => $sepayBank?->code,
            'bank_short_name' => $sepayBank?->short_name,
            'bank_bin' => $sepayBank?->bin,
            'account_number' => $profile?->account_number,
            'account_holder_name' => $profile?->account_holder_name ?? $this->full_name,
            'branch_name' => $profile?->branch_name,
            'branch_code' => $profile?->branch_code,
            'swift_code' => $profile?->swift_code,
        ];
    }

    /**
     * Check if user has valid banking information.
     */
    public function hasValidBankingInfo(): bool
    {
        $profile = $this->userProfile;
        return !empty($profile?->sepay_bank_id) && 
               !empty($profile?->account_number) && 
               !empty($profile?->account_holder_name);
    }

    /**
     * Get personal information array.
     */
    public function getPersonalInfoAttribute(): array
    {
        $profile = $this->userProfile;
        return [
            'full_name' => $profile?->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_code' => $profile?->tax_code,
            'id_card_number' => $profile?->id_number,
            'id_card_issue_date' => $profile?->id_issued_at?->format('d/m/Y'),
            'id_card_issue_place' => $profile?->id_card_place,
            'birth_date' => $profile?->dob?->format('d/m/Y'),
            'gender' => $profile?->gender,
            'address' => $profile?->address,
        ];
    }

    /**
     * Get formatted gender label.
     */
    public function getGenderLabelAttribute(): string
    {
        return match($this->userProfile?->gender) {
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
            default => 'Chưa xác định'
        };
    }

    /**
     * Get total payments received by this user.
     */
    public function getTotalPaymentsAttribute(): float
    {
        return CashOutflow::whereHas('companyInvoice', function($query) {
                $query->where('user_id', $this->id);
            })
            ->where('status', 'success')
            ->sum('amount');
    }

    /**
     * Get formatted total payments.
     */
    public function getFormattedTotalPaymentsAttribute(): string
    {
        return number_format($this->total_payments, 0, ',', '.') . ' VND';
    }

    /**
     * Get last payment date.
     */
    public function getLastPaymentDateAttribute(): ?string
    {
        $lastPayment = CashOutflow::whereHas('companyInvoice', function($query) {
                $query->where('user_id', $this->id);
            })
            ->where('status', 'success')
            ->orderBy('paid_at', 'desc')
            ->first();
        
        return $lastPayment ? $lastPayment->paid_at->format('d/m/Y') : null;
    }

    /**
     * Get display name with tax code if available.
     */
    public function getDisplayNameWithTaxCodeAttribute(): string
    {
        $name = $this->full_name;
        $taxCode = $this->userProfile?->tax_code;
        if ($taxCode) {
            $name .= " ({$taxCode})";
        }
        return $name;
    }

    /**
     * Check if user has complete personal information for payroll.
     */
    public function hasCompletePersonalInfo(): bool
    {
        $profile = $this->userProfile;
        return !empty($this->full_name) && 
               !empty($this->phone) && 
               !empty($profile?->id_number) && 
               !empty($profile?->dob) && 
               !empty($profile?->address);
    }
}
