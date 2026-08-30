<?php

namespace Tests\Core;

use App\Core\Application\Module\ModuleGate;
use App\Core\Application\Module\ModuleRegistry;
use App\Core\Domain\Tenant\TenantContext;
use RuntimeException;

class ModuleSystemTest extends TestCase
{
    /** @test */
    public function disabled_module_is_blocked_by_module_gate(): void
    {
        $tenantA = $this->createTenant('Tenant Gate');
        $module = $this->createModule('crm');
        $this->disableModuleForTenant($tenantA, $module);

        TenantContext::set($tenantA->ulid);
        $gate = app(ModuleGate::class);

        $this->assertFalse($gate->isEnabled('crm'));
    }

    /** @test */
    public function enabled_module_passes_module_gate(): void
    {
        $tenantA = $this->createTenant('Tenant Gate 2');
        $module = $this->createModule('agenda');
        $this->enableModuleForTenant($tenantA, $module);

        TenantContext::set($tenantA->ulid);
        $gate = app(ModuleGate::class);

        $this->assertTrue($gate->isEnabled('agenda'));
    }

    /** @test */
    public function module_with_invalid_manifest_is_not_loaded_silently(): void
    {
        $this->expectException(RuntimeException::class);
        app(ModuleRegistry::class)->register($this->fixture('invalid'));
    }

    /** @test */
    public function missing_required_dependency_generates_explicit_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/requires module/');

        $registry = app(ModuleRegistry::class);
        $registry->register($this->fixture('agenda'));
        $registry->boot();
    }

    /** @test */
    public function valid_manifest_without_dependencies_loads_correctly(): void
    {
        $registry = app(ModuleRegistry::class);
        $registry->register($this->fixture('cadastro'));
        $registry->boot();

        $this->assertTrue($registry->has('cadastro'));
        $this->assertEquals('1.0.0', $registry->get('cadastro')['version']);
    }

    /** @test */
    public function registering_both_modules_resolves_dependencies(): void
    {
        $registry = app(ModuleRegistry::class);
        $registry->register($this->fixture('cadastro'));
        $registry->register($this->fixture('agenda'));
        $registry->boot();

        $this->assertTrue($registry->has('agenda'));
    }

    private function fixture(string $module): string
    {
        return dirname(__DIR__, 2) . "/tests/fixtures/modules/{$module}/module.json";
    }
}
