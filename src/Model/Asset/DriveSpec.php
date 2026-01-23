<?php

namespace App\Model\Asset;

class DriveSpec
{
    public function __construct(private array $data) {}

    public function getRating(): int
    {
        // 'jump' per J-Drive, 'maneuver' o generico 'rating' per altri?
        // Nel form attuale usiamo 'jump' per J-Drive. Standardizziamo un getter intelligente.
        
        if (isset($this->data['jump']) && is_numeric($this->data['jump'])) {
            return (int) $this->data['jump'];
        }

        if (isset($this->data['rating']) && is_numeric($this->data['rating'])) {
            return (int) $this->data['rating'];
        }

        return 0;
    }

    public function getTons(): float
    {
        return isset($this->data['tons']) && is_numeric($this->data['tons'])
            ? (float) $this->data['tons']
            : 0.0;
    }
}
