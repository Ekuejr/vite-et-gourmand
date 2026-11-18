<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Menu;
use App\Services\Geocoder;
use App\Services\PriceCalculator;

/**
 * Points d'accès JSON appelés en JavaScript (fetch) : réponses légères, sans rechargement de page.
 */
final class ApiController extends Controller
{
    /** Lecture et nettoyage des filtres : seuls des nombres positifs sont acceptés. */
    public static function readFilters(): array
    {
        $num = static function (string $key, bool $float = false): int|float|null {
            $v = $_GET[$key] ?? '';
            if (!is_string($v) || $v === '' || !is_numeric($v) || $v < 0) {
                return null;
            }
            return $float ? (float) $v : (int) $v;
        };
        return [
            'prix_max'  => $num('prix_max', true),
            'prix_min'  => $num('prix_min', true),
            'theme'     => $num('theme'),
            'regime'    => $num('regime'),
            'personnes' => $num('personnes'),
        ];
    }

    /** GET /api/menus?prix_max=&prix_min=&theme=&regime=&personnes= */
    public function menus(): void
    {
        $menus = (new Menu())->search(self::readFilters());
        View::json(array_map(static fn(array $m) => [
            'id'          => (int) $m['menu_id'],
            'titre'       => $m['titre'],
            'description' => $m['description'],
            'theme'       => $m['theme'],
            'regime'      => $m['regime'],
            'personnes'   => (int) $m['nombre_personne_minimum'],
            'prix'        => (float) $m['prix_minimum'],
            'stock'       => (int) $m['quantite_restante'],
            'image'       => $m['image'],
            'image_alt'   => $m['image_alt'],
        ], $menus));
    }

    /** GET /api/prix : aperçu du prix détaillé avant validation de la commande. */
    public function price(): void
    {
        $menu = (new Menu())->find((int) $this->input('menu_id'));
        if ($menu === null) {
            View::json(['erreur' => 'Menu introuvable.'], 404);
            return;
        }
        $people = (int) $this->input('nombre_personne');
        $city = $this->input('ville');
        try {
            $km = 0.0;
            if ($city !== '' && !PriceCalculator::isHomeCity($city)) {
                $km = (new Geocoder())->distanceFromBordeaux($this->input('adresse'), $this->input('code_postal'), $city);
                if ($km === null) {
                    View::json(['erreur' => 'Adresse introuvable : vérifiez l\'adresse, le code postal et la ville.'], 422);
                    return;
                }
            }
            $price = PriceCalculator::compute((float) $menu['prix_minimum'], (int) $menu['nombre_personne_minimum'], $people, $city, $km);
            View::json($price);
        } catch (\DomainException $e) {
            View::json(['erreur' => $e->getMessage()], 422);
        }
    }
}
