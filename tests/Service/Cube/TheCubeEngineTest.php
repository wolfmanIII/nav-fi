<?php

namespace App\Tests\Service\Cube;

use App\Entity\BrokerSession;
use App\Service\Cube\TheCubeEngine;
use PHPUnit\Framework\TestCase;

class TheCubeEngineTest extends TestCase
{
    private array $mockEconomyConfig;
    private TheCubeEngine $engine;

    protected function setUp(): void
    {
        $this->mockEconomyConfig = [
            'freight_pricing' => [1 => 1000, 2 => 1600],
            'passengers' => [
                'high' => [1 => 9000],
                'middle' => [1 => 6500],
                'basic' => [1 => 2000],
                'low' => [1 => 700],
            ],
            'contract' => [
                'base_reward_min' => 1000,
                'base_reward_max' => 5000,
                'bonus_chance' => 0.3,
                'bonus_multiplier' => 0.5,
            ],
            'mail' => [
                'flat_rate' => 25000,
            ]
        ];

        $this->engine = new TheCubeEngine($this->mockEconomyConfig);
    }

    public function testDeterministicGeneration(): void
    {
        $session = new BrokerSession();
        $session->setSeed('TEST_SEED_123');
        $session->setSector('Spinward Marches');
        $session->setOriginHex('1910');
        $session->setJumpRange(2);

        $originData = ['trade_codes' => ['In', 'Ri']];

        // Prima esecuzione
        $batch1 = $this->engine->generateBatch($session, $originData, [], 5);

        // Seconda esecuzione (stesso seed)
        $batch2 = $this->engine->generateBatch($session, $originData, [], 5);

        $this->assertEquals($batch1, $batch2, 'Generation should be identical for same session state');
        $this->assertCount(5, $batch1);
    }

    public function testDifferentSeedProducesDifferentResult(): void
    {
        $session1 = new BrokerSession();
        $session1->setSeed('SEED_A');
        $session1->setSector('Spinward Marches');
        $session1->setOriginHex('1910');

        $session2 = new BrokerSession();
        $session2->setSeed('SEED_B'); // Diverso
        $session2->setSector('Spinward Marches');
        $session2->setOriginHex('1910');

        $originData = ['trade_codes' => []];

        $batch1 = $this->engine->generateBatch($session1, $originData, [], 5);
        $batch2 = $this->engine->generateBatch($session2, $originData, [], 5);

        $this->assertNotEquals($batch1, $batch2, 'Different seeds should produce different results');
    }
}
