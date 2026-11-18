<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/**
 * Distance entre Bordeaux (siège de Vite & Gourmand) et l'adresse de livraison.
 *
 * Choix : API Adresse de l'État (api-adresse.data.gouv.fr) - gratuite, sans clé, hébergée en France
 * (cohérent RGPD). Alternative possible : Nominatim (OpenStreetMap) ou OpenRouteService pour une
 * distance routière réelle. Ici on calcule la distance à vol d'oiseau (formule de Haversine) :
 * limite assumée et documentée, simple à expliquer et sans dépendance payante.
 */
final class Geocoder
{
    /** Coordonnées de Bordeaux (place de la Comédie). */
    private const ORIGIN_LAT = 44.8425;
    private const ORIGIN_LON = -0.5737;

    /** @return float|null distance en km, ou null si l'adresse est introuvable */
    public function distanceFromBordeaux(string $address, string $postalCode, string $city): ?float
    {
        $fake = App::config('geocoder_fake_km');
        if ($fake !== null && $fake !== '') {
            return (float) $fake; // mode développement hors ligne
        }

        $query = http_build_query(['q' => "$address $postalCode $city", 'limit' => 1]);
        $ch = curl_init('https://api-adresse.data.gouv.fr/search/?' . $query);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            return null;
        }
        $data = json_decode((string) $body, true);
        $coords = $data['features'][0]['geometry']['coordinates'] ?? null; // [lon, lat]
        if (!is_array($coords)) {
            return null;
        }
        return round(self::haversine(self::ORIGIN_LAT, self::ORIGIN_LON, (float) $coords[1], (float) $coords[0]), 2);
    }

    /** Distance orthodromique entre deux points GPS, en kilomètres. */
    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
