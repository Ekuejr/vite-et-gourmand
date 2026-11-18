<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Machine à états des commandes (règles métier pures, sans base de données => testable unitairement).
 *
 *  en_attente -> accepte -> en_preparation -> en_cours_de_livraison -> livre
 *       livre -> en_attente_retour_materiel (si matériel prêté) -> terminee
 *       livre -> terminee (si aucun matériel prêté)
 *  Annulation possible par le client tant que la commande est "en_attente",
 *  par le personnel jusqu'à "en_preparation" (avec motif + mode de contact obligatoires).
 */
final class OrderWorkflow
{
    public const STAFF_CANCELLABLE = ['en_attente', 'accepte', 'en_preparation'];

    /** Statuts suivants autorisés pour le personnel. */
    public static function nextStatuses(string $current, bool $materialLent): array
    {
        return match ($current) {
            'en_attente'                 => ['accepte'],
            'accepte'                    => ['en_preparation'],
            'en_preparation'             => ['en_cours_de_livraison'],
            'en_cours_de_livraison'      => ['livre'],
            'livre'                      => $materialLent ? ['en_attente_retour_materiel'] : ['terminee'],
            'en_attente_retour_materiel' => ['terminee'],
            default                      => [],
        };
    }

    public static function canTransition(string $from, string $to, bool $materialLent): bool
    {
        return in_array($to, self::nextStatuses($from, $materialLent), true);
    }

    /** Le client peut modifier ou annuler tant qu'aucun employé n'a accepté la commande. */
    public static function userCanEdit(string $status): bool
    {
        return $status === 'en_attente';
    }

    /** Le suivi détaillé est visible par le client une fois la commande acceptée. */
    public static function userCanTrack(string $status): bool
    {
        return !in_array($status, ['en_attente', 'annulee'], true);
    }

    public static function staffCanCancel(string $status): bool
    {
        return in_array($status, self::STAFF_CANCELLABLE, true);
    }
}
