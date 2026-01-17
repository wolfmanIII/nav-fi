<?php

namespace App\Entity;

use App\Repository\AssetAmendmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AssetAmendmentRepository::class)]
#[ORM\Index(name: 'idx_amendment_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_amendment_asset', columns: ['asset_id'])]
#[ORM\Index(name: 'idx_amendment_cost', columns: ['cost_id'])]
#[ORM\Table(name: 'asset_amendment')]
class AssetAmendment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $effectiveDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $effectiveYear = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $patchDetails = null;

    #[ORM\ManyToOne(inversedBy: 'amendments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Cost $cost = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
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

    public function getEffectiveDay(): ?int
    {
        return $this->effectiveDay;
    }

    public function setEffectiveDay(?int $effectiveDay): static
    {
        $this->effectiveDay = $effectiveDay;

        return $this;
    }

    public function getEffectiveYear(): ?int
    {
        return $this->effectiveYear;
    }

    public function setEffectiveYear(?int $effectiveYear): static
    {
        $this->effectiveYear = $effectiveYear;

        return $this;
    }

    public function getPatchDetails(): ?array
    {
        return $this->patchDetails;
    }

    public function setPatchDetails(?array $patchDetails): static
    {
        $this->patchDetails = $patchDetails;

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

    public function getCost(): ?Cost
    {
        return $this->cost;
    }

    public function setCost(?Cost $cost): static
    {
        $this->cost = $cost;

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
}
