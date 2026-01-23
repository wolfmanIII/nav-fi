<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class DriveData
{
    #[Assert\PositiveOrZero]
    public ?int $rating = null;

    #[Assert\PositiveOrZero]
    public ?float $tons = null;

    public ?string $description = null;
    public ?float $costMcr = null;

    // Specifica il nome della chiave per il rating (es. 'jump' o 'rating')
    private string $ratingKey;

    public function __construct(string $ratingKey = 'rating')
    {
        $this->ratingKey = $ratingKey;
    }

    public static function create(string $ratingKey): self
    {
        return new self($ratingKey);
    }

    public static function fromArray(array $data, string $ratingKey = 'rating'): self
    {
        $dto = new self($ratingKey);
        
        // Supporta sia 'jump' che 'rating' in input, ma preferisce ratingKey
        $val = $data[$ratingKey] ?? ($data['rating'] ?? ($data['jump'] ?? null));
        
        $dto->rating = is_numeric($val) ? (int)$val : null;
        $dto->tons = isset($data['tons']) && is_numeric($data['tons']) ? (float)$data['tons'] : null;
        $dto->description = $data['description'] ?? null;
        $dto->costMcr = isset($data['costMcr']) && is_numeric($data['costMcr']) ? (float)$data['costMcr'] : null;
        
        return $dto;
    }

    public function toArray(): array
    {
        return [
            $this->ratingKey => $this->rating,
            'tons' => $this->tons,
            'description' => $this->description,
            'costMcr' => $this->costMcr,
        ];
    }
}
