<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;

/**
 * Espace utilisateur : commandes, suivi, informations personnelles, avis.
 */
final class AccountController extends Controller
{
    public function index(): void
    {
        $this->view('account/index', [
            'title'  => 'Mon espace',
            'user'   => Auth::user(),
            'orders' => (new Order())->forUser((int) Auth::id()),
        ]);
    }

    public function update(): void
    {
        $d = [
            'nom' => $this->input('nom'), 'prenom' => $this->input('prenom'), 'telephone' => $this->input('telephone'),
            'adresse_postale' => $this->input('adresse_postale'), 'code_postal' => $this->input('code_postal'), 'ville' => $this->input('ville'),
        ];
        $errors = AuthController::validateProfile($d);
        if ($errors) {
            $this->back('/mon-compte', $errors);
        }
        (new User())->updateProfile((int) Auth::id(), $d);
        Session::clearOld();
        Session::flash('success', 'Vos informations ont été mises à jour.');
        $this->redirect('/mon-compte');
    }

    public function order(int $id): void
    {
        $model = new Order();
        $order = $model->findForUser($id, (int) Auth::id()); // contrôle de propriété (anti-IDOR)
        if ($order === null) {
            $this->notFound();
        }
        $this->view('account/order', [
            'title'   => 'Commande ' . $order['numero_commande'],
            'order'   => $order,
            'history' => $model->history($id),
        ]);
    }

    public function review(int $id): void
    {
        $order = (new Order())->findForUser($id, (int) Auth::id());
        if ($order === null) {
            $this->notFound();
        }
        if ($order['statut'] !== 'terminee' || $order['avis_id'] !== null) {
            Session::flash('warning', 'Un avis ne peut être déposé qu\'une fois, sur une commande terminée.');
            $this->redirect('/mon-compte/commandes/' . $id);
        }
        $note = (int) $this->input('note');
        $comment = $this->input('commentaire');
        $errors = [];
        if ($note < 1 || $note > 5) {
            $errors['note'] = 'La note doit être comprise entre 1 et 5.';
        }
        if (!Validator::length($comment, 5, 1000)) {
            $errors['commentaire'] = 'Le commentaire doit contenir entre 5 et 1000 caractères.';
        }
        if ($errors) {
            $this->back('/mon-compte/commandes/' . $id, $errors);
        }
        (new Review())->create($id, (int) Auth::id(), $note, $comment);
        Session::clearOld();
        Session::flash('success', 'Merci pour votre avis ! Il sera publié après validation par notre équipe.');
        $this->redirect('/mon-compte/commandes/' . $id);
    }
}
