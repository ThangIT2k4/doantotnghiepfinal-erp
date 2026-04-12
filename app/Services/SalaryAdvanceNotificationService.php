<?php

namespace App\Services;

use App\Models\SalaryAdvance;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Services\NotificationEmailService;
use Illuminate\Support\Facades\Log;

class SalaryAdvanceNotificationService
{
    protected $emailService;

    public function __construct(NotificationEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send notifications when a salary advance is created
     */
    public function notifySalaryAdvanceCreated(SalaryAdvance $salaryAdvance)
    {
        try {
            // Get all managers in the organization
            $managers = $this->getManagersForOrganization($salaryAdvance->organization_id);
            
            // Get the agent who created the request
            $agent = $salaryAdvance->user;

            $recipients = collect($managers)->push($agent)->unique('id');

            foreach ($recipients as $recipient) {
                // Create in-app notification
                $this->createInAppNotification(
                    $recipient,
                    'salary_advance_created',
                    'Yêu cầu ứng lương mới',
                    "Nhân viên {$agent->full_name} đã tạo yêu cầu ứng lương số tiền " . number_format($salaryAdvance->amount, 0, ',', '.') . " {$salaryAdvance->currency}",
                    $salaryAdvance
                );

                // Send email notification only to managers
                if ($managers->contains('id', $recipient->id)) {
                    $this->sendEmailNotification(
                        $recipient,
                        'salary_advance_created',
                        'Yêu cầu ứng lương mới',
                        "Nhân viên {$agent->full_name} đã tạo yêu cầu ứng lương số tiền " . number_format($salaryAdvance->amount, 0, ',', '.') . " {$salaryAdvance->currency}",
                        $salaryAdvance
                    );
                }
            }

            Log::info("Salary advance created notifications sent", [
                'salary_advance_id' => $salaryAdvance->id,
                'recipients_count' => $recipients->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending salary advance created notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notifications when a salary advance is updated
     */
    public function notifySalaryAdvanceUpdated(SalaryAdvance $salaryAdvance, $oldStatus = null, $newStatus = null)
    {
        try {
            // Get all managers in the organization
            $managers = $this->getManagersForOrganization($salaryAdvance->organization_id);
            
            // Get the agent who created the request
            $agent = $salaryAdvance->user;

            $recipients = collect($managers)->push($agent)->unique('id');

            $statusText = $this->getStatusText($newStatus);
            $oldStatusText = $this->getStatusText($oldStatus);

            // Create detailed update message
            $updateDetails = $this->getUpdateDetails($salaryAdvance, $oldStatus, $newStatus);

            foreach ($recipients as $recipient) {
                // Create in-app notification
                $this->createInAppNotification(
                    $recipient,
                    'salary_advance_updated',
                    'Yêu cầu ứng lương đã được cập nhật',
                    $updateDetails,
                    $salaryAdvance
                );

                // Send email notification only to managers
                if ($managers->contains('id', $recipient->id)) {
                    $this->sendEmailNotification(
                        $recipient,
                        'salary_advance_updated',
                        'Yêu cầu ứng lương đã được cập nhật',
                        $updateDetails,
                        $salaryAdvance
                    );
                }
            }

            Log::info("Salary advance updated notifications sent", [
                'salary_advance_id' => $salaryAdvance->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'recipients_count' => $recipients->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending salary advance updated notifications: " . $e->getMessage());
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
    private function createInAppNotification($user, $type, $title, $message, $salaryAdvance)
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
     * Send email notification
     */
    private function sendEmailNotification($user, $type, $title, $message, $salaryAdvance)
    {
        try {
            $this->emailService->sendNotification(
                $user,
                $title,
                $message,
                'info',
                null,
                null,
                false
            );
        } catch (\Exception $e) {
            Log::error("Error sending email notification for salary advance: " . $e->getMessage());
        }
    }

    /**
     * Get status text in Vietnamese
     */
    private function getStatusText($status)
    {
        switch ($status) {
            case 'pending':
                return 'Chờ duyệt';
            case 'approved':
                return 'Đã duyệt';
            case 'rejected':
                return 'Đã từ chối';
            case 'repaid':
                return 'Đã hoàn trả';
            case 'partially_repaid':
                return 'Hoàn trả một phần';
            default:
                return $status;
        }
    }

    /**
     * Get detailed update information
     */
    private function getUpdateDetails(SalaryAdvance $salaryAdvance, $oldStatus = null, $newStatus = null)
    {
        $agent = $salaryAdvance->user;
        $amount = number_format($salaryAdvance->amount, 0, ',', '.');
        $currency = $salaryAdvance->currency;
        
        $details = [];
        
        // Status change
        if ($oldStatus && $newStatus && $oldStatus !== $newStatus) {
            $oldStatusText = $this->getStatusText($oldStatus);
            $newStatusText = $this->getStatusText($newStatus);
            $details[] = "Trạng thái: {$oldStatusText} → {$newStatusText}";
        }
        
        // Check for changes in other fields using wasChanged()
        if ($salaryAdvance->wasChanged('amount')) {
            $oldAmount = number_format($salaryAdvance->getOriginal('amount'), 0, ',', '.');
            $details[] = "Số tiền: {$oldAmount} → {$amount} {$currency}";
        }
        
        if ($salaryAdvance->wasChanged('repayment_method')) {
            $oldMethod = $this->getRepaymentMethodText($salaryAdvance->getOriginal('repayment_method'));
            $newMethod = $this->getRepaymentMethodText($salaryAdvance->repayment_method);
            $details[] = "Phương thức trả: {$oldMethod} → {$newMethod}";
        }
        
        if ($salaryAdvance->wasChanged('expected_repayment_date')) {
            $oldDate = $salaryAdvance->getOriginal('expected_repayment_date');
            $newDate = $salaryAdvance->expected_repayment_date;
            $details[] = "Ngày trả dự kiến: {$oldDate} → {$newDate}";
        }
        
        if ($salaryAdvance->wasChanged('reason')) {
            $details[] = "Lý do đã được cập nhật";
        }
        
        if ($salaryAdvance->wasChanged('note')) {
            $details[] = "Ghi chú đã được cập nhật";
        }
        
        if ($salaryAdvance->wasChanged('installment_months')) {
            $oldMonths = $salaryAdvance->getOriginal('installment_months');
            $newMonths = $salaryAdvance->installment_months;
            $details[] = "Số tháng trả góp: {$oldMonths} → {$newMonths}";
        }
        
        if ($salaryAdvance->wasChanged('monthly_deduction')) {
            $oldDeduction = number_format($salaryAdvance->getOriginal('monthly_deduction'), 0, ',', '.');
            $newDeduction = number_format($salaryAdvance->monthly_deduction, 0, ',', '.');
            $details[] = "Khấu trừ hàng tháng: {$oldDeduction} → {$newDeduction} VND";
        }
        
        // Create final message
        if (empty($details)) {
            return "Yêu cầu ứng lương của {$agent->full_name} (Số tiền: {$amount} {$currency}) đã được cập nhật.";
        }
        
        $message = "Yêu cầu ứng lương của {$agent->full_name} (Số tiền: {$amount} {$currency}) đã được cập nhật:\n";
        $message .= "• " . implode("\n• ", $details);
        
        return $message;
    }

    /**
     * Get repayment method text in Vietnamese
     */
    private function getRepaymentMethodText($method)
    {
        switch ($method) {
            case 'payroll_deduction':
                return 'Trừ lương';
            case 'direct_payment':
                return 'Thanh toán trực tiếp';
            case 'installment':
                return 'Trả góp';
            default:
                return $method;
        }
    }
}
