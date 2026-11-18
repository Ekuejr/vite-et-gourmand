<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentification par session.
 *
 * - On ne stocke en session QUE l'identifiant ; l'utilisateur est relu en base à chaque requête.
 *   Ainsi, un employé désactivé par l'administrateur perd l'accès immédiatement.
 * - session_regenerate_id(true) à la connexion : protège contre la fixation de session.
 */
final class Auth
{
    public const ROLE_USER = 'utilisateur';
    public const ROLE_EMPLOYEE = 'employe';
    public const ROLE_ADMIN = 'administrateur';

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        Session::set('user_id', (int) $user['utilisateur_id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id) {
                $user = (new User())->find((int) $id);
                // Compte supprimé ou désactivé => déconnexion forcée
                self::$user = ($user && (int) $user['actif'] === 1) ? $user : null;
                if (self::$user === null) {
                    Session::forget('user_id');
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['utilisateur_id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /** L'administrateur peut faire tout ce que fait un employé. */
    public static function isStaff(): bool
    {
        return in_array(self::role(), [self::ROLE_EMPLOYEE, self::ROLE_ADMIN], true);
    }
}
