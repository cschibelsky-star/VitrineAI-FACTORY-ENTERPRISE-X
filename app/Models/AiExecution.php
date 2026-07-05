<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecution extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
