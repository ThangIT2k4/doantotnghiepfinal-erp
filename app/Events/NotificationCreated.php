<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->notification->to_user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'subject' => $this->notification->subject,
            'content' => $this->notification->content,
            'status' => $this->notification->status,
            'created_at' => $this->notification->created_at->toIso8601String(),
            'unread_count' => $this->getUnreadCount(),
            'type' => $this->getNotificationType(),
            'icon' => $this->getNotificationIcon(),
            'is_unread' => $this->notification->status === 'queued',
        ];
    }

    /**
     * Get notification type based on subject/content
     */
    private function getNotificationType(): string
    {
        $subject = strtolower($this->notification->subject);
        $content = strtolower($this->notification->content);
        
        if (str_contains($subject, 'thanh toán') || str_contains($content, 'hóa đơn')) {
            return 'payment';
        } elseif (str_contains($subject, 'hợp đồng') || str_contains($content, 'hợp đồng')) {
            return 'contract';
        } elseif (str_contains($subject, 'lịch hẹn') || str_contains($content, 'lịch hẹn')) {
            return 'appointment';
        } elseif (str_contains($subject, 'đánh giá') || str_contains($content, 'đánh giá')) {
            return 'review';
        } elseif (str_contains($subject, 'sửa chữa') || str_contains($content, 'sửa chữa')) {
            return 'maintenance';
        }
        
        return 'system';
    }

    /**
     * Get notification icon based on type
     */
    private function getNotificationIcon(): string
    {
        $type = $this->getNotificationType();
        
        switch ($type) {
            case 'payment':
                return 'fas fa-credit-card';
            case 'contract':
                return 'fas fa-file-contract';
            case 'appointment':
                return 'fas fa-calendar';
            case 'review':
                return 'fas fa-star';
            case 'maintenance':
                return 'fas fa-tools';
            default:
                return 'fas fa-bell';
        }
    }

    /**
     * Get unread notification count for the user
     */
    private function getUnreadCount(): int
    {
        return Notification::where('to_user_id', $this->notification->to_user_id)
            ->where('channel_id', 1)
            ->where('status', 'queued')
            ->count();
    }
}

