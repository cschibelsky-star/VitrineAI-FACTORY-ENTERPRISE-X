<?php

namespace Tests\Core;

use App\Core\Application\Audit\AuditLogger;
use App\Core\Domain\Tenant\TenantContext;

class AuditLoggerTest extends TestCase
{
    /** @test */
    public function audit_logger_records_entry_with_tenant_context(): void
    {
        $tenant = $this->createTenant('Audit Tenant');
        TenantContext::set($tenant->ulid);

        $logger = app(AuditLogger::class);
        $logger->log(
            action: 'create',
            entityType: 'clientes',
            entityId: '01234',
            after: ['name' => 'Fulano'],
            moduleKey: 'cadastro',
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit'
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->ulid,
            'action' => 'create',
            'entity_type' => 'clientes',
            'module_key' => 'cadastro',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
