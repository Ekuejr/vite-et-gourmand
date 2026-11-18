<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Menu;
use App\Models\Review;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title'   => 'Traiteur à Bordeaux depuis 25 ans',
            'reviews' => (new Review())->validated(),
            'menus'   => array_slice((new Menu())->search(), 0, 3),
        ]);
    }
}
