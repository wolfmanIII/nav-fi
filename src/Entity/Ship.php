<?php

namespace App\Entity;

use App\Repository\ShipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShipRepository::class)]
class Ship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $class = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?float $price = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, Mortgage>
     */
    #[ORM\OneToMany(targetEntity: Mortgage::class, mappedBy: 'ship')]
    private Collection $mortgages;

    /**
     * @var Collection<int, Crew>
     */
    #[ORM\OneToMany(targetEntity: Crew::class, mappedBy: 'ship')]
    private Collection $crews;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->mortgages = new ArrayCollection();
        $this->crews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
            $mortgage->setShip($this);
        }

        return $this;
    }

    public function removeMortgage(Mortgage $mortgage): static
    {
        if ($this->mortgages->removeElement($mortgage)) {
            // set the owning side to null (unless already changed)
            if ($mortgage->getShip() === $this) {
                $mortgage->setShip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Crew>
     */
    public function getCrews(): Collection
    {
        return $this->crews;
    }

    public function addCrew(Crew $crew): static
    {
        if (!$this->crews->contains($crew)) {
            $this->crews->add($crew);
            $crew->setShip($this);
        }

        return $this;
    }

    public function removeCrew(Crew $crew): static
    {
        if ($this->crews->removeElement($crew)) {
            // set the owning side to null (unless already changed)
            if ($crew->getShip() === $this) {
                $crew->setShip(null);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCaptain(): bool
    {
        foreach ($this->crews as $crew) {
            if ($crew->isCaptain()) {
                return true;
            }
        }
        return false;
    }

    public function hasMortgage(): bool
    {
        return $this->getMortgages()->count() > 0;
    }

    public function hasMortgageSigned(): bool
    {
        foreach ($this->getMortgages() as $mortgage) {
            if ($mortgage->isSigned()) {
                return true;
            }
        }
        return false;
    }
}
