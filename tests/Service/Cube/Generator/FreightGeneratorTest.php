<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\FreightGenerator;
use PHPUnit\Framework\TestCase;

class FreightGeneratorTest extends TestCase
{
    public function testGenerateFreightCalculatesCorrectly(): void
    {
        $config = [
            'freight_pricing' => [
                2 => 1500,
            ]
        ];

        $repo = $this->createMock(\App\Repository\CompanyRepository::class);
        $repo->method('findAll')->willReturn([]);

        $generator = new FreightGenerator($config, $repo);
        $this->assertTrue($generator->supports('FREIGHT'));
        $this->assertEquals('FREIGHT', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 2,
            'session_day' => 100,
            'session_year' => 1105
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('FREIGHT', $opp->type);
        $this->assertEquals(2, $opp->distance);
        // Tons (10-60) * 1500
        $this->assertGreaterThanOrEqual(15000, $opp->amount);
    }
}
