<div class="container mt-4 form-narrow">
    <h1>Mot de passe oublié</h1>
    <p>Indiquez votre adresse e-mail : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
    <form method="post" action="/mot-de-passe-oublie" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
        </div>
        <button type="submit" class="btn btn-primary">Envoyer le lien</button>
    </form>
</div>
