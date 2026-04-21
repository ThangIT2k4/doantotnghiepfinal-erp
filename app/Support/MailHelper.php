<?php

namespace App\Support;

use App\Jobs\SendQueuedMailJob;
use App\Services\OrganizationMailConfigService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

final class MailHelper
{
    public static function wantsQueuedDispatch(): bool
    {
        return config('queue.default') !== 'sync';
    }

    /**
     * Gửi email đồng bộ hoặc đưa vào queue. SMTP tổ chức chỉ dùng khi MAIL_MAILER=smtp; với Resend/SES
     * thì luôn gửi qua mailer mặc định (.env), phù hợp server chặn cổng SMTP.
     *
     * @param  string|array<int, string>  $to
     */
    public static function sendWithOptionalOrgMail(
        Mailable $mailable,
        string|array $to,
        ?int $organizationId = null
    ): void {
        if (! self::wantsQueuedDispatch()) {
            $orgMail = app(OrganizationMailConfigService::class);
            $useOrgSmtp = $orgMail->tryApplyOrganizationSmtpForOutgoing($organizationId);

            if ($useOrgSmtp) {
                Mail::mailer('smtp')->to($to)->send($mailable);
            } else {
                Mail::to($to)->send($mailable);
            }

            return;
        }

        SendQueuedMailJob::dispatch($to, $mailable, $organizationId);
    }
}
