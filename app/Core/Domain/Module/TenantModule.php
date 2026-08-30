<?php

namespace App\Core\Domain\Module;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    use HasUlids;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Domain\Tenant\Tenant::class, 'tenant_id', 'ulid');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'ulid');
    }
}
