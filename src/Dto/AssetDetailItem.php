<?php

namespace App\Dto;

class AssetDetailItem
{
    public ?string $description = null;
    public ?float $tons = null;
    public ?float $costMcr = null;

    public static function fromArray(?array $data): self
    {
        $item = new self();
        $item->description = $data['description'] ?? null;
        $item->tons = isset($data['tons']) ? (float) $data['tons'] : null;
        $item->costMcr = isset($data['cost_mcr']) ? (float) $data['cost_mcr'] : null;

        return $item;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'tons' => $this->tons,
            'cost_mcr' => $this->costMcr,
        ];
    }
}
