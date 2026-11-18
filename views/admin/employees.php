<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Comptes employés</h1>

            <section class="price-summary p-3 mb-4" aria-labelledby="new-title">
                <h2 id="new-title" class="h5">Créer un compte employé</h2>
                <p class="small">L'employé reçoit un e-mail l'informant de la création de son compte. Le mot de passe n'y figure pas : communiquez-le-lui de vive voix.
                    Il n'est pas possible de créer un compte administrateur depuis l'application.</p>
                <form method="post" action="/admin/employes" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-md-3"><label for="prenom" class="form-label">Prénom</label><input type="text" id="prenom" name="prenom" class="form-control" value="<?= old('prenom') ?>" maxlength="80"></div>
                        <div class="col-md-3"><label for="nom" class="form-label">Nom</label><input type="text" id="nom" name="nom" class="form-control" value="<?= old('nom') ?>" maxlength="80"></div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail (identifiant)</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= old('email') ?>" required<?= aria_error('email') ?>>
                            <?= field_error('email') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Mot de passe provisoire</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" aria-describedby="pwd-help">
                            <div id="pwd-help" class="form-text">10 caractères min. : majuscule, minuscule, chiffre, caractère spécial.</div>
                            <?= field_error('password') ?>
                        </div>
                        <div class="col-md-6 d-flex align-items-end"><button type="submit" class="btn btn-primary">Créer le compte</button></div>
                    </div>
                </form>
            </section>

            <table class="table table-striped bg-white align-middle">
                <caption class="visually-hidden">Liste des employés</caption>
                <thead><tr><th scope="col">Employé</th><th scope="col">E-mail</th><th scope="col">État</th><th scope="col">Action</th></tr></thead>
                <tbody>
                <?php foreach ($employees as $u): ?>
                    <tr>
                        <td><?= e($u['prenom'] . ' ' . $u['nom']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= $u['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Désactivé</span>' ?></td>
                        <td>
                            <form method="post" action="/admin/employes/<?= (int) $u['utilisateur_id'] ?>/statut">
                                <?= csrf_field() ?>
                                <input type="hidden" name="actif" value="<?= $u['actif'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm <?= $u['actif'] ? 'btn-outline-danger' : 'btn-outline-primary' ?>"><?= $u['actif'] ? 'Désactiver' : 'Réactiver' ?><span class="visually-hidden"> le compte de <?= e($u['prenom']) ?></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
