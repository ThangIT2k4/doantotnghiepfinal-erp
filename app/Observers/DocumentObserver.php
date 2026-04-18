<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class DocumentObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        try {
            Log::info('DocumentObserver::created triggered', [
                'document_id' => $document->id,
                'file_name' => $document->file_name,
                'document_type' => $document->document_type,
            ]);

            // Log audit trail
            $this->auditLogService->logCreated($document);

        } catch (\Exception $e) {
            Log::error('Error in DocumentObserver::created: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        try {
            Log::info('DocumentObserver::updated triggered', [
                'document_id' => $document->id,
                'file_name' => $document->file_name,
                'changes' => $document->getDirty(),
            ]);

            // Log audit trail
            $this->auditLogService->logUpdated($document);

        } catch (\Exception $e) {
            Log::error('Error in DocumentObserver::updated: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        try {
            Log::info('DocumentObserver::deleted triggered', [
                'document_id' => $document->id,
                'file_name' => $document->file_name
            ]);

            // Log audit trail
            $this->auditLogService->logDeleted($document);

        } catch (\Exception $e) {
            Log::error('Error in DocumentObserver::deleted: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'error' => $e->getTraceAsString()
            ]);
        }
    }
}

