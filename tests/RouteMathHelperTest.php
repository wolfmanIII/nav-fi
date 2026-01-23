<?php

namespace App\Tests;

use App\Entity\Route;
use App\Entity\Asset;
use App\Service\RouteMathHelper;
use PHPUnit\Framework\TestCase;

class RouteMathHelperTest extends TestCase
{
    private RouteMathHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new RouteMathHelper();
    }

    public function testGetAssetFuelCapacity()
    {
        $helper = new RouteMathHelper();
        $asset = new Asset();

        $asset->setAssetDetails(['fuel' => ['tons' => 40]]);
        $this->assertEquals(40.0, $helper->getAssetFuelCapacity($asset));

        $assetEmpty = new Asset();
        $assetEmpty->setAssetDetails([]);
        $this->assertNull($helper->getAssetFuelCapacity($assetEmpty));

        $this->assertNull($helper->getAssetFuelCapacity(null));
    }

    // ... (lines 33-71 skipped) ...

    public function testGetAssetJumpRating()
    {
        $helper = new RouteMathHelper();
        $asset = new Asset();

        $asset->setAssetDetails(['jDrive' => ['jump' => 2]]);
        $this->assertEquals(2, $helper->getAssetJumpRating($asset));

        $asset->setAssetDetails(['jDrive' => ['jump' => '3']]);
        $this->assertEquals(3, $helper->getAssetJumpRating($asset));

        $assetEmpty = new Asset();
        $assetEmpty->setAssetDetails([]);
        $this->assertNull($helper->getAssetJumpRating($assetEmpty));

        $this->assertNull($helper->getAssetJumpRating(null));
    }

    public function testEstimateJumpFuelReturnsNullIfNoShipDetails(): void
    {
        $asset = $this->createMock(Asset::class);
        // Ritorniamo uno spec vuoto
        $asset->method('getSpec')->willReturn(new \App\Model\Asset\AssetSpec([]));

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

        $distances = [null, 1];

        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertNull($fuel);
    }

    public function testEstimateJumpFuelReturnsNullIfNoDistances(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getSpec')->willReturn(new \App\Model\Asset\AssetSpec([
            'hull' => ['tons' => 200],
        ]));

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

        $distances = [null]; // Solo punto iniziale

        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertNull($fuel);
    }

    public function testResolveJumpRating()
    {
        $helper = new RouteMathHelper();
        $route = new Route();
        $this->assertNull($helper->resolveJumpRating($route));

        $route->setJumpRating(2);
        $this->assertEquals(2, $helper->resolveJumpRating($route));

        // Test fallback su asset
        $route->setJumpRating(null);
        $asset = new Asset();
        $asset->setAssetDetails(['jDrive' => ['jump' => 3]]);
        $route->setAsset($asset);
        $this->assertEquals(3, $helper->resolveJumpRating($route));
    }

    public function testTotalRequiredFuelCalculatesSumOfAllSegments(): void
    {
        $assetMock = $this->createMock(Asset::class);
        $assetMock->method('getSpec')->willReturn(new \App\Model\Asset\AssetSpec([
            'hull' => ['tons' => 200],
        ]));

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($assetMock);

        // Punti di passaggio: A -> (1pc) -> B -> (2pc) -> C
        // Scafo 200 -> 10% = 20 tonnellate/parsec
        // Totale: (1 * 20) + (2 * 20) = 60.00 tonnellate
        $w1 = new \App\Entity\RouteWaypoint();
        $w1->setHex('0101');
        $w2 = new \App\Entity\RouteWaypoint();
        $w2->setHex('0102'); // Distanza 1
        $w3 = new \App\Entity\RouteWaypoint();
        $w3->setHex('0104'); // Distanza 2

        $route->method('getWaypoints')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$w1, $w2, $w3]));

        $totalFuel = $this->helper->totalRequiredFuel($route);
        $this->assertSame('60.00', $totalFuel);
    }
}
