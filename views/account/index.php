<?php
$v = static function (string $f) use ($user): string {
    $old = \App\Core\Session::old($f);
    return e($old !== '' ? $old : (string) $user[$f]);
};
?>
<div class="container mt-4">
    <h1>Mon espace</h1>

    <section class="mt-4" aria-labelledby="orders-title">
        <h2 id="orders-title" class="h3">Mes commandes</h2>
        <?php if (!$orders): ?>
            <p>Vous n'avez pas encore passé de commande. <a href="/menus">Découvrir nos menus</a></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle bg-white">
                    <caption class="visually-hidden">Liste de mes commandes</caption>
                    <thead>
                    <tr>
                        <th scope="col">N°</th><th scope="col">Menu</th><th scope="col">Prestation</th>
                        <th scope="col">Personnes</th><th scope="col">Total</th><th scope="col">Statut</th><th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= e($o['numero_commande']) ?></td>
                            <td><?= e($o['menu_titre']) ?></td>
                            <td><?= date_fr($o['date_prestation']) ?></td>
                            <td><?= (int) $o['nombre_personne'] ?></td>
                            <td><?= money($o['prix_total']) ?></td>
                            <td><?= e(statut_label($o['statut'])) ?></td>
                            <td><a href="/mon-compte/commandes/<?= (int) $o['commande_id'] ?>" class="btn btn-sm btn-outline-primary">Détail<span class="visually-hidden"> de la commande <?= e($o['numero_commande']) ?></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="mt-5 form-medium" aria-labelledby="profile-title">
        <h2 id="profile-title" class="h3">Mes informations personnelles</h2>
        <p>Adresse e-mail (identifiant) : <strong><?= e($user['email']) ?></strong></p>
        <form method="post" action="/mon-compte" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <?php foreach ([
                    'nom' => ['Nom', 'family-name', 'col-md-6'], 'prenom' => ['Prénom', 'given-name', 'col-md-6'],
                    'telephone' => ['GSM', 'tel', 'col-md-6'], 'adresse_postale' => ['Adresse', 'street-address', 'col-md-6'],
                    'code_postal' => ['Code postal', 'postal-code', 'col-md-4'], 'ville' => ['Ville', 'address-level2', 'col-md-8'],
                ] as $field => [$label, $auto, $col]): ?>
                    <div class="<?= $col ?>">
                        <label for="<?= $field ?>" class="form-label"><?= $label ?></label>
                        <input type="text" class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= $v($field) ?>" required autocomplete="<?= $auto ?>"<?= aria_error($field) ?>>
                        <?= field_error($field) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
        </form>
    </section>
</div>
