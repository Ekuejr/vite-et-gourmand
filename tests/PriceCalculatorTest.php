<?php
declare(strict_types=1);

namespace Tests;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires des règles de prix du cahier des charges.
 * Exemple de référence : menu à 296 € pour 8 personnes minimum (37 €/pers.).
 */
final class PriceCalculatorTest extends TestCase
{
    public function testPrixAuMinimumABordeauxSansRemiseNiLivraison(): void
    {
        $p = PriceCalculator::compute(296.0, 8, 8, 'Bordeaux', 0);
        $this->assertSame(296.0, $p['prix_menu']);
        $this->assertSame(0.0, $p['remise']);
        $this->assertSame(0.0, $p['prix_livraison']);
        $this->assertSame(296.0, $p['prix_total']);
    }

    public function testPasDeRemiseAMinimumPlusQuatre(): void
    {
        $p = PriceCalculator::compute(296.0, 8, 12, 'Bordeaux', 0);
        $this->assertSame(444.0, $p['prix_menu']);
        $this->assertSame(0.0, $p['remise']);
    }

    public function testRemiseDeDixPourcentAMinimumPlusCinq(): void
    {
        $p = PriceCalculator::compute(296.0, 8, 13, 'Bordeaux', 0);
        $this->assertSame(481.0, $p['prix_menu']);
        $this->assertSame(48.1, $p['remise']);
        $this->assertSame(432.9, $p['prix_total']);
    }

    public function testLivraisonHorsBordeaux(): void
    {
        // 5 € + 0,59 € x 10 km = 10,90 €
        $p = PriceCalculator::compute(56.0, 2, 2, 'Mérignac', 10);
        $this->assertSame(10.9, $p['prix_livraison']);
        $this->assertSame(66.9, $p['prix_total']);
    }

    public function testBordeauxReconnuQuelleQueSoitLaCasse(): void
    {
        $this->assertTrue(PriceCalculator::isHomeCity('  BORDEAUX '));
        $this->assertFalse(PriceCalculator::isHomeCity('Bordeaux-Lac-Ouest'));
        $p = PriceCalculator::compute(56.0, 2, 2, 'bordeaux', 25);
        $this->assertSame(0.0, $p['prix_livraison'], 'Aucun frais de livraison dans Bordeaux, même si une distance est fournie');
    }

    public function testCommandeSousLeMinimumRefusee(): void
    {
        $this->expectException(\DomainException::class);
        PriceCalculator::compute(296.0, 8, 7, 'Bordeaux', 0);
    }

    public function testCasCompletAvecRemiseEtLivraison(): void
    {
        // Commande de démo VG-2026-0002 : 14 pers., Mérignac à 8,2 km
        $p = PriceCalculator::compute(296.0, 8, 14, 'Mérignac', 8.2);
        $this->assertSame(518.0, $p['prix_menu']);
        $this->assertSame(51.8, $p['remise']);
        $this->assertSame(9.84, $p['prix_livraison']);
        $this->assertSame(476.04, $p['prix_total']);
    }
}
