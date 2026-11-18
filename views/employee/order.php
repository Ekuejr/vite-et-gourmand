<?php use App\Services\OrderWorkflow; ?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <p><a href="/employe/commandes">&larr; Toutes les commandes</a></p>
            <h1>Commande <?= e($order['numero_commande']) ?></h1>
            <p class="lead">Statut actuel : <strong><?= e(statut_label($order['statut'])) ?></strong></p>

            <div class="row g-4">
                <div class="col-md-7">
                    <section class="price-summary p-3" aria-labelledby="client-title">
                        <h2 id="client-title" class="h5">Client et prestation</h2>
                        <dl class="row mb-0 small">
                            <dt class="col-5">Client</dt><dd class="col-7"><?= e($order['prenom'] . ' ' . $order['nom']) ?></dd>
                            <dt class="col-5">GSM</dt><dd class="col-7"><a href="tel:<?= e(preg_replace('/\s/', '', (string) $order['telephone'])) ?>"><?= e($order['telephone']) ?></a></dd>
                            <dt class="col-5">E-mail</dt><dd class="col-7"><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
                            <dt class="col-5">Menu</dt><dd class="col-7"><?= e($order['menu_titre']) ?> - <?= (int) $order['nombre_personne'] ?> pers.</dd>
                            <dt class="col-5">Prestation</dt><dd class="col-7"><?= date_fr($order['date_prestation']) ?> à <?= e(substr($order['heure_livraison'], 0, 5)) ?></dd>
                            <dt class="col-5">Lieu</dt><dd class="col-7"><?= e($order['adresse_prestation'] . ', ' . $order['code_postal_prestation'] . ' ' . $order['ville_prestation']) ?></dd>
                            <dt class="col-5">Total</dt><dd class="col-7"><?= money($order['prix_total']) ?> (dont livraison <?= money($order['prix_livraison']) ?>, remise <?= money($order['remise']) ?>)</dd>
                            <dt class="col-5">Matériel prêté</dt><dd class="col-7"><?= $order['pret_materiel'] ? 'Oui' . ($order['restitution_materiel'] ? ' (restitué)' : '') : 'Non' ?></dd>
                            <?php if ($order['motif_annulation']): ?>
                                <dt class="col-5">Annulation</dt><dd class="col-7"><?= e($order['motif_annulation']) ?> (contact : <?= e($order['mode_contact_annulation']) ?>)</dd>
                            <?php endif; ?>
                        </dl>
                    </section>

                    <?php if ($next): ?>
                        <section class="mt-3" aria-labelledby="status-title">
                            <h2 id="status-title" class="h5">Faire avancer la commande</h2>
                            <?php foreach ($next as $s): ?>
                                <form method="post" action="/employe/commandes/<?= (int) $order['commande_id'] ?>/statut" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="statut" value="<?= e($s) ?>">
                                    <button type="submit" class="btn btn-primary">Passer à : <?= e(statut_label($s)) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!in_array($order['statut'], ['livre', 'en_attente_retour_materiel', 'terminee', 'annulee'], true)): ?>
                        <form method="post" action="/employe/commandes/<?= (int) $order['commande_id'] ?>/materiel" class="mt-3">
                            <?= csrf_field() ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="pret_materiel" name="pret_materiel" value="1" <?= $order['pret_materiel'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pret_materiel">Du matériel est prêté au client</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                        </form>
                    <?php endif; ?>

                    <?php if (OrderWorkflow::staffCanCancel($order['statut'])): ?>
                        <section class="mt-4 border border-danger rounded p-3" aria-labelledby="cancel-title">
                            <h2 id="cancel-title" class="h5 text-danger">Annuler la commande</h2>
                            <p class="small">Obligatoire : contacter le client au préalable (téléphone ou e-mail) et indiquer le motif.</p>
                            <form method="post" action="/employe/commandes/<?= (int) $order['commande_id'] ?>/annuler" novalidate>
                                <?= csrf_field() ?>
                                <fieldset class="mb-2">
                                    <legend class="fs-6">Client contacté par</legend>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mode_contact" id="mc-gsm" value="gsm">
                                        <label class="form-check-label" for="mc-gsm">Téléphone (GSM)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mode_contact" id="mc-mail" value="mail">
                                        <label class="form-check-label" for="mc-mail">E-mail</label>
                                    </div>
                                    <?= field_error('mode_contact') ?>
                                </fieldset>
                                <label for="motif" class="form-label">Motif d'annulation</label>
                                <input type="text" class="form-control mb-2" id="motif" name="motif" maxlength="255" required value="<?= old('motif') ?>"<?= aria_error('motif') ?>>
                                <?= field_error('motif') ?>
                                <button type="submit" class="btn btn-outline-danger">Confirmer l'annulation</button>
                            </form>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">
                    <h2 class="h5">Historique</h2>
                    <ol class="timeline">
                        <?php foreach ($history as $h): ?>
                            <li><strong><?= e(statut_label($h['statut'])) ?></strong><br>
                                <span class="small"><?= date_fr($h['date_modification'], true) ?><?= $h['auteur_prenom'] ? ' - par ' . e($h['auteur_prenom']) : '' ?></span>
                                <?php if ($h['commentaire']): ?><br><span class="small"><?= e($h['commentaire']) ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
