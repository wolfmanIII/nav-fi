<?php

namespace App\Entity;

use App\Repository\CrewRepository;
use App\Validator\Captain;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CrewRepository::class)]
#[ORM\Index(name: 'idx_crew_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_crew_asset', columns: ['asset_id'])]
#[Captain]
class Crew
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_ON_LEAVE = 'On Leave';
    public const STATUS_RETIRED = 'Retired';
    public const STATUS_MIA = 'Missing (MIA)';
    public const STATUS_DECEASED = 'Deceased';

    public static function getStatusChoices(): array
    {
        return [
            self::STATUS_ACTIVE => self::STATUS_ACTIVE,
            self::STATUS_ON_LEAVE => self::STATUS_ON_LEAVE,
            self::STATUS_RETIRED => self::STATUS_RETIRED,
            self::STATUS_MIA => self::STATUS_MIA,
            self::STATUS_DECEASED => self::STATUS_DECEASED,
        ];
    }
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
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, AssetRole>
     */
    #[ORM\ManyToMany(targetEntity: AssetRole::class, inversedBy: 'crews')]
    private Collection $assetRoles;

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

    /**
     * @var Collection<int, Salary>
     */
    #[ORM\OneToMany(targetEntity: Salary::class, mappedBy: 'crew', orphanRemoval: true)]
    private Collection $salaries;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->assetRoles = new ArrayCollection();
        $this->salaries = new ArrayCollection();
        $this->status = null;
    }

    /**
     * @return Collection<int, Salary>
     */
    public function getSalaries(): Collection
    {
        return $this->salaries;
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

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return Collection<int, AssetRole>
     */
    public function getAssetRoles(): Collection
    {
        return $this->assetRoles;
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

    public function addAssetRole(AssetRole $assetRole): static
    {
        if (!$this->assetRoles->contains($assetRole)) {
            // imposta il lato proprietario a null (a meno che non sia già cambiato)
            $this->assetRoles->add($assetRole);
        }

        return $this;
    }

    public function removeAssetRole(AssetRole $assetRole): static
    {
        $this->assetRoles->removeElement($assetRole);

        return $this;
    }

    public function isCaptain(): bool
    {
        foreach ($this->getAssetRoles() as $role) {
            if ($role->getCode() === "CAP") {
                return true;
            }
        }
        return false;
    }

    public function hasMortgageSigned(): bool
    {
        if ($this->getAsset()) {
            return $this->getAsset()->hasMortgageSigned();
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

    public function isDisplayable(): bool
    {
        return !in_array($this->status, [self::STATUS_MIA, self::STATUS_DECEASED], true);
    }

    public function isActiveAtOrAfterDate(?int $referenceYear, ?int $referenceDay): bool
    {
        if (!$referenceYear || !$referenceDay) {
            return true;
        }

        if (!$this->activeYear || !$this->activeDay) {
            return false;
        }

        $activeIndex = $this->activeYear * 1000 + $this->activeDay;
        $referenceIndex = $referenceYear * 1000 + $referenceDay;

        return $activeIndex >= $referenceIndex;
    }

    public function isVisibleInMortgage(?int $mortgageSigningYear, ?int $mortgageSigningDay): bool
    {
        return $this->isDisplayable();
    }
}
