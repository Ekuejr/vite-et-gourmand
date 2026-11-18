<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Commandes, historique de statuts (commande_suivi) et gestion du stock.
 */
final class Order extends Model
{
    public const STATUS_LABELS = [
        'en_attente'                 => 'En attente de validation',
        'accepte'                    => 'Acceptée',
        'en_preparation'             => 'En préparation',
        'en_cours_de_livraison'      => 'En cours de livraison',
        'livre'                      => 'Livrée',
        'en_attente_retour_materiel' => 'En attente du retour de matériel',
        'terminee'                   => 'Terminée',
        'annulee'                    => 'Annulée',
    ];

    private const SELECT = 'SELECT c.*, m.titre AS menu_titre, m.nombre_personne_minimum, m.prix_minimum, m.conditions,
                                   u.nom, u.prenom, u.email, u.telephone,
                                   a.avis_id, a.note AS avis_note, a.statut AS avis_statut
                            FROM commande c
                            JOIN menu m ON m.menu_id = c.menu_id
                            JOIN utilisateur u ON u.utilisateur_id = c.utilisateur_id
                            LEFT JOIN avis a ON a.commande_id = c.commande_id';

    /**
     * Enregistre une commande ET décrémente le stock du menu de façon atomique.
     * SELECT ... FOR UPDATE verrouille la ligne du menu : deux clients ne peuvent pas
     * prendre en même temps la dernière commande disponible (pas de stock négatif).
     */
    public function create(int $userId, array $menu, array $d, array $price): int
    {
        $this->db->beginTransaction();
        try {
            $stock = $this->fetch('SELECT quantite_restante FROM menu WHERE menu_id = :id AND actif = 1 FOR UPDATE', ['id' => $menu['menu_id']]);
            if ($stock === null || (int) $stock['quantite_restante'] < 1) {
                throw new \DomainException('Ce menu n\'est plus disponible à la commande (stock épuisé).');
            }
            $this->execute('UPDATE menu SET quantite_restante = quantite_restante - 1 WHERE menu_id = :id', ['id' => $menu['menu_id']]);

            $this->execute(
                'INSERT INTO commande (numero_commande, utilisateur_id, menu_id, date_prestation, heure_livraison, adresse_prestation,
                    code_postal_prestation, ville_prestation, nombre_personne, prix_menu, remise, distance_km, prix_livraison, prix_total)
                 VALUES (:num, :u, :m, :date, :heure, :adresse, :cp, :ville, :nb, :pm, :remise, :km, :pl, :total)',
                [
                    'num' => 'TMP-' . bin2hex(random_bytes(6)), 'u' => $userId, 'm' => $menu['menu_id'],
                    'date' => $d['date_prestation'], 'heure' => $d['heure_livraison'], 'adresse' => $d['adresse_prestation'],
                    'cp' => $d['code_postal_prestation'], 'ville' => $d['ville_prestation'], 'nb' => $d['nombre_personne'],
                    'pm' => $price['prix_menu'], 'remise' => $price['remise'], 'km' => $price['distance_km'],
                    'pl' => $price['prix_livraison'], 'total' => $price['prix_total'],
                ]
            );
            $id = (int) $this->db->lastInsertId();
            // Numéro lisible : VG-AAAA-0007
            $this->execute('UPDATE commande SET numero_commande = :n WHERE commande_id = :id', [
                'n' => sprintf('VG-%s-%04d', date('Y'), $id), 'id' => $id,
            ]);
            $this->addHistory($id, 'en_attente', $userId);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE c.commande_id = :id', ['id' => $id]);
    }

