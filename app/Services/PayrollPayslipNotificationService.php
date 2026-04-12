<?php

namespace App\Services;

use App\Models\PayrollPayslip;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Services\NotificationEmailService;
use Illuminate\Support\Facades\Log;

class PayrollPayslipNotificationService
{
    protected $emailService;

    public function __construct(NotificationEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send notifications when a payroll payslip is created
     */
    public function notifyPayrollPayslipCreated(PayrollPayslip $payrollPayslip)
    {
        try {
            // Get organization ID from payroll cycle
            $organizationId = $payrollPayslip->payrollCycle->organization_id;
            
            // Get all managers in the organization
            $managers = $this->getManagersForOrganization($organizationId);
            
            // Get the agent who owns the payslip
            $agent = $payrollPayslip->user;

            $recipients = collect($managers)->push($agent)->unique('id');

            $amount = number_format($payrollPayslip->net_amount, 0, ',', '.');
            $period = $payrollPayslip->payrollCycle->period_month;

            foreach ($recipients as $recipient) {
                // Create in-app notification
                $this->createInAppNotification(
                    $recipient,
                    'payroll_payslip_created',
                    'Phiếu lương mới',
                    "Phiếu lương tháng {$period} của {$agent->full_name} đã được tạo với số tiền {$amount} VND",
                    $payrollPayslip
                );
            }

            Log::info("Payroll payslip created notifications sent", [
                'payslip_id' => $payrollPayslip->id,
                'agent_id' => $agent->id,
                'organization_id' => $organizationId,
                'recipients_count' => $recipients->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending payroll payslip created notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notifications when a payroll payslip is updated
     */
    public function notifyPayrollPayslipUpdated(PayrollPayslip $payrollPayslip, $oldStatus = null, $newStatus = null)
    {
        try {
            // Get organization ID from payroll cycle
            $organizationId = $payrollPayslip->payrollCycle->organization_id;
            
            // Get all managers in the organization
            $managers = $this->getManagersForOrganization($organizationId);
            
            // Get the agent who owns the payslip
            $agent = $payrollPayslip->user;

            $recipients = collect($managers)->push($agent)->unique('id');

            $statusText = $this->getStatusText($newStatus);
            $oldStatusText = $this->getStatusText($oldStatus);
            $amount = number_format($payrollPayslip->net_amount, 0, ',', '.');
            $period = $payrollPayslip->payrollCycle->period_month;

            // Create detailed update message
            $updateDetails = $this->getUpdateDetails($payrollPayslip, $oldStatus, $newStatus);

            foreach ($recipients as $recipient) {
                // Create in-app notification
                $this->createInAppNotification(
                    $recipient,
                    'payroll_payslip_updated',
                    'Phiếu lương đã được cập nhật',
                    $updateDetails,
                    $payrollPayslip
                );
            }

            Log::info("Payroll payslip updated notifications sent", [
                'payslip_id' => $payrollPayslip->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'recipients_count' => $recipients->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending payroll payslip updated notifications: " . $e->getMessage());
        }
    }

    /**
     * Get all managers for an organization
     */
    private function getManagersForOrganization($organizationId)
    {
        // Get users with finance module access (replaces manager role check)
        return \App\Services\CapabilityService::getUsersWithModuleAccess('finance', $organizationId);
    }

    /**
     * Create in-app notification
     */
    private function createInAppNotification($user, $type, $title, $message, $payrollPayslip)
    {
        Notification::create([
            'to_user_id' => $user->id,
            'subject' => $title,
            'content' => $message,
            'status' => 'queued',
            'channel_id' => 1, // In-app notification
            'created_at' => now()
        ]);
    }

    /**
     * Get status text in Vietnamese
     */
    private function getStatusText($status)
    {
        switch ($status) {
            case 'pending':
                return 'Chờ thanh toán';
            case 'paid':
                return 'Đã thanh toán';
            default:
                return $status;
        }
    }

    /**
     * Get detailed update information
     */
    private function getUpdateDetails(PayrollPayslip $payrollPayslip, $oldStatus = null, $newStatus = null)
    {
        $agent = $payrollPayslip->user;
        $amount = number_format($payrollPayslip->net_amount, 0, ',', '.');
        $period = $payrollPayslip->payrollCycle->period_month;
        
        $details = [];
        
        // Status change
        if ($oldStatus && $newStatus && $oldStatus !== $newStatus) {
            $oldStatusText = $this->getStatusText($oldStatus);
            $newStatusText = $this->getStatusText($newStatus);
            $details[] = "Trạng thái: {$oldStatusText} → {$newStatusText}";
        }
        
        // Check for changes in other fields using wasChanged()
        if ($payrollPayslip->wasChanged('net_amount')) {
            $oldAmount = number_format($payrollPayslip->getOriginal('net_amount'), 0, ',', '.');
            $details[] = "Số tiền: {$oldAmount} → {$amount} VND";
        }
        
        if ($payrollPayslip->wasChanged('gross_amount')) {
            $oldGross = number_format($payrollPayslip->getOriginal('gross_amount'), 0, ',', '.');
            $newGross = number_format($payrollPayslip->gross_amount, 0, ',', '.');
            $details[] = "Tổng lương: {$oldGross} → {$newGross} VND";
        }
        
        if ($payrollPayslip->wasChanged('deduction_amount')) {
            $oldDeduction = number_format($payrollPayslip->getOriginal('deduction_amount'), 0, ',', '.');
            $newDeduction = number_format($payrollPayslip->deduction_amount, 0, ',', '.');
            $details[] = "Khấu trừ: {$oldDeduction} → {$newDeduction} VND";
        }
        
        if ($payrollPayslip->wasChanged('payment_method')) {
            $oldMethod = $payrollPayslip->getOriginal('payment_method');
            $newMethod = $payrollPayslip->payment_method;
            $details[] = "Phương thức thanh toán: {$oldMethod} → {$newMethod}";
        }
        
        if ($payrollPayslip->wasChanged('note')) {
            $details[] = "Ghi chú đã được cập nhật";
        }
        
        // Create final message
        if (empty($details)) {
            return "Phiếu lương tháng {$period} của {$agent->full_name} (Số tiền: {$amount} VND) đã được cập nhật.";
        }
        
        $message = "Phiếu lương tháng {$period} của {$agent->full_name} (Số tiền: {$amount} VND) đã được cập nhật:\n";
        $message .= "• " . implode("\n• ", $details);
        
        return $message;
    }
}
