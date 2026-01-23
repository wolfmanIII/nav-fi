<?php

namespace App\Model\Asset;

class FuelSpec
{
    public function __construct(private array $data) {}

    public function getCapacity(): float
    {
        return isset($this->data['tons']) && is_numeric($this->data['tons'])
            ? (float) $this->data['tons']
            : 0.0;
    }
}
