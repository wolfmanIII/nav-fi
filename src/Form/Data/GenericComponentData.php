<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class GenericComponentData
{
    public ?string $description = null;

    #[Assert\PositiveOrZero]
    public ?float $tons = null;

    public ?float $costMcr = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->description = $data['description'] ?? null;
        $dto->tons = isset($data['tons']) && is_numeric($data['tons']) ? (float)$data['tons'] : null;
        // Support both camel and snake case
        $cost = $data['costMcr'] ?? ($data['cost_mcr'] ?? null);
        $dto->costMcr = is_numeric($cost) ? (float)$cost : null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'tons' => $this->tons,
            'cost_mcr' => $this->costMcr, // Consistent with legacy
        ];
    }
}
