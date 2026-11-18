<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Reference;
use App\Models\Review;
use App\Services\ImageUploader;
use App\Services\Mailer;
use App\Services\OrderWorkflow;
use App\Services\StatsService;

/**
 * Espace employé (également accessible à l'administrateur).
 * Le contrôle d'accès est fait par le routeur (rôles employe + administrateur).
 */
final class EmployeeController extends Controller
{
    public function dashboard(): void
    {
        $orders = new Order();
        $this->view('employee/dashboard', [
            'title'          => 'Espace employé',
            'pending'        => count($orders->search('en_attente', null)),
            'materialWait'   => count($orders->search('en_attente_retour_materiel', null)),
            'reviewsPending' => count((new Review())->byStatus('en_attente')),
        ]);
    }

    // ------------------------------------------------------------------ Commandes

    public function orders(): void
    {
        $status = $this->input('statut');
        $client = $this->input('client');
        $this->view('employee/orders', [
            'title'  => 'Gestion des commandes',
            'orders' => (new Order())->search($status, $client),
            'status' => $status,
            'client' => $client,
        ]);
    }

    public function order(int $id): void
    {
        $model = new Order();
        $order = $model->find($id);
        if ($order === null) {
            $this->notFound();
        }
        $this->view('employee/order', [
            'title'   => 'Commande ' . $order['numero_commande'],
            'order'   => $order,
            'history' => $model->history($id),
            'next'    => OrderWorkflow::nextStatuses($order['statut'], (bool) $order['pret_materiel']),
        ]);
    }

    public function status(int $id): void
    {
        $model = new Order();
        $order = $model->find($id);
        if ($order === null) {
            $this->notFound();
        }
        $new = $this->input('statut');
        if (!OrderWorkflow::canTransition($order['statut'], $new, (bool) $order['pret_materiel'])) {
            Session::flash('danger', 'Changement de statut non autorisé.');
            $this->redirect('/employe/commandes/' . $id);
        }
        $model->changeStatus($id, $new, (int) Auth::id());
        $order = $model->find($id);
        (new StatsService())->upsertOrder($order);

        // Notifications automatiques
        $mailer = new Mailer();
        if ($new === 'en_attente_retour_materiel') {
            $mailer->materialReturn($order);
        } elseif ($new === 'terminee') {
            $mailer->orderFinished($order, App::config('url') . '/mon-compte/commandes/' . $id);
        }
        Session::flash('success', 'Statut mis à jour : ' . statut_label($new) . '.');
        $this->redirect('/employe/commandes/' . $id);
    }

    /** Matériel prêté (à cocher avant la livraison). */
    public function material(int $id): void
    {
        $model = new Order();
        $order = $model->find($id);
        if ($order === null || in_array($order['statut'], ['livre', 'en_attente_retour_materiel', 'terminee', 'annulee'], true)) {
            Session::flash('danger', 'Le prêt de matériel ne peut plus être modifié pour cette commande.');
            $this->redirect('/employe/commandes/' . $id);
        }
        $model->setMaterialLent($id, !empty($_POST['pret_materiel']));
        Session::flash('success', 'Information sur le prêt de matériel enregistrée.');
        $this->redirect('/employe/commandes/' . $id);
    }

    /** Annulation par le personnel : contact préalable du client obligatoire (mode + motif). */
    public function cancel(int $id): void
    {
        $model = new Order();
        $order = $model->find($id);
        if ($order === null) {
            $this->notFound();
        }
        $mode = $this->input('mode_contact');
        $reason = $this->input('motif');
        $errors = [];
        if (!in_array($mode, ['gsm', 'mail'], true)) {
            $errors['mode_contact'] = 'Indiquez comment le client a été contacté (GSM ou e-mail).';
        }
        if (!Validator::length($reason, 5, 255)) {
            $errors['motif'] = 'Le motif d\'annulation est obligatoire (5 à 255 caractères).';
        }
        if (!OrderWorkflow::staffCanCancel($order['statut'])) {
            $errors['motif'] = 'Cette commande ne peut plus être annulée à ce stade.';
        }
        if ($errors) {
            $this->back('/employe/commandes/' . $id, $errors);
        }
        $model->cancel($id, (int) Auth::id(), $mode, $reason);
        $order = $model->find($id);
        (new StatsService())->upsertOrder($order);
        (new Mailer())->orderCancelled($order, $reason);
        Session::clearOld();
        Session::flash('success', 'Commande annulée, le client a été notifié par e-mail.');
        $this->redirect('/employe/commandes/' . $id);
    }

