<div class="container mt-4 form-narrow">
    <h1>Nouveau mot de passe</h1>
    <form method="post" action="/reinitialisation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3">
            <label for="password" class="form-label">Nouveau mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="10" autocomplete="new-password" aria-describedby="password-help">
            <div id="password-help" class="form-text">10 caractères minimum, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.</div>
            <?= field_error('password') ?>
        </div>
        <div class="mb-3">
            <label for="password_confirm" class="form-label">Confirmation</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required autocomplete="new-password">
            <?= field_error('password_confirm') ?>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
