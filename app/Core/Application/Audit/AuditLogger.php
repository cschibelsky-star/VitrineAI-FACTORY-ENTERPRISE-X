<?php

namespace App\Core\Application\Audit;

use App\Core\Domain\Audit\AuditLog;
use App\Core\Domain\Tenant\TenantContext;

class AuditLogger
{
    public function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $before = [],
        array $after = [],
        ?string $moduleKey = null,
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        AuditLog::withoutGlobalScopes()->create([
            'tenant_id' => TenantContext::get(),
            'user_id' => $userId,
            'module_key' => $moduleKey,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
