<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Modération des avis</h1>
            <h2 class="h4 mt-3">En attente (<?= count($pending) ?>)</h2>
            <?php if (!$pending): ?><p>Aucun avis à modérer.</p><?php endif; ?>
            <?php foreach ($pending as $r): ?>
                <article class="review-card p-3 mb-3">
                    <p class="mb-1"><span class="stars" aria-label="Note <?= (int) $r['note'] ?> sur 5"><?= str_repeat('★', (int) $r['note']) . str_repeat('☆', 5 - (int) $r['note']) ?></span>
                        - <?= e($r['prenom'] . ' ' . $r['nom']) ?> - commande <?= e($r['numero_commande']) ?> (<?= e($r['menu_titre']) ?>)</p>
                    <p>« <?= e($r['commentaire']) ?> »</p>
                    <form method="post" action="/employe/avis/<?= (int) $r['avis_id'] ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <button type="submit" name="decision" value="valider" class="btn btn-sm btn-primary">Valider</button>
                        <button type="submit" name="decision" value="refuser" class="btn btn-sm btn-outline-danger">Refuser</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <h2 class="h4 mt-4">Publiés sur la page d'accueil (<?= count($validated) ?>)</h2>
            <ul>
                <?php foreach ($validated as $r): ?>
                    <li><?= (int) $r['note'] ?>/5 - <?= e($r['prenom']) ?> : <?= e($r['commentaire']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
