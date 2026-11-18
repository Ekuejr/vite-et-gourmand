/**
 * Aperçu du prix en direct sur le formulaire de commande.
 * Le calcul est fait par le SERVEUR (/api/prix) avec la même classe PHP que lors de
 * l'enregistrement : une seule source de vérité, aucun prix calculé "de confiance" côté navigateur.
 */
(() => {
    'use strict';

    const form = document.getElementById('order-form');
    if (!form) return;

    const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
    const errorBox = document.getElementById('price-error');
    const fields = ['nombre_personne', 'adresse_prestation', 'code_postal_prestation', 'ville_prestation'];
    let timer = null;

    function setValue(key, text) {
        const node = form.querySelector(`[data-price="${key}"]`);
        if (node) node.textContent = text;
    }

    async function refresh() {
        const params = new URLSearchParams({
            menu_id: form.dataset.menuId,
            nombre_personne: form.nombre_personne.value,
            adresse: form.adresse_prestation.value,
            code_postal: form.code_postal_prestation.value,
            ville: form.ville_prestation.value,
        });
        try {
            const response = await fetch(`/api/prix?${params}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) {
                errorBox.textContent = data.erreur || 'Calcul impossible.';
                ['prix_menu', 'remise', 'prix_livraison', 'prix_total'].forEach((k) => setValue(k, '-'));
                return;
            }
            errorBox.textContent = '';
            setValue('prix_menu', euro.format(data.prix_menu));
            setValue('remise', data.remise > 0 ? `- ${euro.format(data.remise)}` : euro.format(0));
            setValue('prix_livraison', data.prix_livraison > 0 ? euro.format(data.prix_livraison) : 'Offerte');
            setValue('distance_km', data.distance_km > 0 ? `(${data.distance_km.toLocaleString('fr-FR')} km)` : '');
            setValue('prix_total', euro.format(data.prix_total));
        } catch (err) {
            errorBox.textContent = 'Aperçu du prix momentanément indisponible.';
        }
    }

    fields.forEach((name) => {
        form[name]?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(refresh, 400);
        });
    });
    refresh();
})();
