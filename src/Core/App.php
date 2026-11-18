<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Point central de configuration (lecture du tableau retourné par config/config.php).
 */
final class App
{
    private static array $config = [];

    public static function boot(array $config): void
    {
        self::$config = $config;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    public static function isProd(): bool
    {
        return self::config('env') === 'prod';
    }
}