    // ------------------------------------------------------------------ Menus

    public function menus(): void
    {
        $this->view('employee/menus', ['title' => 'Gestion des menus', 'menus' => (new Menu())->all()]);
    }

    public function menuForm(int $id = 0): void
    {
        $menuModel = new Menu();
        $menu = $id ? $menuModel->find($id, false) : null;
        if ($id && $menu === null) {
            $this->notFound();
        }
        $ref = new Reference();
        $this->view('employee/menu_form', [
            'title'   => $menu ? 'Modifier le menu' : 'Nouveau menu',
            'menu'    => $menu,
            'dishIds' => $menu ? $menuModel->dishIds($id) : [],
            'dishes'  => (new Dish())->all(),
            'themes'  => $ref->themes(),
            'regimes' => $ref->regimes(),
        ]);
    }

    public function menuSave(int $id = 0): void
    {
        $ref = new Reference();
        $d = [
            'titre' => $this->input('titre'), 'description' => $this->input('description'),
            'theme_id' => (int) $this->input('theme_id'), 'regime_id' => (int) $this->input('regime_id'),
            'nombre_personne_minimum' => (int) $this->input('nombre_personne_minimum'),
            'prix_minimum' => str_replace(',', '.', $this->input('prix_minimum')),
            'conditions' => $this->input('conditions'), 'quantite_restante' => (int) $this->input('quantite_restante'),
            'actif' => empty($_POST['actif']) ? 0 : 1,
        ];
        // Les identifiants de plats cochés sont filtrés : seuls les plats existants sont retenus
        $dishIds = array_values(array_intersect(array_map('intval', (array) ($_POST['plats'] ?? [])), $ref->existingIds('plat', 'plat_id')));

        $errors = [];
        if (!Validator::length($d['titre'], 3, 100)) {
            $errors['titre'] = 'Titre obligatoire (3 à 100 caractères).';
        }
        if (!Validator::length($d['description'], 10, 2000)) {
            $errors['description'] = 'Description obligatoire (10 à 2000 caractères).';
        }
        if (!in_array($d['theme_id'], $ref->existingIds('theme', 'theme_id'), true)) {
            $errors['theme_id'] = 'Thème invalide.';
        }
        if (!in_array($d['regime_id'], $ref->existingIds('regime', 'regime_id'), true)) {
            $errors['regime_id'] = 'Régime invalide.';
        }
        if ($d['nombre_personne_minimum'] < 1 || $d['nombre_personne_minimum'] > 500) {
            $errors['nombre_personne_minimum'] = 'Nombre de personnes minimum entre 1 et 500.';
        }
        if (!is_numeric($d['prix_minimum']) || (float) $d['prix_minimum'] <= 0) {
            $errors['prix_minimum'] = 'Prix invalide.';
        }
        if (!Validator::length($d['conditions'], 5, 2000)) {
            $errors['conditions'] = 'Les conditions du menu sont obligatoires.';
        }
        if ($d['quantite_restante'] < 0) {
            $errors['quantite_restante'] = 'Le stock ne peut pas être négatif.';
        }
        if (!$dishIds) {
            $errors['plats'] = 'Sélectionnez au moins un plat.';
        }
        $url = $id ? '/employe/menus/' . $id : '/employe/menus/nouveau';
        if ($errors) {
            $this->back($url, $errors);
        }

        $menuModel = new Menu();
        $menuId = $menuModel->save($id ?: null, $d, $dishIds);

        // Image facultative
        if (!empty($_FILES['image']['name'])) {
            try {
                $alt = $this->input('image_alt') ?: $d['titre'];
                $menuModel->addImage($menuId, ImageUploader::store($_FILES['image']), mb_substr($alt, 0, 150));
            } catch (\DomainException $e) {
                Session::flash('warning', 'Menu enregistré, mais image refusée : ' . $e->getMessage());
                $this->redirect('/employe/menus/' . $menuId);
            }
        }
        Session::clearOld();
        Session::flash('success', 'Menu enregistré.');
        $this->redirect('/employe/menus/' . $menuId);
    }

