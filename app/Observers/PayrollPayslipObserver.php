<?php

namespace App\Observers;

use App\Models\PayrollPayslip;
use App\Models\PayrollCycle;
use App\Services\AuditLogService;
use App\Services\BusinessRules\BusinessRulesService;
use App\Events\PayrollPayslipCreated;
use App\Events\PayrollPayslipUpdated;
use Illuminate\Support\Facades\Log;

class PayrollPayslipObserver
{
    protected $auditLogService;
    protected $businessRulesService;

    public function __construct(
        AuditLogService $auditLogService,
        BusinessRulesService $businessRulesService
    )
    {
        $this->auditLogService = $auditLogService;
        $this->businessRulesService = $businessRulesService;
    }
    /**
     * Handle the PayrollPayslip "created" event.
     */
    public function created(PayrollPayslip $payrollPayslip): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($payrollPayslip, 'creating');
        
        event(new PayrollPayslipCreated($payrollPayslip));
        
        // Log audit trail
        $this->auditLogService->logCreated($payrollPayslip);
    }

    /**
     * Handle the PayrollPayslip "updated" event.
     */
    public function updated(PayrollPayslip $payrollPayslip): void
    {
        // Validate business rules first
        $this->businessRulesService->validate($payrollPayslip, 'updating');
        
        // Check if status changed to 'paid'
        if ($payrollPayslip->isDirty('status') && $payrollPayslip->status === 'paid') {
            $oldStatus = $payrollPayslip->getOriginal('status');
            $newStatus = $payrollPayslip->status;
            
            event(new PayrollPayslipUpdated($payrollPayslip, $oldStatus, $newStatus));
            
            // Kiểm tra và cập nhật trạng thái kỳ lương nếu tất cả phiếu lương đã được thanh toán
            $this->checkAndUpdateCycleStatus($payrollPayslip);
        } elseif ($payrollPayslip->isDirty('status')) {
            $oldStatus = $payrollPayslip->getOriginal('status');
            $newStatus = $payrollPayslip->status;
            
            event(new PayrollPayslipUpdated($payrollPayslip, $oldStatus, $newStatus));
        }
        
        // Log audit trail for all changes
        $this->auditLogService->logUpdated($payrollPayslip);
    }

    /**
     * Handle the PayrollPayslip "deleted" event.
     */
    public function deleted(PayrollPayslip $payrollPayslip): void
    {
        // Validate business rules first (soft delete)
        if (!$payrollPayslip->isForceDeleting()) {
            $this->businessRulesService->validate($payrollPayslip, 'deleting');
        }
        
        // Log audit trail
        $this->auditLogService->logDeleted($payrollPayslip);
        
        // Kiểm tra lại trạng thái kỳ lương sau khi xóa phiếu lương
        // (vì có thể cycle đang ở trạng thái 'paid' nhưng sau khi xóa payslip thì không còn đủ điều kiện)
        if ($payrollPayslip->payrollCycle) {
            $this->checkAndUpdateCycleStatus($payrollPayslip);
        }
    }

    /**
     * Handle the PayrollPayslip "restored" event.
     */
    public function restored(PayrollPayslip $payrollPayslip): void
    {
        // Kiểm tra lại trạng thái kỳ lương sau khi restore phiếu lương
        if ($payrollPayslip->payrollCycle) {
            $this->checkAndUpdateCycleStatus($payrollPayslip);
        }
    }

    /**
     * Handle the PayrollPayslip "force deleted" event.
     */
    public function forceDeleted(PayrollPayslip $payrollPayslip): void
    {
        // Handle force deletion if needed
    }

    /**
     * Kiểm tra và cập nhật trạng thái kỳ lương nếu tất cả phiếu lương đã được thanh toán
     * 
     * @param PayrollPayslip $payrollPayslip
     * @return void
     */
    private function checkAndUpdateCycleStatus(PayrollPayslip $payrollPayslip)
    {
        try {
            // Reload relationship để đảm bảo có dữ liệu mới nhất
            $payrollCycle = $payrollPayslip->payrollCycle;
            
            if (!$payrollCycle) {
                return;
            }

            // Chỉ cập nhật nếu cycle đang ở trạng thái 'locked'
            if ($payrollCycle->status !== 'locked') {
                return;
            }

            // Đếm tổng số phiếu lương trong cycle (không bao gồm đã xóa)
            $totalPayslips = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                ->whereNull('deleted_at')
                ->count();

            // Nếu không có phiếu lương nào, không cập nhật
            if ($totalPayslips === 0) {
                return;
            }

            // Đếm số phiếu lương đã thanh toán
            $paidPayslips = PayrollPayslip::where('payroll_cycle_id', $payrollCycle->id)
                ->where('status', 'paid')
                ->whereNull('deleted_at')
                ->count();

            // Nếu tất cả phiếu lương đã được thanh toán, cập nhật trạng thái cycle thành 'paid'
            if ($paidPayslips >= $totalPayslips) {
                $payrollCycle->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);

                Log::info('Payroll cycle automatically updated to paid status via PayrollPayslipObserver', [
                    'cycle_id' => $payrollCycle->id,
                    'period_month' => $payrollCycle->period_month,
                    'total_payslips' => $totalPayslips,
                    'paid_payslips' => $paidPayslips,
                    'triggered_by' => 'PayrollPayslipObserver'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PayrollPayslipObserver: Error checking and updating cycle status', [
                'payslip_id' => $payrollPayslip->id,
                'cycle_id' => $payrollPayslip->payroll_cycle_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Không throw exception để không ảnh hưởng đến flow chính
        }
    }
}
