<?php

namespace App\Dto;

class JDriveDetailItem extends AssetDetailItem
{
    public ?int $jump = null;

    public static function fromArray(?array $data): self
    {
        $item = new self();
        $item->description = $data['description'] ?? null;
        $item->tons = isset($data['tons']) ? (float) $data['tons'] : null;
        $item->costMcr = isset($data['cost_mcr']) ? (float) $data['cost_mcr'] : null;
        $item->jump = isset($data['jump']) ? (int) $data['jump'] : null;

        return $item;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'tons' => $this->tons,
            'cost_mcr' => $this->costMcr,
            'jump' => $this->jump,
        ];
    }
}
