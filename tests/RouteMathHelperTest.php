<?php

namespace App\Tests;

use App\Entity\Route;
use App\Entity\Ship;
use App\Service\RouteMathHelper;
use PHPUnit\Framework\TestCase;

class RouteMathHelperTest extends TestCase
{
    private RouteMathHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new RouteMathHelper();
    }

    public function testEstimateJumpFuelCalculatesCorrectlyPerParsec(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getShipDetails')->willReturn([
            'hull' => ['tons' => 200],
            'fuel' => ['tons' => 41],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getShip')->willReturn($ship);

        // Scenario: 2 jumps of 1 parsec each
        // Hull 200 -> 10% = 20 tons per parsec
        // Total route would be 40, but LAST segment is 1 parsec = 20.00 tons
        $distances = [null, 1, 1]; 
        
        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertSame('20.00', $fuel);
    }

    public function testEstimateJumpFuelWithMixedDistances(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getShipDetails')->willReturn([
            'hull' => ['tons' => 400],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getShip')->willReturn($ship);

        // Scenario: Jump A (1 parsec) + Jump B (2 parsecs)
        // Hull 400 -> 10% = 40 tons per parsec
        // LAST segment is 2 parsecs -> 80.00 tons
        $distances = [null, 1, 2];
        
        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertSame('80.00', $fuel);
    }

    public function testEstimateJumpFuelReturnsNullIfNoShipDetails(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getShipDetails')->willReturn([]); // No hull info

        $route = $this->createMock(Route::class);
        $route->method('getShip')->willReturn($ship);

        $distances = [null, 1];
        
        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertNull($fuel);
    }

    public function testEstimateJumpFuelReturnsNullIfNoDistances(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getShipDetails')->willReturn([
            'hull' => ['tons' => 200],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getShip')->willReturn($ship);

        $distances = [null]; // Only starting point
        
        $fuel = $this->helper->estimateJumpFuel($route, $distances);
        $this->assertNull($fuel);
    }

    public function testTotalRequiredFuelCalculatesSumOfAllSegments(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getShipDetails')->willReturn([
            'hull' => ['tons' => 200],
        ]);

        $route = $this->createMock(Route::class);
        $route->method('getShip')->willReturn($ship);
        
        // Waypoints: A -> (1pc) -> B -> (2pc) -> C
        // Hull 200 -> 10% = 20 tons/parsec
        // Total: (1 * 20) + (2 * 20) = 60.00 tons
        $w1 = new \App\Entity\RouteWaypoint(); $w1->setHex('0101');
        $w2 = new \App\Entity\RouteWaypoint(); $w2->setHex('0102'); // Distance 1
        $w3 = new \App\Entity\RouteWaypoint(); $w3->setHex('0104'); // Distance 2
        
        $route->method('getWaypoints')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$w1, $w2, $w3]));

        $totalFuel = $this->helper->totalRequiredFuel($route);
        $this->assertSame('60.00', $totalFuel);
    }
}
