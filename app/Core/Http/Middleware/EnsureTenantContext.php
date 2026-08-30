<?php

namespace App\Core\Http\Middleware;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the authenticated user and sets TenantContext.
 * The tenant_id is NEVER read from the request payload.
 *
 * Strategy: single-tenant-per-session via the authenticated user's active tenant.
 * If a user belongs to multiple tenants, a "current_tenant_id" session value may
 * be set after a tenant-selection step — still controlled server-side.
 */
class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenantId = $this->resolveTenantId($request, $user);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context could not be resolved.'], 403);
        }

        TenantContext::set($tenantId);

        return $next($request);
    }

    private function resolveTenantId(Request $request, $user): ?string
    {
        // Prefer the session-stored tenant (set during tenant-selection flow, server-side only).
        $sessionTenantId = $request->session()->get('current_tenant_id');

        if ($sessionTenantId) {
            // Validate the user actually belongs to that tenant.
            $valid = TenantUser::withoutGlobalScopes()
                ->where('tenant_id', $sessionTenantId)
                ->where('user_id', $user->getAuthIdentifier())
                ->exists();

            if ($valid) {
                return $sessionTenantId;
            }
        }

        // Fallback: use the first tenant the user belongs to.
        $tenantUser = TenantUser::withoutGlobalScopes()
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        return $tenantUser?->tenant_id;
    }
}
