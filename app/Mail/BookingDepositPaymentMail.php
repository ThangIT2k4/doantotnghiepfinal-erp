<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDepositPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $isReminder = $this->emailData['is_reminder'] ?? false;
        $isSuccess = $this->emailData['is_success'] ?? false;

        if ($isSuccess) {
            $subject = 'Xác nhận thanh toán đặt cọc thành công - ' . ($this->emailData['booking_reference'] ?? 'N/A');
        } elseif ($isReminder) {
            $subject = 'Nhắc nhở thanh toán đặt cọc - ' . ($this->emailData['booking_reference'] ?? 'N/A');
        } else {
            $subject = 'Thông báo thanh toán đặt cọc - ' . ($this->emailData['booking_reference'] ?? 'N/A');
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-deposit-payment',
            with: [
                'data' => $this->emailData,
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

