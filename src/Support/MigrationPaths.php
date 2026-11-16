<?php

namespace KhattakBits\Core\Support;

/**
 * Migration folder resolution for the D2 split (docs/platform/03 D2.3).
 *
 * Split ke baad har migration `central/` ya `tenant/` mein hai. Kaunse folders
 * `php artisan migrate` chalayega yeh `khattakbits.tenancy.enabled` tay karta hai:
 *
 * - `false` (CP, starter kit, tests): central **aur** tenant — ek hi DB mein poora schema
 * - `true` (multi-DB product): sirf central; tenant folders `tenants:migrate` ko jate hain
 *
 * `class_exists(\Stancl\Tenancy\Tenancy::class)` guard jaan boojh kar use nahi kiya:
 * CP mein package install hi nahi hota, is liye guard false rehta aur CP ka schema
 * adhoora ban jata (RK21, `11` §4.1.1).
 */
final class MigrationPaths
{
    /**
     * Base folder ke andar ke central/tenant folders, plus (optional) modules ke.
     *
     * @param  string  $base  migrations folder, e.g. `.../database/migrations`
     * @param  ModuleRegistry|null  $registry  modules ke `Database/Migrations/{central,tenant}` bhi shamil karein
     * @return array{central: list<string>, tenant: list<string>}
     */
    public static function folders(string $base, ?ModuleRegistry $registry = null): array
    {
        $moduleCentral = $registry instanceof ModuleRegistry
            ? array_values($registry->migrationPaths('central'))
            : [];

        $moduleTenant = $registry instanceof ModuleRegistry
            ? array_values($registry->migrationPaths('tenant'))
            : [];

        return [
            'central' => self::existing([self::join($base, 'central'), ...$moduleCentral]),
            'tenant' => self::existing([self::join($base, 'tenant'), ...$moduleTenant]),
        ];
    }

    /**
     * Woh paths jo `php artisan migrate` ko register karne hain.
     *
     * @param  array{central: list<string>, tenant: list<string>}  $folders
     * @return list<string>
     */
    public static function forMigrate(array $folders, bool $tenancyEnabled): array
    {
        if ($tenancyEnabled) {
            return $folders['central'];
        }

        return array_values(array_unique([...$folders['central'], ...$folders['tenant']]));
    }

    /**
     * @param  array<int, string>  $paths
     * @return list<string>
     */
    private static function existing(array $paths): array
    {
        return array_values(array_filter($paths, static fn (string $path): bool => is_dir($path)));
    }

    private static function join(string $base, string $folder): string
    {
        return rtrim($base, '/\\').DIRECTORY_SEPARATOR.$folder;
    }
}
