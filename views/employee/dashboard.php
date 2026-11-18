<div class="container mt-4">
    <div class="row g-4">
        <div class="col-lg-3"><?php require __DIR__ . '/../partials/staff_nav.php'; ?></div>
        <div class="col-lg-9">
            <h1>Espace employé</h1>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="price-summary p-3 h-100">
                        <p class="h2 mb-0"><?= (int) $pending ?></p>
                        <p>commande(s) à valider</p>
                        <a href="/employe/commandes?statut=en_attente">Voir</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="price-summary p-3 h-100">
                        <p class="h2 mb-0"><?= (int) $materialWait ?></p>
                        <p>retour(s) de matériel attendu(s)</p>
                        <a href="/employe/commandes?statut=en_attente_retour_materiel">Voir</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="price-summary p-3 h-100">
                        <p class="h2 mb-0"><?= (int) $reviewsPending ?></p>
                        <p>avis à modérer</p>
                        <a href="/employe/avis">Voir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
