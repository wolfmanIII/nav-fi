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

    #[ORM\ManyToOne(inversedBy: 'ships')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Campaign $campaign = null;

    #[ORM\Column(nullable: true)]
    private ?int $sessionDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $sessionYear = null;

    #[ORM\OneToOne(mappedBy: 'ship', cascade: ['persist', 'remove'])]
    private ?Mortgage $mortgage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $shipDetails = null;

    /**
     * @var Collection<int, Crew>
     */
    #[ORM\OneToMany(targetEntity: Crew::class, mappedBy: 'ship')]
    private Collection $crews;

    /**
     * @var Collection<int, Cost>
     */
    #[ORM\OneToMany(targetEntity: Cost::class, mappedBy: 'ship')]
    private Collection $costs;

    /**
     * @var Collection<int, Income>
     */
    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'ship')]
    private Collection $incomes;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->crews = new ArrayCollection();
        $this->costs = new ArrayCollection();
        $this->incomes = new ArrayCollection();
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

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getSessionDay(): ?int
    {
        return $this->sessionDay;
    }

    public function setSessionDay(?int $sessionDay): static
    {
        $this->sessionDay = $sessionDay;

        return $this;
    }

    public function getSessionYear(): ?int
    {
        return $this->sessionYear;
    }

    public function setSessionYear(?int $sessionYear): static
    {
        $this->sessionYear = $sessionYear;

        return $this;
    }

    public function getMortgage(): ?Mortgage
    {
        return $this->mortgage;
    }

    public function setMortgage(?Mortgage $mortgage): static
    {
        // unset owning side of the relation if necessary
        if ($mortgage === null && $this->mortgage !== null) {
            $this->mortgage->setShip(null);
        }

        // set the owning side of the relation if necessary
        if ($mortgage !== null && $mortgage->getShip() !== $this) {
            $mortgage->setShip($this);
        }

        $this->mortgage = $mortgage;

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
            $cost->setShip($this);
        }

        return $this;
    }

    public function removeCost(Cost $cost): static
    {
        if ($this->costs->removeElement($cost)) {
            if ($cost->getShip() === $this) {
                $cost->setShip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Income>
     */
    public function getIncomes(): Collection
    {
        return $this->incomes;
    }

    public function addIncome(Income $income): static
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
            $income->setShip($this);
        }

        return $this;
    }

    public function removeIncome(Income $income): static
    {
        if ($this->incomes->removeElement($income)) {
            if ($income->getShip() === $this) {
                $income->setShip(null);
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
        return $this->getMortgage() !== null;
    }

    public function hasMortgageSigned(): bool
    {
        return $this->getMortgage()?->isSigned() === true;
    }

    public function getShipDetails(): ?array
    {
        return $this->shipDetails;
    }

    public function setShipDetails(?array $shipDetails): static
    {
        $this->shipDetails = $shipDetails;

        return $this;
    }
}
