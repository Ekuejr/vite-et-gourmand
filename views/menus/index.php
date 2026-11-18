<?php $scripts = ['/assets/js/menus.js']; ?>
<div class="container mt-4">
    <h1>Nos menus</h1>
    <p class="lead">Retrouvez tous nos menus et affinez votre recherche : la liste se met à jour automatiquement.</p>

    <!-- Le formulaire fonctionne aussi sans JavaScript (envoi classique en GET) : amélioration progressive -->
    <form id="filters" class="filters p-3 mb-4" method="get" action="/menus" aria-label="Filtrer les menus">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label for="prix_min" class="form-label">Prix minimum (€)</label>
                <input type="number" min="0" step="1" class="form-control" id="prix_min" name="prix_min" value="<?= e($_GET['prix_min'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="prix_max" class="form-label">Prix maximum (€)</label>
                <input type="number" min="0" step="1" class="form-control" id="prix_max" name="prix_max" value="<?= e($_GET['prix_max'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="theme" class="form-label">Thème</label>
                <select class="form-select" id="theme" name="theme">
                    <option value="">Tous</option>
                    <?php foreach ($themes as $t): ?>
                        <option value="<?= (int) $t['theme_id'] ?>" <?= (string) ($_GET['theme'] ?? '') === (string) $t['theme_id'] ? 'selected' : '' ?>><?= e($t['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="regime" class="form-label">Régime</label>
                <select class="form-select" id="regime" name="regime">
                    <option value="">Tous</option>
                    <?php foreach ($regimes as $r): ?>
                        <option value="<?= (int) $r['regime_id'] ?>" <?= (string) ($_GET['regime'] ?? '') === (string) $r['regime_id'] ? 'selected' : '' ?>><?= e($r['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="personnes" class="form-label">Nombre de personnes</label>
                <input type="number" min="1" class="form-control" id="personnes" name="personnes" value="<?= e($_GET['personnes'] ?? '') ?>" aria-describedby="personnes-help">
                <div id="personnes-help" class="form-text">Menus accessibles à ce nombre.</div>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="/menus" class="btn btn-outline-primary" id="reset-filters">Effacer</a>
            </div>
        </div>
    </form>

    <p id="results-count" class="fw-semibold" role="status" aria-live="polite"><?= count($menus) ?> menu(s) trouvé(s)</p>
    <div id="menus-list" class="row g-4">
        <?php foreach ($menus as $m): ?>
            <div class="col-md-6 col-lg-4"><?php require __DIR__ . '/../partials/menu_card.php'; ?></div>
        <?php endforeach; ?>
    </div>
</div>
