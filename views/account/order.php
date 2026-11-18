<?php use App\Services\OrderWorkflow; ?>
<div class="container mt-4">
    <p><a href="/mon-compte">&larr; Retour à mon espace</a></p>
    <h1>Commande <?= e($order['numero_commande']) ?></h1>
    <p class="lead">Statut : <strong><?= e(statut_label($order['statut'])) ?></strong></p>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="price-summary p-4" aria-labelledby="detail-title">
                <h2 id="detail-title" class="h4">Détail</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Menu</dt><dd class="col-sm-7"><?= e($order['menu_titre']) ?></dd>
                    <dt class="col-sm-5">Date et heure</dt><dd class="col-sm-7"><?= date_fr($order['date_prestation']) ?> à <?= e(substr($order['heure_livraison'], 0, 5)) ?></dd>
                    <dt class="col-sm-5">Lieu</dt><dd class="col-sm-7"><?= e($order['adresse_prestation'] . ', ' . $order['code_postal_prestation'] . ' ' . $order['ville_prestation']) ?></dd>
                    <dt class="col-sm-5">Personnes</dt><dd class="col-sm-7"><?= (int) $order['nombre_personne'] ?></dd>
                    <dt class="col-sm-5">Prix du menu</dt><dd class="col-sm-7"><?= money($order['prix_menu']) ?></dd>
                    <dt class="col-sm-5">Remise</dt><dd class="col-sm-7"><?= (float) $order['remise'] > 0 ? '- ' . money($order['remise']) : '-' ?></dd>
                    <dt class="col-sm-5">Livraison</dt><dd class="col-sm-7"><?= (float) $order['prix_livraison'] > 0 ? money($order['prix_livraison']) . ' (' . e($order['distance_km']) . ' km)' : 'Offerte' ?></dd>
                    <dt class="col-sm-5 total">Total</dt><dd class="col-sm-7 total"><?= money($order['prix_total']) ?></dd>
                    <?php if ($order['pret_materiel']): ?>
                        <dt class="col-sm-5">Matériel prêté</dt><dd class="col-sm-7"><?= $order['restitution_materiel'] ? 'Restitué' : 'À restituer sous 10 jours ouvrés (600 € de frais sinon)' ?></dd>
                    <?php endif; ?>
                    <?php if ($order['statut'] === 'annulee' && $order['motif_annulation']): ?>
                        <dt class="col-sm-5">Motif d'annulation</dt><dd class="col-sm-7"><?= e($order['motif_annulation']) ?></dd>
                    <?php endif; ?>
                </dl>
            </section>

            <?php if (OrderWorkflow::userCanEdit($order['statut'])): ?>
                <div class="d-flex gap-2 mt-3">
                    <a class="btn btn-outline-primary" href="/mon-compte/commandes/<?= (int) $order['commande_id'] ?>/modifier">Modifier la commande</a>
                    <form method="post" action="/mon-compte/commandes/<?= (int) $order['commande_id'] ?>/annuler">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger">Annuler la commande</button>
                    </form>
                </div>
                <p class="small mt-2">Modification et annulation possibles tant que notre équipe n'a pas accepté la commande.</p>
            <?php endif; ?>

            <?php if ($order['statut'] === 'terminee'): ?>
                <section class="mt-4" aria-labelledby="review-title">
                    <h2 id="review-title" class="h4">Votre avis</h2>
                    <?php if ($order['avis_id']): ?>
                        <p>Merci, vous avez donné la note de <?= (int) $order['avis_note'] ?>/5
                            (<?= $order['avis_statut'] === 'valide' ? 'publié' : ($order['avis_statut'] === 'refuse' ? 'non publié' : 'en attente de validation') ?>).</p>
                    <?php else: ?>
                        <form method="post" action="/mon-compte/commandes/<?= (int) $order['commande_id'] ?>/avis" novalidate>
                            <?= csrf_field() ?>
                            <fieldset class="mb-3">
                                <legend class="form-label fs-6">Note (1 = décevant, 5 = excellent)</legend>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="note" id="note<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="note<?= $i ?>"><?= $i ?></label>
                                    </div>
                                <?php endfor; ?>
                                <?= field_error('note') ?>
                            </fieldset>
                            <div class="mb-3">
                                <label for="commentaire" class="form-label">Commentaire</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3" maxlength="1000" required<?= aria_error('commentaire') ?>><?= old('commentaire') ?></textarea>
                                <?= field_error('commentaire') ?>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer mon avis</button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <section aria-labelledby="track-title">
                <h2 id="track-title" class="h4">Suivi de la commande</h2>
                <?php if (!OrderWorkflow::userCanTrack($order['statut']) && $order['statut'] !== 'annulee'): ?>
                    <p>Le suivi détaillé sera disponible dès que notre équipe aura accepté votre commande.</p>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($history as $h): ?>
                            <li><strong><?= e(statut_label($h['statut'])) ?></strong><br>
                                <span class="small"><?= date_fr($h['date_modification'], true) ?></span></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
