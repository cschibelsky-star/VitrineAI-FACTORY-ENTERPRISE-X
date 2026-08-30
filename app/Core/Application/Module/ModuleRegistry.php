<?php

namespace App\Core\Application\Module;

use InvalidArgumentException;
use RuntimeException;

/**
 * Loads and validates the approved module.json contract.
 */
class ModuleRegistry
{
    /** @var array<string, array> */
    private array $modules = [];

    public function register(string $manifestPath): void
    {
        if (!is_file($manifestPath)) {
            throw new InvalidArgumentException("Module manifest not found: {$manifestPath}");
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in module manifest: {$manifestPath} - " . json_last_error_msg());
        }

        $this->validateManifest($manifest, $manifestPath);
        $key = $manifest['key'];

        if (isset($this->modules[$key])) {
            throw new RuntimeException("Duplicate module key '{$key}'.");
        }

        $this->modules[$key] = $manifest;
    }

    public function boot(): void
    {
        foreach (array_keys($this->modules) as $key) {
            $this->validateDependencies($key);
            $this->validateListenedEvents($key);
        }
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $key): bool
    {
        return isset($this->modules[$key]);
    }

    public function get(string $key): ?array
    {
        return $this->modules[$key] ?? null;
    }

    private function validateManifest(mixed $manifest, string $path): void
    {
        if (!is_array($manifest)) {
            throw new RuntimeException("Module manifest must be a JSON object: {$path}");
        }

        foreach (['key', 'name', 'version', 'dependencies', 'permissions', 'routes', 'events', 'migrations', 'navigation', 'configuration', 'service_provider'] as $field) {
            if (!array_key_exists($field, $manifest)) {
                throw new RuntimeException("Module manifest missing required field '{$field}': {$path}");
            }
        }

        $key = (string) $manifest['key'];
        if ($key === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new RuntimeException("Module key must be unique snake_case: {$path}");
        }

        if (!is_array($manifest['dependencies'])
            || !is_array($manifest['dependencies']['requires'] ?? null)
            || !is_array($manifest['dependencies']['optional_integrations'] ?? null)) {
            throw new RuntimeException("Module dependencies must define requires and optional_integrations arrays: {$path}");
        }

        if (!is_array($manifest['permissions'])) {
            throw new RuntimeException("Module permissions must be an array: {$path}");
        }

        foreach ($manifest['permissions'] as $permission) {
            $permissionKey = is_array($permission) ? (string) ($permission['key'] ?? '') : '';
            if ($permissionKey === '' || !str_starts_with($permissionKey, $key . '.')) {
                throw new RuntimeException("Permission '{$permissionKey}' must be prefixed with '{$key}.'.");
            }
        }

        if (!is_array($manifest['routes']) || !is_array($manifest['events'])
            || !is_array($manifest['events']['emits'] ?? null)
            || !is_array($manifest['events']['listens'] ?? null)
            || !is_array($manifest['navigation']) || !is_array($manifest['configuration'])) {
            throw new RuntimeException("Module manifest contains invalid contract sections: {$path}");
        }

        if (!is_string($manifest['migrations']) || $manifest['migrations'] === ''
            || !is_string($manifest['service_provider']) || $manifest['service_provider'] === '') {
            throw new RuntimeException("Module migrations and service_provider must be non-empty strings: {$path}");
        }
    }

    private function validateDependencies(string $key): void
    {
        $requires = $this->modules[$key]['dependencies']['requires'];

        foreach ($requires as $dependencyKey) {
            if (!$this->has((string) $dependencyKey)) {
                throw new RuntimeException("Module '{$key}' requires module '{$dependencyKey}' which is not registered.");
            }
        }
    }

    private function validateListenedEvents(string $key): void
    {
        $manifest = $this->modules[$key];
        $requires = $manifest['dependencies']['requires'];

        $emittedByRequiredModules = [];
        foreach ($requires as $dependencyKey) {
            $dependency = $this->modules[(string) $dependencyKey] ?? null;
            if ($dependency !== null) {
                $emittedByRequiredModules = array_merge($emittedByRequiredModules, $dependency['events']['emits'] ?? []);
            }
        }

        foreach ($manifest['events']['listens'] as $event) {
            if (!in_array($event, $emittedByRequiredModules, true)) {
                throw new RuntimeException("Module '{$key}' listens to event '{$event}' without a declared required dependency that emits it.");
            }
        }
    }
}
