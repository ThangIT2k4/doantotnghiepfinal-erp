<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait HasSoftDeletesWithUser
{
    use SoftDeletes;

    /**
     * Boot the soft deletes with user trait for a model.
     */
    public static function bootHasSoftDeletesWithUser(): void
    {
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                // Automatically set deleted_by when soft deleting
                if (Auth::check() && $model->hasColumn('deleted_by')) {
                    $model->deleted_by = Auth::id();
                    $model->saveQuietly();
                }
            }
        });

        static::restoring(function ($model) {
            // Clear deleted_by when restoring
            if ($model->hasColumn('deleted_by')) {
                $model->deleted_by = null;
            }
        });
    }

    /**
     * Check if the model has a specific column.
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->fillable) || 
               array_key_exists($column, $this->attributes) ||
               \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), $column);
    }

    /**
     * Get the user who deleted this record.
     */
    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}

