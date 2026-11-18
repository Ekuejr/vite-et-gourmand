<?php use App\Models\Order; ?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Gestion des commandes</h1>
            <form method="get" action="/employe/commandes" class="filters p-3 mb-3" aria-label="Filtrer les commandes">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="statut" class="form-label">Statut</label>
                        <select id="statut" name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <?php foreach (Order::STATUS_LABELS as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="client" class="form-label">Client (nom, prénom ou e-mail)</label>
                        <input type="search" id="client" name="client" class="form-control" value="<?= e($client) ?>">
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filtrer</button></div>
                </div>
            </form>

            <p role="status"><?= count($orders) ?> commande(s)</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle bg-white">
                    <caption class="visually-hidden">Commandes filtrées</caption>
                    <thead><tr>
                        <th scope="col">N°</th><th scope="col">Client</th><th scope="col">Menu</th><th scope="col">Prestation</th>
                        <th scope="col">Total</th><th scope="col">Statut</th><th scope="col"><span class="visually-hidden">Action</span></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= e($o['numero_commande']) ?></td>
                            <td><?= e($o['prenom'] . ' ' . $o['nom']) ?><br><span class="small"><?= e($o['email']) ?></span></td>
                            <td><?= e($o['menu_titre']) ?> (<?= (int) $o['nombre_personne'] ?> p.)</td>
                            <td><?= date_fr($o['date_prestation']) ?> <?= e(substr($o['heure_livraison'], 0, 5)) ?></td>
                            <td><?= money($o['prix_total']) ?></td>
                            <td><?= e(statut_label($o['statut'])) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="/employe/commandes/<?= (int) $o['commande_id'] ?>">Gérer<span class="visually-hidden"> la commande <?= e($o['numero_commande']) ?></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
