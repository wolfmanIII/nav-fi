<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\PassengerGenerator;
use PHPUnit\Framework\TestCase;

class PassengerGeneratorTest extends TestCase
{
    public function testGeneratePassengerCalculatesCorrectly(): void
    {
        $config = [
            'passengers' => [
                'high' => [2 => 200],
                'middle' => [2 => 100],
                'basic' => [2 => 50],
                'low' => [2 => 20],
            ]
        ];

        $repo = $this->createMock(\App\Repository\CompanyRepository::class);
        $repo->method('findAll')->willReturn([]);

        $generator = new PassengerGenerator($config, $repo);
        $this->assertTrue($generator->supports('PASSENGERS'));
        $this->assertEquals('PASSENGERS', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 2,
            'session_day' => 100,
            'session_year' => 1105
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('PASSENGERS', $opp->type);
        $this->assertEquals(2, $opp->distance);

        // Pax count is 2-12. Price is at least 20 per pax.
        $this->assertGreaterThanOrEqual(40, $opp->amount);
        $this->assertArrayHasKey('pax', $opp->details);
        $this->assertArrayHasKey('class', $opp->details);
    }
}
