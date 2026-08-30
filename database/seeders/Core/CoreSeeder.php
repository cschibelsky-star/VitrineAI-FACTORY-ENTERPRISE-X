<?php

namespace Database\Seeders\Core;

use App\Core\Domain\Auth\Permission;
use App\Core\Domain\Auth\Role;
use App\Core\Domain\Module\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the foundational Core data:
 * - System roles (super-admin, admin, member)
 * - Core permissions
 * - Core module entry
 *
 * This seeder is idempotent — safe to run multiple times.
 */
class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedPermissions();
        $this->seedCoreModule();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true],
            ['name' => 'Admin',       'slug' => 'admin',       'is_system' => true],
            ['name' => 'Member',      'slug' => 'member',      'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                array_merge($role, ['ulid' => (string) Str::ulid()])
            );
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            // Tenant management
            ['slug' => 'tenants.view',   'name' => 'View Tenant',   'module' => 'core'],
            ['slug' => 'tenants.edit',   'name' => 'Edit Tenant',   'module' => 'core'],
            // User management
            ['slug' => 'users.view',     'name' => 'View Users',    'module' => 'core'],
            ['slug' => 'users.create',   'name' => 'Create Users',  'module' => 'core'],
            ['slug' => 'users.edit',     'name' => 'Edit Users',    'module' => 'core'],
            ['slug' => 'users.delete',   'name' => 'Delete Users',  'module' => 'core'],
            // Role/permission management
            ['slug' => 'roles.view',     'name' => 'View Roles',    'module' => 'core'],
            ['slug' => 'roles.manage',   'name' => 'Manage Roles',  'module' => 'core'],
            // Module management
            ['slug' => 'modules.view',   'name' => 'View Modules',  'module' => 'core'],
            ['slug' => 'modules.manage', 'name' => 'Manage Modules', 'module' => 'core'],
            // Branding
            ['slug' => 'branding.view',  'name' => 'View Branding', 'module' => 'core'],
            ['slug' => 'branding.edit',  'name' => 'Edit Branding', 'module' => 'core'],
            // Audit
            ['slug' => 'audit.view',     'name' => 'View Audit Log', 'module' => 'core'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                array_merge($perm, ['ulid' => (string) Str::ulid()])
            );
        }
    }

    private function seedCoreModule(): void
    {
        Module::firstOrCreate(
            ['slug' => 'core'],
            [
                'ulid'        => (string) Str::ulid(),
                'name'        => 'Core',
                'slug'        => 'core',
                'version'     => '1.0.0',
                'description' => 'Base module — always active.',
                'is_active'   => true,
                'requires'    => [],
            ]
        );
    }
}
