<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\AuditLogService;
use App\Events\ReviewCreated;
use App\Events\ReviewUpdated;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        try {
            Log::info('ReviewObserver::created triggered', [
                'review_id' => $review->id,
                'tenant_id' => $review->tenant_id,
                'unit_id' => $review->unit_id,
                'overall_rating' => $review->overall_rating,
                'title' => $review->title,
                'status' => $review->status
            ]);

            // Only trigger notification if review is published
            if ($review->status === 'published') {
                event(new ReviewCreated($review));
            }

            // Log audit trail
            $this->auditLogService->logCreated($review);

        } catch (\Exception $e) {
            Log::error('Error in ReviewObserver::created: ' . $e->getMessage(), [
                'review_id' => $review->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        try {
            $changes = $review->getDirty();
            
            // Only trigger notification if status changed to 'published'
            if ($review->isDirty('status') && $review->status === 'published') {
                $originalStatus = $review->getOriginal('status');
                
                Log::info('ReviewObserver::updated triggered - status changed to published', [
                    'review_id' => $review->id,
                    'tenant_id' => $review->tenant_id,
                    'original_status' => $originalStatus,
                    'new_status' => $review->status,
                    'overall_rating' => $review->overall_rating,
                    'title' => $review->title
                ]);

                // Dispatch ReviewCreated event
                event(new ReviewCreated($review));
            }
            
            // Trigger notification for other important review updates
            $importantFields = ['overall_rating', 'title', 'content', 'status'];
            $hasImportantChanges = false;
            
            foreach ($importantFields as $field) {
                if (isset($changes[$field])) {
                    $hasImportantChanges = true;
                    break;
                }
            }
            
            if ($hasImportantChanges) {
                Log::info('ReviewObserver::updated triggered for important changes', [
                    'review_id' => $review->id,
                    'tenant_id' => $review->tenant_id,
                    'changes' => $changes
                ]);
                event(new ReviewUpdated($review, $changes));
            }

            // Log audit trail for all changes
            $this->auditLogService->logUpdated($review, $changes);

        } catch (\Exception $e) {
            Log::error('Error in ReviewObserver::updated: ' . $e->getMessage(), [
                'review_id' => $review->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        try {
            Log::info('ReviewObserver::deleted triggered', [
                'review_id' => $review->id,
                'tenant_id' => $review->tenant_id,
                'title' => $review->title,
                'status' => $review->status
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($review);

        } catch (\Exception $e) {
            Log::error('Error in ReviewObserver::deleted: ' . $e->getMessage(), [
                'review_id' => $review->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}
