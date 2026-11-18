<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Calcul du prix d'une commande - règles métier du cahier des charges :
 *  - le prix du menu est donné pour le nombre MINIMUM de personnes => prix par personne = prix_min / nb_min
 *  - interdiction de commander pour moins que le minimum
 *  - remise de 10 % si nb_personnes >= minimum + 5
 *  - livraison gratuite à Bordeaux ; ailleurs : 5 € + 0,59 € par kilomètre
 *
 * Classe "pure" (aucune dépendance) : le même calcul sert à l'aperçu en direct (API)
 * et à l'enregistrement, et il est couvert par des tests unitaires.
 * Le calcul est TOUJOURS refait côté serveur : un prix envoyé par le navigateur n'est jamais cru.
 */
final class PriceCalculator
{
    public const DISCOUNT_RATE = 0.10;
    public const DISCOUNT_EXTRA_PEOPLE = 5;
    public const DELIVERY_BASE = 5.00;
    public const DELIVERY_PER_KM = 0.59;
    public const HOME_CITY = 'bordeaux';

    /**
     * @return array{prix_par_personne: float, prix_menu: float, remise: float, prix_livraison: float, distance_km: float, prix_total: float}
     */
    public static function compute(float $minPrice, int $minPeople, int $people, string $city, float $distanceKm): array
    {
        if ($minPeople < 1) {
            throw new \InvalidArgumentException('Nombre minimum de personnes invalide.');
        }
        if ($people < $minPeople) {
            throw new \DomainException(sprintf('Ce menu se commande pour %d personnes minimum.', $minPeople));
        }

        $perPerson = $minPrice / $minPeople;
        $menuPrice = round($perPerson * $people, 2);

        $discount = $people >= $minPeople + self::DISCOUNT_EXTRA_PEOPLE
            ? round($menuPrice * self::DISCOUNT_RATE, 2)
            : 0.0;

        if (self::isHomeCity($city)) {
            $distanceKm = 0.0;
            $delivery = 0.0;
        } else {
            $distanceKm = round(max(0.0, $distanceKm), 2);
            $delivery = round(self::DELIVERY_BASE + self::DELIVERY_PER_KM * $distanceKm, 2);
        }

        return [
            'prix_par_personne' => round($perPerson, 2),
            'prix_menu'         => $menuPrice,
            'remise'            => $discount,
            'prix_livraison'    => $delivery,
            'distance_km'       => $distanceKm,
            'prix_total'        => round($menuPrice - $discount + $delivery, 2),
        ];
    }

    /** "Bordeaux", " BORDEAUX ", "bordeaux" => vrai. Les accents/tirets sont normalisés. */
    public static function isHomeCity(string $city): bool
    {
        $normalized = mb_strtolower(trim($city));
        $normalized = strtr($normalized, ['é' => 'e', 'è' => 'e', 'ê' => 'e', '-' => ' ']);
        return $normalized === self::HOME_CITY;
    }
}
