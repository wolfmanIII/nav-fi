<?php

namespace App\Entity;

use App\Repository\RouteWaypointRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RouteWaypointRepository::class)]
#[ORM\Index(name: 'idx_route_waypoint_route', columns: ['route_id'])]
class RouteWaypoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $active = false;

    #[ORM\ManyToOne(inversedBy: 'waypoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Route $route = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(length: 4)]
    #[Assert\NotBlank]
    private ?string $hex = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $world = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $uwp = null;

    #[ORM\Column(nullable: true)]
    private ?int $jumpDistance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(?Route $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getHex(): ?string
    {
        return $this->hex;
    }

    public function setHex(?string $hex): static
    {
        $this->hex = $hex;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;

        return $this;
    }

    public function getWorld(): ?string
    {
        return $this->world;
    }

    public function setWorld(?string $world): static
    {
        $this->world = $world;

        return $this;
    }

    public function getUwp(): ?string
    {
        return $this->uwp;
    }

    public function setUwp(?string $uwp): static
    {
        $this->uwp = $uwp;

        return $this;
    }

    public function getJumpDistance(): ?int
    {
        return $this->jumpDistance;
    }

    public function setJumpDistance(?int $jumpDistance): static
    {
        $this->jumpDistance = $jumpDistance;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
