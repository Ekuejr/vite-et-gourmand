<div class="container mt-5 form-medium">
    <h1>Une erreur est survenue</h1>
    <p>Nous sommes désolés, un problème technique empêche l'affichage de cette page. Merci de réessayer dans quelques instants.</p>
    <?php if (!empty($debug)): ?>
        <!-- Visible uniquement en environnement de développement (APP_ENV=dev) -->
        <pre class="alert alert-secondary small"><?= e($debug) ?></pre>
    <?php endif; ?>
    <p><a href="/" class="btn btn-primary">Retour à l'accueil</a></p>
</div>
