<?php

namespace App\Entity;

use App\Repository\CostCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CostCategoryRepository::class)]
class CostCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Cost>
     */
    #[ORM\OneToMany(targetEntity: Cost::class, mappedBy: 'costCategory')]
    private Collection $costs;

    public function __construct()
    {
        $this->costs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Cost>
     */
    public function getCosts(): Collection
    {
        return $this->costs;
    }

    public function addCost(Cost $cost): static
    {
        if (!$this->costs->contains($cost)) {
            $this->costs->add($cost);
            $cost->setCostCategory($this);
        }

        return $this;
    }

    public function removeCost(Cost $cost): static
    {
        if ($this->costs->removeElement($cost)) {
            if ($cost->getCostCategory() === $this) {
                $cost->setCostCategory(null);
            }
        }

        return $this;
    }
}
