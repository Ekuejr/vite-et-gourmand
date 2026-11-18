<div class="container mt-4 form-medium">
    <h1>Créer un compte</h1>
    <p>Tous les champs sont obligatoires.</p>
    <form method="post" action="/inscription" novalidate>
        <?= csrf_field() ?>
        <fieldset class="mb-3">
            <legend class="h5">Identité</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= old('nom') ?>" required maxlength="80" autocomplete="family-name"<?= aria_error('nom') ?>>
                    <?= field_error('nom') ?>
                </div>
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?= old('prenom') ?>" required maxlength="80" autocomplete="given-name"<?= aria_error('prenom') ?>>
                    <?= field_error('prenom') ?>
                </div>
                <div class="col-md-6">
                    <label for="telephone" class="form-label">Numéro de mobile (GSM)</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= old('telephone') ?>" required autocomplete="tel" placeholder="06 12 34 56 78"<?= aria_error('telephone') ?>>
                    <?= field_error('telephone') ?>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email"<?= aria_error('email') ?>>
                    <?= field_error('email') ?>
                </div>
            </div>
        </fieldset>
        <fieldset class="mb-3">
            <legend class="h5">Adresse postale</legend>
            <div class="row g-3">
                <div class="col-12">
                    <label for="adresse_postale" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="adresse_postale" name="adresse_postale" value="<?= old('adresse_postale') ?>" required autocomplete="street-address"<?= aria_error('adresse_postale') ?>>
                    <?= field_error('adresse_postale') ?>
                </div>
                <div class="col-md-4">
                    <label for="code_postal" class="form-label">Code postal</label>
                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?= old('code_postal') ?>" required inputmode="numeric" pattern="\d{5}" autocomplete="postal-code"<?= aria_error('code_postal') ?>>
                    <?= field_error('code_postal') ?>
                </div>
                <div class="col-md-8">
                    <label for="ville" class="form-label">Ville</label>
                    <input type="text" class="form-control" id="ville" name="ville" value="<?= old('ville') ?>" required autocomplete="address-level2"<?= aria_error('ville') ?>>
                    <?= field_error('ville') ?>
                </div>
            </div>
        </fieldset>
        <fieldset class="mb-3">
            <legend class="h5">Sécurité</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="10" autocomplete="new-password" aria-describedby="password-help"<?= isset(form_errors()['password']) ? ' aria-invalid="true"' : '' ?>>
                    <div id="password-help" class="form-text">10 caractères minimum, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.</div>
                    <?= field_error('password') ?>
                </div>
                <div class="col-md-6">
                    <label for="password_confirm" class="form-label">Confirmation du mot de passe</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required autocomplete="new-password"<?= aria_error('password_confirm') ?>>
                    <?= field_error('password_confirm') ?>
                </div>
            </div>
        </fieldset>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="rgpd" name="rgpd" value="1" required<?= aria_error('rgpd') ?>>
            <label class="form-check-label" for="rgpd">J'accepte que mes données soient utilisées pour gérer mes commandes, conformément aux <a href="/mentions-legales#rgpd">mentions légales (RGPD)</a>.</label>
            <?= field_error('rgpd') ?>
        </div>
        <button type="submit" class="btn btn-primary">Créer mon compte</button>
    </form>
</div>
