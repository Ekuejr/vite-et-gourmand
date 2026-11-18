<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PageController extends Controller
{
    public function legal(): void
    {
        $this->view('pages/legal', ['title' => 'Mentions légales']);
    }

    public function cgv(): void
    {
        $this->view('pages/cgv', ['title' => 'Conditions générales de vente']);
    }
}
