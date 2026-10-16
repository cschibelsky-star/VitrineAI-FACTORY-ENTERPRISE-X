<?php

namespace App\Integrations\Modules;

use App\Core\Domain\Tenant\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

final class HmlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->set('auth.providers.users.model', User::class);
        $this->app['config']->set('session.driver', 'file');
        $this->app['config']->set('session.cookie', 'modulos_sprint2_hml_session');
        $this->app['config']->set('session.secure', getenv('HML_SECURE_COOKIE') !== '0');
        $this->app['config']->set('session.same_site', 'lax');
        $this->app['config']->set('cache.default', 'file');
        $this->app['config']->set('app.timezone', 'America/Sao_Paulo');
        $this->app['config']->set('logging.default', 'stderr');
        $this->loadViewsFrom(base_path('runtime/hml/views'), 'modulos-hml');
    }

    public function boot(): void
    {
        if (getenv('HML_SECURE_COOKIE') !== '0') {
            URL::forceScheme('https');
        }
    }
}
