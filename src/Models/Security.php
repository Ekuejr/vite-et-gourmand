<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Données liées à la sécurité : jetons de réinitialisation et tentatives de connexion échouées.
 */
final class Security extends Model
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_MINUTES = 15;

    /**
     * Crée un jeton de réinitialisation. On renvoie le jeton EN CLAIR (envoyé par mail)
     * mais on ne stocke que son empreinte SHA-256 : une fuite de la base ne permet pas de l'utiliser.
     */
    public function createResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->execute('DELETE FROM password_reset WHERE utilisateur_id = :u', ['u' => $userId]);
        $this->execute(
            'INSERT INTO password_reset (utilisateur_id, token_hash, expires_at) VALUES (:u, :h, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            ['u' => $userId, 'h' => hash('sha256', $token)]
        );
        return $token;
    }

    /** Renvoie l'utilisateur associé à un jeton valide (non expiré, non utilisé). */
    public function findValidReset(string $token): ?array
    {
        return $this->fetch(
            'SELECT * FROM password_reset WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()',
            ['h' => hash('sha256', $token)]
        );
    }

    public function markResetUsed(int $resetId): void
    {
        $this->execute('UPDATE password_reset SET used_at = NOW() WHERE reset_id = :id', ['id' => $resetId]);
    }

    public function recordFailedLogin(string $ip, string $email): void
    {
        $this->execute('INSERT INTO connexion_echec (ip, email) VALUES (:ip, :email)', ['ip' => $ip, 'email' => mb_substr($email, 0, 180)]);
    }

    /** Anti force brute : plus de 5 échecs en 15 minutes depuis la même IP => blocage temporaire. */
    public function isLocked(string $ip): bool
    {
        $row = $this->fetch(
            'SELECT COUNT(*) AS n FROM connexion_echec WHERE ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)',
            ['ip' => $ip]
        );
        return (int) $row['n'] >= self::MAX_ATTEMPTS;
    }

    public function clearFailedLogins(string $ip): void
    {
        $this->execute('DELETE FROM connexion_echec WHERE ip = :ip', ['ip' => $ip]);
    }
}
