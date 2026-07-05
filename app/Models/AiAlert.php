<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAlert extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}
