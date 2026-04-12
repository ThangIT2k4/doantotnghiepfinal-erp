<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false; // Using created_at only

    protected $fillable = [
        'actor_id',
        'organization_id',
        'action',
        'entity_type',
        'entity_id',
        'before_json',
        'after_json',
        'changes_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the organization that owns this audit log
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the entity that was changed
     */
    public function entity()
    {
        // Polymorphic relationship based on entity_type
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Get before data as array
     */
    public function getBeforeAttribute()
    {
        return $this->before_json ? json_decode($this->before_json, true) : null;
    }

    /**
     * Get after data as array
     */
    public function getAfterAttribute()
    {
        return $this->after_json ? json_decode($this->after_json, true) : null;
    }

    /**
     * Get changes as array
     */
    public function getChangesAttribute()
    {
        return $this->changes_json ? json_decode($this->changes_json, true) : null;
    }

    /**
     * Scope for filtering by entity type
     */
    public function scopeForEntity($query, string $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);
        
        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }

    /**
     * Scope for filtering by action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by actor
     */
    public function scopeForActor($query, $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    /**
     * Scope for filtering by organization
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}

