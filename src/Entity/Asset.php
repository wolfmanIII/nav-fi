<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Index(name: 'idx_asset_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_asset_campaign', columns: ['campaign_id'])]
class Asset
{
    public const CATEGORY_SHIP = 'ship';
    public const CATEGORY_BASE = 'base';
    public const CATEGORY_TEAM = 'team';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, options: ['default' => self::CATEGORY_SHIP])]
    private ?string $category = self::CATEGORY_SHIP;

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $class = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00'])]
    private ?string $credits = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Campaign $campaign = null;

    #[ORM\OneToOne(mappedBy: 'asset', cascade: ['persist', 'remove'])]
    private ?Mortgage $mortgage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $shipDetails = null; // Keeping field name for now to avoid drastic data structure change, or could rename to details? Let's keep shipDetails but maybe getter getDetails? Or rename field. The plan said rename references. I'll stick to shipDetails for internal data if I want to be safe, OR rename it to details. Let's rename to details for cleanliness.

    /**
     * @var Collection<int, Crew>
     */
    #[ORM\OneToMany(targetEntity: Crew::class, mappedBy: 'asset')]
    private Collection $crews;

    /**
     * @var Collection<int, ShipAmendment>
     */
    #[ORM\OneToMany(targetEntity: ShipAmendment::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $amendments; // Note: Need to verify if ShipAmendment entity is renamed. For now referring to ShipAmendment but mappedBy 'asset'. Wait, if I rename Ship->Asset, I should update relation in ShipAmendment too.

    /**
     * @var Collection<int, Cost>
     */
    #[ORM\OneToMany(targetEntity: Cost::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $costs;

    /**
     * @var Collection<int, Income>
     */
    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $incomes;

    /**
     * @var Collection<int, Route>
     */
    #[ORM\OneToMany(targetEntity: Route::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $routes;

    /**
     * @var Collection<int, AnnualBudget>
     */
    #[ORM\OneToMany(targetEntity: AnnualBudget::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $annualBudgets;


    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'asset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $transactions;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->crews = new ArrayCollection();
        $this->costs = new ArrayCollection();
        $this->incomes = new ArrayCollection();
        $this->amendments = new ArrayCollection();
        $this->routes = new ArrayCollection();
        $this->annualBudgets = new ArrayCollection();
        $this->transactions = new ArrayCollection();
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

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCredits(): ?string
    {
        return $this->credits;
    }

    public function setCredits(string $credits): static
    {
        $this->credits = $credits;

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

    public function getMortgage(): ?Mortgage
    {
        return $this->mortgage;
    }

    public function setMortgage(?Mortgage $mortgage): static
    {
        // unset owning side of the relation if necessary
        if ($mortgage === null && $this->mortgage !== null) {
            $this->mortgage->setAsset(null);
        }

        // set the owning side of the relation if necessary
        if ($mortgage !== null && $mortgage->getAsset() !== $this) {
            $mortgage->setAsset($this);
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

    /**
     * @return Collection<int, ShipAmendment>
     */
    public function getAmendments(): Collection
    {
        return $this->amendments;
    }

    public function addAmendment(ShipAmendment $amendment): static
    {
        if (!$this->amendments->contains($amendment)) {
            $this->amendments->add($amendment);
            $amendment->setAsset($this);
        }

        return $this;
    }

    public function removeAmendment(ShipAmendment $amendment): static
    {
        if ($this->amendments->removeElement($amendment)) {
            if ($amendment->getAsset() === $this) {
                $amendment->setAsset(null);
            }
        }

        return $this;
    }

    public function addCrew(Crew $crew): static
    {
        if (!$this->crews->contains($crew)) {
            $this->crews->add($crew);
            $crew->setAsset($this);
        }

        return $this;
    }

    public function removeCrew(Crew $crew): static
    {
        if ($this->crews->removeElement($crew)) {
            // set the owning side to null (unless already changed)
            if ($crew->getAsset() === $this) {
                $crew->setAsset(null);
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
            $cost->setAsset($this);
        }

        return $this;
    }

    public function removeCost(Cost $cost): static
    {
        if ($this->costs->removeElement($cost)) {
            if ($cost->getAsset() === $this) {
                $cost->setAsset(null);
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

    /**
     * @return Collection<int, Route>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Route $route): static
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
            $route->setAsset($this);
        }

        return $this;
    }

    public function removeRoute(Route $route): static
    {
        if ($this->routes->removeElement($route)) {
            if ($route->getAsset() === $this) {
                $route->setAsset(null);
            }
        }

        return $this;
    }

    public function addIncome(Income $income): static
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
            $income->setAsset($this);
        }

        return $this;
    }

    public function removeIncome(Income $income): static
    {
        if ($this->incomes->removeElement($income)) {
            if ($income->getAsset() === $this) {
                $income->setAsset(null);
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

    public function getCaptain(): ?string
    {
        foreach ($this->crews as $crew) {
            if ($crew->isCaptain()) {
                return $crew->getName() . " " . $crew->getSurname();
            }
        }
        return null;
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

    // Retaining legacy getters but updating logic
    public function getJumpDriveRating(): ?int
    {
        $details = $this->getShipDetails();
        if (isset($details['jDrive']['jump'])) {
            return (int) $details['jDrive']['jump'];
        }
        return null;
    }

    public function getHullTons(): ?float
    {
        $details = $this->getShipDetails();
        if (isset($details['hull']['tons']) && is_numeric($details['hull']['tons'])) {
            return (float) $details['hull']['tons'];
        }
        return null;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }
}
