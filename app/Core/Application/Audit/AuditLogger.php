<?php

namespace App\Core\Application\Audit;

use App\Core\Domain\Audit\AuditLog;
use App\Core\Domain\Tenant\TenantContext;

/**
 * Records audit events for all significant actions in the system.
 */
class AuditLogger
{
    public function log(
        string $action,
        string $entity,
        ?string $entityId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $module = null,
        ?string $userId = null,
        ?string $ip = null
    ): void {
        AuditLog::withoutGlobalScopes()->create([
            'tenant_id' => TenantContext::get(),
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }
}
