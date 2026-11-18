<?php /** Carte d'un menu (vue globale) - $m : ligne issue de Menu::search() */ ?>
<article class="card card-menu h-100">
    <?php if (!empty($m['image'])): ?>
        <img src="<?= e($m['image']) ?>" class="card-img-top" alt="<?= e($m['image_alt']) ?>" loading="lazy" width="400" height="250">
    <?php endif; ?>
    <div class="card-body d-flex flex-column">
        <h3 class="h5 card-title"><?= e($m['titre']) ?></h3>
        <p class="mb-2">
            <span class="badge badge-theme"><?= e($m['theme']) ?></span>
            <span class="badge badge-regime"><?= e($m['regime']) ?></span>
        </p>
        <p class="card-text"><?= e($m['description']) ?></p>
        <p class="mb-1">Minimum : <strong><?= (int) $m['nombre_personne_minimum'] ?> personnes</strong></p>
        <p class="price mb-3"><?= money($m['prix_minimum']) ?> <span class="fs-6 fw-normal text-body-secondary">pour <?= (int) $m['nombre_personne_minimum'] ?> pers.</span></p>
        <a href="/menus/<?= (int) $m['menu_id'] ?>" class="btn btn-primary mt-auto">Voir le détail<span class="visually-hidden"> du menu <?= e($m['titre']) ?></span></a>
    </div>
</article>
