<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module registration
    |--------------------------------------------------------------------------
    | Paths to module.json manifests that are auto-loaded on boot.
    | Commercial module providers add their path here or call
    | ModuleRegistry::register() in their own ServiceProvider.
    */
    'modules' => [
        // Example: base_path('modules/cadastro/module.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('CORE_AUDIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    | Strategy for resolving the current tenant from an authenticated session.
    | 'session' — reads 'current_tenant_id' from the session (default).
    | 'subdomain' — future option to resolve by request host prefix.
    */
    'tenancy' => [
        'strategy' => env('CORE_TENANCY_STRATEGY', 'session'),
    ],
];
