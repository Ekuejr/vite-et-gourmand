<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testMotDePasseConforme(): void
    {
        $this->assertTrue(Validator::isStrongPassword('Client#Test2026'));
    }

    public function testMotsDePasseRefuses(): void
    {
        $this->assertFalse(Validator::isStrongPassword('Court#1a'), 'moins de 10 caractères');
        $this->assertFalse(Validator::isStrongPassword('sansmajuscule#2026'), 'pas de majuscule');
        $this->assertFalse(Validator::isStrongPassword('SANSMINUSCULE#2026'), 'pas de minuscule');
        $this->assertFalse(Validator::isStrongPassword('SansChiffre#abc'), 'pas de chiffre');
        $this->assertFalse(Validator::isStrongPassword('SansSpecial2026'), 'pas de caractère spécial');
    }

    public function testNumeroDeMobile(): void
    {
        $this->assertTrue(Validator::isMobile('06 12 34 56 78'));
        $this->assertTrue(Validator::isMobile('+33712345678'));
        $this->assertFalse(Validator::isMobile('05 56 00 00 00'), 'fixe refusé : un GSM est demandé');
        $this->assertFalse(Validator::isMobile('0612'));
    }

    public function testEmailEtDates(): void
    {
        $this->assertTrue(Validator::isEmail('client@exemple.fr'));
        $this->assertFalse(Validator::isEmail('pas-un-email'));
        $this->assertTrue(Validator::isDate('2026-12-24'));
        $this->assertFalse(Validator::isDate('2026-02-30'));
        $this->assertTrue(Validator::isTime('18:30'));
        $this->assertFalse(Validator::isTime('25:00'));
    }
}
