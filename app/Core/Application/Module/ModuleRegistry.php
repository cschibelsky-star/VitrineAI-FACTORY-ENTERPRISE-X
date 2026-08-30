<?php

namespace App\Core\Application\Module;

use App\Core\Domain\Module\Module;
use App\Core\Domain\Module\TenantModule;
use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

/**
 * Manages module registration and validation.
 * Each module must provide a module.json manifest.
 */
class ModuleRegistry
{
    /** @var array<string, array> Loaded module manifests keyed by slug */
    private array $modules = [];

    private bool $booted = false;

    /**
     * Register a module from a manifest path.
     * Throws if the manifest is invalid or dependencies are missing.
     */
    public function register(string $manifestPath): void
    {
        if (!file_exists($manifestPath)) {
            throw new InvalidArgumentException("Module manifest not found: {$manifestPath}");
        }

        $raw = file_get_contents($manifestPath);
        $manifest = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in module manifest: {$manifestPath} — " . json_last_error_msg());
        }

        $this->validateManifest($manifest, $manifestPath);

        $slug = $manifest['slug'];

        $this->modules[$slug] = $manifest;
    }

    /**
     * Boot all registered modules — validates dependency graph.
     */
    public function boot(): void
    {
        foreach ($this->modules as $slug => $manifest) {
            $this->validateDependencies($slug);
        }

        $this->booted = true;
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $slug): bool
    {
        return isset($this->modules[$slug]);
    }

    public function get(string $slug): ?array
    {
        return $this->modules[$slug] ?? null;
    }

    private function validateManifest(mixed $manifest, string $path): void
    {
        if (!is_array($manifest)) {
            throw new RuntimeException("Module manifest must be a JSON object: {$path}");
        }

        $required = ['slug', 'name', 'version'];

        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                throw new RuntimeException("Module manifest missing required field '{$field}': {$path}");
            }
        }
    }

    private function validateDependencies(string $slug): void
    {
        $manifest = $this->modules[$slug];
        $requires = $manifest['requires'] ?? [];

        foreach ($requires as $dep) {
            if (!$this->has($dep)) {
                throw new RuntimeException(
                    "Module '{$slug}' requires module '{$dep}' which is not registered."
                );
            }
        }
    }
}
