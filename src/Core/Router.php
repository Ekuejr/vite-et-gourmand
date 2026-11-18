<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur minimal : associe "MÉTHODE + URL" à "Contrôleur@méthode".
 *
 * Chaque route peut exiger un ou plusieurs rôles : le contrôle d'accès est donc
 * centralisé ici (principe du moindre privilège), au lieu d'être oublié dans un contrôleur.
 * Les paramètres {id} sont limités à des chiffres => pas d'injection par l'URL.
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:string, roles:array}> */
    private array $routes = [];

    public function get(string $path, string $handler, array $roles = []): void
    {
        $this->add('GET', $path, $handler, $roles);
    }

    public function post(string $path, string $handler, array $roles = []): void
    {
        $this->add('POST', $path, $handler, $roles);
    }

    private function add(string $method, string $path, string $handler, array $roles): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'roles');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }

            // 1) Contrôle d'accès par rôle
            if ($route['roles'] !== []) {
                if (!Auth::check()) {
                    Session::flash('warning', 'Veuillez vous connecter pour accéder à cette page.');
                    Session::set('redirect_after_login', $uri);
                    header('Location: /connexion');
                    return;
                }
                if (!in_array(Auth::role(), $route['roles'], true)) {
                    http_response_code(403);
                    View::render('errors/403', ['title' => 'Accès refusé']);
                    return;
                }
            }

            // 2) Protection CSRF sur toutes les requêtes POST
            if ($method === 'POST' && !Csrf::check($_POST['_csrf'] ?? null)) {
                http_response_code(419);
                View::render('errors/419', ['title' => 'Session expirée']);
                return;
            }

            [$class, $action] = explode('@', $route['handler']);
            $fqcn = 'App\\Controllers\\' . $class;
            $params = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
            (new $fqcn())->$action(...$params);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Page introuvable']);
    }
}
