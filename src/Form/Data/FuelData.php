<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class FuelData
{
    #[Assert\PositiveOrZero]
    public ?float $tons = null;
    
    public ?string $description = null;
    public ?float $costMcr = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->tons = isset($data['tons']) && is_numeric($data['tons']) ? (float)$data['tons'] : null;
        $dto->description = $data['description'] ?? null;
        $dto->costMcr = isset($data['costMcr']) && is_numeric($data['costMcr']) ? (float)$data['costMcr'] : null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'tons' => $this->tons,
            'description' => $this->description,
            'costMcr' => $this->costMcr,
        ];
    }
}
