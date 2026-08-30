<?php

namespace Tests\Core;

use App\Core\Domain\Auth\Permission;
use App\Core\Domain\Auth\Role;
use App\Core\Domain\Audit\AuditLog;
use App\Core\Domain\Module\Module;
use App\Core\Domain\Module\TenantModule;
use App\Core\Domain\Plan\Plan;
use App\Core\Domain\Tenant\Tenant;
use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use App\Core\Domain\Tenant\User;
use App\Core\Providers\CoreServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createCoreTables();
    }

    private function createCoreTables(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('slug', 120)->unique();
            $table->string('email', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 30)->default('active');
            $table->string('plan_id', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('email', 200)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status', 30)->default('active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_users', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('user_id', 26);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('module', 120)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->string('role_id', 26);
            $table->string('permission_id', 26);
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('tenant_user_roles', function (Blueprint $table) {
            $table->string('tenant_user_id', 26);
            $table->string('role_id', 26);
            $table->primary(['tenant_user_id', 'role_id']);
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('slug', 120)->unique();
            $table->string('version', 30)->default('1.0.0');
            $table->json('requires')->nullable();
            $table->json('optional_integrations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('module_id', 26);
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'module_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26)->nullable();
            $table->string('user_id', 26)->nullable();
            $table->string('module', 120)->nullable();
            $table->string('action', 120);
            $table->string('entity', 200);
            $table->string('entity_id', 36)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    // ----- Helpers -----

    protected function createTenant(string $name = 'Tenant A'): Tenant
    {
        return Tenant::create([
            'ulid'  => (string) Str::ulid(),
            'name'  => $name,
            'slug'  => Str::slug($name) . '-' . Str::random(4),
            'status' => 'active',
        ]);
    }

    protected function createUser(string $email = null): User
    {
        return User::create([
            'ulid'     => (string) Str::ulid(),
            'name'     => 'Test User',
            'email'    => $email ?? 'user-' . Str::random(6) . '@test.com',
            'password' => bcrypt('secret'),
        ]);
    }

    protected function attachUserToTenant(User $user, Tenant $tenant): TenantUser
    {
        return TenantUser::create([
            'ulid'      => (string) Str::ulid(),
            'tenant_id' => $tenant->ulid,
            'user_id'   => $user->ulid,
        ]);
    }

    protected function createRole(string $slug): Role
    {
        return Role::create([
            'ulid' => (string) Str::ulid(),
            'name' => $slug,
            'slug' => $slug,
        ]);
    }

    protected function createPermission(string $slug): Permission
    {
        return Permission::create([
            'ulid' => (string) Str::ulid(),
            'name' => $slug,
            'slug' => $slug,
        ]);
    }

    protected function giveRolePermission(Role $role, Permission $permission): void
    {
        \DB::table('role_permissions')->insertOrIgnore([
            'role_id'       => $role->ulid,
            'permission_id' => $permission->ulid,
        ]);
    }

    protected function assignRoleToTenantUser(TenantUser $tenantUser, Role $role): void
    {
        \DB::table('tenant_user_roles')->insertOrIgnore([
            'tenant_user_id' => $tenantUser->ulid,
            'role_id'        => $role->ulid,
        ]);
    }

    protected function createModule(string $slug, array $overrides = []): Module
    {
        return Module::create(array_merge([
            'ulid'      => (string) Str::ulid(),
            'name'      => $slug,
            'slug'      => $slug,
            'version'   => '1.0.0',
            'is_active' => true,
        ], $overrides));
    }

    protected function enableModuleForTenant(Tenant $tenant, Module $module): TenantModule
    {
        return TenantModule::create([
            'ulid'       => (string) Str::ulid(),
            'tenant_id'  => $tenant->ulid,
            'module_id'  => $module->ulid,
            'is_enabled' => true,
        ]);
    }

    protected function disableModuleForTenant(Tenant $tenant, Module $module): TenantModule
    {
        return TenantModule::create([
            'ulid'       => (string) Str::ulid(),
            'tenant_id'  => $tenant->ulid,
            'module_id'  => $module->ulid,
            'is_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }
}
