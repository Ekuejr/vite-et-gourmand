<?php
/** Gabarit commun : en-tête (menu de navigation), contenu, pied de page avec horaires. */
use App\Core\Auth;
use App\Core\Session;

$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = static fn(string $path): string => ($current === $path || ($path !== '/' && str_starts_with((string) $current, $path))) ? ' active" aria-current="page' : '';
try {
    $schedules = (new \App\Models\Reference())->schedules();
} catch (\Throwable) {
    $schedules = [];
}
$user = Auth::user();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Vite &amp; Gourmand, traiteur à Bordeaux depuis 25 ans : menus de fête, classiques, végétariens et vegan livrés chez vous.">
    <title><?= e($title ?? 'Accueil') ?> | Vite &amp; Gourmand</title>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
<a class="skip-link" href="#contenu">Aller au contenu principal</a>

<header>
    <nav class="navbar navbar-expand-lg navbar-vg" aria-label="Navigation principale">
        <div class="container">
            <a class="navbar-brand fs-3" href="/">Vite &amp; Gourmand</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Ouvrir le menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link<?= $isActive('/') ?>" href="/">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link<?= $isActive('/menus') ?>" href="/menus">Nos menus</a></li>
                    <li class="nav-item"><a class="nav-link<?= $isActive('/contact') ?>" href="/contact">Contact</a></li>
                    <?php if ($user === null): ?>
                        <li class="nav-item"><a class="nav-link<?= $isActive('/connexion') ?>" href="/connexion">Connexion</a></li>
                        <li class="nav-item"><a class="btn btn-light btn-sm" href="/inscription">Créer un compte</a></li>
                    <?php else: ?>
                        <?php if ($user['role'] === Auth::ROLE_USER): ?>
                            <li class="nav-item"><a class="nav-link<?= $isActive('/mon-compte') ?>" href="/mon-compte">Mon espace</a></li>
                        <?php endif; ?>
                        <?php if (Auth::isStaff()): ?>
                            <li class="nav-item"><a class="nav-link<?= $isActive('/employe') ?>" href="/employe">Espace employé</a></li>
                        <?php endif; ?>
                        <?php if ($user['role'] === Auth::ROLE_ADMIN): ?>
                            <li class="nav-item"><a class="nav-link<?= $isActive('/admin') ?>" href="/admin">Administration</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <form action="/deconnexion" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link nav-link">Déconnexion (<?= e($user['prenom']) ?>)</button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="contenu" class="flex-grow-1" tabindex="-1">
    <?php $flashes = Session::pullFlashes(); if ($flashes): ?>
        <div class="container mt-3" role="status" aria-live="polite">
            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> mb-2"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="footer-vg mt-5 pt-4 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h2>Vite &amp; Gourmand</h2>
                <p class="mb-1">Traiteur à Bordeaux depuis 25 ans</p>
                <p class="mb-1">12 quai des Chartrons, 33000 Bordeaux</p>
                <p><a href="/contact">Nous contacter</a></p>
            </div>
            <div class="col-md-5">
                <h2>Horaires d'ouverture</h2>
                <table class="table table-sm table-borderless mb-0 small">
                    <caption class="visually-hidden">Horaires d'ouverture du lundi au dimanche</caption>
                    <tbody>
                    <?php foreach ($schedules as $h): ?>
                        <tr>
                            <th scope="row" class="fw-normal py-0"><?= e($h['jour']) ?></th>
                            <td class="py-0"><?= $h['ferme'] ? 'Fermé' : e(substr((string) $h['heure_ouverture'], 0, 5) . ' - ' . substr((string) $h['heure_fermeture'], 0, 5)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-3">
                <h2>Informations</h2>
                <ul class="list-unstyled">
                    <li><a href="/mentions-legales">Mentions légales</a></li>
                    <li><a href="/cgv">Conditions générales de vente</a></li>
                </ul>
            </div>
        </div>
        <p class="small text-center mt-3 mb-0">&copy; <?= date('Y') ?> Vite &amp; Gourmand - Réalisé par FastDev</p>
    </div>
</footer>

<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
    <script src="<?= e($script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
