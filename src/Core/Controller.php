<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Classe mère des contrôleurs : raccourcis pour afficher une vue, rediriger,
 * et lire proprement les données envoyées.
 */
abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /** Redirige vers le formulaire précédent en conservant la saisie et les erreurs. */
    protected function back(string $url, array $errors): never
    {
        Session::setOld($_POST);
        Session::set('_errors', $errors);
        $this->redirect($url);
    }

    /** Lecture d'un champ texte : trim + type string garanti. */
    protected function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    protected function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Page introuvable']);
        exit;
    }
}
