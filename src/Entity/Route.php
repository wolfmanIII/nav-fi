<?php

namespace App\Entity;

use App\Repository\RouteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RouteRepository::class)]
#[ORM\Index(name: 'idx_route_asset', columns: ['asset_id'])]
#[ORM\Index(name: 'idx_route_campaign', columns: ['campaign_id'])]
class Route
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'routes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne(inversedBy: 'routes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $plannedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $startHex = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $destHex = null;

    #[ORM\Column(nullable: true)]
    private ?int $startDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $destDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $destYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $jumpRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fuelEstimate = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    /**
     * @var Collection<int, RouteWaypoint>
     */
    #[ORM\OneToMany(targetEntity: RouteWaypoint::class, mappedBy: 'route', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $waypoints;

    public function __construct()
    {
        $this->setCode(Uuid::v7()->toRfc4122());
        $this->plannedAt = new \DateTimeImmutable();
        $this->waypoints = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    public function getPlannedAt(): ?\DateTimeImmutable
    {
        return $this->plannedAt;
    }

    public function setPlannedAt(\DateTimeImmutable $plannedAt): static
    {
        $this->plannedAt = $plannedAt;

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

    public function getStartHex(): ?string
    {
        return $this->startHex;
    }

    public function setStartHex(?string $startHex): static
    {
        $this->startHex = $startHex;

        return $this;
    }

    public function getDestHex(): ?string
    {
        return $this->destHex;
    }

    public function setDestHex(?string $destHex): static
    {
        $this->destHex = $destHex;

        return $this;
    }

    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    public function setStartDay(?int $startDay): static
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(?int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getDestDay(): ?int
    {
        return $this->destDay;
    }

    public function setDestDay(?int $destDay): static
    {
        $this->destDay = $destDay;

        return $this;
    }

    public function getDestYear(): ?int
    {
        return $this->destYear;
    }

    public function setDestYear(?int $destYear): static
    {
        $this->destYear = $destYear;

        return $this;
    }

    public function getJumpRating(): ?int
    {
        return $this->jumpRating;
    }

    public function setJumpRating(?int $jumpRating): static
    {
        $this->jumpRating = $jumpRating;

        return $this;
    }

    public function getFuelEstimate(): ?string
    {
        return $this->fuelEstimate;
    }

    public function setFuelEstimate(?string $fuelEstimate): static
    {
        $this->fuelEstimate = $fuelEstimate;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getWaypoints(): Collection
    {
        return $this->waypoints;
    }

    public function addWaypoint(RouteWaypoint $waypoint): static
    {
        if (!$this->waypoints->contains($waypoint)) {
            $this->waypoints->add($waypoint);
            $waypoint->setRoute($this);
        }

        return $this;
    }

    public function removeWaypoint(RouteWaypoint $waypoint): static
    {
        if ($this->waypoints->removeElement($waypoint)) {
            if ($waypoint->getRoute() === $this) {
                $waypoint->setRoute(null);
            }
        }

        return $this;
    }

    public function getStartWorld(): ?string
    {
        $first = $this->waypoints->first();
        return $first ? $first->getWorld() : null;
    }

    public function getDestWorld(): ?string
    {
        $last = $this->waypoints->last();
        return $last ? $last->getWorld() : null;
    }

    public function getJumpDistance(): ?int
    {
        $jumpRating = $this->getJumpRating() ?? $this->getAsset()?->getJumpDriveRating();

        if ($jumpRating === null) {
            return null;
        }

        $waypointCount = $this->waypoints->count();

        return $waypointCount < 2 ? 0 : $jumpRating * ($waypointCount - 1);
    }

    public function getStartDateImperial(): ?string
    {
        if ($this->startDay === null || $this->startYear === null) {
            return null;
        }
        return $this->startDay . '/' . $this->startYear;
    }

    public function getDestDateImperial(): ?string
    {
        if ($this->destDay === null || $this->destYear === null) {
            return null;
        }
        return $this->destDay . '/' . $this->destYear;
    }
}
