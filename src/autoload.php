<?php
/**
 * Autoloader PSR-4 minimal.
 *
 * Pourquoi : l'application doit pouvoir tourner même sans `composer install`
 * (hébergement mutualisé). Le namespace App\ correspond au dossier src/.
 * Si Composer est installé, vendor/autoload.php est chargé en plus (PHPMailer, PHPUnit).
 */
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$composer = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
}
