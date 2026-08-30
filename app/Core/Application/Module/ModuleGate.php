<?php

namespace App\Core\Application\Module;

use App\Core\Domain\Module\TenantModule;
use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Support\Facades\Cache;

/**
 * Backend gate for module access.
 * A disabled module must be blocked at the HTTP layer, not only hidden in the UI.
 */
class ModuleGate
{
    public function __construct(private readonly ModuleRegistry $registry) {}

    /**
     * Check whether a module is enabled for the current tenant.
     * Returns false if there is no active tenant context.
     */
    public function isEnabled(string $moduleSlug): bool
    {
        $tenantId = TenantContext::get();

        if ($tenantId === null) {
            return false;
        }

        $cacheKey = "tenant:{$tenantId}:module:{$moduleSlug}:enabled";

        return Cache::remember($cacheKey, 60, function () use ($tenantId, $moduleSlug) {
            return TenantModule::withoutGlobalScopes()
                ->whereHas('module', fn ($q) => $q->where('slug', $moduleSlug))
                ->where('tenant_id', $tenantId)
                ->where('is_enabled', true)
                ->exists();
        });
    }

    /**
     * Flush the cached enabled state for a module/tenant pair.
     * Call this after enabling/disabling a module.
     */
    public function flushCache(string $tenantId, string $moduleSlug): void
    {
        Cache::forget("tenant:{$tenantId}:module:{$moduleSlug}:enabled");
    }

    /**
     * Enable a module for the current tenant.
     */
    public function enable(string $moduleSlug): void
    {
        $tenantId = TenantContext::require();
        $this->setEnabled($tenantId, $moduleSlug, true);
    }

    /**
     * Disable a module for the current tenant.
     */
    public function disable(string $moduleSlug): void
    {
        $tenantId = TenantContext::require();
        $this->setEnabled($tenantId, $moduleSlug, false);
    }

    private function setEnabled(string $tenantId, string $moduleSlug, bool $enabled): void
    {
        TenantModule::withoutGlobalScopes()
            ->whereHas('module', fn ($q) => $q->where('slug', $moduleSlug))
            ->where('tenant_id', $tenantId)
            ->update(['is_enabled' => $enabled]);

        $this->flushCache($tenantId, $moduleSlug);
    }
}
