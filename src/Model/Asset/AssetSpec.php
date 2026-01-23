<?php

namespace App\Model\Asset;

class AssetSpec
{
    public function __construct(private array $data) {}

    public function getHull(): HullSpec
    {
        return new HullSpec($this->data['hull'] ?? []);
    }

    public function getJDrive(): DriveSpec
    {
        return new DriveSpec($this->data['jDrive'] ?? []);
    }

    public function getMDrive(): DriveSpec
    {
        return new DriveSpec($this->data['mDrive'] ?? []);
    }

    public function getPowerPlant(): DriveSpec
    {
        // PowerPlant ha 'power' o 'output' di solito, ma riusiamo DriveSpec per le tonnellate
        // e mappiamo rating su output se necessario.
        return new DriveSpec($this->data['powerPlant'] ?? []);
    }

    public function getFuel(): FuelSpec
    {
        return new FuelSpec($this->data['fuel'] ?? []);
    }

    /**
     * Ritorna l'array grezzo originale. Utile per debug o salvataggio.
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
