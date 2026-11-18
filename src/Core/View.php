<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rendu des vues PHP dans le gabarit commun (views/layout.php).
 * Les variables passées sont extraites ; dans les vues, TOUTE donnée affichée passe par e()
 * (échappement HTML) => protection contre le XSS.
 */
final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        $file = dirname(__DIR__, 2) . '/views/' . $view . '.php';
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/' . $layout . '.php';
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
