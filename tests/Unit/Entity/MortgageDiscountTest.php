<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Asset;
use App\Entity\Mortgage;
use PHPUnit\Framework\TestCase;

class MortgageDiscountTest extends TestCase
{
    public function testCalculateAssetCostWithFlatDiscount()
    {
        $asset = new Asset();
        $asset->setPrice('1000000'); // 1 MCr

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setDiscount('10'); // 10 Cr? or 10%?
        $mortgage->setDiscountIsPercentage(false); // Default

        // If flat, cost should be 1,000,000 - 10 = 999,990
        $this->assertEquals('999990.000000', $mortgage->calculateAssetCost());
    }

    public function testCalculateAssetCostWithPercentageDiscountBug()
    {
        $asset = new Asset();
        $asset->setPrice('1000000'); // 1 MCr

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setDiscount('10'); // 10%
        $mortgage->setDiscountIsPercentage(true);

        // Expected if working correctly: 1,000,000 - 10% (100,000) = 900,000
        $this->assertEquals('900000.000000', $mortgage->calculateAssetCost());
    }

    public function testUserReportedScenario()
    {
        // Price 55,000,000
        // Discount 25%
        // Shares 2 (2,000,000)
        // Expected: 39,250,000

        $asset = new Asset();
        $asset->setPrice('55000000');

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setAssetShares(2);
        $mortgage->setDiscount('25');
        $mortgage->setDiscountIsPercentage(true);

        // Calculation:
        // Price - Shares = 53,000,000
        // Discount (25% of 55M) = 13,750,000
        // Final = 53M - 13.75M = 39,250,000

        $this->assertEquals('39250000.000000', $mortgage->calculateAssetCost(), 'Failed to replicate user scenario');
    }
}
