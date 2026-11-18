<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validation côté serveur.
 *
 * Règle d'or : la validation HTML/JS du navigateur n'est qu'un confort pour l'utilisateur ;
 * elle se contourne facilement (outils de développement, curl). Seule la validation
 * côté serveur protège réellement l'application.
 */
final class Validator
{
    /** 10 caractères min., au moins 1 minuscule, 1 majuscule, 1 chiffre, 1 caractère spécial (cahier des charges). */
    public const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}$/';

    public static function isStrongPassword(string $password): bool
    {
        return (bool) preg_match(self::PASSWORD_REGEX, $password);
    }

    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 180;
    }

    /** Numéro de mobile français : 06/07 ou +336/+337, espaces/points tolérés. */
    public static function isMobile(string $phone): bool
    {
        $clean = preg_replace('/[\s.\-]/', '', $phone);
        return (bool) preg_match('/^(?:0|\+33)[67]\d{8}$/', $clean);
    }

    public static function length(string $value, int $min, int $max): bool
    {
        $len = mb_strlen($value);
        return $len >= $min && $len <= $max;
    }

    public static function isDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    public static function isTime(string $time): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
    }

    public static function isPostalCode(string $cp): bool
    {
        return (bool) preg_match('/^\d{5}$/', $cp);
    }
}
