<?php
/**
 * Chargement de la configuration depuis le fichier .env.
 *
 * Pourquoi un .env : les identifiants (BDD, SMTP, MongoDB) ne doivent jamais être
 * écrits en dur dans le code ni poussés sur GitHub (dépôt public !).
 */
declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        // On retire un éventuel commentaire en fin de ligne et les guillemets
        $value = trim(preg_replace('/\s+#.*$/', '', $value));
        $value = trim($value, "\"'");
        $_ENV[trim($key)] = $value;
    }
}

/** Lecture d'une variable de configuration avec valeur par défaut. */
function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : (string) $value;
}

return [
    'env'  => env('APP_ENV', 'dev'),
    'url'  => rtrim(env('APP_URL', 'http://localhost:8000'), '/'),
    'db'   => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'vite_gourmand'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
    ],
    'mongo' => [
        'uri' => env('MONGO_URI'),
        'db'  => env('MONGO_DB', 'vite_gourmand'),
    ],
    'mail' => [
        'host'      => env('MAIL_HOST'),
        'port'      => (int) env('MAIL_PORT', '587'),
        'user'      => env('MAIL_USER'),
        'pass'      => env('MAIL_PASS'),
        'from'      => env('MAIL_FROM', 'contact@vite-gourmand.fr'),
        'from_name' => env('MAIL_FROM_NAME', 'Vite & Gourmand'),
        'company'   => env('MAIL_COMPANY', 'contact@vite-gourmand.fr'),
    ],
    'geocoder_fake_km' => env('GEOCODER_FAKE_KM'),
];
