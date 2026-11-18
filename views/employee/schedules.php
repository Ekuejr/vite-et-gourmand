<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Horaires d'ouverture</h1>
            <p>Ces horaires sont affichés dans le pied de page de toutes les pages du site.</p>
            <form method="post" action="/employe/horaires">
                <?= csrf_field() ?>
                <table class="table bg-white align-middle">
                    <caption class="visually-hidden">Horaires par jour</caption>
                    <thead><tr><th scope="col">Jour</th><th scope="col">Ouverture</th><th scope="col">Fermeture</th><th scope="col">Fermé</th></tr></thead>
                    <tbody>
                    <?php foreach ($schedules as $h): $id = (int) $h['horaire_id']; ?>
                        <tr>
                            <th scope="row"><?= e($h['jour']) ?></th>
                            <td><label class="visually-hidden" for="o<?= $id ?>">Ouverture <?= e($h['jour']) ?></label>
                                <input type="time" class="form-control" id="o<?= $id ?>" name="ouverture[<?= $id ?>]" value="<?= e(substr((string) $h['heure_ouverture'], 0, 5)) ?>"></td>
                            <td><label class="visually-hidden" for="f<?= $id ?>">Fermeture <?= e($h['jour']) ?></label>
                                <input type="time" class="form-control" id="f<?= $id ?>" name="fermeture[<?= $id ?>]" value="<?= e(substr((string) $h['heure_fermeture'], 0, 5)) ?>"></td>
                            <td><div class="form-check"><input class="form-check-input" type="checkbox" id="c<?= $id ?>" name="ferme[<?= $id ?>]" value="1" <?= $h['ferme'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="c<?= $id ?>">Fermé<span class="visually-hidden"> le <?= e($h['jour']) ?></span></label></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="btn btn-primary" type="submit">Enregistrer les horaires</button>
            </form>
        </div>
    </div>
</div>
