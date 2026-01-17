<?php

namespace App\Dto;

use App\Entity\Asset;

class AssetSelection
{
    private Asset $asset;
    private bool $selected = false;

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): self
    {
        $this->asset = $asset;
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
