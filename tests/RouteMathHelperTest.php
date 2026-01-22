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

        $asset->setAssetDetails([]);
        $this->assertNull($helper->getAssetFuelCapacity($asset));

        $this->assertNull($helper->getAssetFuelCapacity(null));
    }

    public function testEstimateJumpFuelCalculatesCorrectlyPerParsec(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetDetails')->willReturn([
            'hull' => ['tons' => 200],
            'fuel' => ['tons' => 41],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

        // Scenario: 2 salti da 1 parsec ciascuno
        // Scafo 200 -> 10% = 20 tonnellate per parsec
        // Totale rotta sarebbe 40, ma l'ULTIMO segmento è 1 parsec = 20.00 tonnellate
        $distances = [null, 1, 1];

        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertSame('20.00', $fuel);
    }

    public function testEstimateJumpFuelWithMixedDistances(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetDetails')->willReturn([
            'hull' => ['tons' => 400],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

        // Scenario: Salto A (1 parsec) + Salto B (2 parsec)
        // Scafo 400 -> 10% = 40 tonnellate per parsec
        // L'ULTIMO segmento è 2 parsec -> 80.00 tonnellate
        $distances = [null, 1, 2];

        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertSame('80.00', $fuel);
    }

    public function testGetAssetJumpRating()
    {
        $helper = new RouteMathHelper();
        $asset = new Asset();

        $asset->setAssetDetails(['jDrive' => ['jump' => 2]]);
        $this->assertEquals(2, $helper->getAssetJumpRating($asset));

        $asset->setAssetDetails(['jDrive' => ['jump' => '3']]);
        $this->assertEquals(3, $helper->getAssetJumpRating($asset));

        $asset->setAssetDetails([]);
        $this->assertNull($helper->getAssetJumpRating($asset));

        $this->assertNull($helper->getAssetJumpRating(null));
    }

    public function testEstimateJumpFuelReturnsNullIfNoShipDetails(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetDetails')->willReturn([]); // Nessuna info scafo

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

        $distances = [null, 1];

        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertNull($fuel);
    }

    public function testEstimateJumpFuelReturnsNullIfNoDistances(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetDetails')->willReturn([
            'hull' => ['tons' => 200],
        ]);

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
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetDetails')->willReturn([
            'hull' => ['tons' => 200],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getAsset')->willReturn($asset);

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
