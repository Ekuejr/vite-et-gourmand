<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Espace administrateur</h1>
            <p>En tant qu'administrateur, vous disposez de toutes les fonctions de l'espace employé, ainsi que :</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="price-summary p-3 h-100">
                        <h2 class="h5">Comptes employés</h2>
                        <p>Créer un compte employé, désactiver le compte d'un employé qui quitte l'entreprise.</p>
                        <a href="/admin/employes" class="btn btn-primary">Gérer les comptes</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="price-summary p-3 h-100">
                        <h2 class="h5">Statistiques</h2>
                        <p>Nombre de commandes et chiffre d'affaires par menu (données MongoDB).</p>
                        <a href="/admin/statistiques" class="btn btn-primary">Voir les statistiques</a>
                        <?php if (!$mongo): ?><p class="text-danger small mt-2 mb-0">MongoDB n'est pas configuré sur ce serveur.</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
