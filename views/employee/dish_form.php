<?php
use App\Core\Session;
use App\Models\Dish;

$v = static function (string $f) use ($dish): string {
    $old = Session::old($f);
    return e($old !== '' ? $old : (string) ($dish[$f] ?? ''));
};
$selected = $dish['allergene_ids'] ?? [];
?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9 form-medium">
            <p><a href="/employe/plats">&larr; Tous les plats</a></p>
            <h1><?= e($title) ?></h1>
            <form method="post" action="<?= $dish ? '/employe/plats/' . (int) $dish['plat_id'] : '/employe/plats/nouveau' ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="titre" class="form-label">Titre</label>
                    <input type="text" class="form-control" id="titre" name="titre" value="<?= $v('titre') ?>" required maxlength="100"<?= aria_error('titre') ?>>
                    <?= field_error('titre') ?>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select">
                        <?php foreach (Dish::TYPES as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= html_entity_decode($v('type')) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error('type') ?>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" value="<?= $v('description') ?>" maxlength="255">
                    <?= field_error('description') ?>
                </div>
                <fieldset class="mb-3">
                    <legend class="form-label fs-6">Allergènes</legend>
                    <div class="row">
                        <?php foreach ($allergens as $a): ?>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allergenes[]" id="al<?= (int) $a['allergene_id'] ?>" value="<?= (int) $a['allergene_id'] ?>" <?= in_array((int) $a['allergene_id'], $selected, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="al<?= (int) $a['allergene_id'] ?>"><?= e($a['libelle']) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
