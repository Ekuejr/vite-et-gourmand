<?php $scripts = ['/assets/vendor/chartjs/chart.umd.min.js', '/assets/js/admin-stats.js']; ?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Statistiques</h1>
            <p>Données issues de la base NoSQL <strong>MongoDB</strong> (commandes annulées exclues).</p>
            <?php if (!$mongo): ?>
                <div class="alert alert-warning">MongoDB n'est pas configuré : renseignez MONGO_URI dans le fichier .env puis lancez <code>php tools/sync_mongo.php</code>.</div>
            <?php endif; ?>

            <form id="stats-filters" class="filters p-3 mb-3" aria-label="Filtrer les statistiques">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="menu_id" class="form-label">Menu</label>
                        <select id="menu_id" name="menu_id" class="form-select">
                            <option value="">Tous les menus</option>
                            <?php foreach ($menus as $m): ?>
                                <option value="<?= (int) $m['menu_id'] ?>"><?= e($m['titre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><label for="du" class="form-label">Du</label><input type="date" id="du" name="du" class="form-control"></div>
                    <div class="col-md-3"><label for="au" class="form-label">Au</label><input type="date" id="au" name="au" class="form-control"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Appliquer</button></div>
                </div>
            </form>

            <div class="row g-3 mb-3">
                <div class="col-md-6"><div class="price-summary p-3"><p class="mb-0">Commandes</p><p class="h3 mb-0" id="kpi-orders">-</p></div></div>
                <div class="col-md-6"><div class="price-summary p-3"><p class="mb-0">Chiffre d'affaires</p><p class="h3 mb-0" id="kpi-ca">-</p></div></div>
            </div>

            <div class="price-summary p-3 chart-box">
                <canvas id="chart" role="img" aria-label="Graphique comparant le nombre de commandes par menu"></canvas>
            </div>
            <p id="stats-error" class="text-danger mt-2" role="alert"></p>

            <!-- Alternative textuelle du graphique (RGAA : information non transmise uniquement par l'image) -->
            <table class="table table-sm bg-white mt-3">
                <caption>Détail par menu</caption>
                <thead><tr><th scope="col">Menu</th><th scope="col">Commandes</th><th scope="col">Chiffre d'affaires</th></tr></thead>
                <tbody id="stats-table"></tbody>
            </table>
        </div>
    </div>
</div>
