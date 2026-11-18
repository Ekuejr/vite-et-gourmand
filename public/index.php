<?php
/**
 * Contrôleur frontal : UNIQUE point d'entrée de l'application.
 * Toutes les URL sont redirigées ici (.htaccess), ce qui permet d'appliquer partout
 * les mêmes protections (en-têtes HTTP, session, CSRF, contrôle d'accès).
 * Le dossier public/ est la seule partie exposée au web : src/, config/, .env restent inaccessibles.
 */
declare(strict_types=1);

// Serveur de développement PHP (php -S) : laisser servir directement les fichiers statiques existants
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

use App\Core\App;
use App\Core\Router;
use App\Core\Session;
use App\Core\Auth;

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
require $root . '/src/helpers.php';
App::boot(require $root . '/config/config.php');

// --- Gestion des erreurs : jamais de détail technique affiché en production -------------
error_reporting(E_ALL);
ini_set('display_errors', App::isProd() ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', $root . '/storage/logs/php-error.log');

// --- En-têtes de sécurité HTTP ---------------------------------------------------------
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
header('X-Content-Type-Options: nosniff');       // pas d'interprétation abusive des types MIME
header('X-Frame-Options: DENY');                 // anti clickjacking
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (App::isProd()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // HTTPS obligatoire
}

Session::start();

$router = new Router();
$staff = [Auth::ROLE_EMPLOYEE, Auth::ROLE_ADMIN];
$admin = [Auth::ROLE_ADMIN];
$user  = [Auth::ROLE_USER];

// Pages publiques
$router->get('/', 'HomeController@index');
$router->get('/menus', 'MenuController@index');
$router->get('/menus/{id}', 'MenuController@show');
$router->get('/contact', 'ContactController@form');
$router->post('/contact', 'ContactController@send');
$router->get('/mentions-legales', 'PageController@legal');
$router->get('/cgv', 'PageController@cgv');

// API JSON (filtres dynamiques sans rechargement + aperçu du prix)
$router->get('/api/menus', 'ApiController@menus');
$router->get('/api/prix', 'ApiController@price', $user);

// Authentification
$router->get('/inscription', 'AuthController@registerForm');
$router->post('/inscription', 'AuthController@register');
$router->get('/connexion', 'AuthController@loginForm');
$router->post('/connexion', 'AuthController@login');
$router->post('/deconnexion', 'AuthController@logout');
$router->get('/mot-de-passe-oublie', 'AuthController@forgotForm');
$router->post('/mot-de-passe-oublie', 'AuthController@forgot');
$router->get('/reinitialisation', 'AuthController@resetForm');
$router->post('/reinitialisation', 'AuthController@reset');

// Commande et espace utilisateur
$router->get('/commander/{id}', 'OrderController@form', $user);
$router->post('/commander/{id}', 'OrderController@store', $user);
$router->get('/mon-compte', 'AccountController@index', $user);
$router->post('/mon-compte', 'AccountController@update', $user);
$router->get('/mon-compte/commandes/{id}', 'AccountController@order', $user);
$router->get('/mon-compte/commandes/{id}/modifier', 'OrderController@editForm', $user);
$router->post('/mon-compte/commandes/{id}/modifier', 'OrderController@update', $user);
$router->post('/mon-compte/commandes/{id}/annuler', 'OrderController@cancel', $user);
$router->post('/mon-compte/commandes/{id}/avis', 'AccountController@review', $user);

// Espace employé (accessible aussi à l'administrateur)
$router->get('/employe', 'EmployeeController@dashboard', $staff);
$router->get('/employe/commandes', 'EmployeeController@orders', $staff);
$router->get('/employe/commandes/{id}', 'EmployeeController@order', $staff);
$router->post('/employe/commandes/{id}/statut', 'EmployeeController@status', $staff);
$router->post('/employe/commandes/{id}/annuler', 'EmployeeController@cancel', $staff);
$router->post('/employe/commandes/{id}/materiel', 'EmployeeController@material', $staff);
$router->get('/employe/menus', 'EmployeeController@menus', $staff);
$router->get('/employe/menus/nouveau', 'EmployeeController@menuForm', $staff);
$router->get('/employe/menus/{id}', 'EmployeeController@menuForm', $staff);
$router->post('/employe/menus/nouveau', 'EmployeeController@menuSave', $staff);
$router->post('/employe/menus/{id}', 'EmployeeController@menuSave', $staff);
$router->post('/employe/menus/{id}/supprimer', 'EmployeeController@menuDelete', $staff);
$router->post('/employe/images/{id}/supprimer', 'EmployeeController@imageDelete', $staff);
$router->get('/employe/plats', 'EmployeeController@dishes', $staff);
$router->get('/employe/plats/nouveau', 'EmployeeController@dishForm', $staff);
$router->get('/employe/plats/{id}', 'EmployeeController@dishForm', $staff);
$router->post('/employe/plats/nouveau', 'EmployeeController@dishSave', $staff);
$router->post('/employe/plats/{id}', 'EmployeeController@dishSave', $staff);
$router->post('/employe/plats/{id}/supprimer', 'EmployeeController@dishDelete', $staff);
$router->get('/employe/horaires', 'EmployeeController@schedules', $staff);
$router->post('/employe/horaires', 'EmployeeController@schedulesSave', $staff);
$router->get('/employe/avis', 'EmployeeController@reviews', $staff);
$router->post('/employe/avis/{id}', 'EmployeeController@reviewModerate', $staff);

// Espace administrateur
$router->get('/admin', 'AdminController@dashboard', $admin);
$router->get('/admin/employes', 'AdminController@employees', $admin);
$router->post('/admin/employes', 'AdminController@createEmployee', $admin);
$router->post('/admin/employes/{id}/statut', 'AdminController@toggleEmployee', $admin);
$router->get('/admin/statistiques', 'AdminController@stats', $admin);
$router->get('/admin/statistiques/donnees', 'AdminController@statsData', $admin);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    // Journalisation interne, message neutre pour le visiteur (pas de fuite d'information)
    error_log($e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    \App\Core\View::render('errors/500', ['title' => 'Erreur', 'debug' => App::isProd() ? null : $e->getMessage()]);
}
