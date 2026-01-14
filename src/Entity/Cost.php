<?php

namespace App\Entity;

use App\Entity\LocalLaw;
use App\Repository\CostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CostRepository::class)]
#[ORM\Index(name: 'idx_cost_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_cost_ship', columns: ['ship_id'])]
#[ORM\Index(name: 'idx_cost_category', columns: ['cost_category_id'])]
#[ORM\Index(name: 'idx_cost_payment_date', columns: ['payment_day', 'payment_year'])]
class Cost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentYear = null;

    #[ORM\ManyToOne(inversedBy: 'costs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CostCategory $costCategory = null;

    #[ORM\ManyToOne(inversedBy: 'costs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ship $ship = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?LocalLaw $localLaw = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $detailItems = null;

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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentDay(): ?int
    {
        return $this->paymentDay;
    }

    public function setPaymentDay(?int $paymentDay): static
    {
        $this->paymentDay = $paymentDay;

        return $this;
    }

    public function getPaymentYear(): ?int
    {
        return $this->paymentYear;
    }

    public function setPaymentYear(?int $paymentYear): static
    {
        $this->paymentYear = $paymentYear;

        return $this;
    }

    public function getCostCategory(): ?CostCategory
    {
        return $this->costCategory;
    }

    public function setCostCategory(?CostCategory $costCategory): static
    {
        $this->costCategory = $costCategory;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getLocalLaw(): ?LocalLaw
    {
        return $this->localLaw;
    }

    public function setLocalLaw(?LocalLaw $localLaw): static
    {
        $this->localLaw = $localLaw;

        return $this;
    }

    /**
     * Collection of purchased/provided items with keys like description, quantity and cost.
     */
    public function getDetailItems(): array
    {
        return $this->detailItems ?? [];
    }

    public function setDetailItems(?array $detailItems): static
    {
        $this->detailItems = $detailItems;

        return $this;
    }
}
