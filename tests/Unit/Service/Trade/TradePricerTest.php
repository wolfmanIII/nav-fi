<?php

namespace App\Tests\Unit\Service\Trade;

use App\Entity\Cost;
use App\Service\Trade\TradePricer;
use PHPUnit\Framework\TestCase;

class TradePricerTest extends TestCase
{
    private TradePricer $pricer;

    protected function setUp(): void
    {
        $this->pricer = new TradePricer();
    }

    public function testCalculateMarketPriceWithDeterminsticSeed(): void
    {
        $cost = $this->createMock(Cost::class);
        $cost->method('getAmount')->willReturn('10000');
        $cost->method('getCode')->willReturn('test-uuid-1');
        $cost->method('getDetailItems')->willReturn([
            ['markup_estimate' => 1.5]
        ]);

        $price1 = $this->pricer->calculateMarketPrice($cost);
        $price2 = $this->pricer->calculateMarketPrice($cost);

        $this->assertEquals($price1, $price2, 'Price must be deterministic for the same cost item');
    }

    public function testCalculateMarketPriceWithLoot(): void
    {
        $cost = $this->createMock(Cost::class);
        $cost->method('getAmount')->willReturn('0.00'); // Loot
        $cost->method('getCode')->willReturn('loot-uuid');
        $cost->method('getDetailItems')->willReturn([
            [
                'base_value' => 5000,
                'markup_estimate' => 1.2
            ]
        ]);

        $price = $this->pricer->calculateMarketPrice($cost);

        // base_value 5000 * markup 1.2 = 6000 (Standard)
        // Or if volatile, slightly less.
        // We just check it's not 0.
        $this->assertGreaterThan(0, (float)$price);
    }

    public function testCalculateMarketPriceWithDifferentUUIDs(): void
    {
        $cost1 = $this->createMock(Cost::class);
        $cost1->method('getAmount')->willReturn('1000');
        $cost1->method('getCode')->willReturn('uuid-a');
        $cost1->method('getDetailItems')->willReturn([['markup_estimate' => 1.5]]);

        $cost2 = $this->createMock(Cost::class);
        $cost2->method('getAmount')->willReturn('1000');
        $cost2->method('getCode')->willReturn('uuid-b');
        $cost2->method('getDetailItems')->willReturn([['markup_estimate' => 1.5]]);

        $price1 = $this->pricer->calculateMarketPrice($cost1);
        $price2 = $this->pricer->calculateMarketPrice($cost2);

        $this->assertIsString($price1);
        $this->assertIsString($price2);
    }
}
