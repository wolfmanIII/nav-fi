<?php

namespace App\Entity;

use App\Repository\GameRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: GameRuleRepository::class)]
#[UniqueEntity(fields: ['ruleKey'], message: 'Questa chiave esiste già.')]
class GameRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $ruleKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 50)]
    private ?string $type = 'string';

    #[ORM\Column(length: 100, options: ['default' => 'GLOBAL'])]
    private ?string $category = 'GLOBAL';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuleKey(): ?string
    {
        return $this->ruleKey;
    }

    public function setRuleKey(string $ruleKey): static
    {
        $this->ruleKey = $ruleKey;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Ritorna il valore castato al tipo corretto.
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'int', 'integer' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'bool', 'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
