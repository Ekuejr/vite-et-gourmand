<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestion de la session PHP avec des cookies durcis.
 *  - HttpOnly : le cookie n'est pas lisible en JavaScript (limite le vol de session par XSS).
 *  - SameSite=Lax : le cookie n'est pas envoyé par les requêtes POST d'un autre site (aide contre le CSRF).
 *  - Secure (en prod) : cookie transmis uniquement en HTTPS.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('VGSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => App::isProd(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Message affiché une seule fois (après une redirection). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    /** Mémorise les anciennes saisies pour re-remplir un formulaire en erreur. */
    public static function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['_old'][$key] ?? $default);
    }

    public static function setOld(array $data): void
    {
        unset($data['password'], $data['password_confirm'], $data['_csrf']);
        $_SESSION['_old'] = $data;
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
