<?php

namespace App\Model\Asset;

class HullSpec
{
    public function __construct(private array $data) {}

    public function getTons(): int
    {
        return isset($this->data['tons']) && is_numeric($this->data['tons']) 
            ? (int) $this->data['tons'] 
            : 0;
    }

    public function getPoints(): int
    {
        return isset($this->data['points']) && is_numeric($this->data['points'])
            ? (int) $this->data['points']
            : 0;
    }

    public function getConfiguration(): string
    {
        return $this->data['configuration'] ?? 'Unknown';
    }
}
