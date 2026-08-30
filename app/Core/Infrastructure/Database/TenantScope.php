<?php

namespace App\Core\Infrastructure\Database;

use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope that automatically restricts queries to the current tenant.
 * Applied by the BelongsToTenant trait on every boot.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::get();

        if ($tenantId !== null) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
