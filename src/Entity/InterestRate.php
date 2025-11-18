<?php

namespace App\Entity;

use App\Repository\InterestRateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterestRateRepository::class)]
class InterestRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $price_multiplier = null;

    #[ORM\Column]
    private ?int $price_divider = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $annual_interest_rate = null;

    /**
     * @var Collection<int, Mortgage>
     */
    #[ORM\OneToMany(targetEntity: Mortgage::class, mappedBy: 'interestRate')]
    private Collection $mortgages;

    public function __construct()
    {
        $this->mortgages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPriceMultiplier(): ?string
    {
        return $this->price_multiplier;
    }

    public function setPriceMultiplier(string $price_multiplier): static
    {
        $this->price_multiplier = $price_multiplier;

        return $this;
    }

    public function getPriceDivider(): ?string
    {
        return $this->price_divider;
    }

    public function setPriceDivider(int $price_divider): static
    {
        $this->price_divider = $price_divider;

        return $this;
    }

    public function getAnnualInterestRate(): ?string
    {
        return $this->annual_interest_rate;
    }

    public function setAnnualInterestRate(string $annual_interest_rate): static
    {
        $this->annual_interest_rate = $annual_interest_rate;

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
            $mortgage->setInterestRate($this);
        }

        return $this;
    }

    public function removeMortgage(Mortgage $mortgage): static
    {
        if ($this->mortgages->removeElement($mortgage)) {
            // set the owning side to null (unless already changed)
            if ($mortgage->getInterestRate() === $this) {
                $mortgage->setInterestRate(null);
            }
        }

        return $this;
    }
}
