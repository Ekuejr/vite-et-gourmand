/**
 * Filtres dynamiques de la vue globale des menus.
 * À chaque modification d'un filtre, on interroge l'API /api/menus (fetch) et on
 * reconstruit la liste SANS recharger la page.
 *
 * Sécurité : les cartes sont construites avec createElement + textContent
 * (jamais innerHTML avec des données) => aucune injection XSS possible.
 * Accessibilité : le nombre de résultats est annoncé aux lecteurs d'écran (aria-live).
 */
(() => {
    'use strict';

    const form = document.getElementById('filters');
    const list = document.getElementById('menus-list');
    const count = document.getElementById('results-count');
    if (!form || !list) return;

    const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
    let timer = null;
    let controller = null;

    /** Petite fonction utilitaire de création d'élément. */
    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function renderCard(menu) {
        const col = el('div', 'col-md-6 col-lg-4');
        const card = el('article', 'card card-menu h-100');

        if (menu.image) {
            const img = el('img', 'card-img-top');
            img.src = menu.image;
            img.alt = menu.image_alt || '';
            img.loading = 'lazy';
            img.width = 400;
            img.height = 250;
            card.append(img);
        }
        const body = el('div', 'card-body d-flex flex-column');
        body.append(el('h3', 'h5 card-title', menu.titre));

        const badges = el('p', 'mb-2');
        badges.append(el('span', 'badge badge-theme', menu.theme), ' ', el('span', 'badge badge-regime', menu.regime));
        body.append(badges, el('p', 'card-text', menu.description));

        const min = el('p', 'mb-1', 'Minimum : ');
        min.append(el('strong', '', `${menu.personnes} personnes`));
        body.append(min);

        const price = el('p', 'price mb-3', euro.format(menu.prix) + ' ');
        price.append(el('span', 'fs-6 fw-normal text-body-secondary', `pour ${menu.personnes} pers.`));
        body.append(price);

        const link = el('a', 'btn btn-primary mt-auto', 'Voir le détail');
        link.href = `/menus/${encodeURIComponent(menu.id)}`;
        link.append(el('span', 'visually-hidden', ` du menu ${menu.titre}`));
        body.append(link);

        card.append(body);
        col.append(card);
        return col;
    }

    async function refresh() {
        const params = new URLSearchParams();
        for (const [key, value] of new FormData(form)) {
            if (value !== '') params.append(key, value);
        }
        // Annule la requête précédente si l'utilisateur tape vite (évite des réponses dans le désordre)
        if (controller) controller.abort();
        controller = new AbortController();

        try {
            const response = await fetch(`/api/menus?${params}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const menus = await response.json();

            list.replaceChildren(...menus.map(renderCard));
            count.textContent = `${menus.length} menu(s) trouvé(s)`;
            if (menus.length === 0) {
                list.append(el('p', 'col-12', 'Aucun menu ne correspond à vos critères.'));
            }
            // L'URL reflète les filtres : la recherche reste partageable et compatible avec "Précédent"
            history.replaceState(null, '', params.toString() ? `/menus?${params}` : '/menus');
        } catch (err) {
            if (err.name !== 'AbortError') {
                count.textContent = 'Erreur lors du chargement des menus. Veuillez réessayer.';
            }
        }
    }

    // Délai de 300 ms ("debounce") : on n'envoie pas une requête à chaque touche frappée
    form.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh();
    });
    document.getElementById('reset-filters')?.addEventListener('click', (event) => {
        event.preventDefault();
        form.reset();
        form.querySelectorAll('input').forEach((input) => { input.value = ''; });
        form.querySelectorAll('select').forEach((select) => { select.value = ''; });
        refresh();
    });
})();
