<?php

namespace App\Core\Domain\Tenant;

use App\Core\Infrastructure\Database\TenantScope;

/**
 * Adds multi-tenancy scoping to any Eloquent model.
 * The tenant_id is ALWAYS set from TenantContext — never from user payload or mass-assignment.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        // Unconditionally overwrite tenant_id on every create to prevent
        // injection via mass-assignment or forceFill.
        static::creating(function ($model) {
            $model->tenant_id = TenantContext::require();
        });
    }

    public function initializeBelongsToTenant(): void
    {
        // Ensure tenant_id is never accessible via mass-assignment.
        // It is set exclusively in the creating hook above.
        $this->guarded = array_unique(array_merge($this->guarded ?? [], ['tenant_id']));
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Core\Domain\Tenant\Tenant::class, 'tenant_id', 'ulid');
    }
}
