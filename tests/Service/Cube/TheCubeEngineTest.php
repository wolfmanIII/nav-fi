<?php

namespace App\Tests\Service\Cube;

use App\Dto\Cube\CubeOpportunityData;
use App\Entity\BrokerSession;
use App\Service\Cube\TheCubeEngine;
use PHPUnit\Framework\TestCase;

class TheCubeEngineTest extends TestCase
{
    private TheCubeEngine $engine;
    private $routeMath;
    private $generator;

    protected function setUp(): void
    {
        $this->routeMath = $this->createMock(\App\Service\RouteMathHelper::class);

        // Mock a single generator that supports everything for simplicity
        $this->generator = $this->createMock(\App\Service\Cube\Generator\OpportunityGeneratorInterface::class);
        $this->generator->method('supports')->willReturn(true);
        $this->generator->method('generate')->willReturn(new CubeOpportunityData(
            'TEST-SIG',
            'TEST-TYPE',
            'Test Summary',
            1000.0,
            2,
            []
        ));

        // Create an iterator for the generators
        $generators = new \ArrayIterator([$this->generator]);

        $this->engine = new TheCubeEngine($generators, $this->routeMath);
    }

    public function testGenerateBatchReturnsDtos(): void
    {
        $session = new BrokerSession();
        $session->setSeed('TEST_SEED');
        $session->setSector('Test Sector');
        $session->setOriginHex('0101');
        $session->setJumpRange(2);

        $originData = ['name' => 'Origin World'];

        // Mock route distance
        $this->routeMath->method('distance')->willReturn(2);

        $results = $this->engine->generateBatch($session, $originData, [['hex' => '0102', 'name' => 'Dest World']], 2);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(CubeOpportunityData::class, $results[0]);
        $this->assertEquals('TEST-TYPE', $results[0]->type);
    }
}