    public function menuDelete(int $id): void
    {
        $result = (new Menu())->delete($id);
        Session::flash('success', $result === 'archive'
            ? 'Ce menu a déjà été commandé : il a été archivé (masqué du site) pour conserver l\'historique.'
            : 'Menu supprimé.');
        $this->redirect('/employe/menus');
    }

    public function imageDelete(int $id): void
    {
        $img = (new Menu())->deleteImage($id);
        ImageUploader::delete($img['chemin'] ?? null);
        Session::flash('success', 'Image supprimée.');
        $this->redirect($img ? '/employe/menus/' . (int) $img['menu_id'] : '/employe/menus');
    }

    // ------------------------------------------------------------------ Plats

    public function dishes(): void
    {
        $this->view('employee/dishes', ['title' => 'Gestion des plats', 'dishes' => (new Dish())->all()]);
    }

    public function dishForm(int $id = 0): void
    {
        $dish = $id ? (new Dish())->find($id) : null;
        if ($id && $dish === null) {
            $this->notFound();
        }
        $this->view('employee/dish_form', [
            'title'      => $dish ? 'Modifier le plat' : 'Nouveau plat',
            'dish'       => $dish,
            'allergens'  => (new Reference())->allergens(),
        ]);
    }

    public function dishSave(int $id = 0): void
    {
        $d = ['titre' => $this->input('titre'), 'type' => $this->input('type'), 'description' => $this->input('description')];
        $allergens = array_values(array_intersect(
            array_map('intval', (array) ($_POST['allergenes'] ?? [])),
            (new Reference())->existingIds('allergene', 'allergene_id')
        ));
        $errors = [];
        if (!Validator::length($d['titre'], 3, 100)) {
            $errors['titre'] = 'Titre obligatoire (3 à 100 caractères).';
        }
        if (!array_key_exists($d['type'], Dish::TYPES)) {
            $errors['type'] = 'Type de plat invalide.';
        }
        if (mb_strlen($d['description']) > 255) {
            $errors['description'] = '255 caractères maximum.';
        }
        if ($errors) {
            $this->back($id ? '/employe/plats/' . $id : '/employe/plats/nouveau', $errors);
        }
        (new Dish())->save($id ?: null, $d, $allergens);
        Session::clearOld();
        Session::flash('success', 'Plat enregistré.');
        $this->redirect('/employe/plats');
    }

    public function dishDelete(int $id): void
    {
        (new Dish())->delete($id);
        Session::flash('success', 'Plat supprimé (et retiré des menus qui le contenaient).');
        $this->redirect('/employe/plats');
    }

    // ------------------------------------------------------------------ Horaires

    public function schedules(): void
    {
        $this->view('employee/schedules', ['title' => 'Horaires d\'ouverture', 'schedules' => (new Reference())->schedules()]);
    }

    public function schedulesSave(): void
    {
        $ref = new Reference();
        foreach ($ref->schedules() as $s) {
            $id = (int) $s['horaire_id'];
            $closed = !empty($_POST['ferme'][$id]);
            $open = (string) ($_POST['ouverture'][$id] ?? '');
            $close = (string) ($_POST['fermeture'][$id] ?? '');
            if (!$closed && (!Validator::isTime($open) || !Validator::isTime($close) || $open >= $close)) {
                Session::flash('danger', 'Horaires invalides pour ' . $s['jour'] . ' (l\'ouverture doit précéder la fermeture).');
                $this->redirect('/employe/horaires');
            }
            $ref->updateSchedule($id, $open ?: null, $close ?: null, $closed);
        }
        Session::flash('success', 'Horaires mis à jour.');
        $this->redirect('/employe/horaires');
    }

    // ------------------------------------------------------------------ Avis

    public function reviews(): void
    {
        $model = new Review();
        $this->view('employee/reviews', [
            'title'     => 'Modération des avis',
            'pending'   => $model->byStatus('en_attente'),
            'validated' => $model->byStatus('valide'),
        ]);
    }

    public function reviewModerate(int $id): void
    {
        $approve = $this->input('decision') === 'valider';
        (new Review())->moderate($id, $approve);
        Session::flash('success', $approve ? 'Avis validé : il est visible sur la page d\'accueil.' : 'Avis refusé.');
        $this->redirect('/employe/avis');
    }
}
