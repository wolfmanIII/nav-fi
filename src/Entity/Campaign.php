<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
class Campaign
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $startingYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $sessionDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $sessionYear = null;

    /**
     * @var Collection<int, Ship>
     */
    #[ORM\OneToMany(targetEntity: Ship::class, mappedBy: 'campaign')]
    private Collection $ships;

    public function __construct()
    {
        $this->ships = new ArrayCollection();
        $this->id = Uuid::v7();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getStartingYear(): ?int
    {
        return $this->startingYear;
    }

    public function setStartingYear(?int $startingYear): static
    {
        $this->startingYear = $startingYear;

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

    /**
     * @return Collection<int, Ship>
     */
    public function getShips(): Collection
    {
        return $this->ships;
    }

    public function addShip(Ship $ship): static
    {
        if (!$this->ships->contains($ship)) {
            $this->ships->add($ship);
            $ship->setCampaign($this);
        }

        return $this;
    }

    public function removeShip(Ship $ship): static
    {
        if ($this->ships->removeElement($ship)) {
            if ($ship->getCampaign() === $this) {
                $ship->setCampaign(null);
            }
        }

        return $this;
    }
}
