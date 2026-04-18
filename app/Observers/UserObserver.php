<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        try {
            Log::info('UserObserver::created triggered', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($user);

        } catch (\Exception $e) {
            Log::error('Error in UserObserver::created: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        try {
            // Skip logging if only password_hash changed (for security)
            $dirty = $user->getDirty();
            if (count($dirty) === 1 && isset($dirty['password_hash'])) {
                return;
            }

            Log::info('UserObserver::updated triggered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'changes' => array_keys($dirty),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($user);

        } catch (\Exception $e) {
            Log::error('Error in UserObserver::updated: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        try {
            Log::info('UserObserver::deleted triggered', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($user);

        } catch (\Exception $e) {
            Log::error('Error in UserObserver::deleted: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        try {
            Log::info('UserObserver::restored triggered', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Log audit trail for restoration
            $this->auditLogService->log('restored', 'user', $user->id, null, $user->getAttributes(), []);

        } catch (\Exception $e) {
            Log::error('Error in UserObserver::restored: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

