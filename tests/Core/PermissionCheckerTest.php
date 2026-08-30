<?php

namespace Tests\Core;

use App\Core\Application\Auth\PermissionChecker;
use App\Core\Domain\Tenant\TenantContext;

/**
 * Test 6: Users without permission cannot access protected functionality.
 */
class PermissionCheckerTest extends TestCase
{
    /** @test */
    public function user_without_permission_is_denied(): void
    {
        $tenant = $this->createTenant('RBAC Tenant');
        $user   = $this->createUser();
        $this->attachUserToTenant($user, $tenant);

        TenantContext::set($tenant->ulid);

        /** @var PermissionChecker $checker */
        $checker = app(PermissionChecker::class);
        $this->assertFalse($checker->check($user, 'clientes.view'));
    }

    /** @test */
    public function user_with_assigned_permission_is_allowed(): void
    {
        $tenant = $this->createTenant('RBAC Tenant 2');
        $user   = $this->createUser();
        $tu     = $this->attachUserToTenant($user, $tenant);

        $role       = $this->createRole('admin');
        $permission = $this->createPermission('clientes.view');
        $this->giveRolePermission($role, $permission);
        $this->assignRoleToTenantUser($tu, $role);

        TenantContext::set($tenant->ulid);

        /** @var PermissionChecker $checker */
        $checker = app(PermissionChecker::class);
        $this->assertTrue($checker->check($user, 'clientes.view'));
    }

    /** @test */
    public function user_permission_is_scoped_to_current_tenant(): void
    {
        $tenantA = $this->createTenant('RBAC Tenant A');
        $tenantB = $this->createTenant('RBAC Tenant B');
        $user    = $this->createUser();

        // Assign permission only in tenantA.
        $tuA = $this->attachUserToTenant($user, $tenantA);
        $this->attachUserToTenant($user, $tenantB);

        $role       = $this->createRole('editor');
        $permission = $this->createPermission('posts.edit');
        $this->giveRolePermission($role, $permission);
        $this->assignRoleToTenantUser($tuA, $role);

        /** @var PermissionChecker $checker */
        $checker = app(PermissionChecker::class);

        TenantContext::set($tenantA->ulid);
        $this->assertTrue($checker->check($user, 'posts.edit'), 'Should be allowed in tenantA');

        TenantContext::set($tenantB->ulid);
        $this->assertFalse($checker->check($user, 'posts.edit'), 'Must NOT be allowed in tenantB');
    }
}
