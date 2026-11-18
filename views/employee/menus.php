<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Gestion des menus</h1>
                <a href="/employe/menus/nouveau" class="btn btn-primary">Nouveau menu</a>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-striped align-middle bg-white">
                    <caption class="visually-hidden">Liste des menus</caption>
                    <thead><tr><th scope="col">Titre</th><th scope="col">Thème</th><th scope="col">Min.</th><th scope="col">Prix</th><th scope="col">Stock</th><th scope="col">En ligne</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($menus as $m): ?>
                        <tr>
                            <td><?= e($m['titre']) ?></td>
                            <td><?= e($m['theme']) ?> / <?= e($m['regime']) ?></td>
                            <td><?= (int) $m['nombre_personne_minimum'] ?></td>
                            <td><?= money($m['prix_minimum']) ?></td>
                            <td><?= (int) $m['quantite_restante'] ?></td>
                            <td><?= $m['actif'] ? 'Oui' : 'Non' ?></td>
                            <td class="d-flex gap-1">
                                <a href="/employe/menus/<?= (int) $m['menu_id'] ?>" class="btn btn-sm btn-outline-primary">Modifier<span class="visually-hidden"> <?= e($m['titre']) ?></span></a>
                                <form method="post" action="/employe/menus/<?= (int) $m['menu_id'] ?>/supprimer">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> <?= e($m['titre']) ?></span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
