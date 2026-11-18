<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Services\Mailer;

final class ContactController extends Controller
{
    public function form(): void
    {
        $this->view('contact/form', ['title' => 'Contact']);
    }

    public function send(): void
    {
        // Champ "piège" invisible pour les humains : s'il est rempli, c'est un robot (anti-spam sans captcha tiers)
        if ($this->input('website') !== '') {
            $this->redirect('/contact');
        }
        $title = $this->input('titre');
        $description = $this->input('description');
        $email = $this->input('email');

        $errors = [];
        if (!Validator::length($title, 3, 120)) {
            $errors['titre'] = 'Le titre doit contenir entre 3 et 120 caractères.';
        }
        if (!Validator::length($description, 10, 3000)) {
            $errors['description'] = 'Le message doit contenir entre 10 et 3000 caractères.';
        }
        if (!Validator::isEmail($email)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        }
        if ($errors) {
            $this->back('/contact', $errors);
        }

        (new Mailer())->contact($title, $description, $email);
        Session::clearOld();
        Session::flash('success', 'Merci ! Votre demande a bien été envoyée, nous vous répondrons par e-mail.');
        $this->redirect('/contact');
    }
}
