<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Auth\Permission;
use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Central authorization service.
 * Checks if the authenticated user holds a given permission within the current tenant.
 * Integrate with Laravel Gate via CoreServiceProvider.
 */
class PermissionChecker
{
    /**
     * Check whether $user has $permissionSlug in the current tenant.
     * All checks are scoped to the authenticated tenant_id from TenantContext.
     */
    public function check(Authenticatable $user, string $permissionSlug): bool
    {
        $tenantId = TenantContext::get();

        if ($tenantId === null) {
            return false;
        }

        /** @var TenantUser|null $tenantUser */
        $tenantUser = TenantUser::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($tenantUser === null) {
            return false;
        }

        return $tenantUser
            ->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->contains('slug', $permissionSlug);
    }

    /**
     * Resolve all permission slugs the user holds in the current tenant.
     */
    public function permissions(Authenticatable $user): array
    {
        $tenantId = TenantContext::get();

        if ($tenantId === null) {
            return [];
        }

        $tenantUser = TenantUser::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($tenantUser === null) {
            return [];
        }

        return $tenantUser
            ->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();
    }
}
