<?php

use App\Core\Domain\Tenant\Tenant;
use App\Core\Domain\Tenant\TenantContext;
use App\Core\Domain\Tenant\TenantUser;
use App\Core\Domain\Tenant\User;
use App\Core\Domain\Auth\Role;
use App\Core\Domain\Auth\Permission;
use App\Core\Domain\Module\Module;
use App\Core\Domain\Module\TenantModule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$app = require __DIR__ . '/bootstrap.php';
$app->make(Kernel::class)->bootstrap();
if (!$app->environment('homologation') || config('database.default') !== 'sqlite'
    || config('database.connections.sqlite.database') !== '/data/database.sqlite') {
    throw new RuntimeException('Refusing to initialize an unisolated database.');
}
$paths = array_map(fn ($path) => str_replace(base_path() . '/', '', $path), glob(base_path('database/migrations/2026_08_30_*.php')));
foreach (['Cadastro', 'Agenda', 'Atendimento'] as $module) {
    $paths[] = 'Modules/' . $module . '/database/migrations';
}
if (Artisan::call('migrate', ['--path' => $paths, '--force' => true]) !== 0) {
    throw new RuntimeException('Isolated HML migrations failed.');
}
// A separate account and password are generated on this volume. Nothing is copied from production.
$passwordFile = '/data/operator-password';
if (!is_file($passwordFile)) {
    file_put_contents($passwordFile, getenv('HML_ADMIN_PASSWORD') ?: bin2hex(random_bytes(32)));
    chmod($passwordFile, 0600);
}
DB::transaction(function () use ($passwordFile) {
    $tenant = Tenant::firstOrCreate(['slug' => 'modulos-sprint2-hml'], ['name' => 'Módulos Pro — Homologação', 'status' => 'active']);
    TenantContext::set($tenant->ulid);
    $user = User::firstOrCreate(['email' => getenv('HML_ADMIN_EMAIL') ?: 'vitrineiapro@gmail.com'],
        ['name' => 'Operador HML', 'password' => file_get_contents($passwordFile), 'is_active' => true]);
    $member = TenantUser::firstOrCreate(['user_id' => $user->ulid], ['status' => 'active']);
    $role = Role::firstOrCreate(['tenant_id' => $tenant->ulid, 'key' => 'hml-operator'], ['name' => 'Operador de homologação']);
    DB::table('tenant_user_roles')->insertOrIgnore(['tenant_user_id' => $member->ulid, 'role_id' => $role->ulid]);
    foreach (['Cadastro' => 'cadastro', 'Agenda' => 'agenda', 'Atendimento' => 'atendimento'] as $directory => $key) {
        $manifest = json_decode(file_get_contents(base_path('Modules/' . $directory . '/module.json')), true, flags: JSON_THROW_ON_ERROR);
        $module = Module::firstOrCreate(['key' => $key], ['name' => $manifest['name'], 'version' => $manifest['version'],
            'is_active' => true, 'dependencies' => $manifest['dependencies']]);
        TenantModule::firstOrCreate(['tenant_id' => $tenant->ulid, 'module_id' => $module->ulid], ['enabled' => true, 'activated_at' => now()]);
        foreach ($manifest['permissions'] as $definition) {
            $permission = Permission::firstOrCreate(['key' => $definition['key']], ['name' => $definition['name'], 'module_key' => $key]);
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $role->ulid, 'permission_id' => $permission->ulid]);
        }
    }
});
TenantContext::clear();
fwrite(STDOUT, "Isolated HML initialized. Credentials remain server-side.\n");
