<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Accès aux menus : liste filtrée (vue globale), détail complet, gestion par le personnel.
 */
final class Menu extends Model
{
    /**
     * Liste des menus actifs avec filtres optionnels.
     * La requête est construite dynamiquement, MAIS uniquement avec des fragments SQL écrits
     * dans le code ; les valeurs saisies passent toujours par des paramètres liés (:prix_max...).
     */
    public function search(array $f = []): array
    {
        $where = ['m.actif = 1'];
        $params = [];

        if (isset($f['prix_max']) && $f['prix_max'] !== null) {
            $where[] = 'm.prix_minimum <= :prix_max';
            $params['prix_max'] = $f['prix_max'];
        }
        if (isset($f['prix_min']) && $f['prix_min'] !== null) {
            $where[] = 'm.prix_minimum >= :prix_min';
            $params['prix_min'] = $f['prix_min'];
        }
        if (!empty($f['theme'])) {
            $where[] = 'm.theme_id = :theme';
            $params['theme'] = $f['theme'];
        }
        if (!empty($f['regime'])) {
            $where[] = 'm.regime_id = :regime';
            $params['regime'] = $f['regime'];
        }
        if (!empty($f['personnes'])) {
            // Menus commandables pour un groupe de N personnes : minimum du menu <= N
            $where[] = 'm.nombre_personne_minimum <= :personnes';
            $params['personnes'] = $f['personnes'];
        }

        $sql = 'SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum, m.prix_minimum, m.quantite_restante,
                       t.libelle AS theme, r.libelle AS regime,
                       (SELECT chemin FROM menu_image i WHERE i.menu_id = m.menu_id ORDER BY ordre LIMIT 1) AS image,
                       (SELECT texte_alt FROM menu_image i WHERE i.menu_id = m.menu_id ORDER BY ordre LIMIT 1) AS image_alt
                FROM menu m
                JOIN theme t ON t.theme_id = m.theme_id
                JOIN regime r ON r.regime_id = m.regime_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY m.prix_minimum ASC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id, bool $onlyActive = true): ?array
    {
        $menu = $this->fetch(
            'SELECT m.*, t.libelle AS theme, r.libelle AS regime FROM menu m
             JOIN theme t ON t.theme_id = m.theme_id JOIN regime r ON r.regime_id = m.regime_id
             WHERE m.menu_id = :id' . ($onlyActive ? ' AND m.actif = 1' : ''),
            ['id' => $id]
        );
        if ($menu === null) {
            return null;
        }
        $menu['images'] = $this->fetchAll('SELECT * FROM menu_image WHERE menu_id = :id ORDER BY ordre', ['id' => $id]);
        $menu['plats'] = $this->dishesWithAllergens($id);
        return $menu;
    }

    /** Plats du menu + leurs allergènes (regroupés en une seule requête). */
    private function dishesWithAllergens(int $menuId): array
    {
        $rows = $this->fetchAll(
            "SELECT p.plat_id, p.titre, p.type, p.description, GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR ', ') AS allergenes
             FROM menu_plat mp
             JOIN plat p ON p.plat_id = mp.plat_id
             LEFT JOIN plat_allergene pa ON pa.plat_id = p.plat_id
             LEFT JOIN allergene a ON a.allergene_id = pa.allergene_id
             WHERE mp.menu_id = :id
             GROUP BY p.plat_id, p.titre, p.type, p.description
             ORDER BY FIELD(p.type, 'entree', 'plat', 'dessert'), p.titre",
            ['id' => $menuId]
        );
        return $rows;
    }

    public function all(): array
    {
        return $this->fetchAll(
            'SELECT m.*, t.libelle AS theme, r.libelle AS regime FROM menu m
             JOIN theme t ON t.theme_id = m.theme_id JOIN regime r ON r.regime_id = m.regime_id ORDER BY m.titre'
        );
    }

    public function dishIds(int $menuId): array
    {
        return array_map('intval', array_column(
            $this->fetchAll('SELECT plat_id FROM menu_plat WHERE menu_id = :id', ['id' => $menuId]), 'plat_id'
        ));
    }

    /** Création / mise à jour d'un menu et de ses plats, dans une transaction (tout ou rien). */
    public function save(?int $id, array $d, array $dishIds): int
    {
        $this->db->beginTransaction();
        try {
            $params = [
                'titre' => $d['titre'], 'description' => $d['description'], 'theme' => $d['theme_id'],
                'regime' => $d['regime_id'], 'min' => $d['nombre_personne_minimum'], 'prix' => $d['prix_minimum'],
                'conditions' => $d['conditions'], 'stock' => $d['quantite_restante'], 'actif' => $d['actif'],
            ];
            if ($id === null) {
                $this->execute(
                    'INSERT INTO menu (titre, description, theme_id, regime_id, nombre_personne_minimum, prix_minimum, conditions, quantite_restante, actif)
                     VALUES (:titre, :description, :theme, :regime, :min, :prix, :conditions, :stock, :actif)',
                    $params
                );
                $id = (int) $this->db->lastInsertId();
            } else {
                $params['id'] = $id;
                $this->execute(
                    'UPDATE menu SET titre = :titre, description = :description, theme_id = :theme, regime_id = :regime,
                     nombre_personne_minimum = :min, prix_minimum = :prix, conditions = :conditions,
                     quantite_restante = :stock, actif = :actif WHERE menu_id = :id',
                    $params
                );
                $this->execute('DELETE FROM menu_plat WHERE menu_id = :id', ['id' => $id]);
            }
            $stmt = $this->db->prepare('INSERT INTO menu_plat (menu_id, plat_id) VALUES (:m, :p)');
            foreach (array_unique($dishIds) as $platId) {
                $stmt->execute(['m' => $id, 'p' => $platId]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addImage(int $menuId, string $path, string $alt): void
    {
        $this->execute(
            'INSERT INTO menu_image (menu_id, chemin, texte_alt, ordre)
             VALUES (:m, :c, :a, (SELECT COALESCE(MAX(ordre), 0) + 1 FROM menu_image mi WHERE mi.menu_id = :m2))',
            ['m' => $menuId, 'c' => $path, 'a' => $alt, 'm2' => $menuId]
        );
    }

    /** @return array{chemin: string, menu_id: int}|null */
    public function deleteImage(int $imageId): ?array
    {
        $img = $this->fetch('SELECT chemin, menu_id FROM menu_image WHERE image_id = :id', ['id' => $imageId]);
        $this->execute('DELETE FROM menu_image WHERE image_id = :id', ['id' => $imageId]);
        return $img;
    }

    /**
     * Suppression : si le menu a déjà été commandé, on le désactive (archivage) pour conserver
     * l'historique des commandes et le chiffre d'affaires. Sinon suppression physique.
     */
    public function delete(int $id): string
    {
        $used = $this->fetch('SELECT COUNT(*) AS n FROM commande WHERE menu_id = :id', ['id' => $id]);
        if ((int) $used['n'] > 0) {
            $this->execute('UPDATE menu SET actif = 0 WHERE menu_id = :id', ['id' => $id]);
            return 'archive';
        }
        $this->execute('DELETE FROM menu WHERE menu_id = :id', ['id' => $id]);
        return 'supprime';
    }
}
