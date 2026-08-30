<?php

namespace App\Core\Support\Contracts;

/**
 * Contract every commercial module must satisfy.
 * The ModuleRegistry reads module.json manifests; this interface defines
 * the runtime contract for the module service provider.
 */
interface ModuleContract
{
    /**
     * Unique slug matching the module.json "slug" field.
     */
    public function slug(): string;

    /**
     * Register module routes, bindings, etc.
     */
    public function boot(): void;
}
