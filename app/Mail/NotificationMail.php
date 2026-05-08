<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $notificationContent;
    public $userName;
    public $notificationType;
    public $actionUrl;
    public $actionText;
    public $organizationName;

    /**
     * Create a new message instance.
     *
     * @param string $subject Tiêu đề email
     * @param string $content Nội dung thông báo
     * @param string $userName Tên người nhận
     * @param string $type Loại thông báo (info, success, warning, error)
     * @param string|null $actionUrl URL nút hành động
     * @param string|null $actionText Text nút hành động
     * @param string|null $organizationName Tên tổ chức
     */
    public function __construct(
        string $subject,
        string $content,
        string $userName,
        string $type = 'info',
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $organizationName = null
    ) {
        $this->subject = $subject;
        $this->notificationContent = $content;
        $this->userName = $userName;
        $this->notificationType = $type;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText ?? 'Xem chi tiết';
        $this->organizationName = $organizationName ?? 'ZoroRMS Team';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'subject' => $this->subject,
                'content' => $this->notificationContent,
                'userName' => $this->userName,
                'type' => $this->notificationType,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'organizationName' => $this->organizationName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

