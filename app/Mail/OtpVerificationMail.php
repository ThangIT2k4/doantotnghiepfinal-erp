<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $userName;
    public $expiryMinutes;
    public $emailType;
    public $organizationName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otpCode, string $userName, int $expiryMinutes = 2, string $emailType = 'email_verification', ?string $organizationName = null)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
        $this->expiryMinutes = $expiryMinutes;
        $this->emailType = $emailType;
        $this->organizationName = $organizationName ?? 'ZoroRMS Team';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->emailType === 'password_reset' 
            ? 'Mã xác thực OTP - Đặt lại mật khẩu'
            : 'Mã xác thực OTP - Xác nhận email';
            
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
            view: 'emails.otp-verification',
            with: [
                'otpCode' => $this->otpCode,
                'userName' => $this->userName,
                'expiryMinutes' => $this->expiryMinutes,
                'emailType' => $this->emailType,
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
