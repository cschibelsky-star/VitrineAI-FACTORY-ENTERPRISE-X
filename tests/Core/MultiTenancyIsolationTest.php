<?php

namespace Tests\Core;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\User;

/**
 * Tests 1, 2, 3: Multi-tenant data isolation
 * Tests 4: tenant_id from payload is ignored
 */
class MultiTenancyIsolationTest extends TestCase
{
    /** @test */
    public function tenant_a_cannot_read_records_of_tenant_b(): void
    {
        [$tenantA, $tenantB] = $this->createTwoTenants();
        $userB = $this->createUser();
        $this->attachUserToTenant($userB, $tenantB);

        // TenantUser for B exists without the scope.
        TenantContext::set($tenantA->ulid);

        // From tenantA's perspective, tenantB's user should not be visible.
        $found = \App\Core\Domain\Tenant\TenantUser::where('user_id', $userB->ulid)->first();
        $this->assertNull($found, 'Tenant A must NOT see tenant B records.');
    }

    /** @test */
    public function tenant_a_cannot_update_records_of_tenant_b(): void
    {
        [$tenantA, $tenantB] = $this->createTwoTenants();
        $userB = $this->createUser();
        $tuB = $this->attachUserToTenant($userB, $tenantB);

        TenantContext::set($tenantA->ulid);

        // Attempt update via query — scoped to tenantA, so no rows matched.
        $affected = \App\Core\Domain\Tenant\TenantUser::where('ulid', $tuB->ulid)
            ->update(['status' => 'suspended']);

        $this->assertEquals(0, $affected, 'Tenant A must NOT be able to update tenant B records.');

        // Verify the record in tenantB is unchanged.
        TenantContext::clear();
        $tuB->refresh();
        $this->assertEquals('active', $tuB->status);
    }

    /** @test */
    public function tenant_a_cannot_delete_records_of_tenant_b(): void
    {
        [$tenantA, $tenantB] = $this->createTwoTenants();
        $userB = $this->createUser();
        $tuB = $this->attachUserToTenant($userB, $tenantB);

        TenantContext::set($tenantA->ulid);

        $deleted = \App\Core\Domain\Tenant\TenantUser::where('ulid', $tuB->ulid)->delete();
        $this->assertEquals(0, $deleted, 'Tenant A must NOT be able to delete tenant B records.');

        TenantContext::clear();
        $this->assertDatabaseHas('tenant_users', ['ulid' => $tuB->ulid]);
    }

    /** @test */
    public function tenant_id_from_request_payload_is_ignored(): void
    {
        [$tenantA, $tenantB] = $this->createTwoTenants();
        $user = $this->createUser();
        $this->attachUserToTenant($user, $tenantA);

        // Active context is tenantA.
        TenantContext::set($tenantA->ulid);

        // Simulate attacker injecting tenantB's id via mass-assignment / forceFill.
        // BelongsToTenant must override it unconditionally with the context value.
        $tu = \App\Core\Domain\Tenant\TenantUser::create([
            'ulid'      => (string) \Illuminate\Support\Str::ulid(),
            'user_id'   => $this->createUser()->ulid,
            'tenant_id' => $tenantB->ulid, // ← attacker-supplied, must be ignored
            'status'    => 'active',
        ]);

        $this->assertEquals(
            $tenantA->ulid,
            $tu->fresh()->tenant_id,
            'tenant_id must always be set from TenantContext, ignoring any payload value.'
        );

        // Also verify the injected tenantB value was not persisted.
        $this->assertNotEquals($tenantB->ulid, $tu->fresh()->tenant_id);
    }

    // ----- Helpers -----

    private function createTwoTenants(): array
    {
        return [$this->createTenant('Tenant A'), $this->createTenant('Tenant B')];
    }
}
