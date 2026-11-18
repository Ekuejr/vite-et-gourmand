<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Security;
use App\Models\User;
use App\Services\Mailer;

/**
 * Inscription, connexion, déconnexion et mot de passe oublié.
 */
final class AuthController extends Controller
{
    public function registerForm(): void
    {
        $this->view('auth/register', ['title' => 'Créer un compte']);
    }

    public function register(): void
    {
        $d = [
            'nom' => $this->input('nom'), 'prenom' => $this->input('prenom'), 'telephone' => $this->input('telephone'),
            'email' => mb_strtolower($this->input('email')), 'adresse_postale' => $this->input('adresse_postale'),
            'code_postal' => $this->input('code_postal'), 'ville' => $this->input('ville'),
            'password' => (string) ($_POST['password'] ?? ''), 'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
        ];
        $errors = self::validateProfile($d);
        if (!Validator::isEmail($d['email'])) {
            $errors['email'] = 'Adresse e-mail invalide.';
        } elseif ((new User())->findByEmail($d['email'])) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse e-mail.';
        }
        if (!Validator::isStrongPassword($d['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 10 caractères dont une majuscule, une minuscule, un chiffre et un caractère spécial.';
        } elseif ($d['password'] !== $d['password_confirm']) {
            $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (empty($_POST['rgpd'])) {
            $errors['rgpd'] = 'Vous devez accepter la politique de confidentialité.';
        }
        if ($errors) {
            $this->back('/inscription', $errors);
        }

        // Rôle imposé côté serveur : "utilisateur". Jamais lu depuis le formulaire.
        $id = (new User())->create($d, Auth::ROLE_USER);
        $user = (new User())->find($id);
        (new Mailer())->welcome($user);
        Auth::login($user);
        Session::clearOld();
        Session::flash('success', 'Bienvenue ' . $user['prenom'] . ' ! Votre compte a été créé, un e-mail de bienvenue vous a été envoyé.');
        $this->redirect(Session::get('redirect_after_login', '/menus'));
    }

    /** Règles communes inscription / modification du profil. */
    public static function validateProfile(array $d): array
    {
        $errors = [];
        if (!Validator::length($d['nom'], 1, 80)) {
            $errors['nom'] = 'Le nom est obligatoire (80 caractères max.).';
        }
        if (!Validator::length($d['prenom'], 1, 80)) {
            $errors['prenom'] = 'Le prénom est obligatoire (80 caractères max.).';
        }
        if (!Validator::isMobile($d['telephone'])) {
            $errors['telephone'] = 'Numéro de mobile (GSM) invalide, ex. : 06 12 34 56 78.';
        }
        if (!Validator::length($d['adresse_postale'], 3, 255)) {
            $errors['adresse_postale'] = 'L\'adresse postale est obligatoire.';
        }
        if (!Validator::isPostalCode($d['code_postal'])) {
            $errors['code_postal'] = 'Code postal invalide (5 chiffres).';
        }
        if (!Validator::length($d['ville'], 1, 100)) {
            $errors['ville'] = 'La ville est obligatoire.';
        }
        return $errors;
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['title' => 'Connexion']);
    }

    public function login(): void
    {
        $email = mb_strtolower($this->input('email'));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $security = new Security();

        if ($security->isLocked($ip)) {
            $this->back('/connexion', ['email' => 'Trop de tentatives. Réessayez dans 15 minutes.']);
        }

        $user = (new User())->findByEmail($email);
        // password_verify compare le mot de passe au hash. Message volontairement identique
        // que l'e-mail existe ou non : on ne révèle pas quels comptes existent (énumération).
        if ($user === null || !password_verify($password, $user['password']) || (int) $user['actif'] !== 1) {
            $security->recordFailedLogin($ip, $email);
            $this->back('/connexion', ['email' => 'Identifiants incorrects ou compte désactivé.']);
        }

        // Mise à niveau transparente du hash si l'algorithme par défaut de PHP évolue
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            (new User())->updatePassword((int) $user['utilisateur_id'], $password);
        }

        $security->clearFailedLogins($ip);
        Auth::login($user);
        Session::clearOld();
        Session::flash('success', 'Bonjour ' . $user['prenom'] . ', vous êtes connecté(e).');

        $target = match ($user['role']) {
            Auth::ROLE_ADMIN    => '/admin',
            Auth::ROLE_EMPLOYEE => '/employe',
            default             => Session::get('redirect_after_login', '/mon-compte'),
        };
        Session::forget('redirect_after_login');
        // Redirection uniquement vers une URL interne (pas de redirection ouverte vers un site externe)
        $this->redirect(str_starts_with($target, '/') && !str_starts_with($target, '//') ? $target : '/');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'Vous êtes déconnecté(e).');
        $this->redirect('/');
    }

    public function forgotForm(): void
    {
        $this->view('auth/forgot', ['title' => 'Mot de passe oublié']);
    }

    public function forgot(): void
    {
        $email = mb_strtolower($this->input('email'));
        $user = Validator::isEmail($email) ? (new User())->findByEmail($email) : null;
        if ($user && (int) $user['actif'] === 1) {
            $token = (new Security())->createResetToken((int) $user['utilisateur_id']);
            $link = App::config('url') . '/reinitialisation?token=' . $token;
            (new Mailer())->passwordReset($user['email'], $link);
        }
        // Même réponse dans tous les cas (anti-énumération des comptes)
        Session::flash('success', 'Si un compte correspond à cette adresse, un lien de réinitialisation vient d\'être envoyé.');
        $this->redirect('/connexion');
    }

    public function resetForm(): void
    {
        $token = $this->input('token');
        if (!preg_match('/^[a-f0-9]{64}$/', $token) || !(new Security())->findValidReset($token)) {
            Session::flash('danger', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/mot-de-passe-oublie');
        }
        $this->view('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token]);
    }

    public function reset(): void
    {
        $token = $this->input('token');
        $security = new Security();
        $reset = preg_match('/^[a-f0-9]{64}$/', $token) ? $security->findValidReset($token) : null;
        if ($reset === null) {
            Session::flash('danger', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/mot-de-passe-oublie');
        }
        $password = (string) ($_POST['password'] ?? '');
        if (!Validator::isStrongPassword($password)) {
            $this->back('/reinitialisation?token=' . $token, ['password' => 'Le mot de passe doit contenir au moins 10 caractères dont une majuscule, une minuscule, un chiffre et un caractère spécial.']);
        }
        if ($password !== ($_POST['password_confirm'] ?? '')) {
            $this->back('/reinitialisation?token=' . $token, ['password_confirm' => 'Les deux mots de passe ne correspondent pas.']);
        }
        (new User())->updatePassword((int) $reset['utilisateur_id'], $password);
        $security->markResetUsed((int) $reset['reset_id']);
        Session::flash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        $this->redirect('/connexion');
    }
}
