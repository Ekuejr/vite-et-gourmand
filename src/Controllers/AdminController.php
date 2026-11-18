<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Menu;
use App\Models\User;
use App\Services\Mailer;
use App\Services\StatsService;

/**
 * Espace administrateur : comptes employés et statistiques (MongoDB).
 * L'administrateur a aussi accès à tout l'espace employé (voir routes).
 */
final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->view('admin/dashboard', ['title' => 'Espace administrateur', 'mongo' => (new StatsService())->isAvailable()]);
    }

    public function employees(): void
    {
        $this->view('admin/employees', ['title' => 'Comptes employés', 'employees' => (new User())->employees()]);
    }

    /** Création d'un compte EMPLOYÉ (jamais administrateur : le rôle est codé en dur). */
    public function createEmployee(): void
    {
        $email = mb_strtolower($this->input('email'));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if (!Validator::isEmail($email)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        } elseif ((new User())->findByEmail($email)) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse.';
        }
        if (!Validator::isStrongPassword($password)) {
            $errors['password'] = 'Mot de passe trop faible (10 caractères, majuscule, minuscule, chiffre, caractère spécial).';
        }
        $nom = $this->input('nom') ?: 'Employé';
        $prenom = $this->input('prenom') ?: '-';
        if ($errors) {
            $this->back('/admin/employes', $errors);
        }
        (new User())->create(['email' => $email, 'password' => $password, 'nom' => $nom, 'prenom' => $prenom], Auth::ROLE_EMPLOYEE);
        (new Mailer())->employeeCreated($email); // le mot de passe n'est PAS envoyé par e-mail
        Session::clearOld();
        Session::flash('success', 'Compte employé créé. Un e-mail (sans mot de passe) a été envoyé à ' . $email . '.');
        $this->redirect('/admin/employes');
    }

    public function toggleEmployee(int $id): void
    {
        $active = $this->input('actif') === '1';
        if (!(new User())->setEmployeeActive($id, $active)) {
            Session::flash('danger', 'Compte employé introuvable.');
        } else {
            Session::flash('success', $active ? 'Compte réactivé.' : 'Compte désactivé : l\'employé ne peut plus se connecter.');
        }
        $this->redirect('/admin/employes');
    }

    public function stats(): void
    {
        $this->view('admin/stats', [
            'title' => 'Statistiques',
            'menus' => (new Menu())->all(),
            'mongo' => (new StatsService())->isAvailable(),
        ]);
    }

    /** Données JSON pour le graphique (lues dans MongoDB). */
    public function statsData(): void
    {
        $menuId = (int) $this->input('menu_id') ?: null;
        $from = $this->input('du');
        $to = $this->input('au');
        $from = Validator::isDate($from) ? $from : null;
        $to = Validator::isDate($to) ? $to : null;
        try {
            $rows = (new StatsService())->perMenu($menuId, $from, $to);
            View::json([
                'source'  => 'MongoDB',
                'menus'   => $rows,
                'total_ca' => round(array_sum(array_column($rows, 'chiffre_affaires')), 2),
                'total_commandes' => array_sum(array_column($rows, 'nb_commandes')),
            ]);
        } catch (\Throwable $e) {
            error_log('[Stats] ' . $e->getMessage());
            View::json(['erreur' => 'Statistiques indisponibles (connexion MongoDB).'], 503);
        }
    }
}
