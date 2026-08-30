<?php

namespace App\Core\Application\Module;

use App\Core\Domain\Module\TenantModule;
use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Support\Facades\Cache;

class ModuleGate
{
    public function isEnabled(string $moduleKey): bool
    {
        $tenantId = TenantContext::get();
        if ($tenantId === null) {
            return false;
        }

        $cacheKey = "tenant:{$tenantId}:module:{$moduleKey}:enabled";

        return Cache::remember($cacheKey, 60, function () use ($tenantId, $moduleKey) {
            return TenantModule::withoutGlobalScopes()
                ->whereHas('module', fn ($query) => $query->where('key', $moduleKey))
                ->where('tenant_id', $tenantId)
                ->where('enabled', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();
        });
    }

    public function flushCache(string $tenantId, string $moduleKey): void
    {
        Cache::forget("tenant:{$tenantId}:module:{$moduleKey}:enabled");
    }

    public function enable(string $moduleKey): void
    {
        $this->setEnabled(TenantContext::require(), $moduleKey, true);
    }

    public function disable(string $moduleKey): void
    {
        $this->setEnabled(TenantContext::require(), $moduleKey, false);
    }

    private function setEnabled(string $tenantId, string $moduleKey, bool $enabled): void
    {
        $affected = TenantModule::withoutGlobalScopes()
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey))
            ->where('tenant_id', $tenantId)
            ->update([
                'enabled' => $enabled,
                'activated_at' => $enabled ? now() : null,
            ]);

        if ($affected === 0) {
            throw new \RuntimeException("Module '{$moduleKey}' is not available for tenant '{$tenantId}'.");
        }

        $this->flushCache($tenantId, $moduleKey);
    }
}
