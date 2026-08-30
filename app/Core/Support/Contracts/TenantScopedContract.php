<?php

namespace App\Core\Support\Contracts;

/**
 * Contract for any Eloquent model that is scoped to a tenant.
 * Modules must use the BelongsToTenant trait to satisfy this contract.
 */
interface TenantScopedContract
{
    public function tenant();
}
