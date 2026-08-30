<?php

namespace App\Core\Providers;

use App\Core\Application\Auth\PermissionChecker;
use App\Core\Application\Audit\AuditLogger;
use App\Core\Application\Module\ModuleGate;
use App\Core\Application\Module\ModuleRegistry;
use App\Core\Application\Navigation\NavigationBuilder;
use App\Core\Application\Tenant\TenantBrandingService;
use App\Core\Application\Tenant\TenantSettingService;
use App\Core\Domain\Tenant\TenantContext;
use App\Core\Http\Middleware\EnsureModuleEnabled;
use App\Core\Http\Middleware\EnsurePermission;
use App\Core\Http\Middleware\EnsureTenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/core.php', 'core');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(ModuleGate::class);
        $this->app->singleton(PermissionChecker::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(NavigationBuilder::class);
        $this->app->singleton(TenantBrandingService::class);
        $this->app->singleton(TenantSettingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations'));

        // Register Gate callback — any $ability that has a dot (module.action) is
        // forwarded to PermissionChecker so Policies can use it transparently.
        Gate::before(function ($user, $ability) {
            /** @var PermissionChecker $checker */
            $checker = $this->app->make(PermissionChecker::class);

            if (str_contains($ability, '.')) {
                return $checker->check($user, $ability) ?: null;
            }

            return null; // Let other gates/policies handle it.
        });

        // Register named middleware aliases so routes can use them.
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant', EnsureTenantContext::class);
        $router->aliasMiddleware('module', EnsureModuleEnabled::class);
        $router->aliasMiddleware('permission', EnsurePermission::class);
    }
}
