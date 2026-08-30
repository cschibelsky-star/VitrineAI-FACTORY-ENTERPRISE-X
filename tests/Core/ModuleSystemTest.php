<?php

namespace Tests\Core;

use App\Core\Application\Module\ModuleGate;
use App\Core\Application\Module\ModuleRegistry;
use App\Core\Domain\Tenant\TenantContext;
use RuntimeException;

/**
 * Tests 5, 7, 8: Module system
 */
class ModuleSystemTest extends TestCase
{
    /** @test */
    public function disabled_module_is_blocked_by_module_gate(): void
    {
        $tenantA = $this->createTenant('Tenant Gate');
        $module  = $this->createModule('crm');
        $this->disableModuleForTenant($tenantA, $module);

        TenantContext::set($tenantA->ulid);

        /** @var ModuleGate $gate */
        $gate = app(ModuleGate::class);
        $this->assertFalse(
            $gate->isEnabled('crm'),
            'A disabled module must be blocked (not just hidden).'
        );
    }

    /** @test */
    public function enabled_module_passes_module_gate(): void
    {
        $tenantA = $this->createTenant('Tenant Gate 2');
        $module  = $this->createModule('agenda');
        $this->enableModuleForTenant($tenantA, $module);

        TenantContext::set($tenantA->ulid);

        /** @var ModuleGate $gate */
        $gate = app(ModuleGate::class);
        $this->assertTrue($gate->isEnabled('agenda'));
    }

    /** @test */
    public function module_with_invalid_manifest_is_not_loaded_silently(): void
    {
        $this->expectException(RuntimeException::class);

        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        $registry->register(base_path('tests/fixtures/modules/invalid/module.json'));
    }

    /** @test */
    public function missing_required_dependency_generates_explicit_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/requires module/');

        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        // Register agenda which requires cadastro — but don't register cadastro first.
        $registry->register(base_path('tests/fixtures/modules/agenda/module.json'));
        $registry->boot();
    }

    /** @test */
    public function valid_manifest_without_dependencies_loads_correctly(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        $registry->register(base_path('tests/fixtures/modules/cadastro/module.json'));
        $registry->boot();

        $this->assertTrue($registry->has('cadastro'));
        $this->assertEquals('1.0.0', $registry->get('cadastro')['version']);
    }

    /** @test */
    public function registering_both_modules_resolves_dependencies(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        $registry->register(base_path('tests/fixtures/modules/cadastro/module.json'));
        $registry->register(base_path('tests/fixtures/modules/agenda/module.json'));
        $registry->boot();

        $this->assertTrue($registry->has('agenda'));
    }
}
