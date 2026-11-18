<?php
/**
 * Script en ligne de commande : (re)synchronise toutes les commandes MySQL vers MongoDB.
 * À lancer une fois après l'import du jeu de données, puis en cas de besoin :
 *     php tools/sync_mongo.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI uniquement.');
}

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
require $root . '/src/helpers.php';
\App\Core\App::boot(require $root . '/config/config.php');

$stats = new \App\Services\StatsService();
if (!$stats->isAvailable()) {
    fwrite(STDERR, "MongoDB non configuré : vérifiez l'extension mongodb et MONGO_URI dans .env\n");
    exit(1);
}
$orders = (new \App\Models\Order())->allForStats();
foreach ($orders as $order) {
    $stats->upsertOrder($order);
}
echo count($orders) . " commande(s) synchronisée(s) vers MongoDB.\n";
