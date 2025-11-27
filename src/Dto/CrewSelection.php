<?php

namespace App\Dto;

use App\Entity\Crew;

class CrewSelection
{
    private Crew $crew;
    private bool $selected = false;

    public function getCrew(): Crew
    {
        return $this->crew;
    }

    public function setCrew(Crew $crew): self
    {
        $this->crew = $crew;
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
