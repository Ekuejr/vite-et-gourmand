<div class="container mt-4 form-medium">
    <h1>Contactez-nous</h1>
    <p>Une question, une demande de devis sur mesure ? Écrivez-nous, nous vous répondrons par e-mail.</p>
    <form method="post" action="/contact" novalidate>
        <?= csrf_field() ?>
        <!-- Pot de miel anti-robot : champ masqué aux humains et ignoré des lecteurs d'écran -->
        <div class="visually-hidden" aria-hidden="true">
            <label for="website">Ne pas remplir</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="mb-3">
            <label for="titre" class="form-label">Titre de votre demande</label>
            <input type="text" class="form-control" id="titre" name="titre" value="<?= old('titre') ?>" required maxlength="120"<?= aria_error('titre') ?>>
            <?= field_error('titre') ?>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="6" required maxlength="3000"<?= aria_error('description') ?>><?= old('description') ?></textarea>
            <?= field_error('description') ?>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Votre adresse e-mail (pour vous répondre)</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email', (string) (\App\Core\Auth::user()['email'] ?? '')) ?>" required autocomplete="email"<?= aria_error('email') ?>>
            <?= field_error('email') ?>
        </div>
        <button type="submit" class="btn btn-primary">Envoyer</button>
    </form>
</div>
