<?php

namespace App\Entity;

use App\Repository\CrewRepository;
use App\Validator\Captain;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CrewRepository::class)]
#[Captain]
class Crew
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $surname = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(nullable: true)]
    private ?int $birthYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $birthDay = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $birthWorld = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'crews')]
    private ?Ship $ship = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, ShipRole>
     */
    #[ORM\ManyToMany(targetEntity: ShipRole::class, inversedBy: 'crews')]
    private Collection $shipRoles;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $background = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $activeDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $activeYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $onLeaveDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $onLeaveYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $retiredDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $retiredYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $miaDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $miaYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $deceasedDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $deceasedYear = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->shipRoles = new ArrayCollection();
        $this->status = 'Active';
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getBirthYear(): ?int
    {
        return $this->birthYear;
    }

    public function setBirthYear(?int $birthYear): static
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    public function getBirthDay(): ?int
    {
        return $this->birthDay;
    }

    public function setBirthDay(?int $birthDay): static
    {
        $this->birthDay = $birthDay;

        return $this;
    }

    public function getBirthWorld(): ?string
    {
        return $this->birthWorld;
    }

    public function setBirthWorld(?string $birthWorld): static
    {
        $this->birthWorld = $birthWorld;

        return $this;
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

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

        return $this;
    }

    /**
     * @return Collection<int, ShipRole>
     */
    public function getShipRoles(): Collection
    {
        return $this->shipRoles;
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

    public function addShipRole(ShipRole $shipRole): static
    {
        if (!$this->shipRoles->contains($shipRole)) {
            $this->shipRoles->add($shipRole);
        }

        return $this;
    }

    public function removeShipRole(ShipRole $shipRole): static
    {
        $this->shipRoles->removeElement($shipRole);

        return $this;
    }

    public function isCaptain(): bool
    {
        foreach($this->getShipRoles() as $role) {
            if ($role->getCode() === "CAP") {
                return true;
            }
        }
        return false;
    }

    public function hasMortgageSigned(): bool
    {
        if ($this->getShip()) {
            return $this->getShip()->hasMortgageSigned();
        }
        return false;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(?string $background): static
    {
        $this->background = $background;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getActiveDay(): ?int
    {
        return $this->activeDay;
    }

    public function setActiveDay(?int $activeDay): static
    {
        $this->activeDay = $activeDay;

        return $this;
    }

    public function getActiveYear(): ?int
    {
        return $this->activeYear;
    }

    public function setActiveYear(?int $activeYear): static
    {
        $this->activeYear = $activeYear;

        return $this;
    }

    public function getOnLeaveDay(): ?int
    {
        return $this->onLeaveDay;
    }

    public function setOnLeaveDay(?int $onLeaveDay): static
    {
        $this->onLeaveDay = $onLeaveDay;

        return $this;
    }

    public function getOnLeaveYear(): ?int
    {
        return $this->onLeaveYear;
    }

    public function setOnLeaveYear(?int $onLeaveYear): static
    {
        $this->onLeaveYear = $onLeaveYear;

        return $this;
    }

    public function getRetiredDay(): ?int
    {
        return $this->retiredDay;
    }

    public function setRetiredDay(?int $retiredDay): static
    {
        $this->retiredDay = $retiredDay;

        return $this;
    }

    public function getRetiredYear(): ?int
    {
        return $this->retiredYear;
    }

    public function setRetiredYear(?int $retiredYear): static
    {
        $this->retiredYear = $retiredYear;

        return $this;
    }

    public function getMiaDay(): ?int
    {
        return $this->miaDay;
    }

    public function setMiaDay(?int $miaDay): static
    {
        $this->miaDay = $miaDay;

        return $this;
    }

    public function getMiaYear(): ?int
    {
        return $this->miaYear;
    }

    public function setMiaYear(?int $miaYear): static
    {
        $this->miaYear = $miaYear;

        return $this;
    }

    public function getDeceasedDay(): ?int
    {
        return $this->deceasedDay;
    }

    public function setDeceasedDay(?int $deceasedDay): static
    {
        $this->deceasedDay = $deceasedDay;

        return $this;
    }

    public function getDeceasedYear(): ?int
    {
        return $this->deceasedYear;
    }

    public function setDeceasedYear(?int $deceasedYear): static
    {
        $this->deceasedYear = $deceasedYear;

        return $this;
    }
}
