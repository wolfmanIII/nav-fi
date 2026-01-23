<?php

namespace App\Tests\Form;

use App\Form\Type\ShipDetailsType;
use App\Form\Data\ShipDetailsData;
use Symfony\Component\Form\Test\TypeTestCase;
use App\Form\Type\HullType;
use App\Form\Type\DriveType;
use App\Form\Type\FuelType;
use App\Form\Type\GenericComponentType;
use App\Form\AssetDetailItemType;
use Symfony\Component\Form\PreloadedExtension;

class ShipDetailsTypeTest extends TypeTestCase
{
    // Necessary if custom types are not auto-loaded or rely on services, 
    // but here we are using simple types or we might need to mock if dependencies exist.
    // Our types seem simple enough to just work or be registered manually if needed.
    
    // However, if HullType etc are services, we might need to preload them.
    // Assuming they are simple AbstractTypes for now.

    public function testSubmitValidData()
    {
        $formData = [
            'hull' => ['description' => 'Test Hull', 'tons' => 100, 'configuration' => 'Cone', 'points' => 50, 'costMcr' => 5],
            'jDrive' => ['rating' => 1, 'tons' => 10, 'description' => 'J-1', 'costMcr' => 10],
            'mDrive' => ['rating' => 1, 'tons' => 5, 'description' => 'M-1', 'costMcr' => 5],
            'powerPlant' => ['rating' => 100, 'tons' => 10, 'description' => 'P-1', 'costMcr' => 10],
            'fuel' => ['tons' => 20, 'description' => 'Fuel', 'costMcr' => 1],
            'bridge' => ['description' => 'Bridge', 'tons' => 20, 'costMcr' => 2],
            'computer' => ['description' => 'Comp', 'tons' => 1, 'costMcr' => 1],
            'sensors' => ['description' => 'Sensors', 'tons' => 1, 'costMcr' => 1],
            'cargo' => ['description' => 'Cargo', 'tons' => 10, 'costMcr' => 0],
            // Collections need to be handle carefully as array of arrays
            'staterooms' => [
                ['description' => 'Room 1', 'tons' => 4, 'costMcr' => 0.1],
            ],
        ];

        $model = new ShipDetailsData();
        // $model will be populated by the form

        $form = $this->factory->create(ShipDetailsType::class, $model);

        // Submit the data
        $form->submit($formData);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // Check that $model was modified
        $this->assertEquals('Test Hull', $model->hull->description);
        $this->assertEquals(100, $model->hull->tons);
        $this->assertEquals(1, $model->jDrive->rating);
        $this->assertCount(1, $model->staterooms);
        $this->assertEquals('Room 1', $model->staterooms[0]->description);

        // Retrieve data from form (view data)
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
