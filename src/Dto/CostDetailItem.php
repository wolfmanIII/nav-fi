<?php

namespace App\Dto;

readonly class CostDetailItem
{
    public function __construct(
        public string $description,
        public float $quantity,
        public float $cost,
        public bool $isSold = false,
        public ?float $markupEstimate = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            description: (string)($data['description'] ?? ''),
            quantity: (float)($data['quantity'] ?? 0.0),
            cost: (float)($data['cost'] ?? 0.0),
            isSold: (bool)($data['isSold'] ?? false),
            markupEstimate: isset($data['markup_estimate']) ? (float)$data['markup_estimate'] : null
        );
    }

    public function toArray(): array
    {
        $arr = [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'cost' => $this->cost,
            'isSold' => $this->isSold,
        ];

        if ($this->markupEstimate !== null) {
            $arr['markup_estimate'] = $this->markupEstimate;
        }

        return $arr;
    }

    public function withSoldStatus(bool $sold): self
    {
        return new self(
            $this->description,
            $this->quantity,
            $this->cost,
            $sold,
            $this->markupEstimate
        );
    }
}
