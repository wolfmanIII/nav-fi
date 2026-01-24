<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\ContractGenerator;
use PHPUnit\Framework\TestCase;

class ContractGeneratorTest extends TestCase
{
    public function testGenerateContractCalculatesCorrectly(): void
    {
        // Mock NarrativeService
        $narrative = $this->createMock(\App\Service\Cube\NarrativeService::class);

        $narrative->method('resolveTiers')->willReturn([
            'min' => 1000,
            'max' => 2000,
            'risk' => 'Test Risk',
            'examples' => ['Test Mission']
        ]);

        $narrative->method('generatePatron')->willReturn('Test Patron');
        $narrative->method('generateTwist')->willReturn('Test Twist');

        $generator = new ContractGenerator($narrative);
        $this->assertTrue($generator->supports('CONTRACT'));

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 0
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('CONTRACT', $opp->type);
        $this->assertGreaterThanOrEqual(1000, $opp->amount);
        $this->assertEquals('Test Patron', $opp->details['patron']);
        $this->assertEquals('Test Twist', $opp->details['twist']);
    }
}
