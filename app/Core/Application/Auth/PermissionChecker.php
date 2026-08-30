<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

class PermissionChecker
{
    public function check(Authenticatable $user, string $permissionKey): bool
    {
        $tenantId = TenantContext::get();
        if ($tenantId === null) {
            return false;
        }

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
            ->contains('key', $permissionKey);
    }

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
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }
}
