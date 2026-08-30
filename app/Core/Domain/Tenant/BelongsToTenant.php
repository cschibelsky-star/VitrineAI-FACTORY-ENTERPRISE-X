<?php

namespace App\Core\Domain\Tenant;

use App\Core\Infrastructure\Database\TenantScope;

/**
 * Adds multi-tenancy scoping to any Eloquent model.
 * The tenant_id is always filled from TenantContext — never from user payload.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = TenantContext::require();
            }
        });
    }

    public function initializeBelongsToTenant(): void
    {
        $this->fillable[] = 'tenant_id';
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Core\Domain\Tenant\Tenant::class, 'tenant_id', 'ulid');
    }
}
