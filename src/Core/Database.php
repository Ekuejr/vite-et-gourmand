<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Connexion unique à MySQL/MariaDB via PDO (patron Singleton).
 *
 * Choix de sécurité :
 *  - ERRMODE_EXCEPTION : toute erreur SQL lève une exception (pas d'échec silencieux).
 *  - EMULATE_PREPARES = false : vraies requêtes préparées côté serveur => protection
 *    contre l'injection SQL (les données ne sont jamais concaténées à la requête).
 *  - charset utf8mb4 : accents et emojis gérés, évite certaines attaques d'encodage.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $c = App::config('db');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** Permet d'injecter une connexion (utile pour les tests). */
    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
