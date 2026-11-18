<div class="container mt-4 form-narrow">
    <h1>Connexion</h1>
    <p>Accès réservé aux clients, aux employés et à l'administrateur.</p>
    <form method="post" action="/connexion" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail (identifiant)</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autocomplete="username"<?= aria_error('email') ?>>
            <?= field_error('email') ?>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    </form>
    <p class="mt-3"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
    <p>Pas encore de compte ? <a href="/inscription">Créer un compte</a></p>
</div>
