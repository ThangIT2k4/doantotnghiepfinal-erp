<?php

namespace App\Observers;

use App\Models\OrganizationEmailSetting;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class OrganizationEmailSettingObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the OrganizationEmailSetting "created" event.
     */
    public function created(OrganizationEmailSetting $emailSetting): void
    {
        try {
            Log::info('OrganizationEmailSettingObserver::created triggered', [
                'email_setting_id' => $emailSetting->id,
                'organization_id' => $emailSetting->organization_id,
                'mail_host' => $emailSetting->mail_host,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($emailSetting);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationEmailSettingObserver::created: ' . $e->getMessage(), [
                'email_setting_id' => $emailSetting->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationEmailSetting "updated" event.
     */
    public function updated(OrganizationEmailSetting $emailSetting): void
    {
        try {
            Log::info('OrganizationEmailSettingObserver::updated triggered', [
                'email_setting_id' => $emailSetting->id,
                'organization_id' => $emailSetting->organization_id,
                'changes' => $emailSetting->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($emailSetting);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationEmailSettingObserver::updated: ' . $e->getMessage(), [
                'email_setting_id' => $emailSetting->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the OrganizationEmailSetting "deleted" event.
     */
    public function deleted(OrganizationEmailSetting $emailSetting): void
    {
        try {
            Log::info('OrganizationEmailSettingObserver::deleted triggered', [
                'email_setting_id' => $emailSetting->id,
                'organization_id' => $emailSetting->organization_id,
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($emailSetting);

        } catch (\Exception $e) {
            Log::error('Error in OrganizationEmailSettingObserver::deleted: ' . $e->getMessage(), [
                'email_setting_id' => $emailSetting->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

