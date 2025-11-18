<?php

namespace App\Entity;

use App\Repository\InsuranceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InsuranceRepository::class)]
class Insurance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $annual_cost = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $coverage = null;

    /**
     * @var Collection<int, Mortgage>
     */
    #[ORM\OneToMany(targetEntity: Mortgage::class, mappedBy: 'insurace')]
    private Collection $mortgages;

    public function __construct()
    {
        $this->mortgages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAnnualCost(): ?string
    {
        return $this->annual_cost;
    }

    public function setAnnualCost(string $annual_cost): static
    {
        $this->annual_cost = $annual_cost;

        return $this;
    }

    public function getCoverage(): ?string
    {
        return $this->coverage;
    }

    public function setCoverage(string $coverage): static
    {
        $this->coverage = $coverage;

        return $this;
    }

    /**
     * @return Collection<int, Mortgage>
     */
    public function getMortgages(): Collection
    {
        return $this->mortgages;
    }

    public function addMortgage(Mortgage $mortgage): static
    {
        if (!$this->mortgages->contains($mortgage)) {
            $this->mortgages->add($mortgage);
            $mortgage->setInsurace($this);
        }

        return $this;
    }

    public function removeMortgage(Mortgage $mortgage): static
    {
        if ($this->mortgages->removeElement($mortgage)) {
            // set the owning side to null (unless already changed)
            if ($mortgage->getInsurace() === $this) {
                $mortgage->setInsurace(null);
            }
        }

        return $this;
    }
}
