<?php
/** Menu latéral de l'espace professionnel (employé / administrateur). */
use App\Core\Auth;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$links = [
    '/employe'           => 'Tableau de bord',
    '/employe/commandes' => 'Commandes',
    '/employe/menus'     => 'Menus',
    '/employe/plats'     => 'Plats',
    '/employe/horaires'  => 'Horaires',
    '/employe/avis'      => 'Avis clients',
];
if (Auth::role() === Auth::ROLE_ADMIN) {
    $links += ['/admin' => 'Administration', '/admin/employes' => 'Comptes employés', '/admin/statistiques' => 'Statistiques'];
}
?>
<nav class="list-group sidebar-pro mb-4" aria-label="Espace professionnel">
    <?php foreach ($links as $url => $label):
        $active = $path === $url || ($url !== '/employe' && $url !== '/admin' && str_starts_with((string) $path, $url)); ?>
        <a href="<?= $url ?>" class="list-group-item list-group-item-action<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
