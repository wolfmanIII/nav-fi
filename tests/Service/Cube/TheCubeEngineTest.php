<?php

namespace App\Tests\Service\Cube;

use App\Dto\Cube\CubeOpportunityData;
use App\Entity\BrokerSession;
use App\Service\Cube\TheCubeEngine;
use PHPUnit\Framework\TestCase;

class TheCubeEngineTest extends TestCase
{
    private TheCubeEngine $engine;

    protected function setUp(): void
    {
        $config = [
            'freight_pricing' => [
                1 => 1000,
                2 => 1400,
            ],
            'passengers' => [
                'high' => [1 => 100, 2 => 200],
                'middle' => [1 => 50, 2 => 100],
                'basic' => [1 => 20, 2 => 40],
                'low' => [1 => 10, 2 => 20],
            ],
            'mail' => ['flat_rate' => 25000],
            'contract' => [
                'base_reward_min' => 10000,
                'base_reward_max' => 50000,
                'bonus_chance' => 0.1,
                'bonus_multiplier' => 0.5,
            ]
        ];

        $this->engine = new TheCubeEngine($config);
    }

    public function testGenerateBatchReturnsDtos(): void
    {
        $session = new BrokerSession();
        $session->setSeed('TEST_SEED');
        $session->setSector('Test Sector');
        $session->setOriginHex('0101');
        $session->setJumpRange(2);

        $originData = ['name' => 'Origin World'];

        $results = $this->engine->generateBatch($session, $originData, [], 2);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(CubeOpportunityData::class, $results[0]);
        $this->assertInstanceOf(CubeOpportunityData::class, $results[1]);

        $this->assertNotEmpty($results[0]->signature);
        $this->assertGreaterThan(0, $results[0]->amount);
    }
}
