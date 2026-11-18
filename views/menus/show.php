<?php use App\Core\Auth; use App\Models\Dish; ?>
<div class="container mt-4">
    <nav aria-label="Fil d'Ariane">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
            <li class="breadcrumb-item"><a href="/menus">Nos menus</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($menu['titre']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <h1><?= e($menu['titre']) ?></h1>
            <p>
                <span class="badge badge-theme">Thème : <?= e($menu['theme']) ?></span>
                <span class="badge badge-regime">Régime : <?= e($menu['regime']) ?></span>
            </p>
            <p class="lead"><?= e($menu['description']) ?></p>

            <?php if ($menu['images']): ?>
                <h2 class="h4 mt-4">Galerie</h2>
                <div class="row g-2 mb-4">
                    <?php foreach ($menu['images'] as $img): ?>
                        <div class="col-6">
                            <img src="<?= e($img['chemin']) ?>" alt="<?= e($img['texte_alt']) ?>" class="img-fluid rounded" loading="lazy" width="400" height="250">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 class="h4">Composition du menu</h2>
            <?php foreach (Dish::TYPES as $type => $label):
                $dishes = array_filter($menu['plats'], static fn($p) => $p['type'] === $type);
                if (!$dishes) continue; ?>
                <h3 class="h5 mt-3"><?= e($label) ?><?= count($dishes) > 1 ? 's' : '' ?> au choix</h3>
                <ul class="list-unstyled">
                    <?php foreach ($dishes as $p): ?>
                        <li class="mb-2">
                            <strong><?= e($p['titre']) ?></strong>
                            <?php if ($p['description']): ?> - <?= e($p['description']) ?><?php endif; ?>
                            <br><span class="allergens">Allergènes : <?= $p['allergenes'] ? e($p['allergenes']) : 'aucun allergène majeur' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>

        <aside class="col-lg-5" aria-label="Commander ce menu">
            <div class="conditions-box mb-4" role="note" aria-labelledby="conditions-title">
                <h2 id="conditions-title" class="h5"><span aria-hidden="true">⚠️</span> Conditions de ce menu - à lire avant de commander</h2>
                <p class="mb-0"><?= nl2br(e($menu['conditions'])) ?></p>
            </div>

            <div class="price-summary p-4">
                <p class="price mb-1"><?= money($menu['prix_minimum']) ?></p>
                <p>pour <strong><?= (int) $menu['nombre_personne_minimum'] ?> personnes minimum</strong>
                    (soit <?= money((float) $menu['prix_minimum'] / (int) $menu['nombre_personne_minimum']) ?> par personne)</p>
                <p class="small">-10 % dès <?= (int) $menu['nombre_personne_minimum'] + 5 ?> personnes. Livraison offerte à Bordeaux.</p>
                <p class="<?= (int) $menu['quantite_restante'] <= 3 ? 'text-danger fw-semibold' : '' ?>">
                    Stock disponible : <?= (int) $menu['quantite_restante'] ?> commande(s) possible(s)
                </p>

                <?php if ((int) $menu['quantite_restante'] < 1): ?>
                    <p class="alert alert-warning mb-0">Ce menu n'est plus disponible à la commande.</p>
                <?php elseif (!Auth::check()): ?>
                    <p>Pour commander, connectez-vous ou créez un compte.</p>
                    <a href="/connexion" class="btn btn-primary w-100 mb-2">Se connecter pour commander</a>
                    <a href="/inscription" class="btn btn-outline-primary w-100">Créer un compte</a>
                    <?php \App\Core\Session::set('redirect_after_login', '/commander/' . (int) $menu['menu_id']); ?>
                <?php elseif (Auth::role() === Auth::ROLE_USER): ?>
                    <a href="/commander/<?= (int) $menu['menu_id'] ?>" class="btn btn-primary btn-lg w-100">Commander ce menu</a>
                <?php else: ?>
                    <p class="small mb-0">La commande en ligne est réservée aux comptes clients.</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
