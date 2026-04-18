<?php

namespace App\Services;

use App\Models\CommissionEvent;
use App\Models\User;
use App\Models\Notification;
use App\Models\Lease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionNotificationService
{
    /**
     * Send notification when a commission event occurs
     */
    public function notifyCommissionEvent(CommissionEvent $commissionEvent, string $eventType): bool
    {
        try {
            $successCount = 0;
            $totalNotifications = 0;

            Log::info('Commission event notification started', [
                'commission_event_id' => $commissionEvent->id,
                'event_type' => $eventType,
                'agent_id' => $commissionEvent->agent_id,
                'amount' => $commissionEvent->commission_total
            ]);

            // 1. Send in-app notification to ALL managers of the organization
            $managers = $this->getAllManagersForOrganization($commissionEvent->organization_id);
            foreach ($managers as $manager) {
                $inAppSuccess = $this->sendInAppNotification($manager, $commissionEvent, $eventType);
                if ($inAppSuccess) {
                    $successCount++;
                }
                $totalNotifications++;
            }

            // 2. Send in-app notification to the agent (owner of the commission)
            $agent = User::find($commissionEvent->agent_id);
            if ($agent) {
                $inAppSuccess = $this->sendInAppNotification($agent, $commissionEvent, $eventType);
                if ($inAppSuccess) {
                    $successCount++;
                }
                $totalNotifications++;
            }

            Log::info('Commission event notifications sent', [
                'commission_event_id' => $commissionEvent->id,
                'event_type' => $eventType,
                'successful_notifications' => $successCount,
                'total_notifications' => $totalNotifications
            ]);

            return $successCount > 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to send commission event notifications', [
                'commission_event_id' => $commissionEvent->id,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all managers for organization
     */
    private function getAllManagersForOrganization(int $organizationId): array
    {
        // Get users with finance module access (replaces manager role check)
        $users = \App\Services\CapabilityService::getUsersWithModuleAccess('finance', $organizationId);
        
        $userObjects = [];
        foreach ($users as $user) {
            $userObjects[] = $user;
        }

        return $userObjects;
    }

    /**
     * Send in-app notification
     */
    private function sendInAppNotification(User $user, CommissionEvent $commissionEvent, string $eventType): bool
    {
        try {
            $subject = $this->getCommissionSubject($commissionEvent, $eventType);
            $content = $this->generateCommissionMessage($commissionEvent, $eventType);

            $notification = Notification::create([
                'channel_id' => 1, // in_app channel
                'to_user_id' => $user->id,
                'subject' => $subject,
                'content' => $content,
                'status' => 'queued',
                'created_at' => now(),
            ]);

            Log::info('Commission in-app notification created successfully', [
                'commission_event_id' => $commissionEvent->id,
                'user_id' => $user->id,
                'user_name' => $user->full_name ?? $user->name,
                'event_type' => $eventType,
                'notification_id' => $notification->id
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error('Error creating commission in-app notification', [
                'commission_event_id' => $commissionEvent->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get commission notification subject
     */
    private function getCommissionSubject(CommissionEvent $commissionEvent, string $eventType): string
    {
        $eventText = $this->getEventTypeText($eventType);
        return "Hoa hồng {$eventText} - " . number_format($commissionEvent->commission_total, 0, ',', '.') . " VNĐ";
    }

    /**
     * Get event type text in Vietnamese
     */
    private function getEventTypeText(string $eventType): string
    {
        switch ($eventType) {
            case 'created':
                return 'mới được tạo';
            case 'updated':
                return 'đã được cập nhật';
            case 'approved':
                return 'đã được duyệt';
            case 'paid':
                return 'đã được thanh toán';
            case 'cancelled':
                return 'đã bị hủy';
            default:
                return 'có thay đổi';
        }
    }

    /**
     * Generate commission notification message
     */
    private function generateCommissionMessage(CommissionEvent $commissionEvent, string $eventType): string
    {
        $eventText = $this->getEventTypeText($eventType);
        $message = "💰 HOA HỒNG " . strtoupper($eventText) . "\n\n";
        
        // Thông tin hoa hồng
        $message .= "📋 THÔNG TIN HOA HỒNG:\n";
        $message .= "• ID: {$commissionEvent->id}\n";
        $message .= "• Số tiền: " . number_format($commissionEvent->commission_total, 0, ',', '.') . " VNĐ\n";
        $message .= "• Số tiền gốc: " . number_format($commissionEvent->amount_base, 0, ',', '.') . " VNĐ\n";
        $message .= "• Trạng thái: " . $this->getCommissionStatusText($commissionEvent->status) . "\n";
        $message .= "• Ngày tạo: " . $commissionEvent->created_at->format('d/m/Y H:i') . "\n";
        
        if ($commissionEvent->occurred_at) {
            $message .= "• Ngày xảy ra: " . $commissionEvent->occurred_at->format('d/m/Y H:i') . "\n";
        }
        $message .= "\n";
        
        // Thông tin đại lý
        $agent = User::find($commissionEvent->agent_id);
        if ($agent) {
            $message .= "👤 THÔNG TIN ĐẠI LÝ:\n";
            $message .= "• Tên: {$agent->full_name}\n";
            if ($agent->email) {
                $message .= "• Email: {$agent->email}\n";
            }
            if ($agent->phone) {
                $message .= "• SĐT: {$agent->phone}\n";
            }
            $message .= "\n";
        }
        
        // Thông tin hợp đồng (nếu có)
        if ($commissionEvent->lease_id) {
            $lease = Lease::with('unit.property')->find($commissionEvent->lease_id);
            if ($lease) {
                $message .= "📄 THÔNG TIN HỢP ĐỒNG:\n";
                $message .= "• Số hợp đồng: {$lease->contract_no}\n";
                if ($lease->unit && $lease->unit->property) {
                    $message .= "• Tài sản: {$lease->unit->property->name}\n";
                    $message .= "• Phòng: {$lease->unit->name}\n";
                }
                $message .= "• Ngày bắt đầu: " . $lease->start_date->format('d/m/Y') . "\n";
                $message .= "• Ngày kết thúc: " . $lease->end_date->format('d/m/Y') . "\n";
                $message .= "• Tiền thuê: " . number_format($lease->rent_amount, 0, ',', '.') . " VNĐ/tháng\n";
                $message .= "\n";
            }
        }
        
        return $message;
    }

    /**
     * Get commission status text in Vietnamese
     */
    private function getCommissionStatusText(string $status): string
    {
        switch ($status) {
            case 'pending':
                return 'Chờ xử lý';
            case 'approved':
                return 'Đã duyệt';
            case 'paid':
                return 'Đã thanh toán';
            case 'cancelled':
                return 'Đã hủy';
            default:
                return ucfirst($status);
        }
    }
}
