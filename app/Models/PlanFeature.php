<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $table = 'plan_features';

    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_name',
        'feature_value',
        'feature_type',
    ];

    protected $casts = [
        'feature_value' => 'array',
    ];

    /**
     * Get the plan that owns the feature.
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get the feature value.
     */
    public function getValue()
    {
        if ($this->feature_type === 'boolean') {
            return isset($this->feature_value['enabled']) 
                ? (bool) $this->feature_value['enabled'] 
                : false;
        }

        if ($this->feature_type === 'limit') {
            return isset($this->feature_value['limit']) 
                ? (int) $this->feature_value['limit'] 
                : 0;
        }

        return $this->feature_value;
    }

    /**
     * Check if this is a limit type feature.
     */
    public function isLimit(): bool
    {
        return $this->feature_type === 'limit';
    }

    /**
     * Check if this is a boolean type feature.
     */
    public function isBoolean(): bool
    {
        return $this->feature_type === 'boolean';
    }

    /**
     * Check if this is a json type feature.
     */
    public function isJson(): bool
    {
        return $this->feature_type === 'json';
    }

    /**
     * Set feature value for limit type.
     */
    public function setLimitValue(int $limit)
    {
        $this->feature_value = ['limit' => $limit];
        return $this;
    }

    /**
     * Set feature value for boolean type.
     */
    public function setBooleanValue(bool $enabled)
    {
        $this->feature_value = ['enabled' => $enabled];
        return $this;
    }

    /**
     * Set feature value for json type.
     */
    public function setJsonValue(array $value)
    {
        $this->feature_value = $value;
        return $this;
    }
}

