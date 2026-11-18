<?php use App\Models\Dish; ?>
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Gestion des plats</h1>
                <a href="/employe/plats/nouveau" class="btn btn-primary">Nouveau plat</a>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-striped align-middle bg-white">
                    <caption class="visually-hidden">Liste des plats</caption>
                    <thead><tr><th scope="col">Type</th><th scope="col">Titre</th><th scope="col">Allergènes</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($dishes as $d): ?>
                        <tr>
                            <td><?= e(Dish::TYPES[$d['type']]) ?></td>
                            <td><?= e($d['titre']) ?></td>
                            <td class="small"><?= e($d['allergenes'] ?: '-') ?></td>
                            <td class="d-flex gap-1">
                                <a href="/employe/plats/<?= (int) $d['plat_id'] ?>" class="btn btn-sm btn-outline-primary">Modifier<span class="visually-hidden"> <?= e($d['titre']) ?></span></a>
                                <form method="post" action="/employe/plats/<?= (int) $d['plat_id'] ?>/supprimer">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> <?= e($d['titre']) ?></span></button>
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
