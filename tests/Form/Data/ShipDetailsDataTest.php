<?php

namespace App\Tests\Form\Data;

use App\Form\Data\ShipDetailsData;
use PHPUnit\Framework\TestCase;

class ShipDetailsDataTest extends TestCase
{
    public function testFromArrayPopulatesDto(): void
    {
        $input = [
            'hull' => ['tons' => 200, 'configuration' => 'Needle'],
            'jDrive' => ['jump' => 2, 'tons' => 15],
            'fuel' => ['tons' => 44.5],
        ];

        $dto = ShipDetailsData::fromArray($input);

        $this->assertEquals(200, $dto->hull->tons);
        $this->assertEquals('Needle', $dto->hull->configuration);
        $this->assertEquals(2, $dto->jDrive->rating);
        $this->assertEquals(44.5, $dto->fuel->tons);
    }

    public function testToArrayReturnsStructuredArray(): void
    {
        $dto = new ShipDetailsData();
        $dto->hull->tons = 300;
        $dto->jDrive->rating = 3;
        $dto->powerPlant->tons = 12.5;

        $array = $dto->toArray();

        $this->assertEquals(300, $array['hull']['tons']);
        $this->assertEquals(3, $array['jDrive']['jump']); // Nota chiave 'jump'
        $this->assertEquals(12.5, $array['powerPlant']['tons']);
    }

    public function testRoundTrip(): void
    {
        $input = [
            'hull' => ['tons' => 400, 'points' => 100, 'configuration' => 'Sphere'],
            'jDrive' => ['jump' => 4, 'tons' => 20.0],
            'mDrive' => ['rating' => 4, 'tons' => 10.0],
            'powerPlant' => ['output' => 6, 'tons' => 12.0],
            'fuel' => ['tons' => 120.0],
        ];

        // The DTO toArray() returns the full structure with defaults/nulls
        $expected = [
            'hull' => ['tons' => 400, 'points' => 100, 'configuration' => 'Sphere', 'description' => null, 'costMcr' => null],
            'jDrive' => ['jump' => 4, 'tons' => 20.0, 'description' => null, 'costMcr' => null],
            'mDrive' => ['rating' => 4, 'tons' => 10.0, 'description' => null, 'costMcr' => null],
            'powerPlant' => ['output' => 6, 'tons' => 12.0, 'description' => null, 'costMcr' => null],
            'fuel' => ['tons' => 120.0, 'description' => null, 'costMcr' => null],
            'bridge' => ['description' => null, 'tons' => null, 'cost_mcr' => null],
            'computer' => ['description' => null, 'tons' => null, 'cost_mcr' => null],
            'sensors' => ['description' => null, 'tons' => null, 'cost_mcr' => null],
            'cargo' => ['description' => null, 'tons' => null, 'cost_mcr' => null],
            'staterooms' => [],
            'commonAreas' => [],
            'weapons' => [],
            'systems' => [],
            'software' => [],
            'craft' => [],
            'techLevel' => null,
            'totalCost' => null,
        ];

        $dto = ShipDetailsData::fromArray($input);
        $result = $dto->toArray();

        $this->assertEquals($expected, $result);
    }
}
