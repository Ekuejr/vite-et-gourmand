<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;

/**
 * Statistiques administrateur stockées dans MongoDB (base NoSQL exigée par le cahier des charges).
 *
 * Pourquoi MongoDB ici : les statistiques sont des données dénormalisées, en lecture seule,
 * agrégées par menu et par période. Le pipeline d'agrégation ($match / $group) est fait pour ça
 * et allège la base relationnelle, qui reste la source de vérité des commandes.
 *
 * Fonctionnement : à chaque création / changement de statut d'une commande, un document
 * { _id: id_commande, menu_id, menu_titre, nombre_personne, prix_total, date_commande, statut }
 * est inséré ou mis à jour (upsert). Une erreur MongoDB ne bloque jamais la commande (journalisée).
 *
 * Utilise directement le pilote PHP natif (extension "mongodb") : pas de dépendance Composer.
 */
final class StatsService
{
    private const COLLECTION = 'commandes_stats';

    private ?Manager $manager = null;
    private string $dbName;

    public function __construct()
    {
        $cfg = App::config('mongo');
        $this->dbName = $cfg['db'] ?? 'vite_gourmand';
        if (extension_loaded('mongodb') && !empty($cfg['uri'])) {
            $this->manager = new Manager($cfg['uri'], ['serverSelectionTimeoutMS' => 3000]);
        }
    }

    public function isAvailable(): bool
    {
        return $this->manager !== null;
    }

    /** Insère ou met à jour le document statistique d'une commande. */
    public function upsertOrder(array $order): void
    {
        if (!$this->manager) {
            return;
        }
        try {
            $bulk = new BulkWrite();
            $bulk->update(
                ['_id' => (int) $order['commande_id']],
                ['$set' => [
                    'menu_id'         => (int) $order['menu_id'],
                    'menu_titre'      => (string) $order['menu_titre'],
                    'nombre_personne' => (int) $order['nombre_personne'],
                    'prix_total'      => (float) $order['prix_total'],
                    'date_commande'   => new UTCDateTime(strtotime((string) $order['date_commande']) * 1000),
                    'statut'          => (string) $order['statut'],
                ]],
                ['upsert' => true]
            );
            $this->manager->executeBulkWrite($this->dbName . '.' . self::COLLECTION, $bulk);
        } catch (\Throwable $e) {
            error_log('[MongoDB] ' . $e->getMessage());
        }
    }

    /**
     * Nombre de commandes et chiffre d'affaires par menu (commandes annulées exclues).
     * @param int|null $menuId filtre sur un menu
     * @param string|null $from date de début (Y-m-d) incluse
     * @param string|null $to date de fin (Y-m-d) incluse
     */
    public function perMenu(?int $menuId = null, ?string $from = null, ?string $to = null): array
    {
        if (!$this->manager) {
            throw new \RuntimeException('MongoDB n\'est pas configuré (extension ou MONGO_URI manquant).');
        }
        $match = ['statut' => ['$ne' => 'annulee']];
        if ($menuId) {
            $match['menu_id'] = $menuId;
        }
        if ($from || $to) {
            $range = [];
            if ($from) {
                $range['$gte'] = new UTCDateTime(strtotime($from . ' 00:00:00') * 1000);
            }
            if ($to) {
                $range['$lte'] = new UTCDateTime(strtotime($to . ' 23:59:59') * 1000);
            }
            $match['date_commande'] = $range;
        }

        $command = new Command([
            'aggregate' => self::COLLECTION,
            'pipeline'  => [
                ['$match' => $match],
                ['$group' => [
                    // Regroupement par titre de menu (le titre est dénormalisé dans chaque document)
                    '_id'              => '$menu_titre',
                    'nb_commandes'     => ['$sum' => 1],
                    'chiffre_affaires' => ['$sum' => '$prix_total'],
                ]],
                ['$sort' => ['nb_commandes' => -1]],
            ],
            'cursor' => new \stdClass(),
        ]);

        $rows = [];
        foreach ($this->manager->executeCommand($this->dbName, $command) as $doc) {
            $rows[] = [
                'menu_titre'       => (string) $doc->_id,
                'nb_commandes'     => (int) $doc->nb_commandes,
                'chiffre_affaires' => round((float) $doc->chiffre_affaires, 2),
            ];
        }
        return $rows;
    }
}
