<?php

namespace App\Core\Application\Tenant;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantSetting;

/**
 * Key/value configuration store per tenant.
 * Values are stored as JSON so any scalar or array can be persisted.
 */
class TenantSettingService
{
    /**
     * Get a setting value for the current tenant.
     * Returns $default if the key is not set.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $tenantId = TenantContext::require();

        $setting = TenantSetting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    /**
     * Set (upsert) a setting for the current tenant.
     */
    public function set(string $key, mixed $value): void
    {
        $tenantId = TenantContext::require();

        TenantSetting::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Delete a setting key for the current tenant.
     */
    public function forget(string $key): void
    {
        $tenantId = TenantContext::require();

        TenantSetting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();
    }

    /**
     * Return all settings for the current tenant as a flat key/value array.
     */
    public function all(): array
    {
        $tenantId = TenantContext::require();

        return TenantSetting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->all();
    }
}
