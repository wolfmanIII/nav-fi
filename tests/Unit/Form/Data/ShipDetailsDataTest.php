<?php

namespace App\Tests\Unit\Form\Data;

use App\Form\Data\ShipDetailsData;
use App\Form\Data\HullData;
use App\Form\Data\DriveData;
use App\Form\Data\FuelData;
use App\Dto\AssetDetailItem;
use PHPUnit\Framework\TestCase;

class ShipDetailsDataTest extends TestCase
{
    public function testFromArrayAndToArraySerialization()
    {
        $inputData = [
            'hull' => [
                'description' => 'Standard Hull',
                'tons' => 200,
                'configuration' => 'Streamlined',
                'points' => 80,
                'costMcr' => 10.5
            ],
            'jDrive' => [
                'rating' => 2,
                'tons' => 15,
                'description' => 'Jump-2',
                'costMcr' => 20
            ],
            'mDrive' => [
                'rating' => 4,
                'tons' => 10,
                'description' => 'Thrust-4',
                'costMcr' => 15
            ],
            'powerPlant' => [
                'rating' => 150,
                'tons' => 12,
                'description' => 'Fusion-12',
                'costMcr' => 25
            ],
            'fuel' => [
                'tons' => 40,
                'description' => 'Liquid Hydrogen',
                'costMcr' => 0
            ],
            'bridge' => ['description' => 'Standard Bridge', 'tons' => 20, 'costMcr' => 5],
            'computer' => ['description' => 'Model/2', 'tons' => 0, 'costMcr' => 0.5],
            'sensors' => ['description' => 'Basic', 'tons' => 1, 'costMcr' => 0],
            'cargo' => ['description' => 'Main Hold', 'tons' => 50, 'costMcr' => 0],
            
            // Collections
            'staterooms' => [
                ['description' => 'Standard', 'tons' => 4, 'costMcr' => 0.5],
                ['description' => 'High', 'tons' => 6, 'costMcr' => 0.8],
            ],
            'commonAreas' => [
                 ['description' => 'Gym', 'tons' => 10, 'costMcr' => 1.0],
            ],
            'weapons' => [],
            'systems' => [],
            'software' => [],
            'craft' => [],
            
            'techLevel' => 12,
            'totalCost' => 150.5
        ];

        // Deserialize
        $dto = ShipDetailsData::fromArray($inputData);

        // Verify Objects
        $this->assertInstanceOf(HullData::class, $dto->hull);
        $this->assertEquals(200, $dto->hull->tons);
        $this->assertEquals('Streamlined', $dto->hull->configuration);

        $this->assertInstanceOf(DriveData::class, $dto->jDrive);
        $this->assertEquals(2, $dto->jDrive->rating);

        $this->assertInstanceOf(FuelData::class, $dto->fuel);
        $this->assertEquals(40, $dto->fuel->tons);

        // Verify Collections
        $this->assertCount(2, $dto->staterooms);
        $this->assertInstanceOf(AssetDetailItem::class, $dto->staterooms[0]);
        $this->assertEquals('Standard', $dto->staterooms[0]->description);

        $this->assertCount(1, $dto->commonAreas);
        $this->assertInstanceOf(AssetDetailItem::class, $dto->commonAreas[0]);
        $this->assertEquals('Gym', $dto->commonAreas[0]->description);

        // Serialize back
        $outputArray = $dto->toArray();

        // Verify Structure matches input (ignoring nulls if any)
        $this->assertEquals($inputData['hull']['description'], $outputArray['hull']['description']);
        $this->assertEquals($inputData['staterooms'][0]['description'], $outputArray['staterooms'][0]['description']);
        $this->assertEquals($inputData['commonAreas'][0]['description'], $outputArray['commonAreas'][0]['description']);
        $this->assertEquals(150.5, $outputArray['totalCost']);
    }

    public function testEmptyInitialization()
    {
        $dto = new ShipDetailsData();
        $this->assertNotNull($dto->hull);
        $this->assertNotNull($dto->jDrive);
        $this->assertIsArray($dto->staterooms);
        $this->assertIsArray($dto->commonAreas);
    }
}
