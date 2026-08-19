<?php

namespace App\Support;

/**
 * Holds the tenant (company) resolved for the current request lifecycle.
 *
 * Set by EnsureCompanyContext middleware after authentication. A null value
 * means "no tenant restriction" — used for guests, console commands, and
 * super admins (company_id nullable) who are meant to operate across tenants.
 * Static state is safe here because the app runs per-request (PHP-FPM /
 * artisan serve, no Octane), so nothing persists between requests.
 */
class CurrentCompany
{
    protected static ?int $id = null;

    public static function set(?int $id): void
    {
        static::$id = $id;
    }

    public static function id(): ?int
    {
        return static::$id;
    }

    public static function check(): bool
    {
        return static::$id !== null;
    }

    public static function clear(): void
    {
        static::$id = null;
    }
}
