<?php

namespace Tests\Core;

use App\Core\Domain\Auth\Permission;
use App\Core\Domain\Auth\Role;
use App\Core\Domain\Module\Module;
use App\Core\Domain\Module\TenantModule;
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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('key', 120)->unique();
            $table->string('name', 120);
            $table->json('included_modules')->nullable();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('slug', 120)->unique();
            $table->string('status', 30)->default('active');
            $table->string('plan_id', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_brandings', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('legal_name')->nullable();
            $table->string('document')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->text('print_footer')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('name', 200);
            $table->string('email', 200)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_users', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('user_id', 26);
            $table->string('status', 30)->default('active');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26)->nullable();
            $table->string('key', 120);
            $table->string('name', 120);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('key', 200)->unique();
            $table->string('module_key', 120);
            $table->string('name', 200);
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
            $table->string('key', 120)->unique();
            $table->string('name', 200);
            $table->string('version', 30)->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->json('dependencies')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26);
            $table->string('module_id', 26);
            $table->boolean('enabled')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'module_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('tenant_id', 26)->nullable();
            $table->string('user_id', 26)->nullable();
            $table->string('module')->nullable();
            $table->string('action');
            $table->string('entity');
            $table->string('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    protected function createTenant(string $name = 'Tenant A'): Tenant
    {
        return Tenant::create([
            'ulid' => (string) Str::ulid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'status' => 'active',
        ]);
    }

    protected function createUser(?string $email = null): User
    {
        return User::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Test User',
            'email' => $email ?? 'user-' . Str::random(6) . '@test.com',
            'password' => bcrypt('secret'),
        ]);
    }

    protected function attachUserToTenant(User $user, Tenant $tenant): TenantUser
    {
        TenantContext::set($tenant->ulid);
        return TenantUser::create([
            'ulid' => (string) Str::ulid(),
            'user_id' => $user->ulid,
            'status' => 'active',
        ]);
    }

    protected function createRole(string $key, ?Tenant $tenant = null): Role
    {
        return Role::create([
            'ulid' => (string) Str::ulid(),
            'tenant_id' => $tenant?->ulid,
            'name' => $key,
            'key' => $key,
        ]);
    }

    protected function createPermission(string $key): Permission
    {
        [$moduleKey] = explode('.', $key, 2) + ['core'];
        return Permission::create([
            'ulid' => (string) Str::ulid(),
            'name' => $key,
            'key' => $key,
            'module_key' => $moduleKey,
        ]);
    }

    protected function giveRolePermission(Role $role, Permission $permission): void
    {
        \DB::table('role_permissions')->insertOrIgnore([
            'role_id' => $role->ulid,
            'permission_id' => $permission->ulid,
        ]);
    }

    protected function assignRoleToTenantUser(TenantUser $tenantUser, Role $role): void
    {
        \DB::table('tenant_user_roles')->insertOrIgnore([
            'tenant_user_id' => $tenantUser->ulid,
            'role_id' => $role->ulid,
        ]);
    }

    protected function createModule(string $key, array $overrides = []): Module
    {
        return Module::create(array_merge([
            'ulid' => (string) Str::ulid(),
            'key' => $key,
            'name' => $key,
            'version' => '1.0.0',
            'is_active' => true,
            'dependencies' => ['requires' => [], 'optional_integrations' => []],
        ], $overrides));
    }

    protected function enableModuleForTenant(Tenant $tenant, Module $module): TenantModule
    {
        return TenantModule::create([
            'ulid' => (string) Str::ulid(),
            'tenant_id' => $tenant->ulid,
            'module_id' => $module->ulid,
            'enabled' => true,
            'activated_at' => now(),
        ]);
    }

    protected function disableModuleForTenant(Tenant $tenant, Module $module): TenantModule
    {
        return TenantModule::create([
            'ulid' => (string) Str::ulid(),
            'tenant_id' => $tenant->ulid,
            'module_id' => $module->ulid,
            'enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }
}
