<?php

namespace App\Observers;

use App\Models\ReviewReply;
use App\Services\AuditLogService;
use App\Events\ReviewReplyCreated;
use App\Events\ReviewReplyUpdated;
use Illuminate\Support\Facades\Log;

class ReviewReplyObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    /**
     * Handle the ReviewReply "created" event.
     */
    public function created(ReviewReply $reply): void
    {
        try {
            Log::info('ReviewReplyObserver::created triggered', [
                'reply_id' => $reply->id,
                'review_id' => $reply->review_id,
                'user_id' => $reply->user_id,
                'user_type' => $reply->user_type,
                'content' => substr($reply->content, 0, 50) . '...'
            ]);

            // Always trigger notification for new replies
            event(new ReviewReplyCreated($reply));

            // Log audit trail
            $this->auditLogService->logCreated($reply);

        } catch (\Exception $e) {
            Log::error('Error in ReviewReplyObserver::created: ' . $e->getMessage(), [
                'reply_id' => $reply->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the ReviewReply "updated" event.
     */
    public function updated(ReviewReply $reply): void
    {
        try {
            $changes = $reply->getDirty();
            
            // Trigger notification for important reply updates
            $importantFields = ['content', 'user_type'];
            $hasImportantChanges = false;
            
            foreach ($importantFields as $field) {
                if (isset($changes[$field])) {
                    $hasImportantChanges = true;
                    break;
                }
            }
            
            if ($hasImportantChanges) {
                Log::info('ReviewReplyObserver::updated triggered for important changes', [
                    'reply_id' => $reply->id,
                    'review_id' => $reply->review_id,
                    'user_id' => $reply->user_id,
                    'changes' => $changes
                ]);
                event(new ReviewReplyUpdated($reply, $changes));
            }

            // Log audit trail for all changes
            $this->auditLogService->logUpdated($reply, $changes);

        } catch (\Exception $e) {
            Log::error('Error in ReviewReplyObserver::updated: ' . $e->getMessage(), [
                'reply_id' => $reply->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the ReviewReply "deleted" event.
     */
    public function deleted(ReviewReply $reply): void
    {
        try {
            Log::info('ReviewReplyObserver::deleted triggered', [
                'reply_id' => $reply->id,
                'review_id' => $reply->review_id,
                'user_id' => $reply->user_id,
                'user_type' => $reply->user_type
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($reply);

        } catch (\Exception $e) {
            Log::error('Error in ReviewReplyObserver::deleted: ' . $e->getMessage(), [
                'reply_id' => $reply->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}
