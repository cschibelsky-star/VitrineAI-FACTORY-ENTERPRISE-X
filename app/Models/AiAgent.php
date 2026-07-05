<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(AiQueue::class, 'ai_agent_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AiExecution::class, 'ai_agent_id');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(AiConsumption::class, 'ai_agent_id');
    }
}
