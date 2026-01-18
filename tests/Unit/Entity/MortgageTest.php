<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Asset;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use PHPUnit\Framework\TestCase;

class MortgageTest extends TestCase
{
    public function testCalculateAssetCostBase(): void
    {
        $asset = new Asset();
        $asset->setPrice('1000000.00');

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);

        // No shares, no advance, no discount
        self::assertEquals('1000000.000000', $mortgage->calculateAssetCost());
    }

    public function testCalculateAssetCostWithShares(): void
    {
        $asset = new Asset();
        $asset->setPrice('1000000.00');

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setAssetShares(10); // 10% or just 10 shares? Mortgage entity says ASSET_SHARE_VALUE = 1000000 constant?
        // Wait, ASSET_SHARE_VALUE = 1000000. So 1 share = 1M? 
        // Let's check the code: bcmul($shares, self::ASSET_SHARE_VALUE, 4)
        // If Price is 10M and 5 shares, cost should be 5M.

        $asset->setPrice('10000000.00'); // 10M
        $mortgage->setAssetShares(2); // 2 * 1M = 2M deduction

        self::assertEquals('8000000.000000', $mortgage->calculateAssetCost());
    }

    public function testCalculateAssetCostWithAdvanceAndDiscount(): void
    {
        $asset = new Asset();
        $asset->setPrice('5000000.00');

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setAdvancePayment('1000000.00');
        $mortgage->setDiscount('500000.00');
        $mortgage->setDiscountIsPercentage(false);

        // 5M - 1M (Advance) - 0.5M (Discount) = 3.5M
        self::assertEquals('3500000.000000', $mortgage->calculateAssetCost());
    }

    public function testCalculateMonthlyPaymentStandard(): void
    {
        $asset = new Asset();
        $asset->setPrice('240000.00'); // Easy division

        $rate = new InterestRate();
        $rate->setPriceMultiplier('1.0');
        $rate->setPriceDivider(1);
        $rate->setDuration(240); // 240 months

        $mortgage = new Mortgage();
        $mortgage->setAsset($asset);
        $mortgage->setInterestRate($rate);

        // Logic in entity:
        // AssetCost (from calculateAssetCost) used? 
        // code says: $totalMortgage = bcmul($assetCost, $multiplier)
        // BUT calculateMonthlyPayment method logic:
        // $base = bcdiv($assetPrice, '100', 6) -> 2400
        // ...
        // Wait, the entity has logic:
        // $totalMortgage = bcmul($assetCost, $multiplier, 6);
        // It uses calculateAssetCost() inside calculateMonthlyPayment if rate exists?
        // Let's re-read Entity code carefully in next step if test fails.
        // Line 355: $totalMortgage = bcmul($assetCost, $multiplier, 6);
        // Line 348: $assetCost = $this->calculateAssetCost();

        // So if Price=240k, Cost=240k. 
        // Total = 240k * 1 = 240k.
        // Monthly = 240k / 240 = 1000.

        self::assertEquals('1000.000000', $mortgage->calculateMonthlyPayment());
    }
}
