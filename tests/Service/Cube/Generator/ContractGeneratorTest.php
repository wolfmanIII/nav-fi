<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\ContractGenerator;
use PHPUnit\Framework\TestCase;

class ContractGeneratorTest extends TestCase
{
    public function testGenerateContractCalculatesCorrectly(): void
    {
        $config = [
            'contract' => [
                'base_reward_min' => 1000,
                'base_reward_max' => 2000,
                'bonus_chance' => 0.0, // Force no bonus for deterministic base check
                'bonus_multiplier' => 0.5,
            ]
        ];

        $generator = new ContractGenerator($config);
        $this->assertTrue($generator->supports('CONTRACT'));
        $this->assertEquals('CONTRACT', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 0
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('CONTRACT', $opp->type);
        $this->assertGreaterThanOrEqual(1000, $opp->amount);
        $this->assertArrayHasKey('patron', $opp->details);
    }
}
