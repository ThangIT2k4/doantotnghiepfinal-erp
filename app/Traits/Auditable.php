<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Log the creation of the model
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getDirty();
            if (!empty($changes)) {
                $model->logAudit('updated', $model->getOriginal(), $model->getAttributes(), $changes);
            }
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Log an audit entry
     */
    public function logAudit(string $action, $before = null, $after = null, array $changes = [])
    {
        try {
            $entityType = $this->getAuditEntityType();
            
            AuditLog::create([
                'actor_id' => Auth::id(),
                'action' => $this->getAuditActionPrefix() . '_' . $action,
                'entity_type' => $entityType,
                'entity_id' => $this->getKey(),
                'before_json' => $before ? json_encode($before) : null,
                'after_json' => $after ? json_encode($after) : null,
                'changes_json' => !empty($changes) ? json_encode($changes) : null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log audit: ' . $e->getMessage(), [
                'entity_type' => $this->getAuditEntityType(),
                'entity_id' => $this->getKey(),
                'action' => $action
            ]);
        }
    }

    /**
     * Get the entity type for audit logs
     * Override this method in the model if needed
     */
    protected function getAuditEntityType(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get the action prefix for audit logs
     * Override this method in the model if needed
     */
    protected function getAuditActionPrefix(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the latest audit log
     */
    public function latestAuditLog()
    {
        return $this->morphOne(AuditLog::class, 'entity', 'entity_type', 'entity_id')
            ->latest('created_at');
    }
}

