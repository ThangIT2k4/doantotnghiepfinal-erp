<?php

namespace App\Jobs;

use App\Services\OrganizationMailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendQueuedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string|array<int, string>  $to
     */
    public function __construct(
        public string|array $to,
        public Mailable $mailable,
        public ?int $organizationId = null,
    ) {}

    public function handle(OrganizationMailConfigService $organizationMailConfigService): void
    {
        $useOrgSmtp = $organizationMailConfigService->tryApplyOrganizationSmtpForOutgoing($this->organizationId);

        if ($useOrgSmtp) {
            Mail::mailer('smtp')->to($this->to)->send($this->mailable);

            return;
        }

        Mail::to($this->to)->send($this->mailable);
    }
}
