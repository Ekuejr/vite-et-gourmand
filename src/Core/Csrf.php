<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection contre le Cross-Site Request Forgery.
 *
 * Un jeton aléatoire (32 octets, random_bytes = générateur cryptographique) est stocké
 * en session et ajouté en champ caché dans chaque formulaire. Le routeur refuse tout POST
 * dont le jeton ne correspond pas. hash_equals() compare en temps constant (pas d'attaque temporelle).
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function check(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}
