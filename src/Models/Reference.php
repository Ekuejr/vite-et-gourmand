<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Tables de référence (thèmes, régimes, allergènes) et horaires d'ouverture.
 */
final class Reference extends Model
{
    public function themes(): array
    {
        return $this->fetchAll('SELECT * FROM theme ORDER BY theme_id');
    }

    public function regimes(): array
    {
        return $this->fetchAll('SELECT * FROM regime ORDER BY regime_id');
    }

    public function allergens(): array
    {
        return $this->fetchAll('SELECT * FROM allergene ORDER BY libelle');
    }

    public function schedules(): array
    {
        return $this->fetchAll('SELECT * FROM horaire ORDER BY ordre');
    }

    public function updateSchedule(int $id, ?string $open, ?string $close, bool $closed): void
    {
        $this->execute(
            'UPDATE horaire SET heure_ouverture = :o, heure_fermeture = :c, ferme = :f WHERE horaire_id = :id',
            ['o' => $closed ? null : $open, 'c' => $closed ? null : $close, 'f' => $closed ? 1 : 0, 'id' => $id]
        );
    }

    /** Liste des identifiants existants : sert à valider les cases cochées envoyées par un formulaire. */
    public function existingIds(string $table, string $pk): array
    {
        $allowed = ['theme' => 'theme_id', 'regime' => 'regime_id', 'allergene' => 'allergene_id', 'plat' => 'plat_id'];
        if (($allowed[$table] ?? null) !== $pk) {
            throw new \InvalidArgumentException('Table non autorisée');
        }
        return array_map('intval', array_column($this->fetchAll("SELECT $pk FROM $table"), $pk));
    }
}
