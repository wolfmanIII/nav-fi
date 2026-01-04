<?php

namespace App\Dto;

use App\Entity\Ship;

class ShipSelection
{
    private Ship $ship;
    private bool $selected = false;

    public function getShip(): Ship
    {
        return $this->ship;
    }

    public function setShip(Ship $ship): self
    {
        $this->ship = $ship;
        return $this;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;
        return $this;
    }
}
