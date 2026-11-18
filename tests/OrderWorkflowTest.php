<?php
declare(strict_types=1);

namespace Tests;

use App\Services\OrderWorkflow;
use PHPUnit\Framework\TestCase;

final class OrderWorkflowTest extends TestCase
{
    public function testParcoursNominalSansMateriel(): void
    {
        $this->assertTrue(OrderWorkflow::canTransition('en_attente', 'accepte', false));
        $this->assertTrue(OrderWorkflow::canTransition('accepte', 'en_preparation', false));
        $this->assertTrue(OrderWorkflow::canTransition('livre', 'terminee', false));
        $this->assertFalse(OrderWorkflow::canTransition('livre', 'en_attente_retour_materiel', false));
    }

    public function testMaterielPreteImposeLeRetour(): void
    {
        $this->assertSame(['en_attente_retour_materiel'], OrderWorkflow::nextStatuses('livre', true));
        $this->assertTrue(OrderWorkflow::canTransition('en_attente_retour_materiel', 'terminee', true));
    }

    public function testOnNeSautePasDEtape(): void
    {
        $this->assertFalse(OrderWorkflow::canTransition('en_attente', 'livre', false));
        $this->assertFalse(OrderWorkflow::canTransition('terminee', 'accepte', false));
    }

    public function testDroitsDuClient(): void
    {
        $this->assertTrue(OrderWorkflow::userCanEdit('en_attente'));
        $this->assertFalse(OrderWorkflow::userCanEdit('accepte'), 'plus modifiable une fois acceptée');
        $this->assertTrue(OrderWorkflow::userCanTrack('accepte'));
        $this->assertFalse(OrderWorkflow::userCanTrack('en_attente'));
    }

    public function testAnnulationParLePersonnel(): void
    {
        $this->assertTrue(OrderWorkflow::staffCanCancel('en_preparation'));
        $this->assertFalse(OrderWorkflow::staffCanCancel('livre'));
    }
}
