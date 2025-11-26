<?php

namespace App\Entity;

use App\Repository\CrewRepository;
use App\Validator\Captain;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    /**
     * @var Collection<int, ShipRole>
     */
    #[ORM\ManyToMany(targetEntity: ShipRole::class, inversedBy: 'crews')]
    private Collection $shipRoles;

    public function __construct()
    {
        $this->shipRoles = new ArrayCollection();
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
}
