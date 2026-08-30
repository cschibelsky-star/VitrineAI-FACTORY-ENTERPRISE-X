<?php

namespace App\Core\Domain\Tenant;

use RuntimeException;

/**
 * Maintains the current tenant identity for the lifecycle of a request/job.
 * The tenant_id is never trusted from user input — it is set exclusively by
 * authenticated context (EnsureTenantContext middleware).
 */
class TenantContext
{
    private static ?string $currentTenantId = null;

    public static function set(string $tenantId): void
    {
        static::$currentTenantId = $tenantId;
    }

    public static function get(): ?string
    {
        return static::$currentTenantId;
    }

    public static function require(): string
    {
        if (static::$currentTenantId === null) {
            throw new RuntimeException('TenantContext is not set. Make sure EnsureTenantContext middleware is applied.');
        }

        return static::$currentTenantId;
    }

    public static function clear(): void
    {
        static::$currentTenantId = null;
    }

    public static function isSet(): bool
    {
        return static::$currentTenantId !== null;
    }

    /**
     * Execute a callback in the context of a specific tenant without altering
     * the global state permanently (useful for seeding/testing).
     */
    public static function runAs(string $tenantId, callable $callback): mixed
    {
        $previous = static::$currentTenantId;

        try {
            static::$currentTenantId = $tenantId;
            return $callback();
        } finally {
            static::$currentTenantId = $previous;
        }
    }
}
