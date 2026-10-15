<?php

namespace App\Integrations\Modules;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use Closure;
use Illuminate\Http\Request;

final class EnsureActiveMembership
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_active && TenantUser::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('status', 'active')->whereHas('tenant', fn ($q) => $q->where('status', 'active'))->exists(), 403);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
