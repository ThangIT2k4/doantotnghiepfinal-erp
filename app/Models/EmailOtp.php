<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmailOtp extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'otp_code',
        'type',
        'expires_at',
        'verified_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_used' => 'boolean',
    ];
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            // Validate otp_code format nếu có
            if ($model->otp_code !== null) {
                $model->validateOtpCode($model->otp_code);
            }
        });
        
        static::updating(function ($model) {
            // Validate otp_code format nếu có thay đổi
            if ($model->isDirty('otp_code') && $model->otp_code !== null) {
                $model->validateOtpCode($model->otp_code);
            }
        });
    }
    
    /**
     * Validate otp_code format
     * Format: 6 digits (0-9)
     * Pattern: ^[0-9]{6}$
     */
    protected function validateOtpCode(?string $otpCode): void
    {
        if ($otpCode === null) {
            throw new \InvalidArgumentException('OTP code cannot be null');
        }
        
        // Validate otp_code: must be exactly 6 digits
        if (strlen($otpCode) !== 6 || !preg_match('/^[0-9]{6}$/', $otpCode)) {
            throw new \InvalidArgumentException(
                "Invalid OTP code format: {$otpCode}. Expected format: 6 digits (e.g., 123456)"
            );
        }
    }

    /**
     * Get the user that owns the OTP.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the OTP is valid (not expired and not used).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->is_used;
    }

    /**
     * Mark the OTP as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Generate a random 6-digit OTP code.
     */
    public static function generateOtpCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for email verification.
     */
    public static function createForEmailVerification(int $userId, string $email, int $expiryMinutes = 2): self
    {
        // Invalidate any existing OTPs for this user and type
        self::where('user_id', $userId)
            ->where('type', 'email_verification')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        return self::create([
            'user_id' => $userId,
            'email' => $email,
            'otp_code' => self::generateOtpCode(),
            'type' => 'email_verification',
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Create a new OTP for password reset.
     */
    public static function createForPasswordReset(int $userId, string $email, int $expiryMinutes = 2): self
    {
        // Invalidate any existing OTPs for this user and type
        self::where('user_id', $userId)
            ->where('type', 'password_reset')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        return self::create([
            'user_id' => $userId,
            'email' => $email,
            'otp_code' => self::generateOtpCode(),
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Create a new OTP for email change.
     */
    public static function createForEmailChange(int $userId, string $email, int $expiryMinutes = 2): self
    {
        // Invalidate any existing OTPs for this user and type
        self::where('user_id', $userId)
            ->where('type', 'email_change')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        return self::create([
            'user_id' => $userId,
            'email' => $email,
            'otp_code' => self::generateOtpCode(),
            'type' => 'email_change',
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Scope to get valid OTPs.
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get OTPs by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
