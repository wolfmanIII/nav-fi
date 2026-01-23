<?php

namespace App\Tests\Model\Asset;

use App\Model\Asset\AssetSpec;
use PHPUnit\Framework\TestCase;

class AssetSpecTest extends TestCase
{
    public function testGetHullSpec(): void
    {
        $data = [
            'hull' => ['tons' => 200, 'points' => 80, 'configuration' => 'Needle']
        ];
        $spec = new AssetSpec($data);
        $hull = $spec->getHull();

        $this->assertEquals(200, $hull->getTons());
        $this->assertEquals(80, $hull->getPoints());
        $this->assertEquals('Needle', $hull->getConfiguration());
    }

    public function testGetJDriveSpec(): void
    {
        $data = [
            'jDrive' => ['jump' => 2, 'tons' => 15]
        ];
        $spec = new AssetSpec($data);
        $drive = $spec->getJDrive();

        $this->assertEquals(2, $drive->getRating());
        $this->assertEquals(15.0, $drive->getTons());
    }

    public function testGetFuelSpec(): void
    {
        $data = [
            'fuel' => ['tons' => 44.5]
        ];
        $spec = new AssetSpec($data);
        $fuel = $spec->getFuel();

        $this->assertEquals(44.5, $fuel->getCapacity());
    }

    public function testDefaultsOnEmptyData(): void
    {
        $spec = new AssetSpec([]);

        // Hull defaults
        $this->assertEquals(0, $spec->getHull()->getTons());
        $this->assertEquals('Unknown', $spec->getHull()->getConfiguration());

        // Drive defaults
        $this->assertEquals(0, $spec->getJDrive()->getRating());
        $this->assertEquals(0.0, $spec->getJDrive()->getTons());

        // Fuel defaults
        $this->assertEquals(0.0, $spec->getFuel()->getCapacity());
    }

    public function testDriveFallbackToRatingKey(): void
    {
        // Test compatibility if 'rating' key is used instead of 'jump'
        $data = [
            'mDrive' => ['rating' => 4, 'tons' => 10]
        ];
        $spec = new AssetSpec($data);
        $drive = $spec->getMDrive();

        $this->assertEquals(4, $drive->getRating());
    }
}
