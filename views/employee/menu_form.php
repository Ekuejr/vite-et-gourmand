<?php
use App\Core\Session;
use App\Models\Dish;

$v = static function (string $f, string $default = '') use ($menu): string {
    $old = Session::old($f);
    return e($old !== '' ? $old : (string) ($menu[$f] ?? $default));
};
$action = $menu ? '/employe/menus/' . (int) $menu['menu_id'] : '/employe/menus/nouveau';
?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <p><a href="/employe/menus">&larr; Tous les menus</a></p>
            <h1><?= e($title) ?></h1>
            <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="titre" name="titre" value="<?= $v('titre') ?>" required maxlength="100"<?= aria_error('titre') ?>>
                        <?= field_error('titre') ?>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" <?= !$menu || $menu['actif'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="actif">Menu visible sur le site</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required<?= aria_error('description') ?>><?= $v('description') ?></textarea>
                        <?= field_error('description') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="theme_id" class="form-label">Thème</label>
                        <select class="form-select" id="theme_id" name="theme_id" required>
                            <?php foreach ($themes as $t): ?>
                                <option value="<?= (int) $t['theme_id'] ?>" <?= (string) $t['theme_id'] === html_entity_decode($v('theme_id')) ? 'selected' : '' ?>><?= e($t['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('theme_id') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="regime_id" class="form-label">Régime</label>
                        <select class="form-select" id="regime_id" name="regime_id" required>
                            <?php foreach ($regimes as $r): ?>
                                <option value="<?= (int) $r['regime_id'] ?>" <?= (string) $r['regime_id'] === html_entity_decode($v('regime_id')) ? 'selected' : '' ?>><?= e($r['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error('regime_id') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="nombre_personne_minimum" class="form-label">Nombre de personnes minimum</label>
                        <input type="number" min="1" class="form-control" id="nombre_personne_minimum" name="nombre_personne_minimum" value="<?= $v('nombre_personne_minimum') ?>" required<?= aria_error('nombre_personne_minimum') ?>>
                        <?= field_error('nombre_personne_minimum') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="prix_minimum" class="form-label">Prix pour ce minimum (€)</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="prix_minimum" name="prix_minimum" value="<?= $v('prix_minimum') ?>" required<?= aria_error('prix_minimum') ?>>
                        <?= field_error('prix_minimum') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="quantite_restante" class="form-label">Stock (commandes possibles)</label>
                        <input type="number" min="0" class="form-control" id="quantite_restante" name="quantite_restante" value="<?= $v('quantite_restante', '10') ?>" required<?= aria_error('quantite_restante') ?>>
                        <?= field_error('quantite_restante') ?>
                    </div>
                    <div class="col-12">
                        <label for="conditions" class="form-label">Conditions du menu (délai de commande, conservation...)</label>
                        <textarea class="form-control" id="conditions" name="conditions" rows="2" required<?= aria_error('conditions') ?>><?= $v('conditions') ?></textarea>
                        <?= field_error('conditions') ?>
                    </div>
                    <fieldset class="col-12">
                        <legend class="form-label fs-6">Plats proposés</legend>
                        <div class="row">
                            <?php foreach ($dishes as $d): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="plats[]" id="plat<?= (int) $d['plat_id'] ?>" value="<?= (int) $d['plat_id'] ?>" <?= in_array((int) $d['plat_id'], $dishIds, true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="plat<?= (int) $d['plat_id'] ?>"><?= e(Dish::TYPES[$d['type']]) ?> : <?= e($d['titre']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?= field_error('plats') ?>
                    </fieldset>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Ajouter une image (JPEG, PNG ou WebP, 2 Mo max.)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-md-6">
                        <label for="image_alt" class="form-label">Description de l'image (texte alternatif)</label>
                        <input type="text" class="form-control" id="image_alt" name="image_alt" maxlength="150">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Enregistrer le menu</button>
            </form>

            <?php if ($menu && $menu['images']): ?>
                <h2 class="h5 mt-4">Galerie actuelle</h2>
                <div class="row g-2">
                    <?php foreach ($menu['images'] as $img): ?>
                        <div class="col-6 col-md-3">
                            <img src="<?= e($img['chemin']) ?>" alt="<?= e($img['texte_alt']) ?>" class="img-fluid rounded" width="200" height="125">
                            <form method="post" action="/employe/images/<?= (int) $img['image_id'] ?>/supprimer" class="mt-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Retirer<span class="visually-hidden"> l'image <?= e($img['texte_alt']) ?></span></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
