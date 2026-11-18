<section class="hero" aria-labelledby="hero-title">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 id="hero-title">Vos événements, notre savoir-faire depuis 25 ans</h1>
                <p class="lead mt-3">Julie et José, traiteurs à Bordeaux, imaginent des menus de saison pour Noël, Pâques,
                    vos repas de famille ou vos événements d'entreprise. Livrés chez vous, prêts à servir.</p>
                <a href="/menus" class="btn btn-light btn-lg mt-2">Découvrir nos menus</a>
            </div>
        </div>
    </div>
</section>

<section class="container mt-5" aria-labelledby="about-title">
    <h2 id="about-title" class="section-title">Une équipe de professionnels passionnés</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <h3 class="h5">25 ans d'expérience</h3>
            <p>Depuis un quart de siècle, Vite &amp; Gourmand régale les Bordelais : plus de 10 000 repas servis pour les fêtes et les grandes occasions.</p>
        </div>
        <div class="col-md-4">
            <h3 class="h5">Fait maison, produits locaux</h3>
            <p>Tous nos plats sont cuisinés dans notre laboratoire bordelais à partir de produits frais et de producteurs du Sud-Ouest.</p>
        </div>
        <div class="col-md-4">
            <h3 class="h5">Livraison et service</h3>
            <p>Livraison gratuite à Bordeaux, prêt de matériel de service sur demande et une équipe logistique ponctuelle.</p>
        </div>
    </div>
</section>

<?php if ($menus): ?>
<section class="container mt-5" aria-labelledby="menus-title">
    <h2 id="menus-title" class="section-title">Nos menus du moment</h2>
    <div class="row g-4">
        <?php foreach ($menus as $m): ?>
            <div class="col-md-4"><?php require __DIR__ . '/../partials/menu_card.php'; ?></div>
        <?php endforeach; ?>
    </div>
    <p class="mt-3"><a href="/menus">Voir tous les menus</a></p>
</section>
<?php endif; ?>

<section class="container mt-5" aria-labelledby="reviews-title">
    <h2 id="reviews-title" class="section-title">Ils nous ont fait confiance</h2>
    <?php if (!$reviews): ?>
        <p>Aucun avis publié pour le moment.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($reviews as $r): ?>
                <div class="col-md-6 col-lg-4">
                    <figure class="review-card p-3 h-100 mb-0">
                        <p class="stars mb-1" aria-label="Note : <?= (int) $r['note'] ?> sur 5">
                            <?= str_repeat('★', (int) $r['note']) . str_repeat('☆', 5 - (int) $r['note']) ?>
                        </p>
                        <blockquote class="mb-2"><p class="mb-0">« <?= e($r['commentaire']) ?> »</p></blockquote>
                        <figcaption class="small text-body-secondary"><?= e($r['prenom'] . ' ' . $r['initiale'] . '.') ?> - <?= e($r['menu_titre']) ?></figcaption>
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
