<?php

namespace App\Dto\Cube;

use Symfony\Component\Validator\Constraints as Assert;

class CubeOpportunityData
{
    public function __construct(
        #[Assert\NotBlank]
        public string $signature,

        #[Assert\NotBlank]
        public string $type,

        #[Assert\NotBlank]
        public string $summary,

        #[Assert\NotNull]
        #[Assert\Type('float')]
        public float $amount,

        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public int $distance,

        #[Assert\Type('array')]
        public array $details = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            signature: $data['signature'] ?? '',
            type: $data['type'] ?? 'UNKNOWN',
            summary: $data['summary'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            distance: (int) ($data['distance'] ?? 0),
            details: $data['details'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'signature' => $this->signature,
            'type' => $this->type,
            'summary' => $this->summary,
            'amount' => $this->amount,
            'distance' => $this->distance,
            'details' => $this->details,
        ];
    }
}
