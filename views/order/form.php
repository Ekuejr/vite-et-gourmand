<?php
/** Formulaire de commande (création) ou de modification ($order non nul). */
$scripts = ['/assets/js/order.js'];
$isEdit = $order !== null;
$action = $isEdit ? '/mon-compte/commandes/' . (int) $order['commande_id'] . '/modifier' : '/commander/' . (int) $menu['menu_id'];
// Valeur : ancienne saisie (après erreur) > commande existante > profil du client
$val = static function (string $field, string $fallback = '') use ($order): string {
    $old = \App\Core\Session::old($field);
    return e($old !== '' ? $old : ($order[$field] ?? $fallback));
};
?>
<div class="container mt-4">
    <h1><?= $isEdit ? 'Modifier la commande ' . e($order['numero_commande']) : 'Commander' ?></h1>

    <form method="post" action="<?= e($action) ?>" id="order-form" novalidate
          data-menu-id="<?= (int) $menu['menu_id'] ?>">
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <fieldset class="mb-4">
                    <legend class="h4">1. Vos informations</legend>
                    <p class="small">Pré-remplies depuis votre compte (modifiables dans <a href="/mon-compte">Mon espace</a>).</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" id="nom" class="form-control" value="<?= e($user['nom']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" id="prenom" class="form-control" value="<?= e($user['prenom']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" class="form-control" value="<?= e($user['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">GSM</label>
                            <input type="tel" id="telephone" class="form-control" value="<?= e($user['telephone']) ?>" readonly>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h4">2. La prestation</legend>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="adresse_prestation" class="form-label">Adresse de la prestation</label>
                            <input type="text" class="form-control" id="adresse_prestation" name="adresse_prestation" required
                                   value="<?= $val('adresse_prestation', (string) $user['adresse_postale']) ?>"<?= aria_error('adresse_prestation') ?>>
                            <?= field_error('adresse_prestation') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="code_postal_prestation" class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="code_postal_prestation" name="code_postal_prestation" required inputmode="numeric" pattern="\d{5}"
                                   value="<?= $val('code_postal_prestation', (string) $user['code_postal']) ?>"<?= aria_error('code_postal_prestation') ?>>
                            <?= field_error('code_postal_prestation') ?>
                        </div>
                        <div class="col-md-8">
                            <label for="ville_prestation" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="ville_prestation" name="ville_prestation" required aria-describedby="ville-help"
                                   value="<?= $val('ville_prestation', (string) $user['ville']) ?>"<?= aria_error('ville_prestation') ?>>
                            <div id="ville-help" class="form-text">Livraison offerte à Bordeaux. Ailleurs : 5 € + 0,59 € par km.</div>
                            <?= field_error('ville_prestation') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="date_prestation" class="form-label">Date de la prestation</label>
                            <input type="date" class="form-control" id="date_prestation" name="date_prestation" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                   value="<?= $val('date_prestation') ?>"<?= aria_error('date_prestation') ?>>
                            <?= field_error('date_prestation') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="heure_livraison" class="form-label">Heure de livraison souhaitée</label>
                            <input type="time" class="form-control" id="heure_livraison" name="heure_livraison" required
                                   value="<?= e(substr(html_entity_decode($val('heure_livraison', '12:00')), 0, 5)) ?>"<?= aria_error('heure_livraison') ?>>
                            <?= field_error('heure_livraison') ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h4">3. Le menu</legend>
                    <p class="mb-1"><strong><?= e($menu['titre']) ?></strong><?= $isEdit ? ' (le menu choisi n\'est pas modifiable)' : '' ?></p>
                    <div class="conditions-box my-3">
                        <p class="fw-semibold mb-1">Conditions de ce menu :</p>
                        <p class="mb-0"><?= nl2br(e($menu['conditions'])) ?></p>
                    </div>
                    <label for="nombre_personne" class="form-label">Nombre de personnes (minimum <?= (int) $menu['nombre_personne_minimum'] ?>)</label>
                    <input type="number" class="form-control" id="nombre_personne" name="nombre_personne" required
                           min="<?= (int) $menu['nombre_personne_minimum'] ?>" value="<?= $val('nombre_personne', (string) $menu['nombre_personne_minimum']) ?>"
                           aria-describedby="nb-help"<?= aria_error('nombre_personne') ?>>
                    <div id="nb-help" class="form-text">-10 % sur le menu à partir de <?= (int) $menu['nombre_personne_minimum'] + 5 ?> personnes.</div>
                    <?= field_error('nombre_personne') ?>
                </fieldset>
            </div>

            <div class="col-lg-5">
                <section class="price-summary p-4 position-sticky top-0" aria-labelledby="recap-title">
                    <h2 id="recap-title" class="h4">Récapitulatif du prix</h2>
                    <div id="price-detail" aria-live="polite">
                        <dl class="row mb-0">
                            <dt class="col-7">Prix du menu</dt><dd class="col-5 text-end" data-price="prix_menu">-</dd>
                            <dt class="col-7">Remise (10 %)</dt><dd class="col-5 text-end" data-price="remise">-</dd>
                            <dt class="col-7">Livraison <span class="small" data-price="distance_km"></span></dt><dd class="col-5 text-end" data-price="prix_livraison">-</dd>
                            <dt class="col-7 total">Total TTC</dt><dd class="col-5 text-end total" data-price="prix_total">-</dd>
                        </dl>
                        <p id="price-error" class="text-danger small mt-2 mb-0"></p>
                    </div>
                    <p class="small mt-2">Le prix est recalculé et vérifié par nos serveurs à la validation.</p>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><?= $isEdit ? 'Enregistrer les modifications' : 'Valider ma commande' ?></button>
                </section>
            </div>
        </div>
    </form>
</div>
