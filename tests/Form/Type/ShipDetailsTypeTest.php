<?php

namespace App\Tests\Form\Type;

use App\Form\Type\ShipDetailsType;
use App\Form\Type\HullType;
use App\Form\Type\DriveType;
use App\Form\Type\FuelType;
use App\Form\Data\ShipDetailsData;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

class ShipDetailsTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        // Carica i tipi annidati se necessario, ma TypeTestCase di solito li gestisce se sono classi complete
        return [];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'hull' => ['tons' => 200, 'points' => 80],
            'jDrive' => ['rating' => 2, 'tons' => 15.0], // Nota: rating, non jump, perché il form usa 'rating' internamente al child form
            'mDrive' => ['rating' => 2, 'tons' => 5.0],
            'powerPlant' => ['rating' => 4, 'tons' => 8.0],
            'fuel' => ['tons' => 40.0],
        ];

        $form = $this->factory->create(ShipDetailsType::class);

        // Submit data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        /** @var ShipDetailsData $data */
        $data = $form->getData();

        $this->assertInstanceOf(ShipDetailsData::class, $data);
        $this->assertEquals(200, $data->hull->tons);
        $this->assertEquals(2, $data->jDrive->rating);
        $this->assertEquals(15.0, $data->jDrive->tons);
        $this->assertEquals(40.0, $data->fuel->tons);
    }
}
