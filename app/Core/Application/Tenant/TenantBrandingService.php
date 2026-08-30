<?php

namespace App\Core\Application\Tenant;

use App\Core\Domain\Tenant\TenantBranding;
use App\Core\Domain\Tenant\TenantContext;

/**
 * Provides read/write access to the current tenant's branding settings.
 * All operations are scoped to the tenant resolved from TenantContext.
 */
class TenantBrandingService
{
    /**
     * Return the branding record for the current tenant, or null if not yet configured.
     */
    public function current(): ?TenantBranding
    {
        $tenantId = TenantContext::require();

        return TenantBranding::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Create or update branding for the current tenant.
     *
     * Accepted keys: logo_url, favicon_url, primary_color, secondary_color, print_footer
     */
    public function upsert(array $data): TenantBranding
    {
        $tenantId = TenantContext::require();

        $allowed = ['logo_url', 'favicon_url', 'primary_color', 'secondary_color', 'print_footer'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        /** @var TenantBranding $branding */
        $branding = TenantBranding::withoutGlobalScopes()
            ->firstOrNew(['tenant_id' => $tenantId]);

        $branding->fill($filtered);
        // Ensure tenant_id is always set from context.
        $branding->tenant_id = $tenantId;
        $branding->save();

        return $branding;
    }
}
