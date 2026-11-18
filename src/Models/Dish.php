<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Plats (entrée, plat, dessert) et leurs allergènes.
 */
final class Dish extends Model
{
    public const TYPES = ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'];

    public function all(): array
    {
        return $this->fetchAll(
            "SELECT p.*, GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR ', ') AS allergenes
             FROM plat p
             LEFT JOIN plat_allergene pa ON pa.plat_id = p.plat_id
             LEFT JOIN allergene a ON a.allergene_id = pa.allergene_id
             GROUP BY p.plat_id ORDER BY FIELD(p.type, 'entree', 'plat', 'dessert'), p.titre"
        );
    }

    public function find(int $id): ?array
    {
        $dish = $this->fetch('SELECT * FROM plat WHERE plat_id = :id', ['id' => $id]);
        if ($dish) {
            $dish['allergene_ids'] = array_map('intval', array_column(
                $this->fetchAll('SELECT allergene_id FROM plat_allergene WHERE plat_id = :id', ['id' => $id]), 'allergene_id'
            ));
        }
        return $dish;
    }

    public function save(?int $id, array $d, array $allergenIds): int
    {
        $this->db->beginTransaction();
        try {
            $params = ['titre' => $d['titre'], 'type' => $d['type'], 'description' => $d['description']];
            if ($id === null) {
                $this->execute('INSERT INTO plat (titre, type, description) VALUES (:titre, :type, :description)', $params);
                $id = (int) $this->db->lastInsertId();
            } else {
                $params['id'] = $id;
                $this->execute('UPDATE plat SET titre = :titre, type = :type, description = :description WHERE plat_id = :id', $params);
                $this->execute('DELETE FROM plat_allergene WHERE plat_id = :id', ['id' => $id]);
            }
            $stmt = $this->db->prepare('INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (:p, :a)');
            foreach (array_unique($allergenIds) as $aid) {
                $stmt->execute(['p' => $id, 'a' => $aid]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM plat WHERE plat_id = :id', ['id' => $id]);
    }
}