    /** Commande appartenant à l'utilisateur : empêche de consulter la commande d'un autre en changeant l'id dans l'URL (IDOR). */
    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE c.commande_id = :id AND c.utilisateur_id = :u', ['id' => $id, 'u' => $userId]);
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(self::SELECT . ' WHERE c.utilisateur_id = :u ORDER BY c.date_commande DESC', ['u' => $userId]);
    }

    /** Filtre du personnel : par statut et/ou par client (nom, prénom ou e-mail). */
    public function search(?string $status, ?string $client): array
    {
        $where = [];
        $params = [];
        if ($status !== null && $status !== '' && isset(self::STATUS_LABELS[$status])) {
            $where[] = 'c.statut = :statut';
            $params['statut'] = $status;
        }
        if ($client !== null && $client !== '') {
            $where[] = '(u.nom LIKE :c1 OR u.prenom LIKE :c2 OR u.email LIKE :c3)';
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $client) . '%';
            $params += ['c1' => $like, 'c2' => $like, 'c3' => $like];
        }
        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY c.date_prestation ASC, c.commande_id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function history(int $orderId): array
    {
        return $this->fetchAll(
            'SELECT s.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom FROM commande_suivi s
             LEFT JOIN utilisateur u ON u.utilisateur_id = s.modifie_par
             WHERE s.commande_id = :id ORDER BY s.date_modification, s.suivi_id',
            ['id' => $orderId]
        );
    }

    public function addHistory(int $orderId, string $status, ?int $by, ?string $comment = null): void
    {
        $this->execute(
            'INSERT INTO commande_suivi (commande_id, statut, modifie_par, commentaire) VALUES (:c, :s, :u, :com)',
            ['c' => $orderId, 's' => $status, 'u' => $by, 'com' => $comment]
        );
    }

    /** Modification par le client (le menu n'est pas modifiable). Condition statut = en_attente dans le WHERE : sécurité en profondeur. */
    public function updateByUser(int $id, int $userId, array $d, array $price): bool
    {
        return $this->execute(
            "UPDATE commande SET date_prestation = :date, heure_livraison = :heure, adresse_prestation = :adresse,
                code_postal_prestation = :cp, ville_prestation = :ville, nombre_personne = :nb, prix_menu = :pm,
                remise = :remise, distance_km = :km, prix_livraison = :pl, prix_total = :total
             WHERE commande_id = :id AND utilisateur_id = :u AND statut = 'en_attente'",
            [
                'date' => $d['date_prestation'], 'heure' => $d['heure_livraison'], 'adresse' => $d['adresse_prestation'],
                'cp' => $d['code_postal_prestation'], 'ville' => $d['ville_prestation'], 'nb' => $d['nombre_personne'],
                'pm' => $price['prix_menu'], 'remise' => $price['remise'], 'km' => $price['distance_km'],
                'pl' => $price['prix_livraison'], 'total' => $price['prix_total'], 'id' => $id, 'u' => $userId,
            ]
        ) > 0;
    }

    /** Annulation : le stock du menu est restitué, dans la même transaction. */
    public function cancel(int $id, int $by, ?string $contactMode = null, ?string $reason = null): void
    {
        $this->db->beginTransaction();
        try {
            $order = $this->fetch('SELECT menu_id, statut FROM commande WHERE commande_id = :id FOR UPDATE', ['id' => $id]);
            if ($order === null || $order['statut'] === 'annulee') {
                throw new \DomainException('Commande introuvable ou déjà annulée.');
            }
            $this->execute(
                "UPDATE commande SET statut = 'annulee', mode_contact_annulation = :mode, motif_annulation = :motif WHERE commande_id = :id",
                ['mode' => $contactMode, 'motif' => $reason, 'id' => $id]
            );
            $this->execute('UPDATE menu SET quantite_restante = quantite_restante + 1 WHERE menu_id = :m', ['m' => $order['menu_id']]);
            $comment = $reason ? sprintf('Contact client par %s - motif : %s', $contactMode === 'gsm' ? 'téléphone' : 'e-mail', $reason) : 'Annulée par le client';
            $this->addHistory($id, 'annulee', $by, $comment);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function changeStatus(int $id, string $status, int $by, ?bool $materialLent = null): void
    {
        $sql = 'UPDATE commande SET statut = :s';
        $params = ['s' => $status, 'id' => $id];
        if ($materialLent !== null) {
            $sql .= ', pret_materiel = :p';
            $params['p'] = $materialLent ? 1 : 0;
        }
        if ($status === 'terminee') {
            // Si la commande passait par l'attente de retour de matériel, celui-ci est restitué
            $sql .= ', restitution_materiel = pret_materiel';
        }
        $this->execute($sql . ' WHERE commande_id = :id', $params);
        $this->addHistory($id, $status, $by);
    }

    public function setMaterialLent(int $id, bool $lent): void
    {
        $this->execute('UPDATE commande SET pret_materiel = :p WHERE commande_id = :id', ['p' => $lent ? 1 : 0, 'id' => $id]);
    }

    /** Données de toutes les commandes non annulées : sert à (re)synchroniser MongoDB. */
    public function allForStats(): array
    {
        return $this->fetchAll(
            "SELECT c.commande_id, c.menu_id, m.titre AS menu_titre, c.nombre_personne, c.prix_total, c.date_commande, c.statut
             FROM commande c JOIN menu m ON m.menu_id = c.menu_id"
        );
    }
}
