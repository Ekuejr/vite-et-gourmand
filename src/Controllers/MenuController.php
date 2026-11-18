<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Menu;
use App\Models\Reference;

final class MenuController extends Controller
{
    /** Vue globale : le premier affichage est rendu côté serveur (fonctionne sans JS), puis les filtres passent par l'API. */
    public function index(): void
    {
        $ref = new Reference();
        $this->view('menus/index', [
            'title'   => 'Nos menus',
            'menus'   => (new Menu())->search(ApiController::readFilters()),
            'themes'  => $ref->themes(),
            'regimes' => $ref->regimes(),
        ]);
    }

    public function show(int $id): void
    {
        $menu = (new Menu())->find($id);
        if ($menu === null) {
            $this->notFound();
        }
        $this->view('menus/show', ['title' => $menu['titre'], 'menu' => $menu]);
    }
}
