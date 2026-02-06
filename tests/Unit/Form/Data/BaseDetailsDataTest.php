<?php

namespace App\Tests\Unit\Form\Data;

use App\Form\Data\BaseDetailsData;
use PHPUnit\Framework\TestCase;

class BaseDetailsDataTest extends TestCase
{
    public function testFromArrayAndToArray()
    {
        $input = [
            'hull' => ['tons' => 5000, 'configuration' => 'Buffered Planetoid'],
            'powerPlant' => ['output' => 200, 'tons' => 50],
            'bridge' => ['description' => 'Command CenterAlpha', 'tons' => 100],
            // Missing keys should be handled gracefully
        ];

        $dto = BaseDetailsData::fromArray($input);

        $this->assertEquals(5000, $dto->hull->tons);
        $this->assertEquals(200, $dto->powerPlant->rating);
        $this->assertEquals('Command CenterAlpha', $dto->bridge->description);

        // Assert properties that shouldn't exist
        $this->assertFalse(property_exists($dto, 'jDrive'));
        $this->assertFalse(property_exists($dto, 'mDrive'));
        // Fuel is now a standard component
        $this->assertTrue(property_exists($dto, 'fuel'));

        $output = $dto->toArray();

        $this->assertEquals(5000, $output['hull']['tons']);
        $this->assertArrayNotHasKey('jDrive', $output);
        $this->assertArrayNotHasKey('mDrive', $output);
        $this->assertArrayHasKey('fuel', $output);
    }
}
