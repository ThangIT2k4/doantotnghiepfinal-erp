<?php

namespace App\Observers;

use App\Models\MasterLease;
use App\Models\CompanyInvoice;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class MasterLeaseObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    /**
     * Handle the MasterLease "created" event.
     */
    public function created(MasterLease $masterLease)
    {
        Log::info('MasterLeaseObserver::created triggered', [
            'master_lease_id' => $masterLease->id,
            'contract_no' => $masterLease->contract_no,
            'base_rent' => $masterLease->base_rent,
            'status' => $masterLease->status
        ]);
        
        // Log audit trail
        $this->auditLogService->logCreated($masterLease);
    }

    /**
     * Handle the MasterLease "updated" event.
     */
    public function updated(MasterLease $masterLease)
    {
        // Sync recipient on related company invoices if landlord changed
        if ($masterLease->isDirty('landlord_user_id')) {
            try {
                \App\Models\CompanyInvoice::where('master_lease_id', $masterLease->id)
                    ->whereIn('status', ['draft', 'pending', 'approved', 'overdue'])
                    ->update([
                        'vendor_id' => null,
                        'user_id' => $masterLease->landlord_user_id,
                    ]);
                Log::info('Updated company invoice recipients due to landlord change', [
                    'master_lease_id' => $masterLease->id,
                    'new_landlord_user_id' => $masterLease->landlord_user_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed updating invoice recipients on landlord change: '.$e->getMessage(), [
                    'master_lease_id' => $masterLease->id,
                ]);
            }
        }

        // Log audit trail
        $this->auditLogService->logUpdated($masterLease);
    }

    /**
     * Handle the MasterLease "deleted" event.
     */
    public function deleted(MasterLease $masterLease)
    {
        // When master lease is deleted, cancel all related unpaid company invoices
        $invoices = CompanyInvoice::where('master_lease_id', $masterLease->id)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'status' => 'cancelled',
                'note' => $invoice->note . "\n[Hủy tự động do master lease bị xóa]"
            ]);
        }

        if ($invoices->count() > 0) {
            Log::info('Company invoices automatically cancelled due to master lease deletion', [
                'master_lease_id' => $masterLease->id,
                'cancelled_invoices' => $invoices->pluck('id')->toArray()
            ]);
        }

        // Log audit trail
        $this->auditLogService->logDeleted($masterLease);
    }

}
