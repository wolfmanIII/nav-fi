<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\TradeGenerator;
use PHPUnit\Framework\TestCase;

class TradeGeneratorTest extends TestCase
{
    public function testGenerateTradeCalculatesCorrectly(): void
    {
        $generator = new TradeGenerator();
        $this->assertTrue($generator->supports('TRADE'));
        $this->assertEquals('TRADE', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 0
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('TRADE', $opp->type);
        $this->assertGreaterThan(0, $opp->amount);
        $this->assertArrayHasKey('resource', $opp->details);
        $this->assertArrayHasKey('buy_price', $opp->details);
    }
}
