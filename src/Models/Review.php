<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Avis clients : déposés sur une commande terminée, publiés après validation par un employé.
 */
final class Review extends Model
{
    public function validated(int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT a.note, a.commentaire, a.created_at, u.prenom, LEFT(u.nom, 1) AS initiale, m.titre AS menu_titre
             FROM avis a
             JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id
             JOIN commande c ON c.commande_id = a.commande_id
             JOIN menu m ON m.menu_id = c.menu_id
             WHERE a.statut = 'valide' ORDER BY a.created_at DESC LIMIT " . (int) $limit
        );
    }

    public function byStatus(string $status): array
    {
        return $this->fetchAll(
            'SELECT a.*, u.prenom, u.nom, u.email, c.numero_commande, m.titre AS menu_titre
             FROM avis a
             JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id
             JOIN commande c ON c.commande_id = a.commande_id
             JOIN menu m ON m.menu_id = c.menu_id
             WHERE a.statut = :s ORDER BY a.created_at DESC',
            ['s' => $status]
        );
    }

    public function create(int $orderId, int $userId, int $note, string $comment): void
    {
        $this->execute(
            'INSERT INTO avis (commande_id, utilisateur_id, note, commentaire) VALUES (:c, :u, :n, :com)',
            ['c' => $orderId, 'u' => $userId, 'n' => $note, 'com' => $comment]
        );
    }

    public function moderate(int $id, bool $approve): void
    {
        $this->execute('UPDATE avis SET statut = :s WHERE avis_id = :id', ['s' => $approve ? 'valide' : 'refuse', 'id' => $id]);
    }
}
