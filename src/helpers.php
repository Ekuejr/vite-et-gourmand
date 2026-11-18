<?php
/**
 * Fonctions utilitaires disponibles dans les vues.
 */
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;

/** Échappement HTML systématique : rempart principal contre le XSS. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, string $default = ''): string
{
    return e(Session::old($key, $default));
}

/** Formatage monétaire français : 1 234,50 € */
function money(float|string|null $amount): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' €';
}

function date_fr(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $withTime ? date('d/m/Y à H\hi', $ts) : date('d/m/Y', $ts);
}

/** Libellés lisibles des statuts de commande. */
function statut_label(string $statut): string
{
    return \App\Models\Order::STATUS_LABELS[$statut] ?? $statut;
}

/** Erreurs de validation du formulaire précédent (lues une seule fois). */
function form_errors(): array
{
    static $errors = null;
    if ($errors === null) {
        $errors = Session::get('_errors', []);
        Session::forget('_errors');
    }
    return $errors;
}

function field_error(string $field): string
{
    $errors = form_errors();
    if (!isset($errors[$field])) {
        return '';
    }
    return '<div class="invalid-feedback d-block" id="' . e($field) . '-error">' . e($errors[$field]) . '</div>';
}

/** Attributs ARIA pour relier un champ à son message d'erreur (RGAA). */
function aria_error(string $field): string
{
    return isset(form_errors()[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
}
