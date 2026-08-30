<?php

namespace Tests\Core;

use App\Core\Application\Auth\PermissionChecker;
use App\Core\Application\Module\ModuleGate;
use App\Core\Application\Navigation\NavigationBuilder;
use App\Core\Domain\Tenant\TenantContext;

/**
 * Test 9: Navigation only appears for active module + authorized user.
 */
class NavigationBuilderTest extends TestCase
{
    /** @test */
    public function navigation_excludes_disabled_module_items(): void
    {
        $tenant = $this->createTenant('Nav Tenant');
        $user   = $this->createUser();
        $this->attachUserToTenant($user, $tenant);

        // Module 'crm' is NOT enabled for this tenant.
        $this->createModule('crm');

        TenantContext::set($tenant->ulid);

        $builder = app(NavigationBuilder::class);
        $items   = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'module' => null, 'permission' => null],
            ['label' => 'Clientes', 'route' => 'crm.clientes', 'module' => 'crm', 'permission' => null],
        ];

        $nav = $builder->build($user, $items);
        $labels = array_column($nav, 'label');

        $this->assertContains('Dashboard', $labels);
        $this->assertNotContains('Clientes', $labels, 'CRM module is disabled — must not appear.');
    }

    /** @test */
    public function navigation_excludes_items_without_permission(): void
    {
        $tenant = $this->createTenant('Nav Tenant 2');
        $user   = $this->createUser();
        $this->attachUserToTenant($user, $tenant);

        $module = $this->createModule('financeiro');
        $this->enableModuleForTenant($tenant, $module);

        TenantContext::set($tenant->ulid);

        $builder = app(NavigationBuilder::class);
        $items   = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'module' => null, 'permission' => null],
            ['label' => 'Financeiro', 'route' => 'fin.index', 'module' => 'financeiro', 'permission' => 'financeiro.view'],
        ];

        $nav = $builder->build($user, $items);
        $labels = array_column($nav, 'label');

        $this->assertNotContains('Financeiro', $labels, 'User has no financeiro.view permission.');
    }

    /** @test */
    public function navigation_shows_item_for_active_module_and_authorized_user(): void
    {
        $tenant = $this->createTenant('Nav Tenant 3');
        $user   = $this->createUser();
        $tu     = $this->attachUserToTenant($user, $tenant);

        $module = $this->createModule('relatorios');
        $this->enableModuleForTenant($tenant, $module);

        $role       = $this->createRole('viewer');
        $permission = $this->createPermission('relatorios.view');
        $this->giveRolePermission($role, $permission);
        $this->assignRoleToTenantUser($tu, $role);

        TenantContext::set($tenant->ulid);

        $builder = app(NavigationBuilder::class);
        $items   = [
            ['label' => 'Relatórios', 'route' => 'rel.index', 'module' => 'relatorios', 'permission' => 'relatorios.view'],
        ];

        $nav = $builder->build($user, $items);
        $this->assertCount(1, $nav);
        $this->assertEquals('Relatórios', $nav[0]['label']);
    }
}
