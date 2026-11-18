/**
 * Graphique des statistiques administrateur (Chart.js, auto-hébergé).
 * Les données viennent de /admin/statistiques/donnees, qui interroge MongoDB.
 */
(() => {
    'use strict';

    const form = document.getElementById('stats-filters');
    const canvas = document.getElementById('chart');
    if (!form || !canvas || typeof Chart === 'undefined') return;

    const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
    const errorBox = document.getElementById('stats-error');
    const table = document.getElementById('stats-table');

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                { label: 'Nombre de commandes', data: [], backgroundColor: '#7a1f3d', yAxisID: 'y' },
                { label: "Chiffre d'affaires (€)", data: [], backgroundColor: '#c8963e', yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true,
            animation: false, // pas d'animation : accessibilité (RGAA) et sobriété (éco-conception)
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Commandes' }, ticks: { precision: 0 } },
                y1: { beginAtZero: true, position: 'right', title: { display: true, text: '€' }, grid: { drawOnChartArea: false } },
            },
        },
    });

    async function load() {
        const params = new URLSearchParams();
        for (const [k, v] of new FormData(form)) if (v) params.append(k, v);
        try {
            const res = await fetch(`/admin/statistiques/donnees?${params}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok) throw new Error(data.erreur || 'Erreur');
            errorBox.textContent = '';

            chart.data.labels = data.menus.map((m) => m.menu_titre);
            chart.data.datasets[0].data = data.menus.map((m) => m.nb_commandes);
            chart.data.datasets[1].data = data.menus.map((m) => m.chiffre_affaires);
            chart.update();

            document.getElementById('kpi-orders').textContent = data.total_commandes;
            document.getElementById('kpi-ca').textContent = euro.format(data.total_ca);

            table.replaceChildren(...data.menus.map((m) => {
                const tr = document.createElement('tr');
                [m.menu_titre, String(m.nb_commandes), euro.format(m.chiffre_affaires)].forEach((txt) => {
                    const td = document.createElement('td');
                    td.textContent = txt;
                    tr.append(td);
                });
                return tr;
            }));
        } catch (err) {
            errorBox.textContent = err.message;
        }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); load(); });
    load();
})();
