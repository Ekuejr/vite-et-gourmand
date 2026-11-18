<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Menu;
use App\Models\Order;
use App\Services\Geocoder;
use App\Services\Mailer;
use App\Services\OrderWorkflow;
use App\Services\PriceCalculator;
use App\Services\StatsService;

/**
 * Passage, modification et annulation d'une commande par le client.
 */
final class OrderController extends Controller
{
    public function form(int $id): void
    {
        $menu = (new Menu())->find($id);
        if ($menu === null) {
            $this->notFound();
        }
        $this->view('order/form', [
            'title' => 'Commander : ' . $menu['titre'],
            'menu'  => $menu,
            'user'  => Auth::user(),
            'order' => null,
        ]);
    }

    public function store(int $id): void
    {
        $menu = (new Menu())->find($id);
        if ($menu === null) {
            $this->notFound();
        }
        [$data, $price, $errors] = $this->validate($menu);
        if ($errors) {
            $this->back('/commander/' . $id, $errors);
        }

        try {
            $orderModel = new Order();
            $orderId = $orderModel->create((int) Auth::id(), $menu, $data, $price);
        } catch (\DomainException $e) {
            $this->back('/commander/' . $id, ['nombre_personne' => $e->getMessage()]);
        }

        $order = $orderModel->find($orderId);
        (new StatsService())->upsertOrder($order);
        (new Mailer())->orderConfirmation($order);
        Session::clearOld();
        Session::flash('success', 'Commande ' . $order['numero_commande'] . ' enregistrée ! Un e-mail de confirmation vous a été envoyé.');
        $this->redirect('/mon-compte/commandes/' . $orderId);
    }

    public function editForm(int $id): void
    {
        $order = (new Order())->findForUser($id, (int) Auth::id());
        if ($order === null) {
            $this->notFound();
        }
        if (!OrderWorkflow::userCanEdit($order['statut'])) {
            Session::flash('warning', 'Cette commande a déjà été acceptée : elle n\'est plus modifiable.');
            $this->redirect('/mon-compte/commandes/' . $id);
        }
        $this->view('order/form', [
            'title' => 'Modifier la commande ' . $order['numero_commande'],
            'menu'  => (new Menu())->find((int) $order['menu_id'], false),
            'user'  => Auth::user(),
            'order' => $order,
        ]);
    }

    public function update(int $id): void
    {
        $orderModel = new Order();
        $order = $orderModel->findForUser($id, (int) Auth::id());
        if ($order === null) {
            $this->notFound();
        }
        if (!OrderWorkflow::userCanEdit($order['statut'])) {
            Session::flash('warning', 'Cette commande a déjà été acceptée : elle n\'est plus modifiable.');
            $this->redirect('/mon-compte/commandes/' . $id);
        }
        // Le menu reste celui de la commande d'origine (non modifiable) : on ne lit PAS menu_id du formulaire
        $menu = (new Menu())->find((int) $order['menu_id'], false);
        [$data, $price, $errors] = $this->validate($menu);
        if ($errors) {
            $this->back('/mon-compte/commandes/' . $id . '/modifier', $errors);
        }
        $orderModel->updateByUser($id, (int) Auth::id(), $data, $price);
        (new StatsService())->upsertOrder($orderModel->find($id));
        Session::clearOld();
        Session::flash('success', 'Votre commande a été modifiée.');
        $this->redirect('/mon-compte/commandes/' . $id);
    }

    public function cancel(int $id): void
    {
        $orderModel = new Order();
        $order = $orderModel->findForUser($id, (int) Auth::id());
        if ($order === null) {
            $this->notFound();
        }
        if (!OrderWorkflow::userCanEdit($order['statut'])) {
            Session::flash('warning', 'Cette commande a déjà été acceptée : contactez-nous pour toute annulation.');
            $this->redirect('/mon-compte/commandes/' . $id);
        }
        $orderModel->cancel($id, (int) Auth::id());
        (new StatsService())->upsertOrder($orderModel->find($id));
        Session::flash('success', 'Votre commande a été annulée.');
        $this->redirect('/mon-compte');
    }

    /**
     * Validation serveur du formulaire + calcul du prix.
     * @return array{0: array, 1: array, 2: array} [données propres, prix, erreurs]
     */
    private function validate(array $menu): array
    {
        $d = [
            'date_prestation'        => $this->input('date_prestation'),
            'heure_livraison'        => $this->input('heure_livraison'),
            'adresse_prestation'     => $this->input('adresse_prestation'),
            'code_postal_prestation' => $this->input('code_postal_prestation'),
            'ville_prestation'       => $this->input('ville_prestation'),
            'nombre_personne'        => (int) $this->input('nombre_personne'),
        ];
        $errors = [];
        if (!Validator::isDate($d['date_prestation']) || $d['date_prestation'] <= date('Y-m-d')) {
            $errors['date_prestation'] = 'La date de prestation doit être une date future.';
        }
        if (!Validator::isTime($d['heure_livraison'])) {
            $errors['heure_livraison'] = 'Heure de livraison invalide.';
        }
        if (!Validator::length($d['adresse_prestation'], 3, 255)) {
            $errors['adresse_prestation'] = 'L\'adresse de la prestation est obligatoire.';
        }
        if (!Validator::isPostalCode($d['code_postal_prestation'])) {
            $errors['code_postal_prestation'] = 'Code postal invalide (5 chiffres).';
        }
        if (!Validator::length($d['ville_prestation'], 1, 100)) {
            $errors['ville_prestation'] = 'La ville est obligatoire.';
        }
        if ($d['nombre_personne'] < (int) $menu['nombre_personne_minimum']) {
            $errors['nombre_personne'] = 'Ce menu se commande pour ' . (int) $menu['nombre_personne_minimum'] . ' personnes minimum.';
        }

        $price = [];
        if (!$errors) {
            $km = 0.0;
            if (!PriceCalculator::isHomeCity($d['ville_prestation'])) {
                $km = (new Geocoder())->distanceFromBordeaux($d['adresse_prestation'], $d['code_postal_prestation'], $d['ville_prestation']);
                if ($km === null) {
                    $errors['adresse_prestation'] = 'Adresse introuvable : impossible de calculer les frais de livraison.';
                }
            }
            if (!$errors) {
                $price = PriceCalculator::compute((float) $menu['prix_minimum'], (int) $menu['nombre_personne_minimum'],
                    $d['nombre_personne'], $d['ville_prestation'], (float) $km);
            }
        }
        return [$d, $price, $errors];
    }
}
