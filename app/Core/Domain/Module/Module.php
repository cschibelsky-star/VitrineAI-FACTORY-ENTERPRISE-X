<?php

namespace App\Core\Domain\Module;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasUlids;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'requires' => 'array',
        'optional_integrations' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class, 'module_id', 'ulid');
    }
}
