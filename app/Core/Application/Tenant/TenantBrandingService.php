<?php

namespace App\Core\Application\Tenant;

use App\Core\Domain\Tenant\TenantBranding;
use App\Core\Domain\Tenant\TenantContext;

class TenantBrandingService
{
    public function current(): ?TenantBranding
    {
        $tenantId = TenantContext::require();

        return TenantBranding::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function upsert(array $data): TenantBranding
    {
        $tenantId = TenantContext::require();

        $allowed = [
            'system_name',
            'logo_path',
            'favicon_path',
            'primary_color',
            'secondary_color',
            'accent_color',
            'document_footer',
            'settings',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));

        /** @var TenantBranding $branding */
        $branding = TenantBranding::withoutGlobalScopes()
            ->firstOrNew(['tenant_id' => $tenantId]);

        $branding->fill($filtered);
        $branding->tenant_id = $tenantId;
        $branding->save();

        return $branding;
    }
}
