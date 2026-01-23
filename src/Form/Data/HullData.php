<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class HullData
{
    #[Assert\PositiveOrZero]
    public ?int $tons = null;

    #[Assert\PositiveOrZero]
    public ?int $points = null;

    public ?string $configuration = null;

    public ?string $description = null;
    public ?float $costMcr = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->tons = isset($data['tons']) && is_numeric($data['tons']) ? (int)$data['tons'] : null;
        $dto->points = isset($data['points']) && is_numeric($data['points']) ? (int)$data['points'] : null;
        $dto->configuration = $data['configuration'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->costMcr = isset($data['costMcr']) && is_numeric($data['costMcr']) ? (float)$data['costMcr'] : null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'tons' => $this->tons,
            'points' => $this->points,
            'configuration' => $this->configuration,
            'description' => $this->description,
            'costMcr' => $this->costMcr,
        ];
    }
}
