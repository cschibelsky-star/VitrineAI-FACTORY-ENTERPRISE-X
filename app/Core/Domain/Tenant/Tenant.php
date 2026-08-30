<?php

namespace App\Core\Domain\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasUlids;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function branding(): HasOne
    {
        return $this->hasOne(TenantBranding::class, 'tenant_id', 'ulid');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class, 'tenant_id', 'ulid');
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'tenant_id', 'ulid');
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(\App\Core\Domain\Module\TenantModule::class, 'tenant_id', 'ulid');
    }
}
